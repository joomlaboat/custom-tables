<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\Table;
use Joomla\CMS\Factory;

class extraTasks
{
	/**
	 * @throws Exception
	 *
	 * @since 3.0.0
	 */
	public static function prepareJS(Table $table)
	{
		$fieldId = common::inputGetInt('fieldid', 0);
		if ($fieldId == 0)
			return;

		$field_row = $table->getFieldById($fieldId);

		$document = Factory::getApplication()->getDocument();
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/extratasks.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/modal.js"></script>');
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');
		$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" rel="stylesheet">');

		$extraTask = common::inputGetCmd('extratask', '');
		$stepSize = common::inputGetInt('stepsize', 10);

		if ($extraTask != '') {
			$extraTasksUpdate = 'extraTasksUpdate("' . $extraTask . '","' . common::inputGetBase64('old_typeparams', '') . '","'
				. common::inputGetBase64('new_typeparams', '') . '",' . $table->tableid . ',' . (int)$fieldId . ',"' . $table->tabletitle . '","'
				. $field_row['fieldtitle'] . '",' . $stepSize . ');';

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
