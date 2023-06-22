<?php

namespace Notification\Controller;

use Application\Helper\EntityHelper as ApplicationEntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Form\NewsForm;
use Notification\Model\NewsFile;
use Notification\Model\NewsModel;
use Notification\Model\NewsTypeModel;
use Notification\Repository\NewsFileRepository;
use Notification\Repository\NewsRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class NewsController extends AbstractActionController {

    private $adapter;
    private $form;
    private $repository;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new NewsRepository($adapter);

        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $form = new NewsForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $result = $this->repository->fetchAll();
        $list = Helper::extractDbData($result);
        return Helper::addFlashMessagesToArray($this, [
                    'news' => $list
        ]);
    }
 
    public function addAction() {
        $this->initializeForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
             
            echo '<Pre>';
            if ($this->form->isValid()) {
                $newsModel = new NewsModel();
                $newsModel->exchangeArrayFromForm($this->form->getData());
                $newsModel->newsId = ((int) Helper::getMaxId($this->adapter, $newsModel::TABLE_NAME, $newsModel::NEWS_ID)) + 1;
                $newsModel->createdBy = $this->employeeId;
                $newsModel->approvedBy = $this->employeeId;
                $newsModel->createdDt = Helper::getcurrentExpressionDate();
                $newsModel->approvedDt = Helper::getcurrentExpressionDate();
                $newsModel->status = 'E';
                
                
                
                $subject=$newsModel->newsTitle;
                $description=$newsModel->newsEdesc;
                
                $this->repository->add($newsModel);
                $this->assignTo($newsModel->newsId, $postData);
                
                if(isset($postData['fileUploadList'])){
                    $this->repository->updateNewsFileUploads($newsModel->newsId,$postData['fileUploadList']);
                }
                
                try {
                 HeadNotification::sendMassMail($this->adapter,$postData,$subject, $description);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                
                $this->flashmessenger()->addMessage("News Successfully added!!!");
                return $this->redirect()->toRoute('news');
            }
        }


        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'newsTypeValue' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, NewsTypeModel::TABLE_NAME, NewsTypeModel::NEWS_TYPE_ID, [NewsTypeModel::NEWS_TYPE_DESC], ["STATUS" => "E"], "NEWS_TYPE_DESC", "ASC", null, true, true),
                    'searchValues' => ApplicationEntityHelper::getSearchData($this->adapter),
        ]);
    }

    private function assignTo($newsId, $postData) {
        $assignNewsTo = [];
        if (isset($postData['company'])) {
            foreach ($postData['company'] as $item) {
                array_push($assignNewsTo, ['COMPANY_ID' => $item]);
            }
        }
        if (isset($postData['branch'])) {
            foreach ($postData['branch'] as $item) {
                array_push($assignNewsTo, ['BRANCH_ID' => $item]);
            }
        }
        if (isset($postData['department'])) {
            foreach ($postData['department'] as $item) {
                array_push($assignNewsTo, ['DEPARTMENT_ID' => $item]);
            }
        }
        if (isset($postData['designation'])) {
            foreach ($postData['designation'] as $item) {
                array_push($assignNewsTo, ['DESIGNATION_ID' => $item]);
            }
        }
        if (isset($postData['position'])) {
            foreach ($postData['position'] as $item) {
                array_push($assignNewsTo, ['POSITION_ID' => $item]);
            }
        }
        if (isset($postData['serviceType'])) {
            foreach ($postData['serviceType'] as $item) {
                array_push($assignNewsTo, ['SERVICE_TYPE_ID' => $item]);
            }
        }
        if (isset($postData['serviceEventType'])) {
            foreach ($postData['serviceEventType'] as $item) {
                array_push($assignNewsTo, ['SERVICE_EVENT_TYPE_ID' => $item]);
            }
        }
        if (isset($postData['employeeType'])) {
            foreach ($postData['employeeType'] as $item) {
                array_push($assignNewsTo, ['EMPLOYEE_TYPE' => $item]);
            }
        }
        if (isset($postData['gender'])) {
            foreach ($postData['gender'] as $item) {
                array_push($assignNewsTo, ['GENDER_ID' => $item]);
            }
        }
        if (isset($postData['employee'])) {
            foreach ($postData['employee'] as $item) {
                array_push($assignNewsTo, ['EMPLOYEE_ID' => $item]);
            }
        }
        $this->repository->newsAssign($newsId, $assignNewsTo);
    }

    public function editAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('news');
        }
        $this->initializeForm();

        $request = $this->getRequest();
        $newsModel = new NewsModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $newsModel->exchangeArrayFromForm($this->form->getData());
                $newsModel->modifiedBy = $this->employeeId;
                $newsModel->modifiedDt = Helper::getcurrentExpressionDate();

                $this->repository->edit($newsModel, $id);
                $this->assignTo($id, $postData);
                
                isset($postData['fileUploadList'])?
                    $this->repository->updateNewsFileUploads($id,$postData['fileUploadList'])
                :$this->repository->updateNewsFileUploads($id,[]);
                
                
                $subject=$newsModel->newsTitle;
                $description=$newsModel->newsEdesc;
                try {
                 HeadNotification::sendMassMail($this->adapter,$postData,$subject, $description);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                
                $this->flashmessenger()->addMessage("News Successfully Updated!!!");
                return $this->redirect()->toRoute("news");
            }
        }
        $newsModel->exchangeArrayFromDB($this->repository->fetchById($id));
        $this->form->bind($newsModel);
        $newsToList = $this->repository->getAssignedToList($id);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'newsTypeValue' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, NewsTypeModel::TABLE_NAME, NewsTypeModel::NEWS_TYPE_ID, [NewsTypeModel::NEWS_TYPE_DESC], ["STATUS" => "E"], "NEWS_TYPE_DESC", "ASC", null, true, true),
                    'searchValues' => ApplicationEntityHelper::getSearchData($this->adapter),
                    'newsToList' => $newsToList
        ]);
    }

    public function deleteAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('news');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("News Successfully Deleted!!!");
        return $this->redirect()->toRoute("news");
    }

    public function fileUploadAction() {
        $request = $this->getRequest();
        $responseData = [];
        $files = $request->getFiles()->toArray();
        try {
            if (sizeof($files) > 0) {
                $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
                $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
                $unique = Helper::generateUniqueName();
                $newFileName = $unique . "." . $ext;
                $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/news/" . $newFileName);
                if (!$success) {
                    throw new Exception("Upload unsuccessful.");
                }
                $responseData = ["success" => true, "data" => ["fileName" => $newFileName, "oldFileName" => $fileName . "." . $ext]];
            }
        } catch (Exception $e) {
            $responseData = [
                "success" => false,
                "message" => $e->getMessage(),
                "traceAsString" => $e->getTraceAsString(),
                "line" => $e->getLine()
            ];
        }
        return new JsonModel($responseData);
    } 

    public function pushNewsFileAction() {
        try {
            $newsId = $this->params()->fromRoute('id');
            $request = $this->getRequest();
            $data = $request->getPost();

            $newsFile = new NewsFile();
            $return = [];
            if ($data['newsTypeId'] == null) {
                $newsFile->newsFileId = ((int) Helper::getMaxId($this->adapter, 'HRIS_NEWS_FILE', 'NEWS_FILE_ID')) + 1;
                $newsFile->newsId = $newsId;
                $newsFile->filePath = $data['filePath'];
                $newsFile->fileName = $data['fileName'];
                $newsFile->status = 'E';
                $newsFile->createdDt = Helper::getcurrentExpressionDate();
                $newsFile->createdBy = $this->employeeId;

                $newsFileRepo = new NewsFileRepository($this->adapter);
                $newsFileRepo->add($newsFile);

                $returnData= ['newsFileId' => $newsFile->newsFileId,'filePath'=>$newsFile->filePath];
            } else {
                $newsFile->filePath = $data['filePath'];
                $newsFileRepo = new NewsFileRepository($this->adapter);
                $newsFileRepo->edit($newsFile, $data['newsTypeId']);
                $returnData=['newsFileId' => $data['newsTypeId']];
            }

            return new JsonModel(['success' => true, 'data' => $returnData, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }


    public function pullNewsFileAction() {
        try {
            $id = $this->params()->fromRoute('id');
            $newsfileRepo = new NewsFileRepository($this->adapter);
            $newsFiles = $newsfileRepo->fetchAllNewsFiles($id);
            return new JsonModel(['success' => true, 'data' => $newsFiles, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
