<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\database;
use CustomTables\DataTypes;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\HTML\HTMLHelper;

//jimport('joomla.form.helper');
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
		$whereClause = new MySQLWhereClause();
		$results = database::loadColumn('#__customtables_fields', ['type'], $whereClause, 'type');

		$translations = DataTypes::fieldTypeTranslation();
		$_filter = array();

		if ($results) {
			// get model
			$results = array_unique($results);

			foreach ($results as $type) {
				// Translate the type selection
				$text = $translations[$type];
				// Now add the type and its text to the options array
				$_filter[] = HTMLHelper::_('select.option', $type, common::translate($text));
			}
		}
		return $_filter;
	}
}
