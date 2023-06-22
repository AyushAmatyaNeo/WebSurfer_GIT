<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Zend\Db\Adapter\AdapterInterface;
use Application\Repository\HrisRepository;

class EmployeeGradeRepository extends HrisRepository{

    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
      $this->adapter = $adapter;
    }

    public function getEmployeeGradeDetails($emp){
      $searchCondition = EntityHelper::getSearchConditon($emp['companyId'], $emp['branchId'], $emp['departmentId'], $emp['positionId'], $emp['designationId'], $emp['serviceTypeId'], $emp['serviceEventTypeId'], $emp['employeeTypeId'], $emp['employeeId'], $emp['genderId'], $emp['locationId']);

      $sql = "SELECT
        e.employee_id,
        e.employee_code,
        e.full_name,
        g.opening_grade,
        g.additional_grade,
        g.GRADE_VALUE,
        g.grade_date,
        g.remarks
    FROM
        hris_employees e
        LEFT JOIN hris_employee_grade_info g on (e.employee_id = g.employee_id)
        left join hris_fiscal_years f on (g.fiscal_year_id = f.fiscal_year_id)
        where 1=1 {$searchCondition} ";

    return $this->rawQuery($sql);
    }

    public function postEmployeeGradeDetails($data, $fiscalYearId, $createdBy){
      $opening_grade = $data['OPENING_GRADE'] == null || $data['OPENING_GRADE'] == '' ? 'null' : $data['OPENING_GRADE'];
      $additional_grade = $data['ADDITIONAL_GRADE'] == null || $data['ADDITIONAL_GRADE'] == '' ? 'null' : $data['ADDITIONAL_GRADE'];
      $grade_value = $data['GRADE_VALUE'] == null || $data['GRADE_VALUE'] == '' ? 'null' : $data['GRADE_VALUE'];
      $grade_date = $data['GRADE_DATE'];
      $employee_id = $data['EMPLOYEE_ID'] == null || $data['EMPLOYEE_ID'] == '' ? 'null' : $data['EMPLOYEE_ID'];
      $fiscal_year_id = $fiscalYearId == null || $fiscalYearId == '' ? 'null' : $fiscalYearId;
      $remarks = $data['REMARKS'];

        $sql = "
                DECLARE
                  V_OPENING_GRADE HRIS_EMPLOYEE_GRADE_INFO.OPENING_GRADE%TYPE := {$opening_grade};
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_GRADE_INFO.EMPLOYEE_ID%TYPE := {$employee_id};
                  V_ADDITIONAL_GRADE HRIS_EMPLOYEE_GRADE_INFO.ADDITIONAL_GRADE%TYPE := {$additional_grade};
                  V_FISCAL_YEAR_ID HRIS_EMPLOYEE_GRADE_INFO.FISCAL_YEAR_ID%TYPE := {$fiscalYearId};
                  V_GRADE_VALUE HRIS_EMPLOYEE_GRADE_INFO.GRADE_VALUE%TYPE := {$grade_value};
                  V_GRADE_DATE HRIS_EMPLOYEE_GRADE_INFO.GRADE_DATE%TYPE := '{$grade_date}';
                  V_REMARKS HRIS_EMPLOYEE_GRADE_INFO.REMARKS%TYPE := '{$remarks}';
                  V_CREATED_BY HRIS_EMPLOYEE_GRADE_INFO.CREATED_BY%TYPE := {$createdBy};
                  V_OLD_DATE HRIS_EMPLOYEE_GRADE_INFO.GRADE_DATE%TYPE;

                BEGIN
                  SELECT GRADE_DATE
                  INTO V_OLD_DATE
                  FROM HRIS_EMPLOYEE_GRADE_INFO
                  WHERE EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND to_char(V_GRADE_DATE, 'YYYY') = to_char(GRADE_DATE, 'YYYY')
                  and to_char(V_GRADE_DATE, 'MM') = to_char(GRADE_DATE, 'MM');
                  
                  UPDATE HRIS_EMPLOYEE_GRADE_INFO
                  SET OPENING_GRADE = V_OPENING_GRADE,
                  ADDITIONAL_GRADE = V_ADDITIONAL_GRADE,
                  GRADE_VALUE = V_GRADE_VALUE,
                  GRADE_DATE = V_GRADE_DATE,
                  REMARKS = V_REMARKS,
                  MODIFIED_DATE = trunc(sysdate),
                  MODIFIED_BY = V_CREATED_BY
                  WHERE EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                  
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_EMPLOYEE_GRADE_INFO
                    (
                      EMPLOYEE_ID,
                      FISCAL_YEAR_ID,
                      OPENING_GRADE,
                      ADDITIONAL_GRADE,
                      GRADE_VALUE,
                      GRADE_DATE,
                      REMARKS,
                      created_date,
                      created_by
                    )
                    VALUES
                    (
                      V_EMPLOYEE_ID,
                      V_FISCAL_YEAR_ID,
                      V_OPENING_GRADE,
                      V_ADDITIONAL_GRADE,
                      V_GRADE_VALUE,
                      V_GRADE_DATE,
                      V_REMARKS,
                      trunc(sysdate),
                      V_CREATED_BY
                    );
                END;
";
 //echo $sql; die;
      return $this->rawQuery($sql);
    }
}
