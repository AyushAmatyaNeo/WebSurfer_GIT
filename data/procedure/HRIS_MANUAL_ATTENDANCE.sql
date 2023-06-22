CREATE OR REPLACE PROCEDURE HRIS_MANUAL_ATTENDANCE(
    P_EMPLOYEE_ID HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE ,
    P_ATTENDANCE_DT HRIS_ATTENDANCE_DETAIL.ATTENDANCE_DT%TYPE,
    P_STATUS CHAR,
    P_SHIFT_ID NUMBER :=NULL,
    P_IN_TIME DATE :=NULL,
    P_OUT_TIME DATE :=NULL
    )
AS
  V_WEEK_DAY NUMBER(1);
    V_DYNAMIC_SQL VARCHAR2(1000 BYTE);
    V_TO_TIME TIMESTAMP;
BEGIN
 
IF(P_SHIFT_ID != '0' AND P_SHIFT_ID IS NOT NULL)
THEN
BEGIN
DELETE  FROM HRIS_EMPLOYEE_SHIFT_ROASTER WHERE EMPLOYEE_ID=P_EMPLOYEE_ID AND FOR_DATE=P_ATTENDANCE_DT;

INSERT INTO HRIS_EMPLOYEE_SHIFT_ROASTER
VALUES (P_EMPLOYEE_ID,P_SHIFT_ID,P_ATTENDANCE_DT,NULL,NULL,NULL,NULL);
END;
END IF;

select to_char(p_attendance_dt, 'd') INTO V_WEEK_DAY from dual;

  FOR attendance IN
  (SELECT A.EMPLOYEE_ID,
    A.ATTENDANCE_DT,
    S.START_TIME,
    S.END_TIME,
    A.OVERALL_STATUS,
    s.shift_id
  FROM HRIS_ATTENDANCE_DETAIL A
  JOIN HRIS_SHIFTS S
  ON (A.SHIFT_ID   =S.SHIFT_ID)
  WHERE A.EMPLOYEE_ID=P_EMPLOYEE_ID
  AND A.ATTENDANCE_DT= P_ATTENDANCE_DT
  AND A.OVERALL_STATUS IN ('AB','PR','BA','LA')
  )
  LOOP
  
   V_TO_TIME:=attendance.end_time;
    
    
    -- begin for overriding halfday case
    
    V_DYNAMIC_SQL:='SELECT CASE WHEN WEEKDAY'||V_WEEK_DAY||'=''H'' AND HALF_DAY_OUT_TIME IS NOT NULL  THEN
            HALF_DAY_OUT_TIME
            ELSE
            end_time
            END
            FROM  HRIS_SHIFTS WHERE SHIFT_ID='||attendance.shift_id;
            
            execute immediate V_DYNAMIC_SQL into V_TO_TIME;

    -- end for overriding halfday case
  
  
  
    IF P_STATUS ='P' THEN
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_IN_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_IN_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(attendance.START_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_OUT_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_OUT_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(V_TO_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
    END IF;
    IF P_STATUS ='A' THEN
      DELETE
      FROM HRIS_ATTENDANCE
      WHERE EMPLOYEE_ID=P_EMPLOYEE_ID
      AND ATTENDANCE_DT= P_ATTENDANCE_DT
      AND ATTENDANCE_FROM='SYSTEM';
    END IF ;
    HRIS_REATTENDANCE(attendance.ATTENDANCE_DT,attendance.EMPLOYEE_ID,attendance.ATTENDANCE_DT);
  END LOOP;
END;