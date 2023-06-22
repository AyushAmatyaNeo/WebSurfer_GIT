<?php

namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Setup\Form\ServiceEventTypeForm;
use Setup\Model\ServiceEventType;
use Setup\Repository\ServiceEventTypeRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ServiceEventTypeController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(ServiceEventTypeRepository::class);
        $this->initializeForm(ServiceEventTypeForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $serviceEventTypeList = $this->repository->fetchAll();
                $serviceEventTypes = Helper::extractDbData($serviceEventTypeList);

                return new JsonModel(['success' => true, 'data' => $serviceEventTypes, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo(['acl' => $this->acl]);
    }

    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                try {
                    $serviceEventType = new ServiceEventType();
                    $serviceEventType->exchangeArrayFromForm($this->form->getData());
                    $serviceEventType->serviceEventTypeId = ((int)Helper::getMaxId($this->adapter, ServiceEventType::TABLE_NAME, ServiceEventType::SERVICE_EVENT_TYPE_ID)) + 1;
                    $serviceEventType->createdDt = Helper::getcurrentExpressionDate();
                    $serviceEventType->status = 'E';
                    $this->repository->add($serviceEventType);

                    $this->flashmessenger()->addMessage("Service Event Type Successfully Added!!!");
                    return $this->redirect()->toRoute("serviceEventType");
                } catch (Exception $e) {
                }
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'messages' => $this->flashmessenger()->getMessages()
        ]);
    }

    public function editAction()
    {
        $id = (int)$this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute();
        }
        $request = $this->getRequest();
        $serviceEventType = new ServiceEventType();
        if (!$request->isPost()) {
            $serviceEventType->exchangeArrayFromDb($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($serviceEventType);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $serviceEventType->exchangeArrayFromForm($this->form->getData());
                $serviceEventType->modifiedDt = Helper::getcurrentExpressionDate();

                $this->repository->edit($serviceEventType, $id);
                $this->flashmessenger()->addMessage("Service Event Type Successfully Updated!!!");
                return $this->redirect()->toRoute("serviceEventType");
            }
        }
        return Helper::addFlashMessagesToArray($this, ['form' => $this->form, 'id' => $id]);
    }

    public function deleteAction()
    {
        $id = (int)$this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('serviceEventType');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Service Event Type Successfully Deleted!!!");
        return $this->redirect()->toRoute('serviceEventType');
    }

}
