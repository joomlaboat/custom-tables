<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage libraries/fields.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'imagemethods.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filemethods.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');


class ESFields
{

    public static function getFieldID($tableid, $esfieldname)
	{
		$db = JFactory::getDBO();
		$query= 'SELECT id FROM #__customtables_fields WHERE published=1 AND tableid='.(int)$tableid.' AND fieldname="'.$esfieldname.'"';

		$db->setQuery( $query );

		$rows2 = $db->loadObjectList();
		if(count($rows2)==0)
			return 0;

		$row=$rows2[0];

		return $row->id;
	}


    public static function addLanguageField($tablename,$original_fieldname,$new_fieldname)
    {
        $fields=ESFields::getExistingFields($tablename,false);
        foreach($fields as $field)
        {
            if($field['Field']==$original_fieldname)
            {
                $AdditionOptions='';
                if($field['Null']!='NO')
                    $AdditionOptions='NULL';

                ESFields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['Type'], $AdditionOptions);
                return true;
            }
        }
        return false;
    }

    public static function addESField($establename,$esfieldname,$fieldtype,$PureFieldType,$fieldtitle)
	{
        if($PureFieldType=='')
            return '';
        
			$db = JFactory::getDBO();
			//Add Field
			//$establename - table name without prefix
            //
			//$coltype - customtables filed type
			//$esfieldname - field name withput prefix
			//$PureFieldType - MySQL field type
			$mysqltablename='#__customtables_table_'.$establename;
			$mysqlfieldname='es_'.$esfieldname;

			if(strpos($fieldtype,'multilang')===false)
			{

				$AdditionOptions='';


				$AdditionOptions	.=' COMMENT '.$db->Quote($fieldtitle);

				if($fieldtype!='dummy')
					ESFields::AddMySQLFieldNotExist($mysqltablename, $mysqlfieldname, $PureFieldType, $AdditionOptions);
			}
			else
			{
                $LangMisc	= new ESLanguages;
                $languages=$LangMisc->getLanguageList();

                $index=0;
				foreach($languages as $lang)
				{
                    if($index==0)
                        $postfix='';
                    else
                        $postfix='_'.$lang->sef;

					$AdditionOptions	=' COMMENT '.$db->Quote($fieldtitle);
					ESFields::AddMySQLFieldNotExist($mysqltablename, $mysqlfieldname.$postfix, $PureFieldType, $AdditionOptions);

                    $index++;
				}
			}


			if($fieldtype=='imagegallery')
			{
				//Create table
				ESFields::CreateImageGalleryTable($establename,$esfieldname);
			}

			if($fieldtype=='filebox')
			{
				//Create table
				ESFields::CreateFileBoxTable($esfieldname,$esfieldname);
			}



	}

	public static function deleteESField_byID($tableid,$fieldid)
	{
		$db = JFactory::getDBO();

		$ImageFolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';

		$fieldrow=ESFields::getFieldRow($fieldid);

    	if(!is_object($fieldrow) or count($fieldrow)==0)
			return false;


		$tableid_=$fieldrow->tableid;

		if($tableid!=$tableid_) //check just in case
			return false;

		$tablerow=ESTables::getTableRowByID($tableid);
		$establename=$tablerow->tablename;
		$mysqltablename='#__customtables_table_'.$establename;



				//for Image Gallery
				if($fieldrow->type=='imagegallery')
				{
					//Delete all photos belongs to the gallery


					$imagemethods=new CustomTablesImageMethods;
					$imagemethods->DeleteGalleryImages($establename, $tableid, $fieldrow->fieldname,$fieldrow->typeparams,true);


					//Delete gallery table
					$tName='#__customtables_gallery_'.$establename.'_'.$fieldrow->fieldname;
					$query ='DROP TABLE IF EXISTS '.$tName.'';
					$db->setQuery($query);
					if (!$db->query())    die( $db->stderr());

				}
				elseif($fieldrow->type=='filebox')
				{
					//Delete all files belongs to the filebox


					$imagemethods=new CustomTablesFileMethods;
					$imagemethods->DeleteFileBoxFiles($establename, $tableid, $fieldrow->fieldname,$fieldrow->typeparams,true);


					//Delete gallery table
					$tName='#__customtables_filebox_'.$establename.'_'.$fieldrow->fieldname;
					$query ='DROP TABLE IF EXISTS '.$tName.'';
					$db->setQuery($query);
					if (!$db->query())    die( $db->stderr());

				}
				elseif($fieldrow->type=='image')
				{

					if(ESFields::checkIfFieldExists($establename,$fieldrow->fieldname,true))
					{
						$imagemethods=new CustomTablesImageMethods;
						$p=str_replace('|compare','|delete:',$fieldrow->typeparams); //disable image comparision if set
						$imagemethods->DeleteCustomImages($establename,$fieldrow->fieldname, $ImageFolder,$p,true);
					}
				}

				elseif($fieldrow->type=='user' or $fieldrow->type=='userid' or $fieldrow->type=='sqljoin')
                {
            		ESFields::removeForeignKey($establename,$fieldrow->fieldname);//,'','#__users','id',$msg);
                }
                elseif($fieldrow->type=='file')
				{
					// delete all files
					//if(file_exists($filename))
					//unlink($filename);
				}


				$fieldnames=array();

				if(strpos($fieldrow->type,'multilang')===false)
					$fieldnames[]=$fieldrow->fieldname;
				else
				{
					$LangMisc	= new ESLanguages;
                    $languages=$LangMisc->getLanguageList();

                    $index=0;
                    foreach($languages as $lang)
                    {
                        if($index==0)
                            $postfix='';
                        else
                            $postfix='_'.$lang->sef;

						$fieldnames[]=$fieldrow->fieldname.$postfix;
                    }

				}

				$i=0;

				foreach($fieldnames as $esfieldname)
				{

					if($fieldrow->type!='dummy')
						ESFields::deleteMYSQLField($mysqltablename,'es_'.$esfieldname);
				}

                $query ='DELETE FROM #__customtables_fields WHERE published=1 AND id='.$fieldid;
		$db->setQuery($query);
		$db->execute();
                return true;
	}

    public static function getPureFieldType($fieldtype,$typeparams)
	{

		$t=trim($fieldtype);
		switch($t)
		{
			case 'phponadd':
				return 'varchar(255) NULL';
			case 'filelink':
				return 'varchar(255) NULL';
            case 'alias':
				return 'varchar(255) NULL';
            case 'color':
				return 'varchar(8) NULL';
			case 'string':
				$l=(int)$typeparams;

				if($l==0)
					$l=255;

				if($l<1)
					$l=1;

				if($l>1024)
					$l=1024;

				return 'varchar('.$l.') NULL';

			break;

			case 'multilangstring':

				$l=(int)$typeparams;

				if($l==0)
					$l=255;

				if($l<1)
					$l=1;

				if($l>1024)
					$l=1024;

				return 'varchar('.$l.') NULL';

			break;

			case 'text':
				return 'text NULL';
				break;
			case 'multilangtext':
				return 'text NULL';
				break;

			case 'int':
				return 'int(11) NULL';
				break;

			case 'float':
				$typeparams_arr=explode(',',$typeparams);
				if(count($typeparams_arr)==1)
					return 'decimal(20,'.(int)$typeparams_arr[0].') NULL';
				elseif(count($typeparams_arr)==2)
					return 'decimal('.(int)$typeparams_arr[1].','.(int)$typeparams_arr[0].') NULL';
				else
					return 'decimal(20,2) NULL';

				break;

			case 'customtables':
				$typeparams_arr=explode(',',$typeparams);

				if(count($typeparams_arr)<3)
					return 'varchar(255) NULL';

				$l=(int)$typeparams_arr[2];

				if($l==0)
					$l=255;

				if($l<64)
					$l=64;

				if($l>65535)
					$l=65535;

				return 'varchar('.$l.') NULL';

				break;

			case 'records':
				return 'varchar(255) NULL';
				break;

            //case 'post':
				//return 'text NULL';
				//break;

			case 'sqljoin':
				return 'int(10) NULL';
				break;

			case 'file':
				return 'varchar(255) NULL';
				break;

			case 'image':
				return 'bigint(20) NULL';
				break;

			case 'checkbox':
				return 'tinyint(1) NOT NULL DEFAULT 0';
				break;

			case 'radio':
				return 'varchar(255) NULL';
				break;

			case 'email':
				return 'varchar(255) NULL';
				break;

			case 'url':
				return 'varchar(1024) NULL';
				break;


			case 'date':
				return 'date NULL';
				break;
            
		        case 'time':
				return 'int(11) NULL';
				break;

			case 'creationtime':
				return 'datetime NULL';
				break;

			case 'changetime':
				return 'datetime NULL';
				break;

			case 'lastviewtime':
				return 'datetime NULL';
				break;

			case 'viewcount':
				return 'bigint(20) unsigned NULL';
				break;

			case 'userid': //current user id (auto asigned)
				return 'bigint(20) unsigned NULL';

			case 'user': //user (selection)
				return 'bigint(20) unsigned NULL';

			case 'usergroup': //user group (selection)
				return 'int(11) unsigned NULL';

			case 'usergroups': //user groups (selection)
				return 'varchar(255) NULL';

            case 'language':
				return 'varchar(5) NULL';
				break;

			case 'server':
				return 'varchar(255) NULL';
				break;

			case 'id':
				return 'bigint(20) NULL';
				break;

			case 'dummy':
				return '';
				break;

			case 'imagegallery':
				return 'bigint(20) unsigned NULL';
				break;

			case 'filebox':
				return 'bigint(20) unsigned NULL';
				break;

			case 'article':
				return 'bigint(20) unsigned NULL';
				break;

			case 'multilangarticle':
				return 'bigint(20) unsigned NULL';
				break;

			case 'md5':
				return 'char(32) NULL';
				break;

			case 'log':
				return 'text NOT NULL';
				break;

            case '_id':
				return 'int(10) UNSIGNED NOT NULL';// AUTO_INCREMENT';
				break;

            case '_published':
				return 'tinyint(1) NULL DEFAULT 1';
				break;
            
            case 'phponview':
                $typeparams_arr=explode(',',$typeparams);
                
                if(isset($typeparams_arr[1]) and $typeparams_arr[1]=='dynamic')
                    return ''; //do not store the field values
                else
                    return 'varchar(255) NULL';
                
                break;

			default:

				return 'varchar(255) NULL';
				break;
		}

	}


    public static function AddMySQLFieldNotExist($mysqltablename, $mysqlfieldname, $filedtype, $options)
    {
		$db = JFactory::getDBO();
		if(!ESFields::checkIfFieldExists($mysqltablename,$mysqlfieldname,false))
		{
			$query='ALTER TABLE '.$mysqltablename.' ADD COLUMN '.$mysqlfieldname.' '.$filedtype.' '.$options;

			$db->setQuery($query);
			if (!$db->query())    die('Cannot Add Column'. $db->stderr());
		}

    }


    public static function addForeignKey($establename,$esfieldname,$new_typeparams='',$join_with_table_name='',$join_with_table_field='id',&$msg)
	{
		$mysqltablename='#__customtables_table_'.$establename;
		$mysqlfieldname='es_'.$esfieldname;

		//Create Key only if possible
        $params=explode(',',$new_typeparams);

		if($join_with_table_name=='')
		{

			if($new_typeparams=='')
			{

				$msg='Parameters not set.';
				return false; //Exit if parameters not set
			}

			if(count($params)<2)
			{
				$msg='Parameters not complete.';
				return false;	// Exit if field not set (just in case)
			}

            $establename_joinwith=$params[0];
			$join_with_table_name='#__customtables_table_'.$establename_joinwith;
            $tableid=(int)ESTables::getTableID($establename_joinwith);

			if($tableid==0)
			{
				$msg='Join with table "'.$join_with_table_name.'" not found.';
				return false;	// Exit if table to connect with not found
			}
		}
        
        
        $db = JFactory::getDBO();

        $conf = JFactory::getConfig();
        $database = $conf->get('db');
        
        ESFields::removeForeignKey($establename,$esfieldname);
        
        if(isset($params[7]) and $params[7]=='noforignkey')
        {
            //ESFields::removeForeignKey($establename,$esfieldname);
        }
        else
        {
            ESFields::cleanTableBeforeNormalization($establename,$esfieldname,$join_with_table_name,$join_with_table_field);

            $query='ALTER TABLE '.$db->quoteName($mysqltablename).' ADD FOREIGN KEY ('.$mysqlfieldname.') REFERENCES '.$db->quoteName($database.'.'.$join_with_table_name).' ('.$join_with_table_field.') ON DELETE RESTRICT ON UPDATE RESTRICT;';

            $db->setQuery( $query );

            try
            {
                $db->setQuery($query);
    
            	if (!$db->query())
            		$msg=$db->getErrorMsg();
            }
            catch (RuntimeException $e)
            {
            	$msg=$e->getMessage();
            }
            return false;
        }
	}


    public static function removeForeignKey($establename,$esfieldname)//,$new_typeparams='',$join_with_table_name='',$join_with_table_field='id',&$msg)
	{
        $mysqltablename='#__customtables_table_'.$establename;
		$mysqlfieldname='es_'.$esfieldname;


		$db = JFactory::getDBO();

		$conf = JFactory::getConfig();
		$database = $conf->get('db');

        //get constarnt name

        $query='show create table '.$mysqltablename;
        
        $db->setQuery( $query );
        if (!$db->query())    die( $db->stderr());
        $tablecreatequery = $db->loadAssocList();

        if(count($tablecreatequery)==0)
            return false;
    
        $rec=$tablecreatequery[0];


        $constrances=array();

        $q=$rec['Create Table'];
        $lines=explode(',',$q);

        
        foreach($lines as $line_)
        {
            $line=trim(str_replace('`','',$line_));
            if(strpos($line,'CONSTRAINT')!==false)
            {
                $pair=explode(' ',$line);
                if($pair[4]=='('.$mysqlfieldname.')')
                {
                    $constrances[]=$pair[1];
                }
            }
        }

        foreach($constrances as $constrance)
        {
            $query ='SET foreign_key_checks = 0;';
            $db->setQuery($query);
            if (!$db->query())    die( $db->stderr());

            $query='ALTER TABLE '.$mysqltablename.' DROP FOREIGN KEY '.$constrance;

            $db->setQuery( $query );

            try
            {
                $db->setQuery($query);

            	if (!$db->query())
            		$msg=$db->getErrorMsg();
            }
            catch (RuntimeException $e)
            {
            	$msg=$e->getMessage();
            }


            $query ='SET foreign_key_checks = 1;';
            $db->setQuery($query);
            if (!$db->query())    die( $db->stderr());
        }

		return false;
	}

    public static function addIndexIfNotExist($establename,$esfieldname)
	{

		$mysqltablename='#__customtables_table_'.$establename;
		$mysqlfieldname='es_'.$esfieldname;


		$db = JFactory::getDBO();
		$query='SHOW INDEX FROM '.$mysqltablename.' WHERE Key_name = "'.$mysqlfieldname.'"';
		$db->setQuery( $query );
		if (!$db->query())    die ( $db->stderr());

		$rows2 = $db->loadObjectList();


		if(count($rows2)==0)
		{
			$query='ALTER TABLE '.$mysqltablename.' ADD INDEX('.$mysqlfieldname.');';

			$db->setQuery( $query );
			if (!$db->query())    die ( $db->stderr());
		}


	}


    public static function CreateImageGalleryTable($establename,$esfieldname)
	{

		$tName='#__customtables_gallery_'.$establename.'_'.$esfieldname;
		$db = JFactory::getDBO();

		$query = 'CREATE TABLE IF NOT EXISTS '.$tName.' (
  photoid bigint(20) NOT NULL auto_increment,
  listingid bigint(20) NOT NULL,
  ordering int(10) NOT NULL,
  photo_ext varchar(10) NOT NULL,
  title varchar(100) NOT NULL,
   PRIMARY KEY  (photoid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		return true;
	}


    public static function CreateFileBoxTable($establename,$esfieldname)
	{
		$tName='#__customtables_filebox_'.$establename.'_'.$esfieldname;
		$db = JFactory::getDBO();

		$query = 'CREATE TABLE IF NOT EXISTS '.$tName.' (
  fileid bigint(20) NOT NULL auto_increment,
  listingid bigint(20) NOT NULL,
  ordering int(10) NOT NULL,
  file_ext varchar(10) NOT NULL,
  title varchar(100) NOT NULL,
   PRIMARY KEY  (fileid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		return true;
	}

    public static function ConvertFieldType($establename, $esfieldname,   $ex_type, $ex_typeparams,$ex_PureFieldType, $new_type, $new_typeparams, $PureFieldType, $fieldtitle)
	{
        if($new_type==$ex_type)
			return true; //no need to convert

		$mysqlfieldname='es_'.$esfieldname;

        $unconvertable_types=array('dummy','image','imagegallery','file','filebox','sqljoin','records','customtables','log');

        if(in_array($new_type,$unconvertable_types) or in_array($ex_type,$unconvertable_types))
            return false;


        			$PureFieldType_=$PureFieldType;
					//if($new_type=='checkbox')
						//$PureFieldType_	.=' NOT NULL DEFAULT "0"';

					//Check and fix record
					if($new_type=='customtables')
					{
						//get number of string like "varchar(255)"
						$maxlength=(int)preg_replace("/[^0-9]/", "", $PureFieldType);
						$typeparamsarr=explode(',',$new_typeparams);
						$optionname=$typeparamsarr[0];

						ESFields::FixCustomTablesRecords('#__customtables_table_'.$establename,$esfieldname,$optionname, $maxlength );

					}

					$db = JFactory::getDBO();

					$query = 'ALTER TABLE #__customtables_table_'.$establename.' CHANGE '.$mysqlfieldname.' '.$mysqlfieldname.' '.$PureFieldType_;

					//if($new_type=='sqljoin' or $new_type=='user' or $new_type=='userid')
						//	$AdditionOptions	.=' NULL DEFAULT NULL';

					$query .= ' COMMENT "'.$fieldtitle.'";';

					$db->setQuery( $query );
					if (!$db->query())
						die( $db->stderr());



		return true;
	}



	public static function FixCustomTablesRecords($mysqltablename, $esfieldname, $optionname, $maxlenght)
	{
		$mysqlfieldname='es_'.$esfieldname;

		$es=new CustomTablesMisc;
		$db = JFactory::getDBO();

		$fixcount=0;

		$fixquery= 'SELECT id, '.$mysqlfieldname.' AS fldvalue FROM '.$mysqltablename.'';


		$db->setQuery( $fixquery );

		$db->query();
		$fixrows = $db->loadObjectList();
		foreach($fixrows as $fixrow)
		{

			$newrow	=ESFields::FixCustomTablesRecord($fixrow->fldvalue, $optionname, $maxlenght);

			if($fixrow->fldvalue!=$newrow)
			{
				$fixcount++;

				$fixitquery= 'UPDATE '.$mysqltablename.' SET '.$mysqlfieldname.'="'.$newrow.'" WHERE id='.$fixrow->id;
				$db->setQuery( $fixitquery);
				$db->execute();

			}
		}


	}

    public static function FixCustomTablesRecord($record, $optionname, $maxlen)
	{
		$l=2;

		$e=explode(',',$record);
		$r=array();

		foreach($e as $a)
		{
			$p=explode('.',$a);
			$b=array();

			foreach($p as $t)
			{
				if($t!='')
					$b[]=$t;
			}
			if(count($b)>0)
			{
				$d=implode('.',$b);
				if($d!=$optionname)
					$e=implode('.',$b).'.';

				$l+=strlen($e)+1;
				if($l>=$maxlen)
					break;

				$r[]=$e;
			}
		}

		if(count($r)>0)
			$newrow=','.implode(',',$r).',';
		else
			$newrow='';

		return $newrow;
	}


	public static function cleanTableBeforeNormalization($establename,$esfieldname,$join_with_table_name,$join_with_table_field)
	{
		$mysqltablename='#__customtables_table_'.$establename;
		$mysqlfieldname='es_'.$esfieldname;

		$db = JFactory::getDBO();

		//Find broken records
		$query='SELECT DISTINCT a.'.$mysqlfieldname.' AS id FROM
   '.$mysqltablename.' a LEFT JOIN '.$join_with_table_name.' b ON a.'.$mysqlfieldname.'=b.'.$join_with_table_field.' WHERE b.'.$join_with_table_field.' IS NULL;';



		$db->setQuery( $query );

		if (!$db->query())
			die( $db->stderr());

		$rows = $db->loadObjectList();

		$ids=array();
		$ids[]=$mysqlfieldname.'=0';

		foreach($rows as $row)
		{
			if($row->id!='')
				$ids[]=$mysqlfieldname.'='.$row->id;
		}

		$query = 'UPDATE '.$mysqltablename.' SET '.$mysqlfieldname.'=NULL WHERE '.implode(' OR ',$ids).';';

		$db->setQuery( $query );
		$db->execute();
	}
        

    public static function isLanguageFieldName($fieldname)
    {
        $parts=explode('_',$fieldname);
        if($parts[0]=='es')
        {
            //custom field
            if(count($parts)==3)
                return true;
            else
                return false;
        }


        if(count($parts)==2)
            return true;
        else
            return false;

    }

    public static function getLanguagelessFieldName($fieldname)
    {
        $parts=explode('_',$fieldname);
        if($parts[0]=='es')
        {
            //custom field
            if(count($parts)==3)
                return $parts[0].'_'.$parts[1];
            else
                return '';
        }

        if(count($parts)==2)
            return $parts[0];
        else
            return '';
    }

    public static function getFieldType($tablename,$add_table_prefix=true,$fieldname)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$mysqltablename='#__customtables_table_'.$tablename;
		else
			$mysqltablename=$tablename;

		$query = 'SHOW COLUMNS FROM '.$mysqltablename.' WHERE '.$db->quoteName('field').'='.$db->quote($fieldname);

		$db->setQuery( $query );

        $recs=$db->loadAssocList();

        if(count($recs)==0)
            return '';

        $rec=$recs[0];

		return $rec['Type'];

	}

    public static function getExistingFields($tablename,$add_table_prefix=true)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$mysqltablename='#__customtables_table_'.$tablename;
		else
			$mysqltablename=$tablename;

		$query = 'SHOW COLUMNS FROM '.$mysqltablename;
		$db->setQuery( $query );
        if (!$db->query()) return false;

		return $db->loadAssocList();

	}

    public static function getListOfExistingFields($tablename,$add_table_prefix=true)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$mysqltablename='#__customtables_table_'.$tablename;
		else
			$mysqltablename=$tablename;

		$query = 'SHOW COLUMNS FROM '.$mysqltablename;
		$db->setQuery( $query );
        if (!$db->query()) return false;

        $list=array();
        $recs=$db->loadAssocList();
        foreach($recs as $rec)
        {
            $list[]=$rec['Field'];
        }
		return $list;

	}

	public static function checkIfFieldExists($tablename,$field,$add_table_prefix=true)
	{
		$fields=ESFields::getExistingFields($tablename,$add_table_prefix);
		return ESFields::checkIfFieldExists_inArray($fields,$field);
	}

	public static function checkIfFieldExists_inArray(&$existing_fields,$proj_field)
	{

	    foreach($existing_fields as $existing_field)
	    {
	        if($proj_field==$existing_field['Field'])
	        {
	            return true;
	            break;
	        }
	    }

	    return false;
	}


	public static function deleteMYSQLField($mysqltablename,$fieldname)
	{
		if(ESFields::checkIfFieldExists($mysqltablename,$fieldname,false))
		{

			$db = JFactory::getDBO();

            $query ='SET foreign_key_checks = 0;';
			$db->setQuery($query);
			if (!$db->query())    die( $db->stderr());

			$query='ALTER TABLE '.$mysqltablename.' DROP '.$fieldname;

			$db->setQuery( $query );

			if (!$db->query())
					die( $db->stderr());

            $query ='SET foreign_key_checks = 1;';
			$db->setQuery($query);
			if (!$db->query())    die( $db->stderr());

			return true;
		}
	}

    public static function fixMYSQLField($mysqltablename,$fieldname,$PureFieldType)
	{
		$db = JFactory::getDBO();

        $query='ALTER TABLE '.$mysqltablename.' CHANGE '.$fieldname.' '.$fieldname.' '.$PureFieldType;

		$db->setQuery( $query );

		if (!$db->query())
			die( $db->stderr());

		return true;
	}


    public static function getFieldName($fieldid)
	{
		$db = JFactory::getDBO();

		$jinput = JFactory::getApplication()->input;

		if($fieldid==0)
			$fieldid=JFactory::getApplication()->input->get('fieldid',0,'INT');

		$query = 'SELECT fieldname FROM #__customtables_fields AS s WHERE s.published=1 AND s.id='.$fieldid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return '';

		return $rows[0]->fieldname;
	}

	public static function getFieldRow($fieldid)
	{
		$db = JFactory::getDBO();

		$jinput = JFactory::getApplication()->input;

		if($fieldid==0)
			$fieldid=JFactory::getApplication()->input->get('fieldid',0,'INT');

		$query = 'SELECT * FROM #__customtables_fields AS s WHERE published=1 AND id='.$fieldid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return array();

		return $rows[0];
	}

	public static function getFields($tableid_or_name,$as_object=false)
	{
		$db = JFactory::getDBO();

        if((int)$tableid_or_name>0)
            $query = 'SELECT * FROM #__customtables_fields WHERE published=1 AND tableid='.(int)$tableid_or_name.' ORDER BY ordering, fieldname';
        else
        {
            $w1='(SELECT t.id FROM #__customtables_tables AS t WHERE t.tablename='.$db->quote($tableid_or_name).' LIMIT 1)';
            $query = 'SELECT * FROM #__customtables_fields AS f WHERE f.published=1 AND f.tableid='.$w1.' ORDER BY f.ordering, f.fieldname';
        }

		$db->setQuery( $query );

        if($as_object)
            return $db->loadObjectList();
        else
            return $db->loadAssocList();
	}

	public static function getFieldRowByName($fieldname, $tableid=0,$sj_tablename='')
	{
		$db = JFactory::getDBO();

		if($fieldname=='')
			return array();

		if($sj_tablename=='')
			$query = 'SELECT * FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname='.$db->quote(trim($fieldname)).' LIMIT 1';
		else
		{
			$query = 'SELECT * FROM #__customtables_fields AS s

			INNER JOIN #__customtables_tables AS t ON t.tablename='.$db->quote($sj_tablename).'
			WHERE s.published=1 AND s.tableid=t.id AND s.fieldname='.$db->quote(trim($fieldname)).' LIMIT 1';
		}


		$db->setQuery( $query );

		$rows = $db->loadObjectList();


		if(count($rows)!=1)
		{
			return array();
		}
		return $rows[0];
	}

	public static function getFieldAsocByName($fieldname, $tableid)
	{
		$db = JFactory::getDBO();

		if($fieldname=='')
			$fieldname=JFactory::getApplication()->input->get('fieldname','','CMD');

		if($fieldname=='')
			return array();

		$query = 'SELECT * FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname="'.trim($fieldname).'" LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
		{
			return array();
		}
		return $rows[0];
	}

	public static function getFieldAsocByName_($fieldname,$fields)
	{
		//from fields array
		foreach($fields as $field)
		{
			if($field['fieldname']==$fieldname)
				return $field;
		}
		return array();

	}



	public static function FieldRowByName($fieldname,$esfields)
	{
			foreach($esfields as $field)
			{
					if($field['fieldname']==$fieldname)
					{
							return $field;
					}
			}
			return array();
	}



}
