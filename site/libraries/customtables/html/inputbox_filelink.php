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

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\HTML\HTMLHelper;

class InputBox_fileLink extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

		$path = CUSTOMTABLES_IMAGES_PATH . DIRECTORY_SEPARATOR . $this->field->params[0] ?? '';

		//Check if the path does not start from the root directory
		if (!empty($path)) {
			if ($path[0] !== '/' && (strlen($path) >= 2 && $path[1] !== ':')) {
				$path = '/images/' . $path;
			}
		}

		$parts = explode('/', $path);
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

		if ($parts[0] == 'images' or (isset($parts[1]) and $parts[1] == 'images')) {
			$relativePath = JPATH_SITE . DIRECTORY_SEPARATOR;
			$real_path = $relativePath . $path; //use path relative to website root directory
		} else {
			$relativePath = '';
			$real_path = $path;//un-relative path
		}

		if (file_exists($real_path)) {
			$options[] = array('id' => '', 'name' => '- ' . common::translate('COM_CUSTOMTABLES_SELECT'),
				'data-type' => "filelink");
			$files = scandir($real_path);
			foreach ($files as $f) {
				if (!is_dir($relativePath . $f) and str_contains($f, '.'))
					$options[] = array('id' => $f, 'name' => $f);
			}
		} else
			$options[] = array('id' => '',
				'data-type' => "filelink",
				'name' => '- ' . common::translate('COM_CUSTOMTABLES_PATH') . ' (' . $path . ') ' . common::translate('COM_CUSTOMTABLES_NOTFOUND'));

		return HTMLHelper::_('select.genericlist', $options, $this->attributes['id'],
			self::attributes2String($this->attributes), 'id', 'name', $value, $this->attributes['id']);
	}
}