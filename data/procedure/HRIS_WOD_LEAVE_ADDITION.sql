create or replace PROCEDURE HRIS_WOD_LEAVE_ADDITION(
    P_WOD_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE )
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_DAYOFF.EMPLOYEE_ID%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_DAYOFF.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_DAYOFF.TO_DATE%TYPE;
  V_DURATION NUMBER;
  P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  --
  V_TOTAL_HOUR HRIS_ATTENDANCE_DETAIL.TOTAL_HOUR%TYPE;
  --
  V_SUBSTITUTE_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_BALANCE HRIS_EMPLOYEE_LEAVE_ASSIGN.BALANCE%TYPE;
  V_INCREMENT_DAY FLOAT    :=0;
  V_ON_TRAVEL CHAR(1 BYTE) :='N';
  V_SHIFT_ID  NUMBER(7,0);
BEGIN
-- get substitute leave id and set in variable V_SUBSTITUTE_LEAVE_ID
  SELECT LEAVE_ID
  INTO V_SUBSTITUTE_LEAVE_ID
  FROM HRIS_LEAVE_MASTER_SETUP
  WHERE STATUS='E' AND IS_SUBSTITUTE='Y'
  AND ROWNUM         = 1;
  --
  
  -- ger details of work on day off from param id and set in variables
  SELECT FROM_DATE,
    TO_DATE,
    TRUNC(TO_DATE)-TRUNC(FROM_DATE),
    EMPLOYEE_ID,
    APPROVED_BY
  INTO V_FROM_DATE,
    V_TO_DATE,
    V_DURATION,
    V_EMPLOYEE_ID,
    P_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_WORK_DAYOFF
  WHERE ID= P_WOD_ID;
  --
  
  --select past balance from HRIS_EMPLOYEE_LEAVE_ASSIGN  and set into variable
  -- if not found  the insert new record in HRIS_EMPLOYEE_LEAVE_ASSIGN 
  --for that employee and leave with balance and total 0
  BEGIN
    SELECT BALANCE
    INTO V_BALANCE
    FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
    WHERE EMPLOYEE_ID=V_EMPLOYEE_ID
    AND LEAVE_ID     = V_SUBSTITUTE_LEAVE_ID;
  EXCEPTION
  WHEN no_data_found THEN
    INSERT
    INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
      (
        EMPLOYEE_ID,
        LEAVE_ID,
        PREVIOUS_YEAR_BAL,
        TOTAL_DAYS,
        BALANCE,
        CREATED_DT,
        CREATED_BY
      )
      VALUES
      (
        V_EMPLOYEE_ID,
        V_SUBSTITUTE_LEAVE_ID,
        0,
        0,
        0,
        TRUNC(SYSDATE),
        P_EMPLOYEE_ID
      );
  END;
  --
  
  --loop until the duration of work on day applied
  FOR i IN 0..V_DURATION
  LOOP
  -- check attendance_details for that day(total worked hour,travel,shift_id) and set in variables
    BEGIN
      SELECT TOTAL_HOUR,
        (
        CASE
          WHEN TRAVEL_ID IS NOT NULL
          THEN 'Y'
          ELSE 'N'
        END),
        SHIFT_ID
      INTO V_TOTAL_HOUR,
        V_ON_TRAVEL,
        V_SHIFT_ID
      FROM HRIS_ATTENDANCE_DETAIL
      WHERE EMPLOYEE_ID= V_EMPLOYEE_ID
      AND ATTENDANCE_DT= TRUNC(V_FROM_DATE)+i;
    EXCEPTION
    WHEN no_data_found THEN
      CONTINUE;
    END;
    
    -- if on travel select total working hour from  shift and override into variable v-total_hour
    IF V_ON_TRAVEL = 'Y' THEN
      SELECT TOTAL_WORKING_HR
      INTO V_TOTAL_HOUR
      FROM HRIS_SHIFTS
      WHERE SHIFT_ID = V_SHIFT_ID;
    END IF;
    --
    -- check  total working hour  
    --if greater than 2 then add 0.5 leave if  greater tan 4 then 1 day leave 
    IF((V_TOTAL_HOUR /60)     >= 2 AND(V_TOTAL_HOUR /60) < 4) THEN
      V_INCREMENT_DAY         :=V_INCREMENT_DAY+.5;
    ELSIF ((V_TOTAL_HOUR /60) >=4) THEN
      V_INCREMENT_DAY         :=V_INCREMENT_DAY+1;
    END IF;
  END LOOP;
  --
BEGIN
DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION  WHERE WOD_ID= P_WOD_ID;
END;
  INSERT
  INTO HRIS_EMPLOYEE_LEAVE_ADDITION
    (
      EMPLOYEE_ID,
      LEAVE_ID,
      NO_OF_DAYS,
      REMARKS,
      CREATED_DATE,
      WOD_ID,
      WOH_ID
    )
    VALUES
    (
      V_EMPLOYEE_ID,
      V_SUBSTITUTE_LEAVE_ID,
      V_INCREMENT_DAY,
      'WOD REWARD',
      TRUNC(SYSDATE),
      P_WOD_ID,
      NULL
    );
END;