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

use CustomTables\Fields;

use \Joomla\CMS\Factory;

use \ESTables;

class IntegrityFieldType_FileBox extends \CustomTables\IntegrityChecks
{
	public static function checkFileBox(&$ct,$filebox_table_name,$tablename,$fieldname)
	{
		$db = Factory::getDBO();
		
		if($db->serverType == 'postgresql')
			$field_columns=(object)['columnname' => 'column_name', 'data_type'=>'data_type', 'is_nullable'=>'is_nullable', 'default'=>'column_default'];
		else
			$field_columns=(object)['columnname' => 'Field', 'data_type'=>'Type', 'is_nullable'=>'Null', 'default'=>'Default'];
		

        if(!ESTables::checkIfTableExists($filebox_table_name))
        {
            Fields::CreateFileBoxTable($tablename,$fieldname);
            echo '<p>File Box Table "<span style="color:green;">'.$filebox_table_name.'</span>" <span style="color:green;">Created.</span></p>';
        }
                    $g_ExistingFields=Fields::getExistingFields($filebox_table_name,false);

                    $morethanonelang=false;
                    foreach($ct->Languages->LanguageList as $lang)
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
                            Fields::AddMySQLFieldNotExist($filebox_table_name, $g_fieldname, 'varchar(100) null', '');
                            echo '<p>File Box Field "<span style="color:green;">'.$g_fieldname.'</span>" <span style="color:green;">Added.</span></p>';
                        }

                        $morethanonelang=true;
                    }
    }
}