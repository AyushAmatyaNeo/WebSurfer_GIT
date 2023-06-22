create or replace TRIGGER HRIS_AFTER_OVERTIME_APPLY AFTER
    INSERT OR UPDATE OR DELETE ON HRIS_OVERTIME
    FOR EACH ROW
    DECLARE 
      V_customizedDayOff VARCHAR2(100);
      V_IS_DAYOFF CHAR(1);
      v_sub_leave_id  NUMBER;
      v_shift_id          NUMBER;
      v_weekday1 hris_shifts.weekday1%TYPE;
      v_weekday2 hris_shifts.weekday2%TYPE;
      v_weekday3 hris_shifts.weekday3%TYPE;
      v_weekday4 hris_shifts.weekday4%TYPE;
      v_weekday5 hris_shifts.weekday5%TYPE;
      v_weekday6 hris_shifts.weekday6%TYPE;
      v_weekday7 hris_shifts.weekday7%TYPE;
      v_dayoff        VARCHAR2(1 BYTE);
BEGIN


        SELECT leave_id
        INTO v_sub_leave_id
        FROM hris_leave_master_setup
        WHERE is_substitute = 'Y' and status='E';

    BEGIN
        SELECT hs.shift_id,
          hs.weekday1,
          hs.weekday2,
          hs.weekday3,
          hs.weekday4,
          hs.weekday5,
          hs.weekday6,
          hs.weekday7
        INTO v_shift_id,
          v_weekday1,
          v_weekday2,
          v_weekday3,
          v_weekday4,
          v_weekday5,
          v_weekday6,
          v_weekday7
        FROM HRIS_ATTENDANCE_DETAIL AD
        LEFT JOIN hris_shifts hs
        ON (AD.SHIFT_ID        = hs.SHIFT_ID)
        WHERE 1                = 1
        AND AD.employee_id     = :new.EMPLOYEE_ID
        AND hs.status          = 'E'
        and ad.attendance_dt=:new.OVERTIME_DATE
        AND AD.shift_id        = hs.shift_id;
      EXCEPTION
      WHEN no_data_found THEN
        BEGIN
          SELECT shift_id,
            weekday1,
            weekday2,
            weekday3,
            weekday4,
            weekday5,
            weekday6,
            weekday7
          INTO v_shift_id,
            v_weekday1,
            v_weekday2,
            v_weekday3,
            v_weekday4,
            v_weekday5,
            v_weekday6,
            v_weekday7
          FROM hris_shifts
          WHERE :new.OVERTIME_DATE BETWEEN start_date AND end_date
          AND default_shift = 'Y'
          AND status        = 'E'
          AND ROWNUM        = 1;
        EXCEPTION
        WHEN no_data_found THEN
          raise_application_error(-20344,'No default and normal shift defined for this time period');
        END;
      END;

       v_dayoff := 'N';

     BEGIN
      --------LOGIC FOR 1st and 3rd sunday DO---------
      SELECT VALUE INTO V_customizedDayOff FROM HRIS_SETTINGS WHERE NAME = 'reatt.customizedDayOff';

      IF (V_customizedDayOff = '1') 
      THEN 
        SELECT HRIS_GET_CUSTOMIZED_DO (:new.EMPLOYEE_ID, :new.OVERTIME_DATE) INTO V_IS_DAYOFF FROM DUAL;

        IF(V_IS_DAYOFF='Y')
        THEN
            v_dayoff := 'Y';
        END IF;
     END IF;
    -------------------------------
        IF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '1' ) THEN
          IF v_weekday1                           = 'DAY_OFF' THEN
            v_dayoff                             := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '2' ) THEN
          IF v_weekday2                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '3' ) THEN
          IF v_weekday3                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '4' ) THEN
          IF v_weekday4                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '5' ) THEN
          IF v_weekday5                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '6' ) THEN
          IF v_weekday6                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        ELSIF ( TO_CHAR(:new.OVERTIME_DATE,'D') = '7' ) THEN
          IF v_weekday7                              = 'DAY_OFF' THEN
            v_dayoff                                := 'Y';
          END IF;
        END IF;
        END;


        IF (v_dayoff='Y')
        THEN
            IF
                inserting
            THEN
                IF
                    :new.status IN (
                        'AP'
                    )
                THEN
                    INSERT INTO hris_employee_leave_deduction (
                        ID,
                        EMPLOYEE_ID, 
                        LEAVE_ID,
                        DEDUCTION_DT,
                        NO_OF_DAYS,
                        STATUS,
                        REMARKS,
                        CREATED_DT,
                        OVERTIME_ID
                    ) VALUES (
                        (SELECT nvl(MAX(ID),0) + 1 FROM hris_employee_leave_deduction),
                        :new.EMPLOYEE_ID,
                        v_sub_leave_id,
                        TRUNC(SYSDATE),
                        1,
                        'AP',
                        'AUTO LEAVE DEDUCTED WHEN OVERTIME CLAIMED',
                        TRUNC(SYSDATE),
                        :new.OVERTIME_ID
                    );
                END IF;
            END IF;

            IF
                deleting
            THEN
                DELETE FROM hris_employee_leave_deduction WHERE
                    OVERTIME_ID =:old.OVERTIME_ID;

            END IF;
            IF
                updating
            THEN
                DELETE FROM hris_employee_leave_deduction WHERE
                OVERTIME_ID =:old.OVERTIME_ID;
                IF
                    :new.status IN (
                        'AP'
                    )
                THEN
                    INSERT INTO hris_employee_leave_deduction (
                    ID,
                    EMPLOYEE_ID, 
                    LEAVE_ID,
                    DEDUCTION_DT,
                    NO_OF_DAYS,
                    STATUS,
                    REMARKS,
                    CREATED_DT,
                    OVERTIME_ID
                ) VALUES (
                    (SELECT nvl(MAX(ID),0) + 1 FROM hris_employee_leave_deduction),
                    :new.EMPLOYEE_ID,
                    v_sub_leave_id,
                    TRUNC(SYSDATE),
                    1,
                    'AP',
                    'AUTO LEAVE DEDUCTED WHEN OVERTIME CLAIMED',
                    TRUNC(SYSDATE),
                    :new.OVERTIME_ID
                );
                END IF;
            END IF;
        END IF;

END;


