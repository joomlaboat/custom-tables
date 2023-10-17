<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class JHTMLCTFields
{
    public static function fields($tableid, $currentFieldId, $control_name, $value)
    {
        $query = 'SELECT id, fieldname '
            . ' FROM #__customtables_fields '
            . ' WHERE published=1 AND tableid=' . (int)$tableid . ' AND id!=' . (int)$currentFieldId
            . ' AND type="checkbox"'
            . ' ORDER BY fieldname';

        $fields = database::loadAssocList($query);
        if (!$fields) $fields = array();

        $fields[] = array('id' => '0', 'fieldname' => '- ROOT');

        return JHTML::_('select.genericlist', $fields, $control_name, 'class="inputbox"', 'id', 'fieldname', $value);
    }
}
