CREATE TABLE IF NOT EXISTS `#__customtables_options` (
  `id` int(10) NOT NULL auto_increment,
  `optionname` varchar(50) NOT NULL,
  `published` tinyint(1) NOT NULL default '1', 
  `title` varchar(100) NOT NULL,
  `image` bigint(20) NOT NULL,
  `imageparams` varchar(100) NOT NULL,
  `ordering` int(10) NOT NULL,
  `parentid` int(10) NOT NULL,
  `sublevel` int(10) NOT NULL,
  `isselectable` tinyint(1) NOT NULL default '1',
  `optionalcode` text NULL,
  `link` text NULL,
  `familytree` varchar(255) NOT NULL,
  `familytreestr` varchar(255) NOT NULL,

  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__customtables_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`asset_id` INT(10) unsigned NOT NULL DEFAULT 0 COMMENT '',
	`categoryname` VARCHAR(255) NOT NULL DEFAULT '',
	`params` text NULL,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`created_by` INT(10) unsigned NOT NULL DEFAULT 0,
	`modified_by` INT(10) unsigned NOT NULL DEFAULT 0,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int(11) unsigned NOT NULL DEFAULT 0,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`version` INT(10) unsigned NOT NULL DEFAULT 1,
	`hits` INT(10) unsigned NOT NULL DEFAULT 0,
	`ordering` INT(11) NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_categoryname` (`categoryname`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__customtables_tables` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
	`customphp` VARCHAR(1024) NULL DEFAULT NULL,
	`description` TEXT NULL,
	`tablecategory` INT NULL DEFAULT NULL,
	`tablename` VARCHAR(255) NOT NULL DEFAULT 'customidfield',
	`customtablename` VARCHAR(255) NULL DEFAULT NULL,
	`customidfield` VARCHAR(255) NULL DEFAULT NULL,
	`tabletitle` VARCHAR(255) NULL DEFAULT NULL,
	`params` text NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	`created_by` INT UNSIGNED NOT NULL DEFAULT 0,
	`modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int UNSIGNED NOT NULL DEFAULT 0,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`version` INT UNSIGNED NOT NULL DEFAULT 1,
	`hits` INT UNSIGNED NOT NULL DEFAULT 0,
	`ordering` INT NOT NULL DEFAULT 0,
	`allowimportcontent` TINYINT NOT NULL DEFAULT 0,

	PRIMARY KEY  (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_tabletitle` (`tabletitle`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__customtables_layouts` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
	`changetimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`layoutcode` MEDIUMTEXT NULL,
	`layoutname` VARCHAR(255) NOT NULL DEFAULT '',
	`layouttype` INT(7) NOT NULL DEFAULT 0,
	`tableid` INT(10) NULL DEFAULT NULL,
	`params` text NULL,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`modified_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int(11) UNSIGNED NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`version` INT(10) UNSIGNED NOT NULL DEFAULT 1,
	`hits` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`ordering` INT(11) NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `idx_tableid` (`tableid`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_layoutname` (`layoutname`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__customtables_fields` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tableid` INT(10) UNSIGNED NULL DEFAULT NULL,                         
	`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
	`alias` VARCHAR(50) NOT NULL DEFAULT '',
	`allowordering` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`defaultvalue` VARCHAR(1024) NOT NULL DEFAULT '',
	`fieldname` VARCHAR(100) NOT NULL DEFAULT '',
	`customfieldname` VARCHAR(100) NOT NULL DEFAULT '',
	`fieldtitle` VARCHAR(1024) NOT NULL DEFAULT '',
	`description` TEXT NULL,
	`isrequired` tinyint(1) UNSIGNED NOT NULL default '1',
  `isdisabled` tinyint(1) UNSIGNED NOT NULL default '0',
  `savevalue` tinyint(1) UNSIGNED NOT NULL default '1' COMMENT 'If set to 0 then the value will be recalculated (updated) on every view and mysql field will not be created.',
  `alwaysupdatevalue` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT 'Update default value every time record is edited.',
  
	`type` VARCHAR(50) NULL DEFAULT NULL,
	`typeparams` VARCHAR(1024) NULL DEFAULT NULL,
	`valuerule` VARCHAR(1024) NULL DEFAULT NULL,
	`valuerulecaption` VARCHAR(1024) NULL DEFAULT NULL,
	`params` text NULL,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`parentid` int(10) UNSIGNED NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`modified_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int(11) UNSIGNED NULL DEFAULT NULL,
	`checked_out_time` DATETIME  NULL DEFAULT NULL,
	`version` INT(10) UNSIGNED NOT NULL DEFAULT 1,
	`hits` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`ordering` INT(11) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `idx_tableid` (`tableid`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_fieldtitle` (`fieldtitle`),
	KEY `idx_alias` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `#__customtables_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL,
  `tableid` int(10) UNSIGNED NOT NULL,
  `action` smallint(6) UNSIGNED NOT NULL,
  `listingid` int(10) UNSIGNED NOT NULL,
  `Itemid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




--
-- Always insure this column rules is large enough for all the access control values.
--
ALTER TABLE `#__assets` CHANGE `rules` `rules` MEDIUMTEXT NULL COMMENT 'JSON encoded access control.';

--
-- Always insure this column name is large enough for long component and view names.
--
ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';
