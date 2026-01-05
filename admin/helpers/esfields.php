<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

class JHTMLCTFields
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function fields($tableid, $currentFieldId, $control_name, $value)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', (int)$tableid);
		$whereClause->addCondition('id', (int)$currentFieldId, '!=');
		$whereClause->addCondition('type', 'checkbox');

		$fields = database::loadAssocList('#__customtables_fields', ['id', 'fieldname'], $whereClause, 'fieldname', null);
		if (!$fields) $fields = array();

		$fields[] = array('id' => '0', 'fieldname' => '- ROOT');

		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$default_class = 'form-select';
		else
			$default_class = 'inputbox';

		return HTMLHelper::_('select.genericlist', $fields, $control_name, 'class="' . $default_class . '"', 'id', 'fieldname', $value);
	}
}
