create or replace FUNCTION HRIS_GET_CUSTOMIZED_DO( p_emp_id NUMBER, p_attendance_dt DATE) RETURN CHAR IS
    V_FROM_DATE DATE;
    V_COMPANY_ID NUMBER(7);
    V_BRANCH_ID NUMBER(7);
    V_FIRST_DO DATE;
    V_SECOND_DO DATE;
BEGIN
    BEGIN
      SELECT FROM_DATE
      INTO V_FROM_DATE
      FROM HRIS_MONTH_CODE
      WHERE TRUNC(p_attendance_dt) BETWEEN TRUNC(FROM_DATE) AND TRUNC(TO_DATE);
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      V_FROM_DATE:=TRUNC(SYSDATE);
    END;

    SELECT COMPANY_ID, BRANCH_ID INTO V_COMPANY_ID, V_BRANCH_ID FROM HRIS_EMPLOYEES where EMPLOYEE_ID = p_emp_id;

    FOR dataList in (select * from  hris_eligible_sun_do where apply_to_all_branch = 'Y' and status = 'E')
    LOOP
        IF (V_COMPANY_ID = dataList.company_id) then
            SELECT NEXT_DAY(V_FROM_DATE-1,dataList.DAY_NAME) 
            INTO  V_FIRST_DO 
            FROM dual;

            SELECT NEXT_DAY(V_FROM_DATE-1,dataList.DAY_NAME) + dataList.NEXT_DAY_INTERVAL
            INTO  V_SECOND_DO 
            FROM dual;

            IF(p_attendance_dt = V_FIRST_DO OR p_attendance_dt = V_SECOND_DO) 
            THEN
                RETURN 'Y';
            END IF;
        END IF;
    END LOOP;

    FOR all_details in (select * from  hris_eligible_sun_do where apply_to_all_branch = 'N' and status = 'E')
    LOOP
        IF (V_COMPANY_ID = all_details.company_id and V_BRANCH_ID = all_details.branch_id) 
        THEN
            SELECT NEXT_DAY(V_FROM_DATE-1,all_details.DAY_NAME) 
            INTO  V_FIRST_DO 
            FROM dual;

            SELECT NEXT_DAY(V_FROM_DATE-1,all_details.DAY_NAME) + all_details.NEXT_DAY_INTERVAL
            INTO  V_SECOND_DO 
            FROM dual;

            IF(p_attendance_dt = V_FIRST_DO OR p_attendance_dt = V_SECOND_DO) 
            THEN
                RETURN 'Y';
            END IF;
        END IF;
    END LOOP;


    RETURN 'N';
END;
 