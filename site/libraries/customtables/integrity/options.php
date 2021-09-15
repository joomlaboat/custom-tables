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
use \ESFields;
use \ESLanguages;

class IntegrityOptions extends \CustomTables\IntegrityChecks
{
	public static function checkOptions()
	{
		$jinput = Factory::getApplication()->input;
		$LangMisc	= new ESLanguages;
		$languages=$LangMisc->getLanguageList();
		
		IntegrityOptions::checkOptionsTitleFields($languages);
	}
	
	protected static function checkOptionsTitleFields(&$languages)
    {
		$db = Factory::getDBO();
		
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
				ESFields::AddMySQLFieldNotExist($table_name, $g_fieldname, 'varchar(100) null', '');
				Factory::getApplication()->enqueueMessage('Options Field "'.g_fieldname.'" added.','notice');
            }
			$morethanonelang=true;
        }
    }
}