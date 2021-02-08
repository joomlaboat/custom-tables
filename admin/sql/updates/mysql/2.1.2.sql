ALTER TABLE `#__customtables_tables` ADD COLUMN `customtablename` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `#__customtables_fields` ADD COLUMN `customfieldname` VARCHAR(100) NOT NULL DEFAULT '';

ALTER TABLE `#__customtables_log` CHANGE COLUMN `user` `userid` int(10) UNSIGNED NOT NULL;

