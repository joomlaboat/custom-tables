<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

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
			$value = common::inputGetInt($this->ct->Table->fieldPrefix . $this->field->fieldname);
			if (!$value)
				$value = $defaultValue;
		}

		self::selectBoxAddCSSClass($this->attributes);

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

		$from = '#__users';

		if (defined('_JEXEC')) {

			if ($showUserWithRecords)
				$from .= ' INNER JOIN ' . $this->ct->Table->realtablename . ' ON '
					. $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__users.id';

			//User Group Filter
			$userGroups = (($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? '' : '');
			if ($userGroups != '') {
				$from .= ' INNER JOIN #__user_usergroup_map ON user_id=#__users.id';
				$from .= ' INNER JOIN #__usergroups ON #__usergroups.id=#__user_usergroup_map.group_id';

				$ug = explode(",", $userGroups);
				foreach ($ug as $u)
					$whereClause->addOrCondition('#__usergroups.title', $u);
			}


			//Name Filter
			if (isset($this->field->params[3]))
				$whereClause->addCondition('name', '%' . $this->field->params[3] . '%', 'LIKE');

			return database::loadObjectList($from, ['#__users.id AS id', '#__users.name AS name'], $whereClause, '#__users.name', null,
				null, null, 'OBJECT', '#__users.id');


		} elseif (defined('WPINC')) {

			if ($showUserWithRecords)
				$from .= ' INNER JOIN ' . $this->ct->Table->realtablename . ' ON '
					. $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__users.ID';

			// User Role Filter (WordPress)
			$userRoles = (($this->field->params !== null && count($this->field->params) > 0) ? $this->field->params[0] ?? '' : '');
			if ($userRoles != '') {
				$from .= ' INNER JOIN #__usermeta AS um ON um.user_id = #__users.ID AND um.meta_key = "wp_capabilities"';

				$roles = explode(",", $userRoles);
				foreach ($roles as $role) {
					$whereClause->addOrCondition('um.meta_value', '%' . $role . '%', 'LIKE');
				}
			}

			//Name Filter
			if (isset($this->field->params[3]))
				$whereClause->addCondition('display_name', '%' . $this->field->params[3] . '%', 'LIKE');

			return database::loadObjectList($from, ['#__users.ID AS id', '#__users.display_name AS name'], $whereClause, '#__users.display_name', null,
				null, null, 'OBJECT', '#__users.ID');
		} else {

			throw new Exception('The Search by User is not supported in the current version of the Custom Tables.');
		}

	}
}