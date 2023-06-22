create table HRIS_ELIGIBLE_SUN_DO
(
id number(7) primary key,
company_id number(7),
apply_to_all_branch char(1) not null,
branch_id number(7),
status char(1) not null,
created_by number(7),
created_dt date,
modified_by number(7),
modified_dt date,
deleted_by number(7),
deleted_dt date
);

alter table hris_eligible_sun_do add
(DAY_NAME varchar2(20),
NEXT_DAY_INTERVAL number(2));

ALTER TABLE hris_eligible_sun_do
ADD FOREIGN KEY (company_id) REFERENCES hris_company(company_id);

ALTER TABLE hris_eligible_sun_do
ADD FOREIGN KEY (branch_id) REFERENCES hris_branches(branch_id); 