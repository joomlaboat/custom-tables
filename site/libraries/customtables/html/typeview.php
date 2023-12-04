<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;

class TypeView
{
	public static function user($value, $field = '')
	{
		if ($field == 'online') {
			$query = 'SELECT userid FROM #__session WHERE userid=' . (int)$value . ' LIMIT 1';
			$options = database::loadAssocList($query);
			if (count($options) == 0)
				return 0;
			else
				return 1;
		} elseif ($field == 'usergroups') {
			$selects = '(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1) AS group_title';
			$query = 'SELECT ' . $selects . ' FROM #__user_usergroup_map AS m WHERE user_id=' . (int)$value;
			$groups = database::loadObjectList($query);
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
				return 'wrong field "' . $field . '" !';

			$query = 'SELECT id, name, username, email, registerDate,lastvisitDate FROM #__users WHERE id=' . (int)$value . ' LIMIT 1';
			$rows = database::loadAssocList($query);

			if (count($rows) != 0) {
				$row = $rows[0];
				if (($field == 'registerDate' or $field == 'lastvisitDate') and $row[$field] == '0000-00-00 00:00:00')
					return 'Never';

				if ($field == 'registerdate')
					return $row['registerDate'];
				elseif ($field == 'lastvisitdate')
					return $row['lastvisitDate'];
				else
					return $row[$field];
			} else {
				if ((int)$value != 0)
					return common::translate('COM_CUSTOMTABLES_FIELDS_USER_NOT_FOUND');
			}
		}
		return '';
	}

	public static function userGroup($value): string
	{
		$query = 'SELECT #__usergroups.title AS name FROM #__usergroups WHERE id=' . (int)$value . ' LIMIT 1';
		$options = database::loadObjectList($query);

		if (count($options) == 0)
			return '';

		return $options[0]->name;
	}

	//Unused
	public static function userGroups($valueArrayString): string
	{
		$query = 'SELECT #__usergroups.title AS name FROM #__usergroups';
		$where = [];
		$valueArray = explode(',', $valueArrayString);

		foreach ($valueArray as $value) {
			if ($value != '') {
				$where[] = 'id=' . (int)$value;
			}
		}

		$query .= ' WHERE ' . implode(' OR ', $where) . ' ORDER BY title';
		$options = database::loadObjectList($query);

		if (count($options) == 0)
			return '';

		$groups = array();
		foreach ($options as $opt)
			$groups[] = $opt->name;

		return implode(',', $groups);
	}

	public static function tableJoin(Field &$field, string $layoutcode, $listing_id): string
	{
		$ct = new CT;
		$ct->getTable($field->params[0]);

		//TODO: add selector to the output box
		//$selector = $field->params[6] ?? 'dropdown';

		$row = $ct->Table->loadRecord($listing_id);

		$twig = new TwigProcessor($ct, $layoutcode);

		$value = $twig->process($row);

		if ($twig->errorMessage !== null)
			$ct->errors[] = $twig->errorMessage;

		return $value;
	}

	public static function tableJoinList(Field &$field, ?string $rowValue, array $option_list = []): string
	{
		if ($rowValue === null)
			return '';

		$ct = new CT;
		$ct->getTable($field->params[0]);

		if (count($option_list) == 0)
			$fieldName = $field->params[1];
		else
			$fieldName = $option_list[0];

		$fieldNameParts = explode(':', $fieldName);

		if (count($fieldNameParts) == 2) {
			//It's not a fieldname but layout. Example: tablelesslayout:PersonName
			if ($fieldNameParts[0] == 'tablelesslayout' or $fieldNameParts[0] == 'layout') {
				$Layouts = new Layouts($ct);
				$layoutCode = $Layouts->getLayout($fieldNameParts[1]);

				if ($layoutCode == '')
					throw new Exception('Table Join List value layout not found. (' . $fieldName . ')');
			} else
				throw new Exception('Table Join List value layout syntax invalid. (' . $fieldName . ')');
		} else
			$layoutCode = '{{ ' . $fieldName . ' }}';

		if (($options[2] ?? '') != '')
			$separatorCharacter = $options[1];
		else
			$separatorCharacter = ',';

		require_once('typeview_tablejoinlist.php');
		return TypeView_TableJoinList::resolveRecordTypeValue($field, $layoutCode, $rowValue, '', $separatorCharacter);
	}
}