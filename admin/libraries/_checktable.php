<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage libraries/_checktable.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');

function checkTableOnly($tables_rows)
{
    $conf = JFactory::getConfig();
    $dbprefix = $conf->get('dbprefix');

    foreach($tables_rows as $row)
    {
		if(!ESTables::checkIfTableExists($dbprefix.'customtables_table_'.$row->tablename))
        {
			$conf = JFactory::getConfig();
			$database = $conf->get('db');
			$dbprefix = $conf->get('dbprefix');
		
			if(ESTables::createTableIfNotExists($database,$dbprefix,$row->tablename,$row->tabletitle,$row->customtablename))
				echo '<p>Table "<span style="color:green;">'.$row->tabletitle.'</span>" <span style="color:green;">added.</span></p>';
        }

    }
}

function checkTableFields($tableid,$tablename,$tabletitle,$customtablename)
{
	if($customtablename!='')
		return;
		
	$db = JFactory::getDBO();
    $conf = JFactory::getConfig();
	$database = $conf->get('db');
	$dbprefix = $conf->get('dbprefix');

	if(ESTables::createTableIfNotExists($database,$dbprefix,$tablename,$tabletitle,$customtablename))
		echo '<p>Table "<span style="color:green;">'.$tabletitle.'</span>" <span style="color:green;">added.</span></p>';

	if($customtablename!='')
		$realtablename=$customtablename;
	else
		$realtablename='#__customtables_table_'.$tablename;
    
    $ExistingFields=ESFields::getExistingFields($realtablename, false);
	
	$jinput = JFactory::getApplication()->input;
    $LangMisc	= new ESLanguages;
    $languages=$LangMisc->getLanguageList();

    checkOptionsTitleFields($languages);

    $link=JURI::root().'administrator/index.php?option=com_customtables&view=listoffields&tableid='.$tableid;

	$projected_fields = ESFields::getFields($tableid,false);

    //Delete unnesesary fields:
    $projected_fields[]=['realfieldname'=>'id','type'=>'_id','typeparams'=>''];
    $projected_fields[]=['realfieldname'=>'published','type'=>'_published','typeparams'=>''];


    $task=$jinput->getCmd('task');
    $taskfieldname=$jinput->    getCmd('fieldname');
	
	
	$db = JFactory::getDBO();
		
	if($db->serverType == 'postgresql')
		$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
	else
		$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];
			
	foreach($ExistingFields as $existing_field)
    {
		
		
        $field_mysql_type=$existing_field[$field_columns->data_type];
        if($existing_field[$field_columns->is_nullable]=='YES')
            $field_mysql_type.=' NULL';
        else
            $field_mysql_type.=' NOT NULL';

        $default=$existing_field[$field_columns->default];
        if($default!=null)
            $field_mysql_type.=' DEFAULT '.$default;

        $exst_field=$existing_field[$field_columns->columnname];
        $found=false;
        $found_field='';
        $found_fieldparams='';
        foreach($projected_fields as $projected_field)
        {
			if($projected_field['realfieldname']=='id')
            {
				$found=true;
				$PureFieldType='_id';
			}
            else if($projected_field['type']=='multilangstring' or $projected_field['type']=='multilangtext')
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
                    $gallery_table_name='#__customtables_gallery_'.$establename.'_'.$projected_field['fieldname'];//.'_imagegallery';
                    checkGalleryTitleFields($gallery_table_name,$languages,$establename,$projected_field['fieldname']);

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
                    $filebox_table_name='#__customtables_filebox_'.$establename.'_'.$projected_field['fieldname'];
                    checkFilesTitleFields($filebox_table_name,$languages,$establename,$projected_field['fieldname']);

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
            if($task=='deleteurfield' and $taskfieldname==$exst_field)
            {
                if(ESFields::deleteMYSQLField($complete_table_name,$exst_field))
                    echo '<p>Field "<span style="color:green;">'.$exst_field.'</span>" not registered. <span style="color:green;">Deleted.</span></p>';
            }
            else
                echo '<p>Field "<span style="color:red;">'.$exst_field.'</span>" not registered. <a href="'.$link.'&task=deleteurfield&fieldname='.$exst_field.'">Delete?</a></p>';
        }
        else
        {

            if($PureFieldType!='_id' and strtolower($field_mysql_type)!=strtolower($PureFieldType))
            {
                if($task=='fixfieldtype' and $taskfieldname==$exst_field)
                {
                    if(ESFields::fixMYSQLField($complete_table_name,$found_field,$PureFieldType))
                    {
                        echo '<p>Field "<span style="color:green;">'.str_replace('es_','',$found_field).'</span>" Fixed';
                    }
                }
                else
                {
                    echo '<p>Field "<span style="color:orange;">'.str_replace('es_','',$found_field).' ('.$found_fieldparams.')</span>"'
                        .' has wrong type "<span style="color:red;">'.$field_mysql_type.'</span>" instead of "<span style="color:green;">'.$PureFieldType.'</span>" <a href="'.$link.'&task=fixfieldtype&fieldname='.$exst_field.'">Fix?</a></p>';
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
        {
            checkField($ExistingFields,$tablename,$proj_field,$fieldtype,$projected_field['typeparams'],$languages);

        }

    }

}
    function checkOptionsTitleFields(&$languages)
    {
		$db = JFactory::getDBO();
		
		$column_name_field='';
		if($db->serverType == 'postgresql')
			$column_name_field = 'column_name';
		else
			$column_name_field = 'Field';
		
        $table_name='#__customtables_options';

        $g_ExistingFields=ESFields::getExistingFields($table_name,false);

        $morethanonelang=false;
		foreach($languages as $lang)
        {
           	$g_fieldname='title';
            if($morethanonelang)
				$g_fieldname.='_'.$lang->sef;

            $g_found=false;

            foreach($g_ExistingFields as $g_existing_field)
            {
				$g_exst_field=$g_existing_field[$column_name_field];
                if($g_exst_field==$g_fieldname)
                {
					$g_found=true;
                    break;
                }
            }

            if(!$g_found)
            {
				ESFields::AddMySQLFieldNotExist($table_name, $g_fieldname, 'varchar(100) NULL', '');
                echo '<p>Options Field "<span style="color:green;">'.$g_fieldname.'</span>" <span style="color:green;">added.</span></p>';
            }
			$morethanonelang=true;
        }
    }

    function checkGalleryTitleFields($gallery_table_name,&$languages,$establename,$esfieldname)
    {

        if(!ESTables::checkIfTableExists($gallery_table_name))
        {

            ESFields::CreateImageGalleryTable($establename,$esfieldname);
            echo '<p>Image Gallery Table "<span style="color:green;">'.$gallery_table_name.'</span>" <span style="color:green;">Created.</span></p>';

        }

                    $g_ExistingFields=ESFields::getExistingFields($gallery_table_name,false);

                    $morethanonelang=false;
                    foreach($languages as $lang)
                    {
                    	$g_fieldname='title';
                    	if($morethanonelang)
                    		$g_fieldname.='_'.$lang->sef;

                        $g_found=false;

                        foreach($g_ExistingFields as $g_existing_field)
                        {
                            $g_exst_field=$g_existing_field['Field'];
                            if($g_exst_field==$g_fieldname)
                            {
                                $g_found=true;
                                break;
                            }
                        }

                        if(!$g_found)
                        {
                            ESFields::AddMySQLFieldNotExist($gallery_table_name, $g_fieldname, 'varchar(100) NULL', '');
                            echo '<p>Image Gallery Field "<span style="color:green;">'.$g_fieldname.'</span>" <span style="color:green;">Added.</span></p>';
                        }

                        $morethanonelang=true;
                    }
    }

    function checkFilesTitleFields($filebox_table_name,&$languages,$establename,$esfieldname)
    {
		$db = JFactory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];
		

        if(!ESTables::checkIfTableExists($filebox_table_name))
        {
            ESFields::CreateFileBoxTable($establename,$esfieldname);
            echo '<p>File Box Table "<span style="color:green;">'.$filebox_table_name.'</span>" <span style="color:green;">Created.</span></p>';
        }

                    $g_ExistingFields=ESFields::getExistingFields($filebox_table_name,false);

                    $morethanonelang=false;
                    foreach($languages as $lang)
                    {
                    	$g_fieldname='title';
                    	if($morethanonelang)
                    		$g_fieldname.='_'.$lang->sef;

                        $g_found=false;

                        foreach($g_ExistingFields as $g_existing_field)
                        {
                            $g_exst_field=$g_existing_field[$field_columns->columnname];
                            if($g_exst_field==$g_fieldname)
                            {
                                $g_found=true;
                                break;
                            }
                        }

                        if(!$g_found)
                        {
                            ESFields::AddMySQLFieldNotExist($filebox_table_name, $g_fieldname, 'varchar(100) NULL', '');
                            echo '<p>File Box Field "<span style="color:green;">'.$g_fieldname.'</span>" <span style="color:green;">Added.</span></p>';
                        }

                        $morethanonelang=true;
                    }
    }

    function addField($establename,$fieldname,$fieldtype,$typeparams)
    {
        $PureFieldType=ESFields::getPureFieldType($fieldtype,$typeparams);
        ESFields::AddMySQLFieldNotExist('#__customtables_table_'.$establename, $fieldname, $PureFieldType, '');

        echo '<p>Field "<span style="color:green;">'.$fieldname.'</span>" <span style="color:green;">Added.</span></p>';
    }

    function checkField($ExistingFields,$tablename,$proj_field,$fieldtype,$typeparams,&$languages)
    {
		$db = JFactory::getDBO();
		
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
                    addField($tablename,$fieldname,$fieldtype,$typeparams);
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
                addField($tablename,$proj_field,$fieldtype,$typeparams);
        }
    }
