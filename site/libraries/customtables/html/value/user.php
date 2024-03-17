<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Value_user extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): ?string
	{
		return self::renderUserValue($this->rowValue, $this->option_list[0] ?? '', $this->option_list[1] ?? null);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function renderUserValue(?int $value, string $field = '', ?string $format = null): ?string
	{
		if ($value === null)
			return null;

		if ($field == 'online') {
			if (empty($value))
				return null;

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('userid', $value);

			$options = database::loadAssocList('#__session', ['userid'], $whereClause, null, null, 1);
			if (count($options) == 0)
				return 0;
			else
				return 1;
		} elseif ($field == 'usergroups') {
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('user_id', $value);

			$groups = database::loadObjectList('#__user_usergroup_map AS m', ['GROUP_TITLE'], $whereClause);
			$group_list = [];

			foreach ($groups as $group)
				$group_list[] = $group->group_title;

			return implode(',', $group_list);
		} else {
			$allowedFields = array('id', 'name', 'email', 'username', 'registerdate', 'lastvisitdate');

			$field = strtolower($field);
			if ($field == '')
				$field = 'name';
			elseif (!in_array($field, $allowedFields))
				return 'Wrong user field "' . $field . '". Available fields: id, name, email, username, registerdate, lastvisitdate, online.';

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('id', $value);

			$rows = database::loadAssocList('#__users', ['id', 'name', 'username', 'email', 'registerDate', 'lastvisitDate'], $whereClause, null, null, 1);

			if (count($rows) != 0) {
				$row = $rows[0];

				if ($field == 'registerdate') {
					return common::formatDate($row['registerDate'], $format, 'Never');
				} elseif ($field == 'lastvisitdate')
					return common::formatDate($row['lastvisitDate'], $format, 'Never');
				else
					return $row[$field];
			} else {
				if ($value != 0)
					return common::translate('COM_CUSTOMTABLES_FIELDS_USER_NOT_FOUND');
			}
		}
		return '';
	}
}