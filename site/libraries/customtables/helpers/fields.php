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

class ESFields
{
    public static function getFieldID($tableid, $fieldname)
	{
		$db = JFactory::getDBO();
		$query= 'SELECT id FROM #__customtables_fields WHERE published=1 AND tableid='.(int)$tableid.' AND fieldname='.$db->quote($fieldname);

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
		
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null'];
		
        foreach($fields as $field)
        {
            if($field[$field_columns->columnname]==$original_fieldname)
            {
                $AdditionOptions='';
                if($field[$field_columns->is_nullable]!='NO')
					$AdditionOptions='null';

                ESFields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field[$field_columns->data_type], $AdditionOptions);
                return true;
            }
        }
        return false;
    }

    public static function addESField($realtablename,$realfieldname,$fieldtype,$PureFieldType,$fieldtitle)
	{
        if($PureFieldType=='')
            return '';
        
		$db = JFactory::getDBO();

		if(strpos($fieldtype,'multilang')===false)
		{
			$AdditionOptions='';
			if($db->serverType != 'postgresql')
				$AdditionOptions=' COMMENT '.$db->Quote($fieldtitle);

			if($fieldtype!='dummy')
				ESFields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, $AdditionOptions);
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

				$AdditionOptions='';
				if($db->serverType != 'postgresql')
					$AdditionOptions	=' COMMENT '.$db->Quote($fieldtitle);

				ESFields::AddMySQLFieldNotExist($realtablename, $realfieldname.$postfix, $PureFieldType, $AdditionOptions);

				$index++;
			}
		}

		if($fieldtype=='imagegallery')
		{
			//Create table
			//get CT table name if possible
			
			$establename=str_replace($db->getPrefix().'customtables_table','',$realtablename);
			$esfieldname=str_replace('es_','',$realfieldname);
			ESFields::CreateImageGalleryTable($establename,$esfieldname);
		}
		elseif($fieldtype=='filebox')
		{
			//Create table
			//get CT table name if possible
			$establename=str_replace('#__customtables_table','',$realtablename);
			$esfieldname=str_replace('es_','',$realfieldname);
			ESFields::CreateFileBoxTable($establename,$esfieldname);
		}
	}

	public static function deleteESField_byID($fieldid)
	{
		$db = JFactory::getDBO();

		$ImageFolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';

		$fieldrow=ESFields::getFieldRow($fieldid);

    	if(!is_object($fieldrow) or count($fieldrow)==0)
			return false;
			
		$tablerow=ESTables::getTableRowByID($fieldrow->tableid);

		//for Image Gallery
		if($fieldrow->type=='imagegallery')
		{
			//Delete all photos belongs to the gallery
			
			$imagemethods=new CustomTablesImageMethods;
			$gallery_table_name='#__customtables_gallery_'.$tablerow->tablename.'_'.$fieldrow->fieldname;
			$imagemethods->DeleteGalleryImages($gallery_table_name, $tableid, $fieldrow->fieldname,$fieldrow->typeparams,true);

			//Delete gallery table
			$query ='DROP TABLE IF EXISTS '.$gallery_table_name;
			$db->setQuery($query);
			$db->execute();
		}
		elseif($fieldrow->type=='filebox')
		{
			//Delete all files belongs to the filebox

			$filebox_table_name='#__customtables_filebox_'.$tablerow->tablename.'_'.$fieldrow->fieldname;
			CustomTablesFileMethods::DeleteFileBoxFiles($filebox_table_name, $tableid, $fieldrow->fieldname,$fieldrow->typeparams,true);

			//Delete gallery table
			$query ='DROP TABLE IF EXISTS '.$filebox_table_name;
			$db->setQuery($query);
			$db->execute();
		}
		elseif($fieldrow->type=='image')
		{
			if(ESFields::checkIfFieldExists($tablerow->realtablename,$fieldrow->realfieldname))
			{
				$imagemethods=new CustomTablesImageMethods;
				$imageparams=str_replace('|compare','|delete:',$fieldrow->typeparams); //disable image comparision if set
				$imagemethods->DeleteCustomImages($tablerow->realtablename,$fieldrow->realfieldname, $ImageFolder, $imageparams, $tablerow->realidfieldname, true);
			}
		}
		elseif($fieldrow->type=='user' or $fieldrow->type=='userid' or $fieldrow->type=='sqljoin')
		{
			ESFields::removeForeignKey($tablerow->realtablename,$fieldrow->realfieldname);//,'','#__users','id',$msg);
		}
        elseif($fieldrow->type=='file')
		{
			// delete all files
			//if(file_exists($filename))
			//unlink($filename);
		}

		$realfieldnames=array();

		if(strpos($fieldrow->type,'multilang')===false)
		{
			$realfieldnames[]=$fieldrow->realfieldname;
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

				$realfieldnames[]=$fieldrow->realfieldname.$postfix;
			}
		}

		$i=0;

		foreach($realfieldnames as $realfieldname)
		{
			if($fieldrow->type!='dummy')
			{
				$msg='';
				ESFields::deleteMYSQLField($tablerow->realtablename,$realfieldname,$msg);
			}
		}

		//Delete field from the list
		$query ='DELETE FROM #__customtables_fields WHERE published=1 AND id='.$fieldid;
		$db->setQuery($query);
		$db->execute();
        return true;
	}

	public static function comparePureFieldTypes($fieldtype1_, $fieldtype2_)
	{
		$fieldtype1=strtolower($fieldtype1_);
		$fieldtype2=strtolower($fieldtype2_);
		
		if($fieldtype1 == $fieldtype2)
			return true;
		
		//Remove Text Between Parentheses
		$fieldtype1_no_par = preg_replace("/\([^)]+\)/","",$fieldtype1); // replace "varchar(255) null" with "varchar null"
		$fieldtype2_no_par = preg_replace("/\([^)]+\)/","",$fieldtype2); // replace "varchar(255) null" with "varchar null"
		
		$db = JFactory::getDBO();
		if($db->serverType == 'postgresql')
		{
			if($fieldtype1=='character varying null' and $fieldtype2_no_par == 'varchar null')
				return true;
				
			if($fieldtype1=='integer null' and $fieldtype2_no_par == 'int null')
				return true;
		}
		else
		{
			if($fieldtype1_no_par=='int null' and $fieldtype2_no_par == 'int null')
				return true;
				
			if($fieldtype1_no_par=='tinyint not null default 0' and $fieldtype2_no_par == 'tinyint not null default 0')
				return true;
				
			if($fieldtype1_no_par=='bigint unsigned null' and $fieldtype2_no_par == 'bigint unsigned null')
				return true;
			
			if($fieldtype1_no_par=='bigint null' and $fieldtype2_no_par == 'bigint null')
				return true;
			
			if($fieldtype1_no_par=='int unsigned null' and $fieldtype2_no_par == 'int unsigned null')
				return true;			
		}
		
		return false;
	}

    public static function getPureFieldType($fieldtype,$typeparams)
	{
		$db = JFactory::getDBO();
		
		$t=trim($fieldtype);
		switch($t)
		{
			case 'phponadd':
				return 'varchar(255) null';
			case 'filelink':
				return 'varchar(255) null';
            case 'alias':
				return 'varchar(255) null';
            case 'color':
				return 'varchar(8) null';
			case 'string':
				$l=(int)$typeparams;

				if($l==0)
					$l=255;

				if($l<1)
					$l=1;

				if($l>1024)
					$l=1024;

				return 'varchar('.$l.') null';

			break;

			case 'multilangstring':

				$l=(int)$typeparams;

				if($l==0)
					$l=255;

				if($l<1)
					$l=1;

				if($l>1024)
					$l=1024;

				return 'varchar('.$l.') null';

			break;

			case 'text':
				return 'text null';
				break;
			case 'multilangtext':
				return 'text null';
				break;

			case 'int':
				
				if($db->serverType == 'postgresql')
					return 'int null';
				else
					return 'int null';
				
				break;

			case 'float':
				$typeparams_arr=explode(',',$typeparams);
				
				if($db->serverType == 'postgresql')
				{
					if(count($typeparams_arr)==1)
						return 'numeric(20,'.(int)$typeparams_arr[0].') null';
					elseif(count($typeparams_arr)==2)
						return 'numeric('.(int)$typeparams_arr[1].','.(int)$typeparams_arr[0].') null';
					else
						return 'numeric(20,2) null';
				}
				else
				{
					if(count($typeparams_arr)==1)
						return 'decimal(20,'.(int)$typeparams_arr[0].') null';
					elseif(count($typeparams_arr)==2)
						return 'decimal('.(int)$typeparams_arr[1].','.(int)$typeparams_arr[0].') null';
					else
						return 'decimal(20,2) null';
				}

				break;

			case 'customtables':
				$typeparams_arr=explode(',',$typeparams);

				if(count($typeparams_arr)<3)
					return 'varchar(255) null';

				$l=(int)$typeparams_arr[2];

				if($l==0)
					$l=255;

				if($l<64)
					$l=64;

				if($l>65535)
					$l=65535;

				return 'varchar('.$l.') null';

				break;

			case 'records':
				return 'varchar(255) null';
				break;

			case 'sqljoin':
				if($db->serverType == 'postgresql')
					return 'int null';
				else
					return 'int null';
				
				break;

			case 'file':
				return 'varchar(255) null';
				break;

			case 'image':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint null';
					
				break;

			case 'checkbox':
				if($db->serverType == 'postgresql')
					return 'smallint not null default 0';
				else
					return 'tinyint not null default 0';
				
				break;

			case 'radio':
				return 'varchar(255) null';
				break;

			case 'email':
				return 'varchar(255) null';
				break;

			case 'url':
				return 'varchar(1024) null';
				break;


			case 'date':
				return 'date null';
				break;
            
		    case 'time':
				if($db->serverType == 'postgresql')
					return 'int null';
				else
					return 'int null';
					
				break;

			case 'creationtime':
				if($db->serverType == 'postgresql')
					return 'TIMESTAMP null';
				else
					return 'datetime null';
					
				break;

			case 'changetime':
				if($db->serverType == 'postgresql')
					return 'TIMESTAMP null';
				else
					return 'datetime null';
					
				break;

			case 'lastviewtime':
				if($db->serverType == 'postgresql')
					return 'TIMESTAMP null';
				else
					return 'datetime null';
					
				break;

			case 'viewcount':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'userid': //current user id (auto asigned)

				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';

			case 'user': //user (selection)
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';

			case 'usergroup': //user group (selection)
				if($db->serverType == 'postgresql')
					return 'int null';
				else
					return 'int unsigned null';

			case 'usergroups': //user groups (selection)
				return 'varchar(255) null';

            case 'language':
				return 'varchar(5) null';
				break;

			case 'server':
				return 'varchar(255) null';
				break;

			case 'id':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'dummy':
				return '';
				break;

			case 'imagegallery':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'filebox':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'article':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'multilangarticle':
				if($db->serverType == 'postgresql')
					return 'bigint null';
				else
					return 'bigint unsigned null';
					
				break;

			case 'md5':
				return 'char(32) null';
				break;

			case 'log':
				return 'text null';
				break;

            case '_published':

				if($db->serverType == 'postgresql')
					return 'samllint not null default 1';
				else
					return 'tinyint not null default 1';
					
				break;
            
            case 'phponview':
                $typeparams_arr=explode(',',$typeparams);
                
                if(isset($typeparams_arr[1]) and $typeparams_arr[1]=='dynamic')
                    return ''; //do not store the field values
                else
                    return 'varchar(255) null';
                
                break;

			default:

				return 'varchar(255) null';
				break;
		}

	}


    public static function AddMySQLFieldNotExist($realtablename, $realfieldname, $fieldtype, $options)
    {
		$db = JFactory::getDBO();
		if(!ESFields::checkIfFieldExists($realtablename,$realfieldname,false))
		{
			$query='ALTER TABLE '.$realtablename.' ADD COLUMN '.$realfieldname.' '.$fieldtype.' '.$options;

			$db->setQuery($query);
			$db->execute();
		}
    }


    public static function addForeignKey($realtablename, $realfieldname, $new_typeparams='', $join_with_table_name='',$join_with_table_field='',&$msg)
	{
		if($db->serverType == 'postgresql')
			return false;
			
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

			$tablerow = ESTables::getTableRowByName($params[0]); //$params[0] - is tablename
			if(!is_object($tablerow))
			{
				$msg='Join with table "'.$join_with_table_name.'" not found.';
				return false;	// Exit if table to connect with not found
			}
			
            $join_with_table_name=$tablerow->realtablename;
			$join_with_table_field=$tablerow->realidfieldname;
		}
        
        $db = JFactory::getDBO();
        $conf = JFactory::getConfig();
        $database = $conf->get('db');
        
        ESFields::removeForeignKey($realtablename,$realfieldname);
        
        if(isset($params[7]) and $params[7]=='noforignkey')
        {
			//Do nothing
        }
        else
        {
            ESFields::cleanTableBeforeNormalization($realtablename,$realfieldname,$join_with_table_name,$join_with_table_field);

            $query='ALTER TABLE '.$db->quoteName($realtablename).' ADD FOREIGN KEY ('.$realfieldname.') REFERENCES '
				.$db->quoteName($database.'.'.$join_with_table_name).' ('.$join_with_table_field.') ON DELETE RESTRICT ON UPDATE RESTRICT;';

            try
            {
                $db->setQuery($query);
				$db->execute();
            }
            catch (RuntimeException $e)
            {
            	$msg=$e->getMessage();
            }
            return false;
        }
	}


    public static function removeForeignKey($realtablename,$realfieldname)
	{
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
			return false;
		
		$conf = JFactory::getConfig();
		$database = $conf->get('db');

        //get constarnt name
        $query='show create table '.$realtablename;
        
        $db->setQuery( $query );
        $db->execute();
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
                if($pair[4]=='('.$realfieldname.')')
                {
                    $constrances[]=$pair[1];
                }
            }
        }

        foreach($constrances as $constrance)
        {
            $query ='SET foreign_key_checks = 0;';
            $db->setQuery($query);
            $db->execute();

            $query='ALTER TABLE '.$realtablename.' DROP FOREIGN KEY '.$constrance;

            try
            {
                $db->setQuery($query);
				$db->execute();
            }
            catch (RuntimeException $e)
            {
            	$msg=$e->getMessage();
            }


            $query ='SET foreign_key_checks = 1;';
            $db->setQuery($query);
            $db->execute();
        }

		return false;
	}

    public static function addIndexIfNotExist($realtablename,$realfieldname)
	{
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
		{
			//Indexes not yet supported
		}
		else
		{
			$db = JFactory::getDBO();
			$query='SHOW INDEX FROM '.$realtablename.' WHERE Key_name = "'.$realfieldname.'"';
			$db->setQuery( $query );
			$db->execute();

			$rows2 = $db->loadObjectList();


			if(count($rows2)==0)
			{
				$query='ALTER TABLE '.$realtablename.' ADD INDEX('.$realfieldname.');';

				$db->setQuery( $query );
				$db->execute();
			}
		}
	}


    public static function CreateImageGalleryTable($tablename,$fieldname)
	{
		$image_gallery_table='#__customtables_gallery_'.$tablename.'_'.$fieldname;
		$db = JFactory::getDBO();

		$query = 'CREATE TABLE IF not EXISTS '.$image_gallery_table.' (
  photoid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  photo_ext varchar(10) not null,
  title varchar(100) not null,
   PRIMARY KEY  (photoid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
		$db->setQuery( $query );
		$db->execute();

		return true;
	}


    public static function CreateFileBoxTable($tablename,$fieldname)
	{
		$filebox_gallery_table='#__customtables_filebox_'.$tablename.'_'.$fieldname;
		$db = JFactory::getDBO();

		$query = 'CREATE TABLE IF not EXISTS '.$filebox_gallery_table.' (
  fileid bigint not null auto_increment,
  listingid bigint not null,
  ordering int not null,
  file_ext varchar(10) not null,
  title varchar(100) not null,
   PRIMARY KEY  (fileid)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1 ;
';
		$db->setQuery( $query );
		$db->execute();

		return true;
	}

    public static function ConvertFieldType($realtablename, $realfieldname,   $ex_type, $ex_typeparams,$ex_PureFieldType, $new_type, $new_typeparams, $PureFieldType, $fieldtitle)
	{
        if($new_type==$ex_type)
			return true; //no need to convert

        $unconvertable_types=array('dummy','image','imagegallery','file','filebox','records','customtables','log');

        if(in_array($new_type,$unconvertable_types) or in_array($ex_type,$unconvertable_types))
            return false;

        			$PureFieldType_=$PureFieldType;

					//Check and fix record
					if($new_type=='customtables')
					{
						//get number of string like "varchar(255)"
						$maxlength=(int)preg_replace("/[^0-9]/", "", $PureFieldType);
						$typeparamsarr=explode(',',$new_typeparams);
						$optionname=$typeparamsarr[0];

						ESFields::FixCustomTablesRecords($realtablename,$realfieldname,$optionname, $maxlength );
					}

					$db = JFactory::getDBO();

					if($db->serverType == 'postgresql')
					{
						$parts=explode(' ',$PureFieldType_);
						$query = 'ALTER TABLE '.$realtablename
							.' ALTER COLUMN '.$realfieldname.' TYPE '.$parts[0];
							
						$db->setQuery( $query );
						$db->execute();
					}
					else
					{
						$query = 'ALTER TABLE '.$realtablename.' CHANGE '.$realfieldname.' '.$realfieldname.' '.$PureFieldType_;
						$query .= ' COMMENT '.$db->quote($fieldtitle);
						
						$db->setQuery( $query );
						$db->execute();
					}
		return true;
	}

	public static function FixCustomTablesRecords($realtablename, $realfieldname, $optionname, $maxlenght)
	{
		//CutomTables field type
		if($db->serverType == 'postgresql')
			return;
		
		$es=new CustomTablesMisc;
		$db = JFactory::getDBO();

		$fixcount=0;

		$fixquery= 'SELECT id, '.$realfieldname.' AS fldvalue FROM '.$realtablename.'';


		$db->setQuery( $fixquery );

		$fixrows = $db->loadObjectList();
		foreach($fixrows as $fixrow)
		{

			$newrow	=ESFields::FixCustomTablesRecord($fixrow->fldvalue, $optionname, $maxlenght);

			if($fixrow->fldvalue!=$newrow)
			{
				$fixcount++;

				$fixitquery= 'UPDATE '.$realtablename.' SET '.$realfieldname.'="'.$newrow.'" WHERE id='.$fixrow->id;
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


	public static function cleanTableBeforeNormalization($realtablename,$realfieldname,$join_with_table_name,$join_with_table_field)
	{
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
			return;
			
		//Find broken records
		$query='SELECT DISTINCT a.'.$realfieldname.' AS customtables_distinct_temp_id FROM
			'.$realtablename.' a LEFT JOIN '.$join_with_table_name.' b ON a.'.$realfieldname.'=b.'.$join_with_table_field
			.' WHERE b.'.$join_with_table_field.' IS NULL;';

		$db->setQuery( $query );
		$db->execute();

		$rows = $db->loadAssocList();

		$ids=array();
		$ids[]=$realfieldname.'=0';

		foreach($rows as $row)
		{
			if($row['customtables_distinct_temp_id']!='')
				$ids[]=$realfieldname.'='.$row['customtables_distinct_temp_id'];
		}

		$query = 'UPDATE '.$realtablename.' SET '.$realfieldname.'=NULL WHERE '.implode(' OR ',$ids).';';

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

    public static function getFieldType($tablename,$add_table_prefix=true,$realfieldname)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$realtablename='#__customtables_table_'.$tablename;
		else
			$realtablename=$tablename;
			
		$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);
		
		if($db->serverType == 'postgresql')
			$query = 'SELECT data_type FROM information_schema.columns WHERE table_name = '.$db->quote($realtablename).' AND column_name='.$db->quote($realfieldname);
		else
			$query = 'SHOW COLUMNS FROM '.$realtablename.' WHERE '.$db->quoteName('field').'='.$db->quote($realfieldname);

		$db->setQuery( $query );

        $recs=$db->loadAssocList();

        if(count($recs)==0)
            return '';

        $rec=$recs[0];
		
		if($db->serverType == 'postgresql')
			return $rec['data_type'];
		else
			return $rec['Type'];
	}

	//MySQL only
    public static function getExistingFields($tablename,$add_table_prefix=true)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$realtablename='#__customtables_table_'.$tablename;
		else
			$realtablename=$tablename;

		if($db->serverType == 'postgresql')
		{
			$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);
			$query = 'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '.$db->quote($realtablename);
		}
		else
		{
			$query = 'SHOW COLUMNS FROM '.$realtablename;
		}

		$db->setQuery( $query );

		return $db->loadAssocList();

	}

    public static function getListOfExistingFields($tablename,$add_table_prefix=true)
	{
		$db = JFactory::getDBO();

		if($add_table_prefix)
			$realtablename=$db->getPrefix().'customtables_table_'.$tablename;
		else
			$realtablename=$tablename;

		if($db->serverType == 'postgresql')
		{
			$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);
			$query = 'SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_name = '.$db->quote($realtablename);
		}
		else
		{
			$query = 'SHOW COLUMNS FROM '.$realtablename;
		}
     
		$list=array();
		
		$db->setQuery( $query );
		$recs=$db->loadAssocList();
        
        if($db->serverType == 'postgresql')
		{
			foreach($recs as $rec)
				$list[]=$rec['column_name'];
        }
		else
		{
			foreach($recs as $rec)
				$list[]=$rec['Field'];
        }
		return $list;
	}

	public static function checkIfFieldExists($realtablename,$realfieldname)//,$add_table_prefix=true)
	{
		$realfieldnames=ESFields::getListOfExistingFields($realtablename,false);
		return in_array($realfieldname,$realfieldnames);
	}

	public static function deleteMYSQLField($realtablename,$realfieldname,&$msg)
	{
		if(ESFields::checkIfFieldExists($realtablename,$realfieldname,false))
		{
			try
			{
				$db = JFactory::getDBO();

				$query ='SET foreign_key_checks = 0;';
				$db->setQuery($query);
				$db->execute();

				$query='ALTER TABLE '.$realtablename.' DROP '.$realfieldname;

				$db->setQuery( $query );
				$db->execute();

				$query ='SET foreign_key_checks = 1;';
				$db->setQuery($query);
				$db->execute();
				
				return true;
			}
			catch (Exception $e)
			{
				$msg='<p style="color:red;">Caught exception: '.$e->getMessage().'</p>';
				return false;
			}
		}
	}

    public static function fixMYSQLField($realtablename,$fieldname,$PureFieldType,&$msg)
	{
		try
		{
			$db = JFactory::getDBO();

			if($PureFieldType=='_id')
				$query='ALTER TABLE '.$realtablename.' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';
			elseif($PureFieldType=='_published')
				$query='ALTER TABLE '.$realtablename.' CHANGE published published TINYINT NOT NULL DEFAULT 1';
			else
				$query='ALTER TABLE '.$realtablename.' CHANGE '.$fieldname.' '.$fieldname.' '.$PureFieldType;

			$db->setQuery( $query );
			$db->execute();
			
			$msg='';
			return true;
		}
		catch (Exception $e)
		{
			$msg='<p style="color:red;">Caught exception: '.$e->getMessage().'</p>';
			return false;
		}
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

	public static function getFieldRow($fieldid = 0)
	{
		$db = JFactory::getDBO();

		$jinput = JFactory::getApplication()->input;

		if($fieldid==0)
			$fieldid=JFactory::getApplication()->input->get('fieldid',0,'INT');

		$query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE published=1 AND id='.$fieldid.' LIMIT 1';

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
            $query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields WHERE published=1 AND tableid='.(int)$tableid_or_name.' ORDER BY ordering, fieldname';
        else
        {
            $w1='(SELECT t.id FROM #__customtables_tables AS t WHERE t.tablename='.$db->quote($tableid_or_name).' LIMIT 1)';
            $query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields AS f WHERE f.published=1 AND f.tableid='.$w1.' ORDER BY f.ordering, f.fieldname';
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
			$query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname='.$db->quote(trim($fieldname)).' LIMIT 1';
		else
		{
			$query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields AS s

			INNER JOIN #__customtables_tables AS t ON t.tablename='.$db->quote($sj_tablename).'
			WHERE s.published=1 AND s.tableid=t.id AND s.fieldname='.$db->quote(trim($fieldname)).' LIMIT 1';
		}


		$db->setQuery( $query );

		$rows = $db->loadObjectList();


		if(count($rows)!=1)
		{
			return null;
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

		$query = 'SELECT '.ESFields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname="'.trim($fieldname).'" LIMIT 1';
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



	public static function FieldRowByName($fieldname,&$esfields)
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


	public static function getRealFieldName($fieldname,&$esfields)
	{
		foreach($esfields as $row)
		{
			if($row['allowordering']==1 and $row['fieldname']==$fieldname)
				return $row['realfieldname'];
		}
		return '';
	}
	
	protected static function getFieldRowSelects()
	{
		$db = JFactory::getDBO();

		if($db->serverType == 'postgresql')
			$realfieldname_query='CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
		else
			$realfieldname_query='IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';
		
        return '*, '.$realfieldname_query;
	}
	
	
	public static function checkField($ExistingFields,$realtablename,$proj_field,$type)
    {
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];

        $found=false;
        foreach($ExistingFields as $existing_field)
        {
            if($proj_field==$existing_field[$field_columns->columnname])
            {
                $found=true;
                break;
            }
        }

        if(!$found)
		{
			$query='ALTER TABLE '.$realtablename.' ADD COLUMN '.$proj_field.' '.$type;

			$db->setQuery($query);
			$db->execute();
		}
    }
	
	
	public static function shortFieldObjects(&$esfields)
	{
		$field_objects = [];
		
		foreach($esfields as $esfield)
			$field_objects[] = ESFields::shortFieldObject($esfield,null,[]);

		return $field_objects;
	}
	
	public static function shortFieldObject(&$esfield,$value,$options)
	{
		$field = [];
		$field['fieldname'] = $esfield['fieldname'];
		$field['title'] = $esfield['fieldtitle'];
		$field['defaultvalue'] = $esfield['defaultvalue'];
		$field['description'] = $esfield['description'];
		$field['isrequired'] = $esfield['isrequired'];
		$field['isdisabled'] = $esfield['isdisabled'];
		$field['type'] = $esfield['type'];
		
		$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);
		$field['typeparams'] = $typeparams;
		$field['valuerule'] = $esfield['valuerule'];
		$field['valuerulecaption'] = $esfield['valuerulecaption'];
		
		$field['value'] = $value;
		
		if(count($options) == 1 and $options[0] == '')
			$field['options'] = null;
		else
			$field['options'] = $options;
		
		return $field;
	}

}
