<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Repository\AttendanceStatusRepository;
use Exception;
use ManagerService\Repository\AttendanceApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\AttendanceRequestForm;
use SelfService\Model\AttendanceRequestModel;
use SelfService\Repository\AttendanceRequestRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;

class AttendanceApproveController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AttendanceApproveRepository::class);
        $this->initializeForm(AttendanceRequestForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllRequest($this->employeeId);
                $list = iterator_to_array($rawList, false);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    private function makeDecision($id, $role, $approve, $remarks = null, $enableFlashNotification = false) {
        $notificationEvent = null;
        $message = null;
        $model = new AttendanceRequestModel();
        $model->id = $id;
        switch ($role) {
            case 2:
                $model->recommendedRemarks = $remarks;
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
                $model->status = $approve ? "RC" : "R";
                $message = $approve ? "Attendance Request Recommended" : "Attendance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ATTENDANCE_RECOMMEND_ACCEPTED : NotificationEvents::ATTENDANCE_RECOMMEND_REJECTED;
                break;
            case 4:
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
            case 3:
                $model->approvedRemarks = $remarks;
                $model->approvedDate = Helper::getcurrentExpressionDate();
                $model->approvedBy = $this->employeeId;
                $model->status = $approve ? "AP" : "R";
                $message = $approve ? "Attendance Request Approved" : "Attendance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ATTENDANCE_APPROVE_ACCEPTED : NotificationEvents::ATTENDANCE_APPROVE_REJECTED;
                break;
        }
        $this->repository->edit($model, $id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');


        if ($id === 0) {
            return $this->redirect()->toRoute("attedanceapprove");
        }
        $attendanceRequestRepository = new AttendanceRequestRepository($this->adapter);


        $request = $this->getRequest();
        $model = new AttendanceRequestModel();
        $detail = $attendanceRequestRepository->fetchByIdWithEmployeeId($id, $this->employeeId);

//        if ($this->employeeId != $detail['RECOMMENDER_ID'] && $this->employeeId != $detail['APPROVER_ID']) {
//            return $this->redirect()->toRoute("attedanceapprove");
//        }

        $employeeId = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];

        $approvedDT = $detail['APPROVED_DT'];

        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        if ($request->isPost()) {
            $postedData = (array) $request->getPost();
            $action = $postedData['submit'];
            $this->makeDecision($id, $role, $action == 'Approve', $postedData[$role == 2 ? 'recommendedRemarks' : 'approvedRemarks'], true);
            return $this->redirect()->toRoute("attedanceapprove");
        }
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);


        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'status' => $detail['STATUS'],
                    'employeeName' => $employeeName,
                    'employeeId' => $this->employeeId,
                    'approver' => $authApprover,
                    'requestedDt' => $detail['REQUESTED_DT'],
                    'role' => $role,
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'recommendedBy' => $recommenderId,
                    'approvedDT' => $approvedDT,
                    'requestedEmployeeId' => $requestedEmployeeID,
        ]);
    }

    public function statusAction() {
        $attendanceStatus = [
            '-1' => 'All',
            'RQ' => 'Pending',
            'RC' => 'Recommended',
            'AP' => 'Approved',
            'R' => 'Rejected'
        ];
        $attendanceStatusFormElement = new Select();
        $attendanceStatusFormElement->setName("attendanceStatus");
        $attendanceStatusFormElement->setValueOptions($attendanceStatus);
        $attendanceStatusFormElement->setAttributes(["id" => "attendanceRequestStatusId", "class" => "form-control reset-field"]);
        $attendanceStatusFormElement->setLabel("Status");

        return Helper::addFlashMessagesToArray($this, [
                    'attendanceStatus' => $attendanceStatusFormElement,
                    'approverId' => $this->employeeId,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function batchApproveRejectAction() {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            $this->makeDecision($postData['id'], $postData['role'], $postData['btnAction'] == "btnApprove");
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pullAttendanceRequestStatusListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $attendanceStatusRepository = new AttendanceStatusRepository($this->adapter);
            if (key_exists('approverId', $data)) {
                $approverId = $data['approverId'];
            } else {
                $approverId = null;
            }
            $result = $attendanceStatusRepository->getFilteredRecord($data, $approverId);
            $recordList = Helper::extractDbData($result);
            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
                "num" => count($recordList)
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
