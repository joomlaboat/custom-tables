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
  `optionalcode` text NOT NULL,
  `link` text NOT NULL,
  `familytree` varchar(255) NOT NULL,
  `familytreestr` varchar(255) NOT NULL,

  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `#__customtables_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`asset_id` INT(10) unsigned NOT NULL DEFAULT 0 COMMENT '',
	`categoryname` VARCHAR(255) NOT NULL DEFAULT '',
	`params` text NOT NULL DEFAULT '',
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
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`asset_id` INT(10) unsigned NOT NULL DEFAULT 0 COMMENT '',
	`customphp` VARCHAR(1024) NOT NULL DEFAULT '',
	`description` TEXT NOT NULL,
	`tablecategory` INT(11) NULL DEFAULT 0,
	`tablename` VARCHAR(255) NOT NULL DEFAULT '',
	`tabletitle` VARCHAR(255) NOT NULL DEFAULT '',
	`params` text NOT NULL DEFAULT '',
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
	`allowimportcontent` tinyint(1) NOT NULL default '1',

	PRIMARY KEY  (`id`),
	KEY `idx_checkout` (`checked_out`),
	KEY `idx_createdby` (`created_by`),
	KEY `idx_modifiedby` (`modified_by`),
	KEY `idx_state` (`published`),
	KEY `idx_tabletitle` (`tabletitle`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__customtables_layouts` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`asset_id` INT(10) unsigned NOT NULL DEFAULT 0 COMMENT '',
	`changetimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`layoutcode` MEDIUMTEXT NOT NULL,
	`layoutname` VARCHAR(255) NOT NULL DEFAULT '',
	`layouttype` INT(7) NOT NULL DEFAULT 0,
	`tableid` INT(10) NULL DEFAULT NULL,
	`params` text NULL DEFAULT NULL,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`created_by` INT(10) unsigned NULL DEFAULT NULL,
	`modified_by` INT(10) unsigned NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int(11) unsigned NULL DEFAULT NULL,
	`checked_out_time` DATETIME NULL DEFAULT NULL,
	`version` INT(10) unsigned NOT NULL DEFAULT 1,
	`hits` INT(10) unsigned NOT NULL DEFAULT 0,
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
	`id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
	`tableid` INT(10) unsigned NULL DEFAULT NULL,                         
	`asset_id` INT(10) unsigned NOT NULL DEFAULT 0 COMMENT '',
	`alias` VARCHAR(50) NOT NULL DEFAULT '',
	`allowordering` TINYINT(1) unsigned NOT NULL DEFAULT 0,
	`defaultvalue` VARCHAR(1024) NOT NULL DEFAULT '',
	`fieldname` VARCHAR(100) NOT NULL DEFAULT '',
	`fieldtitle` VARCHAR(1024) NOT NULL DEFAULT '',
	`description` TEXT NULL DEFAULT NULL,
	`isrequired` tinyint(1) unsigned NOT NULL default '1',
  `isdisabled` tinyint(1) unsigned NOT NULL default '0',
  `savevalue` tinyint(1) unsigned NOT NULL default '1' COMMENT 'If set to 0 then the value will be recalculated (updated) on every view and mysql field will not be created.',
  `alwaysupdatevalue` tinyint(1) unsigned NOT NULL default '0' COMMENT 'Update default value every time record is edited.',
  
	`type` VARCHAR(50) NULL DEFAULT NULL,
	`typeparams` VARCHAR(1024) NULL DEFAULT NULL,
	`valuerule` VARCHAR(1024) NULL DEFAULT NULL,
	`params` text NULL DEFAULT NULL,
	`published` TINYINT(3) NOT NULL DEFAULT 1,
	`parentid` int(10) unsigned NOT NULL DEFAULT 0,
	`created_by` INT(10) unsigned NULL DEFAULT NULL,
	`modified_by` INT(10) unsigned NULL DEFAULT NULL,
	`created` DATETIME NULL DEFAULT NULL,
	`modified` DATETIME NULL DEFAULT NULL,
	`checked_out` int(11) unsigned NULL DEFAULT NULL,
	`checked_out_time` DATETIME  NULL DEFAULT NULL,
	`version` INT(10) unsigned NOT NULL DEFAULT 1,
	`hits` INT(10) unsigned NOT NULL DEFAULT 0,
	`ordering` INT(11) unsigned NOT NULL DEFAULT 0,
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
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL,
  `tableid` int(10) UNSIGNED NOT NULL,
  `action` smallint(6) UNSIGNED NOT NULL,
  `listingid` int(10) UNSIGNED NOT NULL,
  `Itemid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




--
-- Always insure this column rules is large enough for all the access control values.
--
ALTER TABLE `#__assets` CHANGE `rules` `rules` MEDIUMTEXT NOT NULL COMMENT 'JSON encoded access control.';

--
-- Always insure this column name is large enough for long component and view names.
--
ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';
