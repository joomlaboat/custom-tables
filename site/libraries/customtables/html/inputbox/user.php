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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;

class InputBox_user extends BaseInputBox
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

		try {
			$options = $this->buildQuery($showUserWithRecords);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return $this->renderSelect($value ?? '', $options);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function buildQuery(bool $showUserWithRecords = false): array
	{
		$whereClause = new MySQLWhereClause();

		//$query = 'SELECT #__users.id AS id, #__users.name AS name FROM #__users';

		$from = '#__users';
		if ($showUserWithRecords)
			$from .= ' INNER JOIN ' . $this->ct->Table->realtablename . ' ON ' . $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__users.id';

		//	$where = [];

		//User Group Filter
		$userGroup = $this->field->params[0] ?? '';
		if ($userGroup != '') {
			$from .= ' INNER JOIN #__user_usergroup_map ON user_id=#__users.id';
			$from .= ' INNER JOIN #__usergroups ON #__usergroups.id=#__user_usergroup_map.group_id';

			$ug = explode(",", $userGroup);
//			$w = array();
			foreach ($ug as $u)
				$whereClause->addOrCondition('#__usergroups.title', $u);
			//$w[] = '#__usergroups.title=' . database::quote($u);

			//if (count($w) > 0)
			//$where [] = '(' . implode(' OR ', $w) . ')';
		}

		//Name Filter
		if (isset($this->field->params[3]))
			$whereClause->addCondition('name', '%' . $this->field->params[3] . '%', 'LIKE');
		//$where [] = 'INSTR(name,"' . $this->field->params[3] . '")';

		//if (count($where) > 0)
		//$query .= ' WHERE ' . implode(' AND ', $where);

		//$query .= ' GROUP BY #__users.id';
		//$query .= ' ORDER BY #__users.name';
		return database::loadObjectList($from, ['#__users.id AS id', '#__users.name AS name'], $whereClause, '#__users.name', null, null, null, 'OBJECT', '#__users.id');
	}
}