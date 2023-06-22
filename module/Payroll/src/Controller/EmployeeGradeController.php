<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Authentication\Storage\StorageInterface;
use Zend\View\Model\JsonModel;
use Payroll\Repository\EmployeeGradeRepository;


class EmployeeGradeController extends HrisController {

    public $adapter;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->adapter = $adapter;
        $this->initializeRepository(EmployeeGradeRepository::class);
    }

    public function indexAction() {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        return $this->stickFlashMessagesTo([
                    'acl' => $this->acl,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'fiscalYears' => $fiscalYears
        ]);
    }

    public function getEmployeeGradeAction(){
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $fiscalYearId = $postedData['fiscalYearId'];
            $employeeFilter = $postedData['employeeFilter'];
            $result = $this->repository->getEmployeeGradeDetails($employeeFilter, $fiscalYearId);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postEmployeeGradeAction(){
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            // print_r($data); die;
            foreach ($data as $key => $value) {
                $this->repository->postEmployeeGradeDetails($value, $postedData['fiscalYearId'], $this->employeeId);
            }
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function excelAction(){
        $fiscalYears=EntityHelper::getTableKVList($this->adapter, "HRIS_FISCAL_YEARS", "FISCAL_YEAR_ID", ["FISCAL_YEAR_NAME"], null,null,false,'FISCAL_YEAR_ID','desc');
        $fiscalYearsSE = $this->getSelectElement(['name' => 'fiscalYear', 'id' => 'fiscalYear', 'class' => 'form-control ', 'label' => 'Type'], $fiscalYears);
        return $this->stickFlashMessagesTo([
                    'acl' => $this->acl,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'leaveYearSelect' => $fiscalYearsSE
        ]);
    }

    public function updateGradeDetailsAction(){
        $excelData = $_POST['data'];
        $fiscalYearId = $_POST['fiscalYearId'];
        $basedOn = $_POST['basedOn'];
        foreach ($excelData as $data) {
            if($basedOn == 2){ $data['B'] = EntityHelper::getEmployeeIdFromCode($this->adapter, $data['B']); }
            if($data['B'] == null || $data['B'] == ''){ continue; }
            $item['EMPLOYEE_ID'] = $data['B'];
            $item['OPENING_GRADE'] = $data['E'];
            $item['ADDITIONAL_GRADE'] = $data['F'];
            $item['GRADE_VALUE'] = $data['G'];
            $item['GRADE_DATE'] = $data['H'];
            $item['REMARKS'] = $data['J'];
            $this->repository->postEmployeeGradeDetails($item, $fiscalYearId, $this->employeeId);
        }
        return new JsonModel(['success' => true, 'error' => '']);
    }
}