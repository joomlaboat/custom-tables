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
defined('_JEXEC') or die();

use Exception;

class Search_usergroup extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render($value): string
	{
		if ($this->ct->Env->user->id != 0) {
			require_once('inputbox_usergroup.php');
			$this->attributes['id'] = $this->objectName;
			$this->attributes['name'] = $this->objectName;
			$InputBox_UserGroup = new InputBox_UserGroup($this->ct, $this->field, null, [], $this->attributes);
			return $InputBox_UserGroup->render($value, null, true);
		}
		return '';
	}
}