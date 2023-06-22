CREATE TABLE HRIS_SETTINGS 
(ID NUMBER(7) PRIMARY KEY,
NAME VARCHAR(100),
VALUE VARCHAR2(100),
COMPANY_ID NUMBER(7),
BRANCH_ID NUMBER(7),
STATUS CHAR(1),
CREATED_BY NUMBER(7),
CREATED_DT DATE,
MODIFIED_BY NUMBER(7),
MODIFIED_DT DATE);  

insert into hris_settings (id, name, value, status, created_dt, created_by) values
((select nvl(max(id),0) +1 from hris_settings), 
'reatt.customizedDayOff','0','E',trunc(sysdate),0);

insert into hris_settings (id, name, value, status, created_dt, created_by) values
((select nvl(max(id),0) +1 from hris_settings), 
'reatt.dayOffTravelAutoSubstituteLeaveAddition','0','E',trunc(sysdate),0);

insert into hris_settings (id, name, value, status, created_dt, created_by) values
((select nvl(max(id),0) +1 from hris_settings), 
'reatt.dayOffTrainingAutoSubstituteLeaveAddition','0','E',trunc(sysdate),0);
