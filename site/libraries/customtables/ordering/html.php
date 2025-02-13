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

class OrderingHTML
{
	public static function getOrderBox(Ordering $ordering, string $listOfFields = null): string
	{
		$listOfFields_Array = !empty($listOfFields) ? explode(",", $listOfFields) : [];
		$lists = $ordering->getSortByFields();

		// Initialize the sorting options with a default "Order By" placeholder
		$fieldsToSort = [
			['value' => '', 'label' => ' - ' . common::translate('COM_CUSTOMTABLES_ORDER_BY')]
		];

		// Filter sorting fields if a list is provided
		if (!empty($listOfFields_Array)) {
			foreach ($lists as $list) {

				$fieldName = trim(strtok($list['value'], " ")); // Extract first part before space

				if (in_array($fieldName, $listOfFields_Array, true))
					$fieldsToSort[] = ['value' => $list['value'], 'label' => $list['label']];
			}
		} else {
			$fieldsToSort = array_merge($fieldsToSort, $lists);
		}

		$moduleIDString = $ordering->Params->ModuleId ?? 'null';
		$defaultClass = CUSTOMTABLES_JOOMLA_MIN_4 ? 'form-control' : 'inputbox';

		$options = [];

		foreach ($fieldsToSort as $sortField) {
			$isSelected = ($ordering->ordering_processed_string === $sortField['value']) ? ' selected' : '';
			$options[] = '<option value="' . htmlspecialchars($sortField['value'], ENT_QUOTES) . '"' . $isSelected . '>'
				. htmlspecialchars($sortField['label'] ?? '', ENT_QUOTES) . '</option>';
		}

		return '<select name="esordering" id="esordering" onChange="ctOrderChanged(this.value, ' . $moduleIDString . ');" class="' . $defaultClass . '">'
			. PHP_EOL . implode(PHP_EOL, $options) . PHP_EOL . '</select>';
	}
}