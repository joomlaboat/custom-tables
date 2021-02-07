
CREATE SEQUENCE IF NOT EXISTS #__customtables_options_seq;
CREATE SEQUENCE IF NOT EXISTS #__customtables_categories_seq;
CREATE SEQUENCE IF NOT EXISTS #__customtables_tables_seq;
CREATE SEQUENCE IF NOT EXISTS #__customtables_fields_seq;
CREATE SEQUENCE IF NOT EXISTS #__customtables_layouts_seq;
CREATE SEQUENCE IF NOT EXISTS #__customtables_logs_seq;


ALTER SEQUENCE #__customtables_options_seq RESTART WITH 1;
ALTER SEQUENCE #__customtables_categories_seq RESTART WITH 1;
ALTER SEQUENCE #__customtables_tables_seq RESTART WITH 1;
ALTER SEQUENCE #__customtables_fields_seq RESTART WITH 1;
ALTER SEQUENCE #__customtables_layouts_seq RESTART WITH 1;
ALTER SEQUENCE #__customtables_logs_seq RESTART WITH 1;


CREATE TABLE IF NOT EXISTS #__customtables_categories (
	id INT NOT NULL DEFAULT NEXTVAL ('#__customtables_categories_seq'),
	asset_id INT NOT NULL DEFAULT 0 ,
	categoryname VARCHAR(255) NOT NULL DEFAULT '',
	params text NOT NULL DEFAULT '',
	published SMALLINT NOT NULL DEFAULT 1,
	created_by INT NOT NULL DEFAULT 0,
	modified_by INT NOT NULL DEFAULT 0,
	created TIMESTAMP(0) NULL DEFAULT NULL,
	modified TIMESTAMP(0) NULL DEFAULT NULL,
	checked_out int NOT NULL DEFAULT 0,
	checked_out_time TIMESTAMP(0) NULL DEFAULT NULL,
	version INT NOT NULL DEFAULT 1,
	hits INT NOT NULL DEFAULT 0,
	ordering INT NOT NULL DEFAULT 0,
	PRIMARY KEY  (id)
);

CREATE TABLE IF NOT EXISTS #__customtables_options (
  id int NOT NULL default nextval ('#__customtables_options_seq'),
  optionname varchar(50) NOT NULL,
  published smallint NOT NULL default '1', 
  title varchar(100) NOT NULL,
  image bigint NOT NULL,
  imageparams varchar(100) NOT NULL,
  ordering int NOT NULL,
  parentid int NOT NULL,
  sublevel int NOT NULL,
  isselectable smallint NOT NULL default '1',
  optionalcode text NOT NULL,
  link text NOT NULL,
  familytree varchar(255) NOT NULL,
  familytreestr varchar(255) NOT NULL,

  PRIMARY KEY  (id)
);


CREATE TABLE IF NOT EXISTS #__customtables_tables (
	id INT NOT NULL DEFAULT NEXTVAL ('#__customtables_tables_seq'),
	asset_id INT NOT NULL DEFAULT 0 ,
	customphp VARCHAR(1024) NOT NULL DEFAULT '',
	description TEXT NOT NULL,
	tablecategory INT NULL DEFAULT 0,
	tablename VARCHAR(255) NOT NULL DEFAULT '',
	customtablename VARCHAR(255) NOT NULL DEFAULT '',
	tabletitle VARCHAR(255) NOT NULL DEFAULT '',
	params text NOT NULL DEFAULT '',
	published SMALLINT NOT NULL DEFAULT 1,
	created_by INT NOT NULL DEFAULT 0,
	modified_by INT NOT NULL DEFAULT 0,
	created TIMESTAMP(0) NULL DEFAULT NULL,
	modified TIMESTAMP(0) NULL DEFAULT NULL,
	checked_out int NOT NULL DEFAULT 0,
	checked_out_time TIMESTAMP(0) NULL DEFAULT NULL,
	version INT NOT NULL DEFAULT 1,
	hits INT NOT NULL DEFAULT 0,
	ordering INT NOT NULL DEFAULT 0,
	allowimportcontent smallint NOT NULL default '1',

	PRIMARY KEY  (id)
)  ;

CREATE TABLE IF NOT EXISTS #__customtables_fields (
	id INT check (id > 0) NOT NULL DEFAULT NEXTVAL ('#__customtables_fields_seq'),
	tableid INT check (tableid > 0) NULL DEFAULT NULL,                         
	asset_id INT NOT NULL DEFAULT 0 ,
	alias VARCHAR(50) NOT NULL DEFAULT '',
	allowordering SMALLINT check (allowordering > 0) NOT NULL DEFAULT 0,
	defaultvalue VARCHAR(1024) NOT NULL DEFAULT '',
	fieldname VARCHAR(100) NOT NULL DEFAULT '',
	customfieldname VARCHAR(100) NOT NULL DEFAULT '',
	fieldtitle VARCHAR(1024) NOT NULL DEFAULT '',
	description TEXT NULL DEFAULT NULL,
	isrequired smallint NOT NULL default '1',
	isdisabled smallint NOT NULL default '0',
  	savevalue smallint NOT NULL default '1' ,
  	alwaysupdatevalue smallint NOT NULL default '0' ,
  
	type VARCHAR(50) NULL DEFAULT NULL,
	typeparams VARCHAR(1024) NULL DEFAULT NULL,
	valuerule VARCHAR(1024) NULL DEFAULT NULL,
	valuerulecaption VARCHAR(1024) NULL DEFAULT NULL,
	params text NULL DEFAULT NULL,
	published SMALLINT NOT NULL DEFAULT 1,
	parentid int NOT NULL DEFAULT 0,
	created_by INT NULL DEFAULT NULL,
	modified_by INT NULL DEFAULT NULL,
	created TIMESTAMP(0) NULL DEFAULT NULL,
	modified TIMESTAMP(0) NULL DEFAULT NULL,
	checked_out int NULL DEFAULT NULL,
	checked_out_time TIMESTAMP(0)  NULL DEFAULT NULL,
	version INT NOT NULL DEFAULT 1,
	hits INT NOT NULL DEFAULT 0,
	ordering INT NOT NULL DEFAULT 0,
	PRIMARY KEY  (id)
);

CREATE TABLE IF NOT EXISTS #__customtables_layouts (
	id INT NOT NULL DEFAULT NEXTVAL ('#__customtables_layouts_seq'),
	asset_id INT NOT NULL DEFAULT 0 ,
	changetimestamp TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	layoutcode TEXT NOT NULL,
	layoutname VARCHAR(255) NOT NULL DEFAULT '',
	layouttype INT NOT NULL DEFAULT 0,
	tableid INT NULL DEFAULT NULL,
	params text NULL DEFAULT NULL,
	published SMALLINT NOT NULL DEFAULT 1,
	created_by INT NULL DEFAULT NULL,
	modified_by INT NULL DEFAULT NULL,
	created TIMESTAMP(0) NULL DEFAULT NULL,
	modified TIMESTAMP(0) NULL DEFAULT NULL,
	checked_out int NULL DEFAULT NULL,
	checked_out_time TIMESTAMP(0) NULL DEFAULT NULL,
	version INT NOT NULL DEFAULT 1,
	hits INT NOT NULL DEFAULT 0,
	ordering INT NOT NULL DEFAULT 0,
	PRIMARY KEY  (id)
);

CREATE TABLE IF NOT EXISTS #__customtables_log (
  id int CHECK (id > 0) NOT NULL DEFAULT NEXTVAL ('#__customtables_log_seq'),
  userid int CHECK (userid > 0) NOT NULL,
  datetime timestamp(0) NOT NULL,
  tableid int CHECK (tableid > 0) NOT NULL,
  action smallint CHECK (action > 0) NOT NULL,
  listingid int CHECK (listingid > 0) NOT NULL,
  Itemid int NOT NULL
) ;


CREATE INDEX IF NOT EXISTS idx_ct_layouts_state ON #__customtables_categories (published);
CREATE INDEX IF NOT EXISTS idx_ct_layouts_categoryname ON #__customtables_categories (categoryname);
CREATE INDEX IF NOT EXISTS idx_ct_tables_tablename ON #__customtables_tables USING btree (tablename);
CREATE INDEX IF NOT EXISTS idx_ct_fields_tableid ON #__customtables_fields (tableid);
CREATE INDEX IF NOT EXISTS idx_ct_fields_state ON #__customtables_fields (published);
CREATE INDEX IF NOT EXISTS idx_ct_fields_alias ON #__customtables_fields (alias);


CREATE INDEX IF NOT EXISTS idx_ct_layouts_tableid ON #__customtables_layouts (tableid);
CREATE INDEX IF NOT EXISTS idx_ct_layouts_state ON #__customtables_layouts (published);
CREATE INDEX IF NOT EXISTS idx_ct_layouts_layoutname ON #__customtables_layouts (layoutname);

