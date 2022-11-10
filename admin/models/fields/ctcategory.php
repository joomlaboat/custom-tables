<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

//https://docs.joomla.org/Creating_a_custom_form_field_type
class JFormFieldCTCategory extends JFormFieldList
{
    /**
     * Element name
     *
     * @access    public
     * @var        string
     *
     */
    public $type = 'ctcategory';

    public function getOptions($add_empty_option = true)//$name, $value, &$node, $control_name)
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id,categoryname');
        $query->from('#__customtables_categories');
        $query->order('categoryname');
        $query->where('published=1');

        $db->setQuery((string)$query);
        $records = $db->loadObjectList();

        $options = array();
        if ($records) {
            if ($add_empty_option)
                $options[] = JHtml::_('select.option', '', Text::_('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'));

            foreach ($records as $rec)
                $options[] = JHtml::_('select.option', $rec->id, $rec->categoryname);
        }
        return $options;
    }
}
