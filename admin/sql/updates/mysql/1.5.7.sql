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


--------------------------------------------------------------------------------------------
ALTER TABLE `#__customtables_tables` CHANGE `tablecategory` `tablecategory` int(11) NULL DEFAULT NULL;
ALTER TABLE `#__customtables_tables` CHANGE `checked_out` `checked_out` int(11) UNSIGNED DEFAULT 0;
ALTER TABLE `#__customtables_tables` CHANGE `ordering` `ordering` int(11) DEFAULT 0;

ALTER TABLE `#__customtables_tables` CHANGE `asset_id` `asset_id` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `#__customtables_tables` CHANGE `created_by` `created_by` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `#__customtables_tables` CHANGE `modified_by` `modified_by` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `#__customtables_tables` CHANGE `version` `version` int(10) UNSIGNED DEFAULT 1;
ALTER TABLE `#__customtables_tables` CHANGE `hits` `hits` int(10) UNSIGNED DEFAULT 0;

ALTER TABLE `#__customtables_tables` CHANGE `published` `published` tinyint(3) DEFAULT 1;

ALTER TABLE `#__customtables_tables` CHANGE `allowimportcontent` `allowimportcontent` tinyint(1) DEFAULT 0;

ALTER TABLE `#__customtables_tables` CHANGE `params` `params` test utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `#__customtables_tables` CHANGE `created` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__customtables_tables` CHANGE `modified` `modified` datetime NULL DEFAULT NULL;
                                                                


--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `tablecategory` int(11) NULL DEFAULT NULL;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `checked_out` int(11) UNSIGNED DEFAULT 0;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `ordering` int(11) DEFAULT 0;

--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `asset_id` int(10) UNSIGNED DEFAULT 0;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `created_by` int(10) UNSIGNED DEFAULT 0;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `modified_by`  int(10) UNSIGNED DEFAULT 0;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `version` int(10) UNSIGNED DEFAULT 1;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `hits` int(10) UNSIGNED DEFAULT 0;

--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `published` tinyint(3) DEFAULT 1;

--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `allowimportcontent` tinyint(1) DEFAULT 0;

--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `created` datetime NULL DEFAULT NULL;
--ALTER TABLE `epYmtLZXjos_customtables_tables` ADD `modified` datetime NULL DEFAULT NULL;