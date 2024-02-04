<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\TableHelper;
use CustomTables\Fields;
use Joomla\CMS\Factory;

class extraTasks
{
	public static function prepareJS()
	{
		$fieldid = common::inputGetInt('fieldid', 0);
		if ($fieldid == 0)
			return;

		$field_row = Fields::getFieldRow($fieldid);
		$tableid = $field_row->tableid;
		$table_row = TableHelper::getTableRowByID($tableid);

		$document = Factory::getDocument();
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/extratasks.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/modal.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');
		$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" rel="stylesheet">');

		$extraTask = common::inputGetCmd('extratask', '');
		$stepSize = common::inputGetInt('stepsize', 10);

		if ($extraTask != '') {
			$extraTasksUpdate = 'extraTasksUpdate("' . $extraTask . '","' . common::inputGetBase64('old_typeparams', '') . '","'
				. common::inputGetBase64('new_typeparams', '') . '",' . (int)$tableid . ',' . (int)$fieldid . ',"' . $table_row->tabletitle . '","'
				. $field_row->fieldtitle . '",' . $stepSize . ');';

			$js = '
		<script>
		window.addEventListener( "load", function( event ) {
		' . $extraTasksUpdate . '
	});
		</script>
';
			$document->addCustomTag($js);
		}
	}
}
