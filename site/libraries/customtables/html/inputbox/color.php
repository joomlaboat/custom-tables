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

use Joomla\CMS\Factory;

class InputBox_color extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetAlnum($this->ct->Env->field_prefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		$showAlpha = $this->option_list[0] == 'transparent';

		if (isset($this->option_list[1]) and $this->option_list[1] != "")
			$palette = explode(',', $this->option_list[1]);
		else
			$palette = null;

		// Create the color picker field
		$elementId = $this->attributes['id'];

		// Include necessary CSS and JavaScript files for Spectrum Color Picker

		if (defined('_JEXEC')) {
			$app = Factory::getApplication();
			$document = $app->getDocument();

			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/spectrum.js"></script>');
			$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/spectrum.css" type="text/css" rel="stylesheet" >');
		}

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

		self::addCSSClass($this->attributes, 'color-picker-class');

		$this->attributes['type'] = 'text';
		$this->attributes['value'] = $value;
		return '<input ' . self::attributes2String($this->attributes) . ' />' . $js;
	}
}