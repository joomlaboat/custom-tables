<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Save_usergroups
{
	var CT $ct;
	public Field $field;
	var ?array $row_new;

	function __construct(CT &$ct, Field $field, ?array $row_new)
	{
		$this->ct = &$ct;
		$this->field = $field;
		$this->row_new = $row_new;
	}

	/**
	 * @throws Exception
	 * @since 3.3.3
	 */
	function saveFieldSet(): ?array
	{
		switch (($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] : '') {
			case 'radio':
			case 'single';
				$value = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
				if (isset($value))
					return ['value' => ',' . $value . ','];

				break;
			case 'multibox':
			case 'checkbox':
			case 'multi';
				$valueArray = common::inputPostArray($this->field->comesfieldname, null, 'create-edit-record');

				if (isset($valueArray) and is_array($valueArray))
					return ['value' => ',' . implode(',', $valueArray) . ','];
				else {
					return ['value' => null];
				}
		}
		return null;
	}
}