<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\Fields;
use Joomla\CMS\Factory;

class extraTasks
{
    public static function prepareJS()
    {
        $input = Factory::getApplication()->input;

        $fieldid = $input->getInt('fieldid', 0);
        if ($fieldid == 0)
            return;

        $field_row = Fields::getFieldRow($fieldid);
        $tableid = $field_row->tableid;
        $table_row = ESTables::getTableRowByID($tableid);

        $document = Factory::getDocument();
        $document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/extratasks.js"></script>');
        $document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/modal.js"></script>');
        $document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/base64.js"></script>');
        $document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/modal.css" rel="stylesheet">');

        $extraTask = $input->getCmd('extratask', '');
        $stepSize = $input->getInt('stepsize', 10);

        if ($extraTask != '') {
            $extraTasksUpdate = 'extraTasksUpdate("' . $extraTask . '","' . $input->get('old_typeparams', '', 'BASE64') . '","'
                . $input->get('new_typeparams', '', 'BASE64') . '",' . (int)$tableid . ',' . (int)$fieldid . ',"' . $table_row->tabletitle . '","'
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
