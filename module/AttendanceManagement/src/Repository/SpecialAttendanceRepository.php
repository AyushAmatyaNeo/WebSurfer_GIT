<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use AttendanceManagement\Model\SpecialAttendanceSetup;
use AttendanceManagement\Model\SpecialAttendanceAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Application\Repository\HrisRepository;
use Application\Repository\RepositoryInterface;

class SpecialAttendanceRepository extends HrisRepository implements RepositoryInterface {

  public function __construct(AdapterInterface $adapter) {
    parent::__construct($adapter, SpecialAttendanceSetup::TABLE_NAME);
    $this->adapter = $adapter;
  }

  public function add(Model $model) {
    return $this->tableGateway->insert($model->getArrayCopyForDB());
  }

  public function edit(Model $model, $id) {
    return $this->tableGateway->update($model->getArrayCopyForDB(),[SpecialAttendanceSetup::ID=>$id]);
  }

  public function fetchAll() {
    return $this->tableGateway->select([SpecialAttendanceSetup::STATUS=>'E'])->toArray();
  }

  public function fetchById($id) {
    return $this->tableGateway->select([SpecialAttendanceSetup::ID=>$id, SpecialAttendanceSetup::STATUS=>'E'])->current();
  }

  public function delete($id) {
    return $this->tableGateway->update([SpecialAttendanceSetup::STATUS=>'D'], [SpecialAttendanceSetup::ID=>$id]);
  }

  public function filterEmployees($searchQuery) {
    $boundedParameter = [];
    $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], $searchQuery['genderId'], $searchQuery['locationId'], $searchQuery['functionalTypeId']);
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

    $sql = "SELECT 
              E.EMPLOYEE_ID                                                AS EMPLOYEE_ID,
              E.EMPLOYEE_CODE                                                   AS EMPLOYEE_CODE,
              INITCAP(E.FIRST_NAME)                                              AS MIDDLE_NAME,
              INITCAP(E.MIDDLE_NAME)                                              AS FULL_NAME,
              INITCAP(E.LAST_NAME)                                              AS LAST_NAME,
              INITCAP(E.FULL_NAME)                                              AS FULL_NAME,
              INITCAP(G.GENDER_NAME)                                            AS GENDER_NAME,
              (C.COMPANY_NAME)                                           AS COMPANY_NAME,
              (B.BRANCH_NAME)                                            AS BRANCH_NAME,
              (D.DEPARTMENT_NAME)                                        AS DEPARTMENT_NAME,
              (DES.DESIGNATION_TITLE)                                    AS DESIGNATION_TITLE,
              (P.POSITION_NAME)                                          AS POSITION_NAME,
              P.LEVEL_NO                                                        AS LEVEL_NO,
              INITCAP(ST.SERVICE_TYPE_NAME)                                     AS SERVICE_TYPE_NAME,
              (CASE WHEN E.EMPLOYEE_TYPE='R' THEN 'REGULAR' ELSE 'WORKER' END)  AS EMPLOYEE_TYPE
            FROM HRIS_EMPLOYEES E
            LEFT JOIN HRIS_COMPANY C
            ON E.COMPANY_ID=C.COMPANY_ID
            LEFT JOIN HRIS_BRANCHES B
            ON E.BRANCH_ID=B.BRANCH_ID
            LEFT JOIN HRIS_DEPARTMENTS D
            ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
            LEFT JOIN HRIS_DESIGNATIONS DES
            ON E.DESIGNATION_ID=DES.DESIGNATION_ID
            LEFT JOIN HRIS_POSITIONS P
            ON E.POSITION_ID=P.POSITION_ID
            LEFT JOIN HRIS_SERVICE_TYPES ST
            ON E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID
            LEFT JOIN HRIS_GENDERS G
            ON E.GENDER_ID=G.GENDER_ID
            WHERE 1                 =1 AND E.STATUS='E' 
            {$searchCondition['sql']} order by E.FULL_NAME  ";
            
            $statement = $this->adapter->query($sql);
            return $statement->execute($boundedParameter); 
  }

  public function assignSpToEmployees($spId, $employeeId, $attendanceDate, $displayInOutFlag, $createdBy){
    $boundedParameter = [];
    $boundedParameter['EMPLOYEE_ID'] = $employeeId;
    $boundedParameter['SP_ID'] = $spId;
    $boundedParameter['ATTENDANCE_DATE'] = $attendanceDate;
    $boundedParameter['DISPLAY_IN_OUT'] = $displayInOutFlag;
    $boundedParameter['CREATED_BY'] = $createdBy;

    $sql = "
    DECLARE
    V_SP_ID HRIS_SPECIAL_ATTENDANCE_ASSIGN.SP_ID%TYPE := :SP_ID;
    V_EMPLOYEE_ID HRIS_SPECIAL_ATTENDANCE_ASSIGN.EMPLOYEE_ID%TYPE := :EMPLOYEE_ID;
    V_ATTENDANCE_DATE HRIS_SPECIAL_ATTENDANCE_ASSIGN.ATTENDANCE_DATE%TYPE := :ATTENDANCE_DATE;
    V_DISPLAY_IN_OUT HRIS_SPECIAL_ATTENDANCE_ASSIGN.DISPLAY_IN_OUT%TYPE := :DISPLAY_IN_OUT;
    V_CREATED_BY HRIS_SPECIAL_ATTENDANCE_ASSIGN.CREATED_BY%TYPE := :CREATED_BY;
    V_ID HRIS_SPECIAL_ATTENDANCE_ASSIGN.ID%TYPE;
    BEGIN
    DELETE FROM HRIS_SPECIAL_ATTENDANCE_ASSIGN WHERE EMPLOYEE_ID = V_EMPLOYEE_ID AND  ATTENDANCE_DATE = V_ATTENDANCE_DATE;
    INSERT INTO HRIS_SPECIAL_ATTENDANCE_ASSIGN(ID, SP_ID, EMPLOYEE_ID, ATTENDANCE_DATE, DISPLAY_IN_OUT, STATUS, CREATED_DT, CREATED_BY) VALUES((SELECT NVL(MAX(ID), 0) + 1 FROM HRIS_SPECIAL_ATTENDANCE_ASSIGN), V_SP_ID, V_EMPLOYEE_ID, V_ATTENDANCE_DATE, V_DISPLAY_IN_OUT, 'AP', SYSDATE, V_CREATED_BY);
    END;
    ";
    $this->executeStatement($sql, $boundedParameter);
  }

  public function removeSpFromEmployees($employeeId, $attendanceDate){
    $boundedParameter = [];
    $boundedParameter['EMPLOYEE_ID'] = $employeeId;
    $boundedParameter['ATTENDANCE_DATE'] = $attendanceDate;
    $sql = "DELETE FROM HRIS_SPECIAL_ATTENDANCE_ASSIGN WHERE EMPLOYEE_ID = :EMPLOYEE_ID AND ATTENDANCE_DATE = :ATTENDANCE_DATE";
    $this->executeStatement($sql, $boundedParameter);
  }

  public function reAttendance($employeeId, $date){
    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    $boundedParameter['date'] = $date;
    $this->executeStatement("BEGIN HRIS_REATTENDANCE(:date, :employeeId, :date); END;", $boundedParameter);
  }

}
