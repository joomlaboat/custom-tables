<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use \JoomlaBasicMisc;
use \ESTables;

use \Joomla\CMS\Factory;

class Fields
{
    public static function getFieldID($tableid, $fieldname)
	{
		$db = Factory::getDBO();
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
        $fields=Fields::getExistingFields($tablename,false);
		
		$db = Factory::getDBO();
		
        foreach($fields as $field)
        {
            if($field['column_name']==$original_fieldname)
            {
                $AdditionOptions='';
                if($field['is_nullable']!='NO')
					$AdditionOptions='null';

                Fields::AddMySQLFieldNotExist($tablename, $new_fieldname, $field['data_type'], $AdditionOptions);
                return true;
            }
        }
        return false;
    }

    public static function addField(&$ct,$realtablename,$realfieldname,$fieldtype,$PureFieldType,$fieldtitle)
	{
        if($PureFieldType=='')
            return '';
        
		$db = Factory::getDBO();

		if(strpos($fieldtype,'multilang')===false)
		{
			$AdditionOptions='';
			if($db->serverType != 'postgresql')
				$AdditionOptions=' COMMENT '.$db->Quote($fieldtitle);

			if($fieldtype!='dummy')
				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, $AdditionOptions);
		}
		else
		{
			$index=0;
			foreach($this->ct->Languages->LanguageList as $lang)
			{
				if($index==0)
					$postfix='';
                else
					$postfix='_'.$lang->sef;

				$AdditionOptions='';
				if($db->serverType != 'postgresql')
					$AdditionOptions	=' COMMENT '.$db->Quote($fieldtitle);

				Fields::AddMySQLFieldNotExist($realtablename, $realfieldname.$postfix, $PureFieldType, $AdditionOptions);

				$index++;
			}
		}

		if($fieldtype=='imagegallery')
		{
			//Create table
			//get CT table name if possible
			
			$establename=str_replace($db->getPrefix().'customtables_table','',$realtablename);
			$esfieldname=str_replace('es_','',$realfieldname);
			Fields::CreateImageGalleryTable($establename,$esfieldname);
		}
		elseif($fieldtype=='filebox')
		{
			//Create table
			//get CT table name if possible
			$establename=str_replace('#__customtables_table','',$realtablename);
			$esfieldname=str_replace('es_','',$realfieldname);
			Fields::CreateFileBoxTable($establename,$esfieldname);
		}
	}

	public static function deleteField_byID(&$ct,$fieldid)
	{
		$db = Factory::getDBO();

		$ImageFolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';

		$fieldrow=Fields::getFieldRow($fieldid);

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
			if(Fields::checkIfFieldExists($tablerow->realtablename,$fieldrow->realfieldname))
			{
				$imagemethods=new CustomTablesImageMethods;
				$imageparams=str_replace('|compare','|delete:',$fieldrow->typeparams); //disable image comparision if set
				$imagemethods->DeleteCustomImages($tablerow->realtablename,$fieldrow->realfieldname, $ImageFolder, $imageparams, $tablerow->realidfieldname, true);
			}
		}
		elseif($fieldrow->type=='user' or $fieldrow->type=='userid' or $fieldrow->type=='sqljoin')
		{
			Fields::removeForeignKey($tablerow->realtablename,$fieldrow->realfieldname);
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
            $index=0;
            foreach($this->ct->Languages->LanguageList as $lang)
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
				Fields::deleteMYSQLField($tablerow->realtablename,$realfieldname,$msg);
			}
		}

		//Delete field from the list
		$query ='DELETE FROM #__customtables_fields WHERE published=1 AND id='.$fieldid;
		$db->setQuery($query);
		$db->execute();
        return true;
	}

	public static function makeProjectedFieldType($ct_fieldtype_array)
	{
		$type = (object)$ct_fieldtype_array; 

		$db = Factory::getDBO();
		
		$elements=[];
		
		switch($type->data_type)
		{
			case null:
				return '';
				
			case 'varchar':
				$elements[]='varchar('.$type->length.')';
				break;
				
			case 'text':
				$elements[]='text';
				break;
				
			case 'char':
				$elements[]='char('.$type->length.')';
				break;
				
			case 'int':
				$elements[]='int';
				
				if($db->serverType != 'postgresql')
				{
					if($type->is_nullable != null and $type->is_unsigned)
						$elements[]='unsigned';
				}
				break;
				
			case 'bigint':
				$elements[]='bigint';
				
				if($db->serverType != 'postgresql')
				{
					if($type->is_nullable != null and $type->is_unsigned)
						$elements[]='unsigned';
				}
				break;

			case 'decimal':
				if($db->serverType == 'postgresql')
					$elements[]='numeric('.$type->length.')';
				else
					$elements[]='decimal('.$type->length.')';
					
				break;
				
			case 'tinyint':
				if($db->serverType == 'postgresql')
					$elements[]='smallint';
				else
					$elements[]='tinyint';
					
				break;
				
			case 'date':
				$elements[]='date';
				break;
				
			case 'datetime':
				if($db->serverType == 'postgresql')
					$elements[]='TIMESTAMP';
				else
					$elements[]='datetime';
					
				break;
				
			default:
				return '';
		}
		
		if($type->is_nullable)
			$elements[]='null';
		else
			$elements[]='not null';
			
		if($type->default !== null)
			$elements[]='default '.(is_numeric($type->default) ? $type->default : $db->quote($type->default));
			
		if($type->extra !== null)
			$elements[]=$type->extra;
		
		return implode(' ',$elements);
	}
	
	public static function getProjectedFieldType($ct_fieldtype,$typeparams)
	{
		//Returns an array of mysql column parameters
		switch(trim($ct_fieldtype))
		{
			case '_id':
				return ['data_type' => 'int','is_nullable' => false, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => 'auto_increment'];
				
			case '_published':
				return ['data_type' => 'tinyint','is_nullable' => false, 'is_unsigned' => false, 'length' => null, 'default' => 1, 'extra' => null];
				
			case 'filelink':
			case 'file':
			case 'url':
				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 1024, 'default' => null, 'extra' => null];
			case 'alias':
			case 'records':
			case 'radio':
			case 'email':
			case 'server':
			case 'usergroups':
				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];
			case 'color':
				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 8, 'default' => null, 'extra' => null];
			case 'string':
			case 'multilangstring':
				$l=(int)$typeparams;
				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => ($l < 1 ? 255 : ($l > 1024 ? 1024 : $l)), 'default' => null, 'extra' => null];
			case 'text':
			case 'multilangtext':
			case 'log':
				return ['data_type' => 'text','is_nullable'=> true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
			case 'int':
				return ['data_type' => 'int','is_nullable'=> true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];
			case 'float':
				
				$typeparams_arr=explode(',',$typeparams);
			
				if(count($typeparams_arr)==1)
					$l='20,'.(int)$typeparams_arr[0];
				elseif(count($typeparams_arr)==2)
					$l=(int)$typeparams_arr[1].','.(int)$typeparams_arr[0];
				else
					$l='20,2';
				return ['data_type' => 'decimal','is_nullable'=> true, 'is_unsigned' => false, 'length' => $l, 'default' => null, 'extra' => null];

			case 'customtables':
				$typeparams_arr=explode(',',$typeparams);

				if(count($typeparams_arr)<255)
					$l=255;
				else
					$l=(int)$typeparams_arr[2];

				if($l>65535)
					$l=65535;

				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => $l, 'default' => null, 'extra' => null];

			case 'userid':
			case 'user':
			case 'usergroup':
			case 'sqljoin':
			case 'article':
			case 'multilangarticle':
				return ['data_type' => 'int','is_nullable'=> true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

			case 'image':
				return ['data_type' => 'bigint','is_nullable'=> true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

			case 'checkbox':
				return ['data_type' => 'tinyint','is_nullable'=> false, 'is_unsigned' => false, 'length' => null, 'default' => 0, 'extra' => null];

			case 'date':
				return ['data_type' => 'date','is_nullable'=> true, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
            
		    case 'time':
				return ['data_type' => 'int','is_nullable'=> true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

			case 'creationtime':
			case 'changetime':
			case 'lastviewtime':
				return ['data_type' => 'datetime','is_nullable'=> true, 'is_unsigned' => false, 'length' => null, 'default' => null, 'extra' => null];

			case 'viewcount':
			case 'imagegallery':
			case 'filebox':
				return ['data_type' => 'bigint','is_nullable'=> true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

            case 'language':
				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 5, 'default' => null, 'extra' => null];

			case 'id':
				return ['data_type' => 'bigint','is_nullable'=> true, 'is_unsigned' => true, 'length' => null, 'default' => null, 'extra' => null];

			case 'dummy':
				return ['data_type' => null,'is_nullable'=> null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null];
				break;

			case 'md5':
				return ['data_type' => 'char','is_nullable'=> true, 'is_unsigned' => null, 'length' => 32, 'default' => null, 'extra' => null];

			case 'phponadd':
			case 'phponchange':
            case 'phponview':
                $typeparams_arr=explode(',',$typeparams);
                
                if(isset($typeparams_arr[1]) and $typeparams_arr[1]=='dynamic')
                    return ['data_type' => null,'is_nullable'=> null, 'is_unsigned' => null, 'length' => null, 'default' => null, 'extra' => null]; //do not store field values
                else
                    return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];
                
                break;

			default:

				return ['data_type' => 'varchar','is_nullable'=> true, 'is_unsigned' => null, 'length' => 255, 'default' => null, 'extra' => null];
				break;
		}
	}
	
	public static function getPureFieldType($ct_fieldtype,$typeparams)
	{
		$ct_fieldtype_array = Fields::getProjectedFieldType($ct_fieldtype,$typeparams);

		$type = Fields::makeProjectedFieldType($ct_fieldtype_array);

		return $type;
	}


    public static function AddMySQLFieldNotExist($realtablename, $realfieldname, $fieldtype, $options)
    {
		$db = Factory::getDBO();
		if(!Fields::checkIfFieldExists($realtablename,$realfieldname,false))
		{
			$query='ALTER TABLE '.$realtablename.' ADD COLUMN '.$realfieldname.' '.$fieldtype.' '.$options;

			$db->setQuery($query);
			$db->execute();
		}
    }


    public static function addForeignKey($realtablename_, $realfieldname, $new_typeparams='', $join_with_table_name ='',$join_with_table_field='',&$msg)
	{
		$db = Factory::getDBO();
		
		$conf = Factory::getConfig();
		$dbprefix = $conf->get('dbprefix');
		
		$realtablename = str_replace('#__',$dbprefix,$realtablename_);
		
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
		
		$join_with_table_name = str_replace('#__',$dbprefix,$join_with_table_name);

        $conf = Factory::getConfig();
        $database = $conf->get('db');
        
        Fields::removeForeignKey($realtablename,$realfieldname);
        
        if(isset($params[7]) and $params[7]=='noforignkey')
        {
			//Do nothing
        }
        else
        {
            Fields::cleanTableBeforeNormalization($realtablename,$realfieldname,$join_with_table_name,$join_with_table_field);

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

	protected static function getTableConstrances($realtablename,$realfieldname)
	{	
		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
			return false;
		
		$conf = Factory::getConfig();
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
                
				if($realfieldname == '')
					$constrances[]=$pair;
				elseif($pair[4]=='('.$realfieldname.')')
                    $constrances[]=$pair[1];
            }
        }
		
		return $constrances;
	}

	protected static function removeForeignKeyConstrance($realtablename,$constrance)
	{
		$db = Factory::getDBO();
		
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
			Factory::getApplication()->enqueueMessage($e->getMessage(),'error');
		}

        $query ='SET foreign_key_checks = 1;';
        $db->setQuery($query);
        $db->execute();
	}

    public static function removeForeignKey($realtablename,$realfieldname)
	{
		$db = Factory::getDBO();
		
		$constrances = Fields::getTableConstrances($realtablename,$realfieldname);

        foreach($constrances as $constrance)
        {
            Fields::removeForeignKey($realtablename,$constrance);
        }

		return false;
	}

    public static function addIndexIfNotExist($realtablename,$realfieldname)
	{
		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
		{
			//Indexes not yet supported
		}
		else
		{
			$db = Factory::getDBO();
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
		$db = Factory::getDBO();

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
		$db = Factory::getDBO();

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

						Fields::FixCustomTablesRecords($realtablename,$realfieldname,$optionname, $maxlength );
					}

					$db = Factory::getDBO();

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

		$db = Factory::getDBO();

		$fixcount=0;

		$fixquery= 'SELECT id, '.$realfieldname.' AS fldvalue FROM '.$realtablename.'';


		$db->setQuery( $fixquery );

		$fixrows = $db->loadObjectList();
		foreach($fixrows as $fixrow)
		{

			$newrow	=Fields::FixCustomTablesRecord($fixrow->fldvalue, $optionname, $maxlenght);

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
		$db = Factory::getDBO();
		
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
		$db = Factory::getDBO();

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
		$db = Factory::getDBO();

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
			$realtablename=str_replace('#__',$db->getPrefix(),$realtablename);
			
			//$query = 'SHOW COLUMNS FROM '.$realtablename;
			//$db->setQuery( $query );
			
			$conf = Factory::getConfig();
			$database = $conf->get('db');
			
			$query = 'SELECT COLUMN_NAME AS column_name,'
				.'DATA_TYPE AS data_type,'
				.'COLUMN_TYPE AS column_type,'
				.'IF(COLUMN_TYPE LIKE \'%unsigned\', \'YES\', \'NO\') AS is_unsigned,'
				.'IS_NULLABLE AS is_nullable,'
				.'COLUMN_DEFAULT AS column_default,'
				.'EXTRA AS extra'
				.' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='.$db->quote($database).' AND TABLE_NAME='.$db->quote($realtablename);

		}

		$db->setQuery( $query );
		return $db->loadAssocList();
	}

    public static function getListOfExistingFields($tablename,$add_table_prefix=true)
	{
		$realfieldnames=Fields::getExistingFields($tablename,$add_table_prefix);
		/*
		$db = Factory::getDBO();

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
        */
		foreach($realfieldnames as $rec)
			$list[]=$rec['column_name'];

		return $list;
	}

	public static function checkIfFieldExists($realtablename,$realfieldname)//,$add_table_prefix=true)
	{
		$realfieldnames=Fields::getListOfExistingFields($realtablename,false);
		
		return in_array($realfieldname,$realfieldnames);
	}

	public static function deleteMYSQLField($realtablename,$realfieldname,&$msg)
	{
		if(Fields::checkIfFieldExists($realtablename,$realfieldname,false))
		{
			try
			{
				$db = Factory::getDBO();

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
		$db = Factory::getDBO();

		if($fieldname=='id')
		{
			$constrances = Fields::getTableConstrances($realtablename,'');
			
			//Delete same table child-parent constrances
			
		    foreach($constrances as $constrance)
			{
				if($constrance[7]=='(id)')
					Fields::removeForeignKeyConstrance($realtablename,$constrance[1]);
			}

			$query='ALTER TABLE '.$realtablename.' CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT';

			$db->setQuery( $query );
			$db->execute();
			
			$msg='';
			return true;
		}
		elseif($fieldname=='published')
			$query='ALTER TABLE '.$realtablename.' CHANGE published published TINYINT NOT NULL DEFAULT 1';
		else
			$query='ALTER TABLE '.$realtablename.' CHANGE '.$fieldname.' '.$fieldname.' '.$PureFieldType;

		try
		{
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
		$db = Factory::getDBO();

		$jinput = Factory::getApplication()->input;

		if($fieldid==0)
			$fieldid=Factory::getApplication()->input->get('fieldid',0,'INT');

		$query = 'SELECT fieldname FROM #__customtables_fields AS s WHERE s.published=1 AND s.id='.$fieldid.' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return '';

		return $rows[0]->fieldname;
	}

	public static function getFieldRow($fieldid = 0)
	{
		$db = Factory::getDBO();

		$jinput = Factory::getApplication()->input;

		if($fieldid==0)
			$fieldid=Factory::getApplication()->input->get('fieldid',0,'INT');

		$query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE id='.$fieldid.' LIMIT 1';//published=1 AND 

		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if(count($rows)!=1)
			return array();

		return $rows[0];
	}

	public static function getFields($tableid_or_name,$as_object=false,$order_fields = true)
	{
		$db = Factory::getDBO();
		
		if($order_fields)
			$order=' ORDER BY f.ordering, f.fieldname';
		else
			$order='';

        if((int)$tableid_or_name>0)
            $query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS f WHERE f.published=1 AND f.tableid='.(int)$tableid_or_name.$order;
        else
        {
            $w1='(SELECT t.id FROM #__customtables_tables AS t WHERE t.tablename='.$db->quote($tableid_or_name).' LIMIT 1)';
            $query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS f WHERE f.published=1 AND f.tableid='.$w1.$order;
        }

		$db->setQuery( $query );

        if($as_object)
            return $db->loadObjectList();
        else
            return $db->loadAssocList();
	}

	public static function getFieldRowByName($fieldname, $tableid=0,$sj_tablename='')
	{
		$db = Factory::getDBO();

		if($fieldname=='')
			return array();

		if($sj_tablename=='')
			$query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname='.$db->quote(trim($fieldname)).' LIMIT 1';
		else
		{
			$query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS s

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
		$db = Factory::getDBO();

		if($fieldname=='')
			$fieldname=Factory::getApplication()->input->get('fieldname','','CMD');

		if($fieldname=='')
			return array();

		$query = 'SELECT '.Fields::getFieldRowSelects().' FROM #__customtables_fields AS s WHERE s.published=1 AND tableid='.(int)$tableid.' AND fieldname="'.trim($fieldname).'" LIMIT 1';
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

	public static function FieldRowByName($fieldname,&$ctfields)
	{
		foreach($ctfields as $field)
		{
			if($field['fieldname']==$fieldname)
			{
				return $field;
			}
		}	
		return array();
	}

	public static function getRealFieldName($fieldname,&$ctfields)
	{
		foreach($ctfields as $row)
		{
			if($row['allowordering']==1 and $row['fieldname']==$fieldname)
				return $row['realfieldname'];
		}
		return '';
	}
	
	protected static function getFieldRowSelects()
	{
		$db = Factory::getDBO();

		if($db->serverType == 'postgresql')
			$realfieldname_query='CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
		else
			$realfieldname_query='IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';
		
        return '*, '.$realfieldname_query;
	}
	
	/*
	public static function checkField($ExistingFields,$realtablename,$proj_field,$type)
    {
		$db = Factory::getDBO();

		$found=false;

        foreach($ExistingFields as $existing_field)
        {
            if($proj_field==$existing_field['column_name'])
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
	*/
	
	public static function shortFieldObjects(&$ctfields)
	{
		$field_objects = [];
		
		foreach($ctfields as $esfield)
			$field_objects[] = Fields::shortFieldObject($esfield,null,[]);

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
