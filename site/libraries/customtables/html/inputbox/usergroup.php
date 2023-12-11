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

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;

class InputBox_usergroup extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.0
	 */
	function render(?string $value, ?string $defaultValue, bool $showUserWithRecords = false): string
	{
		if ($this->ct->Env->user->id === null)
			return '';

		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname);
			if (!$value)
				$value = $defaultValue;
		}

		self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

		//Build Query
		$query = $this->buildQuery($showUserWithRecords);

		try {
			$options = database::loadObjectList($query);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		// Start building the select element with attributes
		$select = '<select ' . self::attributes2String($this->attributes) . '>';

		// Optional default option
		$selected = (0 === (int)$value) ? ' selected' : '';
		$select .= '<option value=""' . $selected . '> - ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		// Generate options for each file in the folder
		foreach ($options as $option) {
			$selected = ($option->id === (int)$value) ? ' selected' : '';
			$select .= '<option value="' . $option->id . '"' . $selected . '>' . $option->name . '</option>';
		}
		$select .= '</select>';
		return $select;
	}

	protected function buildQuery(bool $showUserWithRecords = false): string
	{
		$query = 'SELECT #__usergroups.id AS id, #__usergroups.title AS name FROM #__usergroups';

		if ($showUserWithRecords)
			$query .= ' INNER JOIN ' . $this->ct->Table->realtablename . ' ON ' . $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__usergroups.id';

		$where = [];

		$availableUserGroupsList = ($this->field->params[0] == '' ? [] : $this->field->params);

		if (count($availableUserGroupsList) == 0) {
			$where [] = '#__usergroups.title!=' . database::quote('Super Users');
		} else {
			$whereOr = [];
			foreach ($availableUserGroupsList as $availableUserGroup) {
				if ($availableUserGroup != '')
					$whereOr[] = '#__usergroups.title=' . database::quote($availableUserGroup);
			}
			$where = '(' . implode(' OR ', $whereOr) . ')';
		}

		if (count($where) > 0)
			$query .= ' WHERE ' . implode(' AND ', $where);

		$query .= ' GROUP BY #__usergroups.id';
		$query .= ' ORDER BY #__usergroups.title';
		return $query;
	}
}