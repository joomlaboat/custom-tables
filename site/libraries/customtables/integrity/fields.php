<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage integrity/fields.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
namespace CustomTables\Integrity;
 
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;

use \ESTables;
use \ESFields;
use \ESLanguages;

class IntegrityFields extends \CustomTables\IntegrityChecks
{
	public static function checkFields($tableid,$tablename,$tabletitle,$customtablename,$link)
	{
		require_once('fieldtype_filebox.php');
		require_once('fieldtype_gallery.php');
		
		$result='';
	
		if($customtablename!='')
			return $result;
		
		$db = Factory::getDBO();
		$conf = Factory::getConfig();
		$database = $conf->get('db');
		$dbprefix = $conf->get('dbprefix');

		if(ESTables::createTableIfNotExists($database,$dbprefix,$tablename,$tabletitle,$customtablename))
			$result.='<p>Table "<span style="color:green;">'.$tabletitle.'</span>" <span style="color:green;">added.</span></p>';

		if($customtablename!='')
			$realtablename=$customtablename;
		else
			$realtablename='#__customtables_table_'.$tablename;
    
		$ExistingFields=ESFields::getExistingFields($realtablename, false);
	
		$jinput = Factory::getApplication()->input;
		$LangMisc	= new ESLanguages;
		$languages=$LangMisc->getLanguageList();
  
		$projected_fields = ESFields::getFields($tableid,false);

		//Delete unnesesary fields:
		$projected_fields[]=['realfieldname'=>'id','type'=>'_id','typeparams'=>''];
		$projected_fields[]=['realfieldname'=>'published','type'=>'_published','typeparams'=>''];

		$task=$jinput->getCmd('task');
		$taskfieldname=$jinput->getCmd('fieldname');
		$tasktableid=$jinput->getInt('tableid');
		
		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];
			
		foreach($ExistingFields as $existing_field)
		{
		    $field_mysql_type=strtolower($existing_field[$field_columns->data_type]);
			
			if($existing_field[$field_columns->is_nullable]=='YES')
				$field_mysql_type.=' null';
			else
				$field_mysql_type.=' not null';

			$default=$existing_field[$field_columns->default];
			
			if($default!=null)
				$field_mysql_type.=' default '.$default;

			$exst_field=$existing_field[$field_columns->columnname];
			$found=false;
			$found_field='';
			$found_fieldparams='';
        
			foreach($projected_fields as $projected_field)
			{
				if($projected_field['realfieldname']=='id' and $exst_field=='id')
				{
					$found=true;
					$PureFieldType='_id';
					break;
				}
				elseif($projected_field['realfieldname']=='published' and $exst_field=='published')
				{
					$found=true;
					$PureFieldType='_published';
					break;
				}
				elseif($projected_field['type']=='multilangstring' or $projected_field['type']=='multilangtext')
				{
					$morethanonelang=false;
					foreach($languages as $lang)
					{
						$fieldname=$projected_field['realfieldname'];
						if($morethanonelang)
							$fieldname.='_'.$lang->sef;

						if($exst_field==$fieldname)
						{
							$PureFieldType=ESFields::getPureFieldType($projected_field['type'], $projected_field['typeparams']);
							$found_field=$projected_field['realfieldname'];
							$found_fieldparams=$projected_field['typeparams'];
							$found=true;
							break;
						}
						$morethanonelang=true;
					}
				}
				elseif($projected_field['type']=='imagegallery')
				{
					if($exst_field==$projected_field['realfieldname'])
					{
						$gallery_table_name='#__customtables_gallery_'.$tablename.'_'.$projected_field['fieldname'];
						IntegrityFieldType_Gallery::checkGallery($gallery_table_name,$languages,$tablename,$projected_field['fieldname']);

						$PureFieldType=ESFields::getPureFieldType($projected_field['type'], $projected_field['typeparams']);
						$found_field=$projected_field['realfieldname'];
						$found_fieldparams=$projected_field['typeparams'];
						$found=true;
						break;
					}
				}
				elseif($projected_field['type']=='filebox')
				{
					if($exst_field==$projected_field['realfieldname'])
					{
						$filebox_table_name='#__customtables_filebox_'.$tablename.'_'.$projected_field['fieldname'];
						IntegrityFieldType_FileBox::checkFileBox($filebox_table_name,$languages,$tablename,$projected_field['fieldname']);

						$PureFieldType=ESFields::getPureFieldType($projected_field['type'], $projected_field['typeparams']);
						$found_field=$projected_field['realfieldname'];
						$found_fieldparams=$projected_field['typeparams'];
						$found=true;
						break;
					}
				}
				elseif($projected_field['type']=='dummy')
				{
					if($exst_field==$projected_field['realfieldname'])
					{
						$found=false;
						break;
					}
				}
				else
				{
					if($exst_field==$projected_field['realfieldname'])
					{
						$PureFieldType=ESFields::getPureFieldType($projected_field['type'], $projected_field['typeparams']);
						$found_field=$projected_field['realfieldname'];
						$found_fieldparams=$projected_field['typeparams'];
						$found=true;
						break;
					}
				}
			}

			if(!$found or $PureFieldType=='')
			{
				//Delete field
				if($tableid == $tasktableid and $task=='deleteurfield' and $taskfieldname==$exst_field)
				{
					$msg='';
					if(ESFields::deleteMYSQLField($realtablename,$exst_field,$msg))
						$result.='<p>Field "<span style="color:green;">'.$exst_field.'</span>" not registered. <span style="color:green;">Deleted.</span></p>';
					
					if($msg!='')
							$result.=$msg;
				}
				else
					$result.='<p>Field "<span style="color:red;">'.$exst_field.'</span>" not registered. <a href="'.$link.'&task=deleteurfield&fieldname='.$exst_field.'">Delete?</a></p>';
			}
			else
			{
				if($PureFieldType=='_id')
				{
					//Check ID field auto increment param.
					if($existing_field[$field_columns->is_nullable]=='YES' or $existing_field['Extra'] != 'auto_increment')
					{
						$msg='';
						if(ESFields::fixMYSQLField($realtablename,$found_field,$PureFieldType,$msg))
							$result.=$msg.'<p>Field "<span style="color:green;">id</span>" fixed</p>';
					
						if($msg!='')
							$result.=$msg;
					}
				}
				elseif($PureFieldType=='_published')
				{
					if($existing_field[$field_columns->is_nullable]=='YES')
					{
						$msg='';
						if(ESFields::fixMYSQLField($realtablename,$found_field,$PureFieldType,$msg))
							$result.='<p>Field "<span style="color:green;">published</span>" fixed</p>';
					
						if($msg!='')
							$result.=$msg;
					}
				}
				elseif(!ESFields::comparePureFieldTypes($field_mysql_type,$PureFieldType))
				{
					if($tableid == $tasktableid and $task=='fixfieldtype' and $taskfieldname==$exst_field)
					{
						$msg='';
						if(ESFields::fixMYSQLField($realtablename,$found_field,$PureFieldType,$msg))
							$result.='<p>Field "<span style="color:green;">'.str_replace('es_','',$found_field).'</span>" fixed.</p>';
					
						if($msg!='')
							$result.=$msg;
					}
					else
					{
						$result.='<p>Field "<span style="color:orange;">'.str_replace('es_','',$found_field).' ('.$found_fieldparams.')</span>"'
							.' has wrong type "<span style="color:red;">'.$field_mysql_type.'</span>" instead of "<span style="color:green;">'
							.$PureFieldType.'</span>" <a href="'.$link.'&task=fixfieldtype&fieldname='.$exst_field.'">Fix?</a></p>';
					}
				}
			}
		}

		//Add missing fields
		foreach($projected_fields as $projected_field)
		{
			$proj_field=$projected_field['realfieldname'];
			$fieldtype=$projected_field['type'];
			if($fieldtype!='dummy')
				IntegrityFields::checkField($ExistingFields,$realtablename,$proj_field,$fieldtype,$projected_field['typeparams'],$languages);
        }
	
		return $result;
	}
	
		
	protected static function checkField($ExistingFields,$realtablename,$proj_field,$fieldtype,$typeparams,&$languages)
    {
		$result = '';
		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];

        if($fieldtype=='multilangstring' or $fieldtype=='multilangtext')
        {
            $found=false;
            $morethanonelang=false;
        	foreach($languages as $lang)
        	{
        		$fieldname=$proj_field;
        		if($morethanonelang)
        			$fieldname.='_'.$lang->sef;

                $found=false;
                foreach($ExistingFields as $existing_field)
                {
                    if($fieldname==$existing_field[$field_columns->columnname])
                    {
                        $found=true;
                        break;
                    }
                }

                if(!$found)
                {
                    //Add field
                    addField($realtablename,$fieldname,$fieldtype,$typeparams);
                }

                $morethanonelang=true;
            }
        }
        else
        {
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
                IntegrityFields::addField($realtablename,$proj_field,$fieldtype,$typeparams);
        }
    }
	
	protected static function addField($realtablename,$realfieldname,$fieldtype,$typeparams)
    {
        $PureFieldType=ESFields::getPureFieldType($fieldtype,$typeparams);
        ESFields::AddMySQLFieldNotExist($realtablename, $realfieldname, $PureFieldType, '');

		Factory::getApplication()->enqueueMessage('Field "'.$realfieldname.'" added.','notice');
    }
}