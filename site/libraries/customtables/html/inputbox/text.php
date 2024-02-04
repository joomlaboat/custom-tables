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
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Uri\Uri;

class InputBox_text extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		if (in_array('spellcheck', $this->field->params)) {
			$file_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'thirdparty'
				. DIRECTORY_SEPARATOR . 'jsc' . DIRECTORY_SEPARATOR . 'include.js';

			if (file_exists($file_path)) {
				$this->ct->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/thirdparty/jsc/include.js"></script>');
				$this->ct->document->addCustomTag('<script>$Spelling.SpellCheckAsYouType("' . $this->attributes['id'] . '");</script>');
				$this->ct->document->addCustomTag('<script>$Spelling.DefaultDictionary = "English";</script>');
			}
		}

		if (in_array('rich', $this->field->params)) {
			$w = $this->option_list[2] ?? '100%';
			$h = $this->option_list[3] ?? '300';
			$c = 0;
			$l = 0;
			$editor_name = $this->ct->app->get('editor');
			$editor = Editor::getInstance($editor_name);
			return '<div>' . $editor->display($this->attributes['id'], $value, $w, $h, $c, $l) . '</div>';
		} else {
			return '<textarea ' . self::attributes2String($this->attributes) . '>' . htmlspecialchars($value ?? '') . '</textarea>';
		}
	}
}