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

class InputBox_usergroups extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.0
	 */
	function render(?string $value, ?string $defaultValue): string
	{
		if ($this->ct->Env->user->id === null)
			return '';

		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname);
			if ($value === null)
				$value = $defaultValue;

			$value = preg_replace('/[^\0-9]/u', '', $value);
			if ($value == '')
				$value = null;
		}

		$valueArray = explode(',', $value);

		self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

		try {
			$records = $this->buildQuery();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		$selector = $this->field->params[0];

		return match ($selector) {
			'single' => $this->getSelect($records, $valueArray),
			'multi' => $this->getSelect($records, $valueArray, true),
			'radio' => $this->getRadio($records, $valueArray),
			'checkbox' => $this->getCheckbox($records, $valueArray),
			'multibox' => $this->getMultibox($records, $valueArray),
			default => '<p>Incorrect selector</p>',
		};
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function buildQuery(): array
	{
		$whereClause = new MySQLWhereClause();

		$availableUserGroups = $this->field->params[1] ?? '';
		$availableUserGroupList = (trim($availableUserGroups) == '' ? [] : explode(',', trim($availableUserGroups)));

		if (count($availableUserGroupList) == 0) {
			$whereClause->addCondition('#__usergroups.title', 'Super Users', '!=');
		} else {
			foreach ($availableUserGroupList as $availableUserGroup) {
				if ($availableUserGroup != '')
					$whereClause->addOrCondition('#__usergroups.title', $availableUserGroup);
			}
		}
		return database::loadObjectList('#__usergroups', ['#__usergroups.id AS id', '#__usergroups.title AS name'], $whereClause, '#__usergroups.title');
	}

	protected function getSelect(array $records, array $valueArray, bool $multiple = false, ?string $customElementId = null): string
	{
		$attributes = $this->attributes;

		if ($customElementId !== null) {
			$attributes['id'] = $customElementId;
			$attributes['name'] = $customElementId;
		}

		if ($multiple)
			$attributes['name'] .= '[]';

		$htmlresult = '<SELECT ' . self::attributes2String($attributes) . ($multiple ? ' MULTIPLE' : '') . '>';

		foreach ($records as $row) {
			$htmlresult .= '<option value="' . $row->id . '"'
				. ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' selected' : '')
				. '>' . htmlspecialchars($row->name ?? '') . '</option>';
		}

		$htmlresult .= '</SELECT>';
		return $htmlresult;
	}

	protected function getRadio(array $records, array $valueArray): string
	{
		$htmlresult = '<table style="border:none;" id="usergroups_table_' . $this->attributes['id'] . '">';
		$i = 0;
		foreach ($records as $row) {
			$htmlresult .= '<tr><td style="vertical-align: middle">'
				. '<input type="radio" '
				. 'name="' . $this->attributes['id'] . '" '
				. 'id="' . $this->attributes['id'] . '_' . $i . '" '
				. 'value="' . $row->id . '" '
				. 'data-type="usergroups" '
				. ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
				. ' /></td>'
				. '<td style="vertical-align: middle">'
				. '<label for="' . $this->attributes['id'] . '_' . $i . '">' . $row->name . '</label>'
				. '</td></tr>';
			$i++;
		}
		$htmlresult .= '</table>';

		return $htmlresult;
	}

	protected function getCheckbox(array $records, array $valueArray): string
	{
		$htmlresult = '<table style="border:none;" id="usergroups_table_' . $this->attributes['id'] . '">';
		$i = 0;
		foreach ($records as $row) {
			$htmlresult .= '<tr><td style="vertical-align: middle">'
				. '<input type="checkbox" '
				. 'name="' . $this->attributes['id'] . '[]" '
				. 'id="' . $this->attributes['id'] . '_' . $i . '" '
				. 'value="' . $row->id . '" '
				. 'data-type="usergroups" '
				. ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
				. ' /></td>'
				. '<td style="vertical-align: middle">'
				. '<label for="' . $this->attributes['id'] . '_' . $i . '">' . $row->name . '</label>'
				. '</td></tr>';
			$i++;
		}
		$htmlresult .= '</table>';
		return $htmlresult;
	}

	protected function getMultibox(array $records, array $valueArray): string
	{
		$control_name = $this->attributes['id'];

		$ctInputBoxRecords_r = [];
		$ctInputBoxRecords_v = [];
		$ctInputBoxRecords_p = [];

		foreach ($records as $rec) {
			$row = (array)$rec;
			if (in_array($row['id'], $valueArray) and count($valueArray) > 0) {
				$ctInputBoxRecords_r[] = $row['id'];
				$ctInputBoxRecords_v[] = $row['name'];
				$ctInputBoxRecords_p[] = 1;
			}
		}

		$htmlresult = '
		<script>
			ctInputBoxRecords_r["' . $control_name . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_r) . ';
			ctInputBoxRecords_v["' . $control_name . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_v) . ';
			ctInputBoxRecords_p["' . $control_name . '"] = ' . common::ctJsonEncode($ctInputBoxRecords_p) . ';
		</script>
		';

		$single_box = $this->getSelect($records, $valueArray, false, $control_name . '_selector');

		$htmlresult .= '<div style="padding-bottom:20px;"><div style="width:90%;" id="' . $control_name . '_box"></div>'
			. '<div style="height:30px;">'
			. '<div id="' . $control_name . '_addButton" style="visibility:visible;"><img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/new.png" alt="Add" title="Add" style="cursor: pointer;" '
			. 'onClick="ctInputBoxRecords_addItem(\'' . $control_name . '\',\'_selector\')" /></div>'
			. '<div id="' . $control_name . '_addBox" style="visibility:hidden;">'
			. '<div style="float:left;">' . $single_box . '</div>'
			. '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/plus.png" alt="Add" title="Add" '
			. 'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;" onClick="ctInputBoxRecords_DoAddItem(\'' . $control_name . '\',\'_selector\')" />'
			. '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/cancel.png" alt="Cancel" title="Cancel" style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;" '
			. 'onClick="ctInputBoxRecords_cancel(\'' . $control_name . '\')" />'

			. '</div>'
			. '</div>'
			. '<div style="display:none;"><select name="' . $control_name . '[]" id="' . $control_name . '" MULTIPLE ></select></div>'
			. '</div>
		
		<script>
			ctInputBoxRecords_showMultibox("' . $control_name . '","");
		</script>
		';
		return $htmlresult;
	}
}