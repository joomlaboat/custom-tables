<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\Languages;
use Joomla\CMS\Version;
use Joomla\CMS\Form\FormHelper;

class CTTypes
{
	public static function getField($type, $attributes, $field_value = '')
	{
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		FormHelper::loadFieldClass($type);

		try {

			if ($version < 4)
				$xml = new JXMLElement('<?xml version="1.0" encoding="utf-8"?><field />');
			else
				$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><field />');

			foreach ($attributes as $key => $value) {
				if ('_options' == $key) {
					foreach ($value as $_opt_value) {
						$xml->addChild('option', $_opt_value->text)->addAttribute('value', $_opt_value->value);
					}
					continue;
				}
				$xml->addAttribute($key, $value);
			}

			//$class = 'FormHelper' . $type;
			$class = $type;

			$field = new $class();
			$field->setup($xml, $field_value);

			return $field;
		} catch (Exception $e) {
			return false;
		}
	}

	public static function language(string $elementId, ?int $value = null, array $attributes = []): string
	{
		$lang = new Languages();

		// Start building the select element with attributes
		$select = '<select id="' . htmlspecialchars($elementId) . '" name="' . htmlspecialchars($elementId) . '"';

		// Add attributes
		foreach ($attributes as $key => $attr) {
			$select .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
		}

		$select .= '>';

		$select .= '<option value="">' . common::translate('COM_CUSTOMTABLES_SELECT_LANGUAGE') . '</option>'; // Optional default option

		// Generate options for each file in the folder
		foreach ($lang->LanguageList as $language) {
			$selected = ($language->id === $value) ? ' selected' : '';
			$select .= '<option value="' . $language->id . '" ' . $selected . '>' . $language->caption . '</option>';

		}
		$select .= '</select>';
		return $select;
	}

	public static function filelink(string $elementId, string $folderPath, ?string $value = null, array $attributes = []): string
	{
		// Check if the folder exists
		if (is_dir($folderPath)) {
			// Get the list of files in the folder
			$files = scandir($folderPath);

			// Start building the select element with attributes
			$select = '<select id="' . htmlspecialchars($elementId) . '" name="' . htmlspecialchars($elementId) . '"';

			// Add attributes
			foreach ($attributes as $key => $attr) {
				$select .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
			}

			$select .= '>';

			$select .= '<option value="">' . common::translate('COM_CUSTOMTABLES_SELECT_FIL') . '</option>'; // Optional default option

			// Generate options for each file in the folder
			foreach ($files as $file) {
				if ($file !== '.' && $file !== '..' && !is_dir($folderPath . '/' . $file)) {
					$fileValue = htmlspecialchars($file);
					$selected = ($fileValue === $value) ? ' selected' : '';
					$select .= '<option value="' . $fileValue . '"' . $selected . '>' . $fileValue . '</option>';
				}
			}

			$select .= '</select>';

			return $select;
		} else {
			return 'Folder does not exist.';
		}
	}
}
