<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\HTML\HTMLHelper;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class JHTMLCTFields
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function fields($tableid, $currentFieldId, $control_name, $value)
	{
		/*		$query = 'SELECT id, fieldname '
					. ' FROM #__customtables_fields '
					. ' WHERE published=1 AND tableid=' . (int)$tableid . ' AND id!=' . (int)$currentFieldId
					. ' AND type="checkbox"'
					. ' ORDER BY fieldname';
		*/
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addCondition('tableid', (int)$tableid);
		$whereClause->addCondition('id', (int)$currentFieldId, '!=');
		$whereClause->addCondition('type', 'checkbox');

		$fields = database::loadAssocList('#__customtables_fields', ['id', 'fieldname'], $whereClause, 'fieldname', null);
		if (!$fields) $fields = array();

		$fields[] = array('id' => '0', 'fieldname' => '- ROOT');

		return HTMLHelper::_('select.genericlist', $fields, $control_name, 'class="inputbox"', 'id', 'fieldname', $value);
	}
}
