<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use Joomla\CMS\Language\Text;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

//https://docs.joomla.org/Creating_a_custom_form_field_type
class JFormFieldCTTable extends JFormFieldList
{
    public $type = 'cttable';

    public function getOptions($add_empty_option = true)//$name, $value, &$node, $control_name)
    {
        $query = 'SELECT id,tabletitle FROM #__customtables_tables WHERE published=1 ORDER BY tabletitle';
        $records = database::loadObjectList((string)$query);

        $options = array();
        if ($records) {
            if ($add_empty_option)
                $options[] = JHtml::_('select.option', '', Text::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'));

            foreach ($records as $rec)
                $options[] = JHtml::_('select.option', $rec->id, $rec->tabletitle);
        }
        return $options;
    }
}
