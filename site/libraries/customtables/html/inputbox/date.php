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

use Joomla\CMS\HTML\HTMLHelper;

class InputBox_date extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			$value = preg_replace('/[^\0-9]/u', '', $value);

			if ($value == '')
				$value = $defaultValue;
		}

		if ($value == "0000-00-00" or is_null($value))
			$value = '';

		if (isset($this->option_list[2]) and $this->option_list[2] != "")
			$format = $this->phpToJsDateFormat($this->option_list[2]);
		else
			$format = null;

		if ($this->field->params !== null and $this->field->params[0] == 'datetime') {
			$this->attributes['showTime'] = true;
			if ($format === null)
				$format = '%Y-%m-%d %H:%M:%S';
		} else {
			if ($format === null)
				$format = '%Y-%m-%d';
		}

		if (defined('WPINC')) {
			return 'The date field type is not yet supported by the WordPress version of the Custom Tables.';
		} else {
			return HTMLHelper::calendar($value, $this->attributes['name'], $this->attributes['id'], $format, $this->attributes);
		}
	}

	protected function phpToJsDateFormat($phpFormat): string
	{
		$formatConversion = array(
			'Y' => '%Y',  // Year
			'y' => '%y',  // Year
			'm' => '%m',  // Month
			'n' => '%n',  // Month without leading zeros
			'd' => '%d',  // Day of the month
			'j' => '%e',  // Day of the month without leading zeros
			'H' => '%H',  // Hours in 24-hour format
			'i' => '%M',  // Minutes
			's' => '%S',  // Seconds
			// Add more format conversions as needed
		);

		return strtr($phpFormat, $formatConversion);
	}
}