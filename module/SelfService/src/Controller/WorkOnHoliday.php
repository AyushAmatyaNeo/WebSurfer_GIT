<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\WorkOnHolidayForm;
use SelfService\Model\WorkOnHoliday as WorkOnHolidayModel;
use SelfService\Repository\WorkOnHolidayRepository;
use WorkOnHoliday\Repository\WorkOnHolidayStatusRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class WorkOnHoliday extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(WorkOnHolidayRepository::class);
        $this->initializeForm(WorkOnHolidayForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {

                $result = $this->repository->getAllByEmployeeId($this->employeeId);
                $list = Helper::extractDbData($result);

                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function addAction() {
        $request = $this->getRequest();

        $model = new WorkOnHolidayModel();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, WorkOnHolidayModel::TABLE_NAME, WorkOnHolidayModel::ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Work on Holiday Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::WORKONHOLIDAY_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("workOnHoliday");
            }
        }

        $holidays = $this->getHolidayList($this->employeeId);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeId' => $this->employeeId,
                    'holidays' => $holidays["holidayKVList"],
                    'holidayObjList' => $holidays["holidayList"]
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id", 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('workOnHoliday');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Work on Holiday Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('workOnHoliday');
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("workOnHoliday");
        }

        $holidays = $this->getHolidayList($this->employeeId);
        $holidaySE = $this->form->get('holidayId');
        $holidaySE->setValueOptions($holidays["holidayKVList"]);
        $holidaySE->setAttributes(["disabled" => "disabled"]);

        $detail = $this->repository->fetchById($id);
        $model = new WorkOnHolidayModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeName' => $detail['FULL_NAME'],
                    'status' => $detail['STATUS'],
                    'requestedDate' => $detail['REQUESTED_DATE'],
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] ? $detail['RECOMMENDED_BY_NAME'] : $detail['RECOMMENDER_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] ? $detail['APPROVED_BY_NAME'] : $detail['APPROVER_NAME'],
        ]);
    }

    public function getHolidayList($employeeId) {
        $wohRepo = new WorkOnHolidayStatusRepository($this->adapter);
        $holidayResult = $wohRepo->getAttendedHolidayList($employeeId);
        $holidayList = [];
        $holidayObjList = [];
        foreach ($holidayResult as $holidayRow) {
            $holidayList[$holidayRow['HOLIDAY_ID']] = $holidayRow['HOLIDAY_ENAME'] . " (" . $holidayRow['START_DATE'] . " to " . $holidayRow['END_DATE'] . ")";
            $holidayObjList[$holidayRow['HOLIDAY_ID']] = $holidayRow;
        }
        return ['holidayKVList' => $holidayList, 'holidayList' => $holidayObjList];
    }

}
