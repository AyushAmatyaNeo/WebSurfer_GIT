<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\LevelForm;
use Setup\Model\Company;
use Setup\Model\level;
use Setup\Repository\LevelRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class LevelController extends AbstractActionController {

    private $repository;
    private $form;
    private $adapter;
    private $employeeId;
    private $storageData;
    private $acl;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        // print_r('dfhbfd');die;
        $this->repository = new LevelRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm() {
        $levelForm = new LevelForm();
        $builder = new AnnotationBuilder();
        if (!$this->form) {
            $this->form = $builder->createForm($levelForm);
        }
    }

    public function indexAction() {
        $request = $this->getRequest();
        if($request->isPost()){
            try{
                $result=$this->repository->fetchActiveRecords();
                $levelList=Helper::extractDbData($result);
                return new CustomViewModel(['success'=>true, 'data'=>$levelList, 'error'=>'']);
            }catch(Exception $e){
                return new CustomViewModel(['success'=>false, 'data' =>[],'error' =>$e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    public function addAction() {
        ACLHelper::checkFor(ACLHelper::ADD, $this->acl, $this);
        $this->initializeForm();
        $request = $this->getRequest();
        if ($request->isPost()) {

            $this->form->setData($request->getPost());

            if ($this->form->isValid()) {
                $level = new level();
                $level->exchangeArrayFromForm($this->form->getData());
                $level->levelId = ((int) Helper::getMaxId($this->adapter, level::TABLE_NAME, level::LEVEL_ID)) + 1;
                $level->createdDt = Helper::getcurrentExpressionDate();
                $level->createdBy = $this->employeeId;
                $level->status = 'E';
                // echo '<pre>';print_r($level);die;
                $this->repository->add($level);

                $this->flashmessenger()->addMessage("Level Successfully added!!!");
                return $this->redirect()->toRoute("level");
            }
        }
        return new ViewModel(Helper::addFlashMessagesToArray(
                        $this, [
                    'customRenderer' => Helper::renderCustomView(),
                    'form' => $this->form,
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, true, true),
                    'messages' => $this->flashmessenger()->getMessages()
                        ]
                )
        );
    }

    public function editAction() {
        ACLHelper::checkFor(ACLHelper::UPDATE, $this->acl, $this);
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('level');
        }
        $this->initializeForm();
        $request = $this->getRequest();

        $level = new level();
        if (!$request->isPost()) {
            $level->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($level);
        } else {

            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $level->exchangeArrayFromForm($this->form->getData());
                $level->modifiedDt = Helper::getcurrentExpressionDate();
                $level->modifiedBy = $this->employeeId;
                $this->repository->edit($level, $id);
                $this->flashmessenger()->addMessage("Level Successfully Updated!!!");
                return $this->redirect()->toRoute("level");
            }
        }
        return Helper::addFlashMessagesToArray(
                        $this, [
                    'customRenderer' => Helper::renderCustomView(),
                    'form' => $this->form,
                    'id' => $id
                        ]
        );
    }

    public function deleteAction() {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('level');
        }
        // echo '<pre>';print_r($id);die;
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Level Successfully Deleted!!!");
        return $this->redirect()->toRoute('level');
    }

}

