<?php

namespace Loan\Repository;

use Zend\Db\TableGateway\TableGateway;
use Application\Helper\EntityHelper;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Application\Repository\RepositoryInterface;
use Setup\Model\HrEmployees;

class LoanStatusRepository implements RepositoryInterface {

    private $adapter;

    public function __construct(\Zend\Db\Adapter\AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function add(\Application\Model\Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(\Application\Model\Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function getFilteredRecord($data, $recomApproveId) {
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $loanId = $data['loanId'];
        $loanRequestStatusId = $data['loanRequestStatusId'];
        $employeeTypeId = $data['employeeTypeId'];


        $sql = "SELECT INITCAP(L.LOAN_NAME) AS LOAN_NAME,
                  LR.REQUESTED_AMOUNT,
                  INITCAP(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY'))                   AS LOAN_DATE_AD,
                  BS_DATE(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY'))                   AS LOAN_DATE_BS,
                  INITCAP(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(LR.STATUS)                                    AS STATUS,
                  REC_APP_ROLE(U.EMPLOYEE_ID,RA.RECOMMEND_BY,RA.APPROVED_BY)      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,RA.RECOMMEND_BY,RA.APPROVED_BY) AS YOUR_ROLE,
                  LR.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  LR.LOAN_REQUEST_ID                                              AS LOAN_REQUEST_ID,
                  INITCAP(TO_CHAR(LR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(LR.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                          AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                          AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                        AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                        AS APPROVER_NAME,
                  LR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  LR.APPROVED_BY                                                  AS APPROVED_BY,
                  LR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  LR.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_LOAN_REQUEST LR
                LEFT OUTER JOIN HRIS_LOAN_MASTER_SETUP L
                ON L.LOAN_ID=LR.LOAN_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LR.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=LR.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=LR.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON LR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES U
                ON (U.EMPLOYEE_ID=RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID =RA.APPROVED_BY)
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E'
                AND (E1.STATUS   =
                  CASE
                    WHEN E1.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E1.STATUS  IS NULL)
                AND (E2.STATUS =
                  CASE
                    WHEN E2.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E2.STATUS    IS NULL)
                AND (RECM.STATUS =
                  CASE
                    WHEN RECM.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR RECM.STATUS  IS NULL)
                AND (APRV.STATUS =
                  CASE
                    WHEN APRV.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR APRV.STATUS   IS NULL)
                AND U.EMPLOYEE_ID = {$recomApproveId}";
        if ($loanRequestStatusId != -1) {
            $sql .= " AND  LR.STATUS='{$loanRequestStatusId}') ";
        }

        if ($loanId != -1) {
            $sql .= " AND LR.LOAN_ID ='" . $loanId . "'";
        }

        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
        }

        if ($employeeId != -1) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }

        if ($companyId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
        }
        if ($branchId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }
        if ($departmentId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
        }
        if ($designationId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
        }
        if ($positionId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
        }
        if ($serviceTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
        }
        if ($serviceEventTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
        }

        $sql .= " ORDER BY LR.REQUESTED_DATE DESC";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getLoanRequestList($data) {
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $loanId = $data['loanId'];
        $loanRequestStatusId = $data['loanRequestStatusId'];
        $employeeTypeId = $data['employeeTypeId'];
        $loanStatus = $data['loanStatus'];

        $sql = "SELECT
                  E.EMPLOYEE_CODE as EMPLOYEE_CODE, 
                  INITCAP(L.LOAN_NAME) AS LOAN_NAME,
                  LR.REQUESTED_AMOUNT,
                  INITCAP(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY'))                   AS LOAN_DATE_AD,
                  (CASE WHEN LR.STATUS = 'AP' AND LR.LOAN_STATUS = 'OPEN' THEN 'Y' ELSE 'N' END)              AS ALLOW_EDIT,
                  (CASE WHEN LR.LOAN_STATUS = 'CLOSED' AND LR.LOAN_REQUEST_ID IN (
                    SELECT LOAN_REQ_ID FROM HRIS_LOAN_CASH_PAYMENT
                  ) THEN 'Y' ELSE 'N' END) AS ALLOW_CORRECTION,
                  BS_DATE(TO_CHAR(LR.LOAN_DATE, 'DD-MON-YYYY'))                   AS LOAN_DATE_BS,
                  INITCAP(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(LR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(LR.STATUS)                                    AS STATUS,
                  LR.LOAN_STATUS                                                  AS LOAN_STATUS,
                  LR.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  LR.LOAN_REQUEST_ID                                              AS LOAN_REQUEST_ID,
                  INITCAP(TO_CHAR(LR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(LR.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                          AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                          AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                        AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                        AS APPROVER_NAME,
                  LR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  LR.APPROVED_BY                                                  AS APPROVED_BY,
                  LR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  LR.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_LOAN_REQUEST LR
                LEFT OUTER JOIN HRIS_LOAN_MASTER_SETUP L
                ON L.LOAN_ID=LR.LOAN_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LR.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=LR.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=LR.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON LR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E'
                --AND (E1.STATUS   =
                 -- CASE
                --    WHEN E1.STATUS IS NOT NULL
                --    THEN ('E')
                --  END
               -- OR E1.STATUS  IS NULL)
               -- AND (E2.STATUS =
               --   CASE
                --    WHEN E2.STATUS IS NOT NULL
               --     THEN ('E')
                --  END
               -- OR E2.STATUS    IS NULL)
                AND (RECM.STATUS =
                  CASE
                    WHEN RECM.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR RECM.STATUS  IS NULL)
                AND (APRV.STATUS =
                  CASE
                    WHEN APRV.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR APRV.STATUS   IS NULL)";
        if ($loanRequestStatusId != -1) {
            $sql .= " AND  LR.STATUS='{$loanRequestStatusId}') ";
        }

        if ($loanId != -1) {
            $sql .= " AND LR.LOAN_ID ='" . $loanId . "'";
        }

        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
        }

        if ($employeeId != -1) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }

        if ($companyId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
        }
        if ($branchId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }
        if ($departmentId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
        }
        if ($designationId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
        }
        if ($positionId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
        }
        if ($serviceTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
        }
        if ($serviceEventTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
        }
        if ($loanStatus != 'BOTH') {
          $sql .= " AND LR.LOAN_STATUS = '".$loanStatus."'";
        }

        $sql .= " ORDER BY LR.LOAN_REQUEST_ID DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function editList($id){
      $sql = "SELECT HLPD.SNO, HLPD.PAYMENT_ID, HE.FULL_NAME, HLMS.INTEREST_RATE, HLMS.LOAN_NAME,
      HLPD.FROM_DATE, HLPD.TO_DATE, 
      HLPD.AMOUNT, 
      HLPD.PRINCIPLE_AMOUNT, HLPD.INTEREST_AMOUNT, HLPD.PAID_FLAG AS PAID 
      FROM HRIS_LOAN_PAYMENT_DETAIL HLPD
      JOIN HRIS_EMPLOYEE_LOAN_REQUEST HELR ON HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID
      JOIN HRIS_EMPLOYEES HE ON HELR.EMPLOYEE_ID = HE.EMPLOYEE_ID
      JOIN HRIS_LOAN_MASTER_SETUP HLMS ON HLMS.LOAN_ID = HELR.LOAN_ID
      WHERE HELR.LOAN_REQUEST_ID = $id ORDER BY HLPD.SNO";
     
      $statement = $this->adapter->query($sql);
      $result = $statement->execute();
      return $result;
    }
 
    public function skipMonth($requestId, $id){ 
      $sql = "BEGIN
      HRIS_LOAN_PAYMENT_DETAILS({$requestId},{$id}); 
      END;
      ";

      $statement = $this->adapter->query($sql); 
      $statement->execute(); 
    }

    public function getPaidStatus($requestId, $id){
      $sql = "SELECT PAID_FLAG, AMOUNT FROM HRIS_LOAN_PAYMENT_DETAIL WHERE PAYMENT_ID = $id";

      $statement = $this->adapter->query($sql); 
      return $statement->execute();
    }
 
    public function getLoanRequestId($id){
      $sql = "SELECT DISTINCT LOAN_REQUEST_ID FROM HRIS_LOAN_PAYMENT_DETAIL WHERE 
      PAYMENT_ID = $id";

      $statement = $this->adapter->query($sql);
      $result = $statement->execute();
      return $result;
    }

    // public function getLoanRequestDetails($ids){

    //   $ids = implode($ids, ',');

    //   $sql = "SELECT (CASE WHEN (SELECT COUNT(*) FROM HRIS_LOAN_PAYMENT_DETAIL 
    //   WHERE LOAN_REQUEST_ID IN ($ids)
    //   AND PAID_FLAG = 'N') > 0 
    //   THEN 'OPEN' 
    //   ELSE 'CLOSED' END) 
    //   AS STATUS,
    //   SUM(CASE WHEN PAID_FLAG = 'N' THEN AMOUNT ELSE 0 END) 
    //   AS BALANCE,
    //   SUM(CASE WHEN PAID_FLAG = 'Y' THEN AMOUNT ELSE 0 END) 
    //   AS PAID_AMOUNT
    //   FROM HRIS_LOAN_PAYMENT_DETAIL
    //   where LOAN_REQUEST_ID IN ($ids) GROUP BY LOAN_REQUEST_ID 
    //   ORDER BY LOAN_REQUEST_ID DESC;";
     
    //   $statement = $this->adapter->query($sql);
    //   $result = $statement->execute();
    //   return $result;
    // }
    
    public function getApprovedStatus($id){
      $sql = "SELECT STATUS FROM HRIS_EMPLOYEE_LOAN_REQUEST WHERE LOAN_REQUEST_ID = $id";
      $statement = $this->adapter->query($sql); 
      return $statement->execute();
    }

    public function getLoanDetails($searchQuery){
      $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], $searchQuery['genderId'], $searchQuery['locationId'], $searchQuery['functionalTypeId']);

      $sql = "SELECT
      e.employee_id,
      e.employee_code,
      e.full_name,
      lr.loan_id,
      lms.loan_name,
      lr.amount as REQUESTED_AMOUNT,
      cp.total_paid as PAID,
      (lr.amount-cp.total_paid) as BALANCE
  FROM
      hris_employees           e
      JOIN (
          SELECT
              employee_id,
              loan_id,
              SUM(requested_amount) amount
          FROM
              hris_employee_loan_request
          GROUP BY
              employee_id,
              loan_id
      ) lr ON ( e.employee_id = lr.employee_id )
      JOIN hris_loan_master_setup   lms ON ( lms.loan_id = lr.loan_id )
      JOIN (
        SELECT employee_id, loan_id, PAID_AMONUT+CASH_PAID_AMOUNT TOTAL_PAID FROM
        (SELECT lr.employee_id, lr.loan_id, NVL(SUM(LPD.AMOUNT), 0) AS PAID_AMONUT
        , NVL(SUM(lcp.payment_amount), 0) AS CASH_PAID_AMOUNT 
        FROM HRIS_LOAN_PAYMENT_DETAIL LPD
        JOIN hris_employee_loan_request LR ON (lr.loan_request_id = lpd.loan_request_id)
        LEFT JOIN hris_loan_cash_payment LCP ON (lcp.loan_req_id = lr.loan_request_id)
        WHERE lpd.paid_flag = 'Y'
        GROUP BY lr.employee_id, LR.LOAN_ID)
      ) CP ON ( lr.employee_id = cp.employee_id and lr.loan_id = cp.loan_id)
      WHERE lr.status = 'AP' and lr.EMPLOYEE_ID IN
                  ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' 
                  ) {$searchConditon}";

    $statement = $this->adapter->query($sql); 
    return $statement->execute();
    }
}
