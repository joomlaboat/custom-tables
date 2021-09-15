<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class extraTasks
{
	public static function prepareJS()
	{
		$input	= JFactory::getApplication()->input;
		
		$fieldid=(int)$input->getInt('fieldid',0);
		if($fieldid==0)
			return;
		
		$field_row=ESFields::getFieldRow($fieldid);
		$tableid=$field_row->tableid;
		$table_row=ESTables::getTableRowByID($tableid);
		
		$document = JFactory::getDocument();
		$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/extratasks.js"></script>');
		$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/modal.js"></script>');
		$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/modal.css" rel="stylesheet">');
		$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/base64.js"></script>');

		$extratask=$input->getCmd('extratask','');
		
		if($extratask=='updateimages')
		{
			$js='
		<script>
		window.addEventListener( "load", function( event ) {
		ctExtraUpdateImages(\''.$input->get('old_typeparams','','BASE64').'\',\''.$input->get('new_typeparams','','BASE64').'\','.(int)$fieldid.',\''.$table_row->tabletitle.'\',\''.$field_row->fieldtitle.'\');
	});
		</script>
';
			$document->addCustomTag($js);
		}
	}
}
