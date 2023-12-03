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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Form\FormHelper;

class CTTypes
{
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

	public static function color(string $elementId, ?string $value = null, $showAlpha = false, ?array $palette = null, array $attributes = []): string
	{
		// Include necessary CSS and JavaScript files for Spectrum Color Picker
		HTMLHelper::_('stylesheet', 'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css');
		HTMLHelper::_('script', 'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js');

		// JavaScript code to initialize Spectrum Color Picker
		//palette: [ ['#ff0000', '#00ff00', '#0000ff'],['#ffff00', '#ff00ff', '#00ffff'] ]

		$js = '
        <script>jQuery(document).ready(function($) {
            $("#' . $elementId . '").spectrum({
                preferredFormat: "hex", // Set preferred color format
                showInput: true, // Display typed color values
                
                ' . ($palette !== null ? '
                showPalette: true, // Show a palette of basic colors
                palette: [["' . implode('","', $palette) . '"]],' : '') . '
                
                ' . ($showAlpha ? '
                allowEmpty: true, // Allow clearing the color to make it transparent
				showAlpha: true, // Show the alpha channel slider
				' : '') . '
            });
        });
    </script>';

		if (key_exists('class', $attributes)) {
			if (str_contains($attributes['class'], 'color-picker-class')) {
				$attributes['class'] .= ' color-picker-class';
			}
		} else {
			$attributes['class'] = 'color-picker-class';
		}

		$attributesString = '';
		// Add attributes
		foreach ($attributes as $key => $attr) {
			$attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
		}

		return '<input type="text" id="' . $elementId . '" name="' . $elementId . '" value="' . $value . '" ' . $attributesString . ' />' . $js;
	}
}
