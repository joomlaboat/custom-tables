<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use CustomTables\database;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldCTOption extends JFormFieldList
{
    /**
     * Element name
     *
     * @access    protected
     * @var        string
     *
     */
    protected $type = 'ctoption';

    protected function getOptions()//$name, $value, &$node, $control_name)
    {
        $currentoptionid = 0;
        if (common::inputGet('id'))
            $currentoptionid = common::inputGetInt('id', 0);

        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id,title');
        $query->from('#__customtables_options');
        $query->order('title');
        $query->where('id!=' . $currentoptionid);

        $records = database::loadObjectList($query);

        $options = array();
        if ($records) {
            $options[] = JHtml::_('select.option', '', Text::_('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
            foreach ($records as $rec)
                $options[] = JHtml::_('select.option', $rec->id, $rec->title);
        }
        return $options;
    }
}
