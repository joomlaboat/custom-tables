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

use CTTypes;

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

		$result = '';

		$transparent = $this->option_list[0] == 'transparent';
		if (isset($this->option_list[1]) and $this->option_list[1] != "")
			$palette = explode(',', $this->option_list[1]);
		else
			$palette = null;

		// Create the color picker field
		$inputbox = CTTypes::color($this->attributes['id'], $value, $transparent, $palette, $this->attributes);

		$result .= $inputbox;
		return $result;
	}
}