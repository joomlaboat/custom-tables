update `#__customtables_fields` set created="2001-01-01 01:01:01" where created<"2001-01-01 01:01:01";
update `#__customtables_fields` set modified="2001-01-01 01:01:01" where modified<"2001-01-01 01:01:01";
update `#__customtables_fields` set checked_out_time="2001-01-01 01:01:01" where checked_out_time<"2001-01-01 01:01:01";

ALTER TABLE `#__customtables_fields` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;

update `#__customtables_fields` set created=NULL where created="2001-01-01 01:01:01";
update `#__customtables_fields` set modified=NULL where modified="2001-01-01 01:01:01";
update `#__customtables_fields` set checked_out_time=NULL where checked_out_time="2001-01-01 01:01:01";

ALTER TABLE `#__customtables_fields` CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `created_by` `created_by` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `modified_by` `modified_by` INT(10) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__customtables_fields` CHANGE `typeparams` `typeparams` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `#__customtables_fields` CHANGE `defaultvalue` `defaultvalue` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `#__customtables_fields` CHANGE `fieldtitle` `fieldtitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `#__customtables_fields` CHANGE `valuerule` `valuerule` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;


SET @tablename=(SELECT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name LIKE "%customtables_fields" AND table_schema = DATABASE() LIMIT 1);

SET @f="savevalue";
SET @w=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name LIKE "%customtables_fields" AND table_schema = DATABASE() AND column_name = @f);
SET @a=CONCAT('ALTER TABLE `',@tablename,'` ADD `',@f,'` TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"');
SET @preparedStatement = (SELECT IF(@w > 0,'SELECT "Field exists"',@a));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

update `#__customtables_fields` set savevalue=1;

SET @f="alwaysupdatevalue";
SET @w=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name LIKE "%customtables_fields" AND table_schema = DATABASE() AND column_name = @f);
SET @a=CONCAT('ALTER TABLE `',@tablename,'` ADD `',@f,'` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"');
SET @preparedStatement = (SELECT IF(@w > 0,'SELECT "Field exists"',@a));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
