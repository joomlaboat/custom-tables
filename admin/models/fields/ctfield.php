<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\DataTypes;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\HTML\HTMLHelper;

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
	 * @since 1.0.0
	 *
	 */
	public $type = 'ctfield';

	public function getOptions($add_empty_option = true): array
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
