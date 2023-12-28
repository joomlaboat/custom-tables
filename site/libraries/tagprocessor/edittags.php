<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Twig_Html_Tags;

class tagProcessor_Edit
{
	public static function process(CT &$ct, &$pageLayout, &$row, $getEditFieldNamesOnly = false): array
	{
		$ct_html = new Twig_Html_Tags($ct, false);

		tagProcessor_Edit::process_captcha($ct_html, $pageLayout); //Converted to Twig. Original replaced.

		$buttons = tagProcessor_Edit::process_button($ct_html, $pageLayout);

		$fields = tagProcessor_Edit::process_fields($ct, $pageLayout, $row, $getEditFieldNamesOnly); //Converted to Twig. Original replaced.
		return ['fields' => $fields, 'buttons' => $buttons];
	}

	protected static function process_captcha($ct_html, &$pageLayout): void
	{
		$options = [];
		$captchas = JoomlaBasicMisc::getListToReplace('captcha', $options, $pageLayout, '{}');

		foreach ($captchas as $captcha) {
			$captcha_code = $ct_html->captcha();
			$pageLayout = str_replace($captcha, $captcha_code, $pageLayout);
		}
	}

	protected static function process_button($ct_html, &$pageLayout)
	{
		$options = [];
		$buttons = JoomlaBasicMisc::getListToReplace('button', $options, $pageLayout, '{}');

		if (count($buttons) == 0)
			return null;

		for ($i = 0; $i < count($buttons); $i++) {
			$option = JoomlaBasicMisc::csv_explode(',', $options[$i]);

			if ($option[0] != '')
				$type = $option[0];//button set
			else
				$type = 'save';

			$title = $option[1] ?? '';
			$redirectlink = $option[2] ?? null;
			$optional_class = $option[3] ?? '';

			$b = $ct_html->button($type, $title, $redirectlink, $optional_class);

			$pageLayout = str_replace($buttons[$i], $b, $pageLayout);
		}

		return $ct_html->button_objects;
	}

	protected static function process_fields(CT &$ct, &$pageLayout, &$row, $getEditFieldNamesOnly = false): array
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'esinputbox.php');

		$inputBox = new ESInputBox($ct);

		if ($ct->Params->requiredLabel != '')
			$inputBox->requiredLabel = $ct->Params->requiredLabel;

		//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
		$calendars = array();
		$replaceItCode = common::generateRandomString();
		$items_to_replace = array();

		$field_objects = self::renderFields($ct, $row, $pageLayout, $inputBox, $calendars, $replaceItCode, $items_to_replace, $getEditFieldNamesOnly);

		foreach ($items_to_replace as $item)
			$pageLayout = str_replace($item[0], $item[1], $pageLayout);

		return $field_objects;
	}

	protected static function renderFields(CT &$ct, &$row, &$pageLayout, $inputBox, &$calendars, $replaceItCode, &$items_to_replace, $getEditFieldNamesOnly = false): array
	{
		$field_objects = [];
		$calendars = array();

		//custom layout
		if (!isset($inputBox->ct->Table->fields) or !is_array($inputBox->ct->Table->fields))
			return [];

		for ($f = 0; $f < count($inputBox->ct->Table->fields); $f++) {
			$fieldrow = $inputBox->ct->Table->fields[$f];
			$options = array();
			$entries = JoomlaBasicMisc::getListToReplace($fieldrow['fieldname'], $options, $pageLayout, '[]');

			if (count($entries) > 0) {
				for ($i = 0; $i < count($entries); $i++) {

					if ($getEditFieldNamesOnly) {
						$ct->editFields[] = $fieldrow['fieldname'];
						$newReplaceItCode = '';
					} else {
						$option_list = JoomlaBasicMisc::csv_explode(',', $options[$i]);

						$result = '';

						if ($fieldrow['type'] == 'date')
							$calendars[] = $inputBox->ct->Env->field_prefix . $fieldrow['fieldname'];

						if ($fieldrow['type'] != 'dummy' and !Fields::isVirtualField($fieldrow))
							$result = $inputBox->renderFieldBox($fieldrow, $row, $option_list, '');

						if ($inputBox->ct->Env->frmt == 'json') {
							$field_objects[] = $result;
							$result = '';
						}

						$newReplaceItCode = $replaceItCode . str_pad(count($items_to_replace), 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);
						$items_to_replace[] = array($newReplaceItCode, $result);
					}
					$pageLayout = str_replace($entries[$i], $newReplaceItCode, $pageLayout);
				}
			}
		}

		return $field_objects;
	}
}
