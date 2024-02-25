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

use Joomla\CMS\Editor\Editor;

class InputBox_multilingualtext extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$RequiredLabel = 'Field is required';
		$result = '';
		$firstLanguage = true;
		foreach ($this->ct->Languages->LanguageList as $lang) {
			if ($firstLanguage) {
				$postfix = '';
				$firstLanguage = false;
			} else
				$postfix = '_' . $lang->sef;

			$fieldname = $this->field->fieldname . $postfix;

			$value = null;
			if (isset($this->row) and array_key_exists($this->ct->Env->field_prefix . $fieldname, $this->row)) {
				$value = $this->row[$this->ct->Env->field_prefix . $fieldname];
			} else {
				Fields::addLanguageField($this->ct->Table->realtablename, $this->ct->Env->field_prefix . $this->field->fieldname, $this->ct->Env->field_prefix . $fieldname);
				$this->ct->errors[] = 'Field "' . $this->ct->Env->field_prefix . $fieldname . '" not yet created. Go to /Custom Tables/Database schema/Checks to create that field.';
				$value = '';
			}

			if ($value === null) {
				$value = common::inputGetString($this->ct->Env->field_prefix . $this->field->fieldname, '');
				if ($value == '')
					$value = $defaultValue;
			}

			$result .= ($this->field->isrequired == 1 ? ' ' . $RequiredLabel : '');

			$attributes = $this->attributes;
			$attributes['id'] = $this->attributes['id'] . $postfix;
			$attributes['name'] = $this->attributes['name'] . $postfix;

			$result .= '<div id="' . $fieldname . '_div" class="multilangtext">';

			if ($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] == 'rich') {
				$result .= '<span class="language_label_rich">' . $lang->caption . '</span>';

				$w = 500;
				$h = 200;
				$c = 0;
				$l = 0;

				$editor_name = $this->ct->app->get('editor');
				$editor = Editor::getInstance($editor_name);

				$input = '<div>' . $editor->display($attributes['name'], $value, $w, $h, $c, $l) . '</div>';
			} else {
				$input = '<textarea ' . self::attributes2String($attributes) . '>' . htmlspecialchars($value ?? '') . '</textarea>'
					. '<span class="language_label">' . $lang->caption . '</span>';
			}
			$result .= '<div id="' . $fieldname . '_div" class="multilangtext">' . $input . '</div>';
		}
		return $result;
	}
}