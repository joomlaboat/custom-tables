<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class InputBox_text extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes);
	}

	/**
	 * @throws Exception
	 * @since 3.9.9
	 */
	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		if (defined('_JEXEC')) {

			if (in_array('spellcheck', $this->field->params)) {

				$file_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'jsc' . DIRECTORY_SEPARATOR . 'include.js';

				if (file_exists($file_path)) {

					$this->ct->LayoutVariables['scripts'][] = URI::root(true) . '/components/com_customtables/thirdparty/jsc/include.js';
					$this->ct->LayoutVariables['script'] .= '$Spelling.SpellCheckAsYouType("' . $this->attributes['id'] . '");';
					$this->ct->LayoutVariables['script'] .= '$Spelling.DefaultDictionary = "English";';
					//$this->ct->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/thirdparty/jsc/include.js"></script>');
					//$this->ct->document->addCustomTag('<script>$Spelling.SpellCheckAsYouType("' . $this->attributes['id'] . '");</script>');
					//$this->ct->document->addCustomTag('<script>$Spelling.DefaultDictionary = "English";</script>');
				}
			}

			$editorType = $this->field->params[0] ?? '';
			if (isset($this->option_list[4]))
				$editorType = $this->option_list[4];

			if ($editorType == 'rich') {
				$w = $this->option_list[2] ?? '100%';
				$h = $this->option_list[3] ?? '300';
				$c = 0;
				$l = 0;
				$editor_name = Factory::getApplication()->get('editor');
				$editor = Editor::getInstance($editor_name);
				return '<div>' . $editor->display($this->attributes['id'], $value, $w, $h, $c, $l) . '</div>';
			} else {
				return '<textarea ' . self::attributes2String($this->attributes) . '>' . htmlspecialchars($value ?? '') . '</textarea>';
			}
		} elseif (defined('WPINC')) {
			// WordPress Handling
			$editorType = $this->field->params[0] ?? '';
			if (isset($this->option_list[4])) {
				$editorType = $this->option_list[4];
			}

			if ($editorType == 'rich') {
				ob_start();
				$editor_settings = [
					'textarea_name' => $this->attributes['id'],
					'media_buttons' => true,
					'textarea_rows' => 10,
					'tinymce' => true,
				];
				wp_editor($value, $this->attributes['id'], $editor_settings);
				return ob_get_clean();
			} else {
				return '<textarea ' . self::attributes2String($this->attributes) . '>' . htmlspecialchars($value ?? '') . '</textarea>';
			}
		} else {
			throw new Exception('Multilingual textarea not supported in the current version of the Custom Tables');
		}
	}
}