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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\DataTypes;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

//https://docs.joomla.org/Creating_a_custom_form_field_type
class JFormFieldCTField extends JFormFieldList
{
    /**
     * Element name
     *
     * @access    public
     * @var        string
     *
     */
    public $type = 'ctfield';

    public function getOptions($add_empty_option = true)//$name, $value, &$node, $control_name)
    {
        // Get a db connection.
        $db = Factory::getDbo();

        // Create a new query object.
        $query = $db->getQuery(true);

        // Select the text.
        $query->select($db->quoteName('type'));
        $query->from($db->quoteName('#__customtables_fields'));
        $query->order($db->quoteName('type'));

        // Reset the query using our newly populated query object.
        $db->setQuery($query);

        $results = $db->loadColumn();

        $translations = DataTypes::fieldTypeTranslation();
        $_filter = array();

        if ($results) {
            // get model
            //$model = $this->getModel();
            $results = array_unique($results);

            foreach ($results as $type) {
                // Translate the type selection
                $text = $translations[$type];
                // Now add the type and its text to the options array
                $_filter[] = JHtml::_('select.option', $type, Text::_($text));
            }
        }
        return $_filter;
    }
}
