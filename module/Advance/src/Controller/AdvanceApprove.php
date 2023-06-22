<?php
namespace Advance\Controller;

use Advance\Form\AdvanceRequestForm;
use Advance\Model\AdvancePayment;
use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvanceApproveRepository;
use Advance\Repository\AdvancePaymentRepository;
use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AdvanceApprove extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AdvanceApproveRepository::class);
        $this->initializeForm(AdvanceRequestForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $search['employeeId'] = $this->employeeId;
                $search['status'] = 'OVERRIDE';
                $rawList = $this->repository->getAllFiltered($search);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');
        if ($id === 0 || $role === 0) {
            return $this->redirect()->toRoute("advance-approve");
        }
        $request = $this->getRequest();
        $advanceRequestModel = new AdvanceRequestModel();
        $detail = $this->repository->fetchById($id);

        if ($request->isPost()) {
            $postedData = (array) $request->getPost();
            $action = $postedData['submit'];
            $this->makeDecision($id, $role, $action == 'Approve', $postedData[$role == 2 ? 'recommendedRemarks' : 'approvedRemarks'], true);
            return $this->redirect()->toRoute("advance-approve");
        }

        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        $advanceRequestModel->exchangeArrayFromDB($detail);
        $this->form->bind($advanceRequestModel);

        return Helper::addFlashMessagesToArray($this, [
                'form' => $this->form,
                'id' => $id,
                'role' => $role,
                'employeeName' => $detail['FULL_NAME'],
                'employeeId' => $detail['EMPLOYEE_ID'],
                'status' => $detail['STATUS'],
                'statusDetail' => $detail['STATUS_DETAIL'],
                'requestedDate' => $detail['REQUESTED_DATE'],
                'recommender' => $authRecommender,
                'approver' => $authApprover,
                'advances' => EntityHelper::getTableKVListWithSortOption($this->adapter, AdvanceSetupModel::TABLE_NAME, AdvanceSetupModel::ADVANCE_ID, [AdvanceSetupModel::ADVANCE_ENAME], ["STATUS" => 'E'], AdvanceSetupModel::ADVANCE_ENAME, "ASC", " ", false, true),
                'detail' => $detail
        ]);
    }

    private function makeDecision($id, $role, $approve, $remarks = null, $enableFlashNotification = false) {
        $notificationEvent = null;
        $message = null;
        $model = new AdvanceRequestModel();
        $model->advanceRequestId = $id;
        switch ($role) {
            case 2:
                $model->recommendedRemarks = $remarks;
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
                $model->status = $approve ? "RC" : "R";
                $message = $approve ? "Advance Request Recommended" : "Advance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ADVANCE_RECOMMEND_ACCEPTED : NotificationEvents::ADVANCE_RECOMMEND_REJECTED;
                break;
            case 4:
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
            case 3:
                $model->approvedRemarks = $remarks;
                $model->approvedDate = Helper::getcurrentExpressionDate();
                $model->approvedBy = $this->employeeId;
                $model->status = $approve ? "AP" : "R";
                $message = $approve ? "Advance Request Approved" : "Advance Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::ADVANCE_APPROVE_ACCEPTED : NotificationEvents::ADVANCE_APPROVE_REJECTED;
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

    public function batchApproveRejectAction() {
        $request = $this->getRequest();
        try {
            if (!$request->ispost()) {
                throw new Exception('the request is not post');
            }
            $postData = $request->getPost()['data'];
            $postBtnAction = $request->getPost()['btnAction'];
            if ($postBtnAction == 'btnApprove') {
                $action = 'Approve';
            } elseif ($postBtnAction == 'btnReject') {
                $action = 'Reject';
            } else {
                throw new Exception('no action defined');
            }


            if ($postData == null) {
                throw new Exception('no selected rows');
            } else {
                try {
                    foreach ($postData as $data) {
                        $advanceRequestModel = new AdvanceRequestModel();
                        $id = $data['id'];
                        $role = $data['role'];
                        $detail = $this->repository->fetchById($id);


                        if ($role == 2) {
                            $advanceRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                            $advanceRequestModel->recommendedBy = (int) $this->employeeId;
                            if ($action == "Reject") {
                                $advanceRequestModel->status = "R";
                            } elseif ($action == "Approve") {
                                $advanceRequestModel->status = "RC";
                            }
                            $this->repository->edit($advanceRequestModel, $id);
                            try {
                                $advanceRequestModel->advanceRequestId = $id;
                                HeadNotification::pushNotification(($advanceRequestModel->status == 'RC') ? NotificationEvents::ADVANCE_RECOMMEND_ACCEPTED : NotificationEvents::ADVANCE_RECOMMEND_REJECTED, $advanceRequestModel, $this->adapter, $this);
                            } catch (Exception $e) {
                                
                            }
                        } elseif ($role == 3 || $role == 4) {
                            $advanceRequestModel->approvedDate = Helper::getcurrentExpressionDate();
                            $advanceRequestModel->approvedBy = (int) $this->employeeId;
                            if ($action == "Reject") {
                                $advanceRequestModel->status = "R";
                            } elseif ($action == "Approve") {
                                $advanceRequestModel->status = "AP";
                            }
                            if ($role == 4) {
                                $advanceRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                                $advanceRequestModel->recommendedBy = (int) $this->employeeId;
                            }
//                            $this->advancePaymentAdd($detail);
                            $this->repository->edit($advanceRequestModel, $id);
                            try {
                                $advanceRequestModel->advanceRequestId = $id;
                                HeadNotification::pushNotification(($advanceRequestModel->status == 'AP') ? NotificationEvents::ADVANCE_APPROVE_ACCEPTED : NotificationEvents::ADVANCE_APPROVE_REJECTED, $advanceRequestModel, $this->adapter, $this);
                            } catch (Exception $e) {
                                $this->flashmessenger()->addMessage($e->getMessage());
                            }
                        }
                    }
                } catch (Exception $ex) {
                    
                }
            }

            return new CustomViewModel(['success' => true,]);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function statusAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $searchQuery = $request->getPost();
                $searchQuery['employeeId'] = $this->employeeId;
                $rawList = $this->repository->getAllFiltered($searchQuery);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'advanceRequestStatusId', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
                'advanceStatus' => $statusSE,
                'recomApproveId' => $this->employeeId,
                'searchValues' => EntityHelper::getSearchData($this->adapter),
         ]);
    }

    public function advancePaymentAdd($details) {
        $advancePaymentRepository = new AdvancePaymentRepository($this->adapter);

        $advanceRequestId = $details['ADVANCE_REQUEST_ID'];
        $requestedAmt = $details['REQUESTED_AMOUNT'];
        $employeeSalary = $details['SALARY'];
        $monthlyDeductionRate = $details['DEDUCTION_RATE'];
        $advanceDate = $details['DATE_OF_ADVANCE'];

        $monthlyDedeuctionAmt = ($monthlyDeductionRate / 100) * $employeeSalary;
        $monthCodeDetails = $advancePaymentRepository->getMonthCode($advanceDate);

        $nepYear = $monthCodeDetails['YEAR'];
        $nepMonth = $monthCodeDetails['MONTH_NO'];


        $actualPyamentMonths = ceil($requestedAmt / $monthlyDedeuctionAmt);

        $advancePayment = new AdvancePayment();
        $advancePayment->advanceRequestId = $advanceRequestId;
        $advancePayment->createdBy = $this->employeeId;
        $advancePayment->status = 'PE';

        for ($i = 1; $i <= $actualPyamentMonths; $i++) {

            $advancePayment->amount = ($requestedAmt > $monthlyDedeuctionAmt) ? $monthlyDedeuctionAmt : $requestedAmt;
            $advancePayment->nepYear = $nepYear;
            $advancePayment->nepMonth = $nepMonth;

            $requestedAmt = $requestedAmt - $monthlyDedeuctionAmt;
            if ($nepMonth == 12) {
                $nepYear = $nepYear + 1;
                $nepMonth = 1;
            } else {
                $nepMonth = $nepMonth + 1;
            }
            $advancePaymentRepository->add($advancePayment);
        }
    }
}
