create or replace PROCEDURE hris_attendance_after_insert (
    p_employee_id       hris_attendance.employee_id%TYPE,
    p_attendance_dt     hris_attendance.attendance_dt%TYPE,
    p_attendance_time   hris_attendance.attendance_time%TYPE,
    p_remarks           hris_attendance.remarks%TYPE,
    p_purpose           hris_attd_device_master.purpose%TYPE := NULL
) AS

    v_in_time             hris_attendance_detail.in_time%TYPE;
    v_shift_id            hris_shifts.shift_id%TYPE;
    v_overall_status      hris_attendance_detail.overall_status%TYPE;
    v_late_status         hris_attendance_detail.late_status%TYPE := 'N';
    v_halfday_flag        hris_attendance_detail.halfday_flag%TYPE;
    v_halfday_period      hris_attendance_detail.halfday_period%TYPE;
    v_grace_period        hris_attendance_detail.grace_period%TYPE;
    v_late_in             hris_shifts.late_in%TYPE;
    v_early_out           hris_shifts.early_out%TYPE;
    v_late_start_time     TIMESTAMP;
    v_early_end_time      TIMESTAMP;
    v_total_working_min   NUMBER;
    v_late_count          NUMBER := 0;
    v_total_hour          NUMBER := 0;
    v_two_day_shift       hris_attendance_detail.two_day_shift%TYPE;
    v_ignore_time         hris_shifts.ignore_time%TYPE;
    v_half_interval       DATE;
    v_attendance_dt       DATE;
    v_attendance_time     TIMESTAMP;
BEGIN
    v_attendance_dt := trunc(p_attendance_dt);
    v_attendance_time := TO_DATE(
        TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
         || ' '
         || TO_CHAR(p_attendance_time,'HH:MI AM'),
        'DD-MON-YYYY HH:MI AM'
    );
  --

    BEGIN
        SELECT
            shift_id,
            overall_status,
            late_status,
            halfday_flag,
            halfday_period,
            grace_period,
            in_time,
            halfday_period,
            two_day_shift,
            ignore_time
        INTO
            v_shift_id,v_overall_status,v_late_status,v_halfday_flag,v_halfday_period,v_grace_period,v_in_time,v_halfday_period,v_two_day_shift
,v_ignore_time
        FROM
            hris_attendance_detail
        WHERE
                attendance_dt = trunc(v_attendance_dt)
            AND
                employee_id = p_employee_id;

        BEGIN
            select TOTAL_WORKING_HR into v_total_working_min from HRIS_SHIFTS where shift_id = v_shift_id;
        EXCEPTION
        WHEN no_data_found THEN
            v_total_working_min := 0;
        END;

        IF
            v_ignore_time = 'Y'
        THEN
            IF
                ( v_overall_status = 'DO' )
            THEN
                v_overall_status := 'WD';
            ELSIF ( v_overall_status = 'HD' ) THEN
                v_overall_status := 'WH';
            ELSIF ( v_overall_status = 'LV' ) THEN
                NULL;
            ELSIF ( v_overall_status = 'TV' ) THEN
                NULL;
            ELSIF ( v_overall_status = 'TN' ) THEN
                NULL;
            ELSE
                v_overall_status := 'PR';
            END IF;

            IF
                p_purpose = 'IN'
            THEN
                UPDATE hris_attendance_detail
                    SET
                        in_time = TO_DATE(
                            TO_CHAR(v_attendance_time,'DD-MON-YY HH:MI AM'),
                            'DD-MON-YY HH:MI AM'
                        ),
                        overall_status = v_overall_status,
                        in_remarks = p_remarks
                WHERE
                        attendance_dt = v_attendance_dt
                    AND
                        employee_id = p_employee_id
                    AND
                        in_time IS NULL;

                return;
            END IF;

            IF
                p_purpose = 'OUT'
            THEN
                v_attendance_dt := v_attendance_dt - 1;
                UPDATE hris_attendance_detail
                    SET
                        out_time = v_attendance_time,
                        overall_status = v_overall_status,
                        out_remarks = p_remarks
                WHERE
                        attendance_dt = v_attendance_dt
                    AND
                        employee_id = p_employee_id;

                return;
            END IF;

            IF
                (
                    v_in_time IS NULL
                )
            THEN
                UPDATE hris_attendance_detail
                    SET
                        in_time = TO_DATE(
                            TO_CHAR(v_attendance_time,'DD-MON-YY HH:MI AM'),
                            'DD-MON-YY HH:MI AM'
                        ),
                        overall_status = v_overall_status,
                        late_status = v_late_status,
                        in_remarks = p_remarks
                WHERE
                        attendance_dt = v_attendance_dt
                    AND
                        employee_id = p_employee_id;

                return;
            END IF;

            SELECT
                SUM(abs(EXTRACT(HOUR FROM diff) ) * 60 + abs(EXTRACT(MINUTE FROM diff) ) )
            INTO
                v_total_hour
            FROM
                (
                    SELECT
                        ( v_attendance_time - v_in_time ) AS diff
                    FROM
                        dual
                );

            UPDATE hris_attendance_detail
                SET
                    out_time = v_attendance_time,
                    late_status = v_late_status,
                    out_remarks = p_remarks,
                    total_hour = v_total_hour,
                    ot_minutes = ( v_total_hour - v_total_working_min )
            WHERE
                    attendance_dt = v_attendance_dt
                AND
                    employee_id = p_employee_id;

            return;
        END IF;

        IF
            v_two_day_shift = 'E'
        THEN
      --
            SELECT
                late_in,
                early_out,
                late_start_time,
                early_end_time,
                early_end_time + ( late_start_time - early_end_time ) / 2,
                total_working_hr
            INTO
                v_late_in,v_early_out,v_late_start_time,v_early_end_time,v_half_interval,v_total_working_min
            FROM
                (
                    SELECT
                        s.late_in,
                        s.early_out,
                        TO_DATE(
                            TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                             || ' '
                             || TO_CHAR(
                                s.start_time + ( (1 / 1440) * nvl(s.late_in,0) ),
                                'HH:MI AM'
                            ),
                            'DD-MON-YYYY HH:MI AM'
                        ) AS late_start_time,
                        TO_DATE(
                            TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                             || ' '
                             || TO_CHAR(
                                s.end_time - ( (1 / 1440) * nvl(s.early_out,0) ),
                                'HH:MI AM'
                            ),
                            'DD-MON-YYYY HH:MI AM'
                        ) AS early_end_time,
                        s.total_working_hr
                    FROM
                        hris_shifts s
                    WHERE
                        s.shift_id = v_shift_id
                );
      --

            IF
                v_attendance_time < v_half_interval
            THEN
                v_late_start_time := v_late_start_time - 1;
                v_attendance_dt := v_attendance_dt - 1;
                SELECT
                    overall_status,
                    late_status,
                    halfday_flag,
                    halfday_period,
                    grace_period,
                    in_time,
                    halfday_period,
                    grace_period
                INTO
                    v_overall_status,v_late_status,v_halfday_flag,v_halfday_period,v_grace_period,v_in_time,v_halfday_period,v_grace_period
                FROM
                    hris_attendance_detail
                WHERE
                        attendance_dt = trunc(v_attendance_dt)
                    AND
                        employee_id = p_employee_id;

            ELSE
                v_early_end_time := v_early_end_time + 1;
            END IF;
      --

        END IF;

    EXCEPTION
        WHEN no_data_found THEN
            dbms_output.put_line('Attendance Job for ' || v_attendance_dt || ' not excecuted');
            return;
    END;
  --

    BEGIN
        IF
            v_halfday_period IS NOT NULL
        THEN
            SELECT
                late_in,
                early_out,
                TO_DATE(
                    TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                     || ' '
                     || TO_CHAR(late_start_time,'HH:MI AM'),
                    'DD-MON-YYYY HH:MI AM'
                ),
                TO_DATE(
                    TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                     || ' '
                     || TO_CHAR(early_end_time,'HH:MI AM'),
                    'DD-MON-YYYY HH:MI AM'
                ),
                total_working_hr
            INTO
                v_late_in,v_early_out,v_late_start_time,v_early_end_time,v_total_working_min
            FROM
                (
                    SELECT
                        s.late_in,
                        s.early_out,
                        (
                            CASE
                                WHEN v_halfday_period = 'F'  THEN s.half_day_in_time
                                ELSE s.start_time
                            END
                        ) + ( ( 1 / 1440 ) * nvl(s.late_in,0) ) AS late_start_time,
                        (
                            CASE
                                WHEN v_halfday_period = 'F'  THEN s.end_time
                                ELSE s.half_day_out_time
                            END
                        ) - ( ( 1 / 1440 ) * nvl(s.early_out,0) ) AS early_end_time,
                        s.total_working_hr
                    FROM
                        hris_shifts s
                    WHERE
                        s.shift_id = v_shift_id
                );

        ELSIF
            v_grace_period IS NOT NULL
        THEN
            SELECT
                late_in,
                early_out,
                TO_DATE(
                    TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                     || ' '
                     || TO_CHAR(late_start_time,'HH:MI AM'),
                    'DD-MON-YYYY HH:MI AM'
                ),
                TO_DATE(
                    TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                     || ' '
                     || TO_CHAR(early_end_time,'HH:MI AM'),
                    'DD-MON-YYYY HH:MI AM'
                ),
                total_working_hr
            INTO
                v_late_in,v_early_out,v_late_start_time,v_early_end_time,v_total_working_min
            FROM
                (
                    SELECT
                        s.late_in,
                        s.early_out,
                        (
                            CASE
                                WHEN v_grace_period = 'E'  THEN s.grace_start_time
                                ELSE s.start_time
                            END
                        ) + ( ( 1 / 1440 ) * nvl(s.late_in,0) ) AS late_start_time,
                        (
                            CASE
                                WHEN v_grace_period = 'E'  THEN s.end_time
                                ELSE s.grace_end_time
                            END
                        ) - ( ( 1 / 1440 ) * nvl(s.early_out,0) ) AS early_end_time,
                        s.total_working_hr
                    FROM
                        hris_shifts s
                    WHERE
                        s.shift_id = v_shift_id
                );

        END IF;
    EXCEPTION
        WHEN no_data_found THEN
            raise_application_error(
                -20344,
                'SHIFT WITH SHIFT_ID => ' || v_shift_id || ' NOT FOUND.'
            );
    END;
  --   CHECK FOR ADJUSTED SHIFT

    BEGIN
        SELECT
            TO_DATE(
                TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                 || ' '
                 || TO_CHAR(late_start_time,'HH:MI AM'),
                'DD-MON-YYYY HH:MI AM'
            ),
            TO_DATE(
                TO_CHAR(v_attendance_dt,'DD-MON-YYYY')
                 || ' '
                 || TO_CHAR(early_end_time,'HH:MI AM'),
                'DD-MON-YYYY HH:MI AM'
            )
        INTO
            v_late_start_time,v_early_end_time
        FROM
            (
                SELECT
                    sa.start_time + ( ( 1 / 1440 ) * nvl(v_late_in,0) ) AS late_start_time,
                    sa.end_time - ( ( 1 / 1440 ) * nvl(v_early_out,0) ) AS early_end_time
                FROM
                    hris_shift_adjustment sa
                    JOIN hris_employee_shift_adjustment esa ON (
                        sa.adjustment_id = esa.adjustment_id
                    )
                WHERE
                    (
                        trunc(v_attendance_dt) BETWEEN trunc(sa.adjustment_start_date) AND trunc(sa.adjustment_end_date)
                    ) AND
                        esa.employee_id = p_employee_id
            );

    EXCEPTION
        WHEN no_data_found THEN
            dbms_output.put_line('NO ADJUSTMENT FOUND FOR EMPLOYEE =>'
             || p_employee_id
             || 'ON THE DATE'
             || v_attendance_dt);
    END;
  --      END FOR CHECK FOR ADJUSTED_SHIFT

    IF
        (
            v_in_time IS NULL
        )
    THEN
        IF
            ( v_overall_status = 'DO' )
        THEN
            v_overall_status := 'WD';
        ELSIF ( v_overall_status = 'HD' ) THEN
            v_overall_status := 'WH';
        ELSIF ( v_overall_status = 'LV' ) THEN
            IF
                ( v_halfday_flag != 'Y' AND
                    v_halfday_period IS NOT NULL
                ) OR
                    v_grace_period IS NOT NULL
            THEN
                v_overall_status := 'LP';
            END IF;
        ELSIF ( v_overall_status = 'TV' ) THEN
            NULL;
        ELSIF ( v_overall_status = 'TN' ) THEN
            NULL;
        ELSE
            v_overall_status := 'PR';
        END IF;

        IF
            ( v_attendance_dt < trunc(SYSDATE) )
        THEN
            v_late_status := 'X';
        END IF;
        IF
            v_overall_status = 'PR' AND v_late_start_time < v_attendance_time
        THEN
            IF
                ( v_attendance_dt < trunc(SYSDATE) )
            THEN
                v_late_status := 'Y';
            ELSE
                v_late_status := 'L';
            END IF;
        END IF;
    --

        UPDATE hris_attendance_detail
            SET
                in_time = TO_DATE(
                    TO_CHAR(v_attendance_time,'DD-MON-YY HH:MI AM'),
                    'DD-MON-YY HH:MI AM'
                ),
                overall_status = v_overall_status,
                late_status = v_late_status,
                in_remarks = p_remarks
        WHERE
                attendance_dt = v_attendance_dt
            AND
                employee_id = p_employee_id;

        return;
    END IF;
  --

    IF
        v_overall_status = 'PR' AND v_early_end_time > v_attendance_time
    THEN
        IF
            (
                v_late_status IN (
                    'L','Y'
                )
            )
        THEN
            v_late_status := 'B';
        ELSE
            v_late_status := 'E';
        END IF;
    ELSE
        IF
            ( v_late_status = 'B' )
        THEN
            v_late_status := 'L';
        ELSIF ( v_late_status = 'E' ) THEN
            v_late_status := 'N';
        ELSIF ( v_late_status = 'X' ) THEN
            v_late_status := 'N';
        ELSIF ( v_late_status = 'Y' ) THEN
            v_late_status := 'L';
        END IF;
    END IF;
  --

    SELECT
        SUM(abs(EXTRACT(HOUR FROM diff) ) * 60 + abs(EXTRACT(MINUTE FROM diff) ) )
    INTO
        v_total_hour
    FROM
        (
            SELECT
                ( v_attendance_time - v_in_time ) AS diff
            FROM
                dual
        );

    IF
        ( v_total_hour <= 5 )
    THEN
        return;
    END IF;
    UPDATE hris_attendance_detail
        SET
            out_time = v_attendance_time,
            late_status = v_late_status,
            out_remarks = p_remarks,
            total_hour = v_total_hour,
            ot_minutes = ( v_total_hour - v_total_working_min )
    WHERE
            attendance_dt = v_attendance_dt
        AND
            employee_id = p_employee_id;

END;


