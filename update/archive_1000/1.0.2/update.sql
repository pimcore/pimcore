

alter table users add column firstname varchar(255);
alter table users add column lastname varchar(255);
alter table users add column email varchar(255);

ALTER TABLE `translations` ADD COLUMN `date` bigint(20) NULL DEFAULT NULL;