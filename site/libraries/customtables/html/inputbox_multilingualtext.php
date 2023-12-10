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
use Joomla\CMS\Editor\Editor;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class InputBox_MultilingualText extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render_multilingualText(?string $defaultValue): string
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

			$result .= '<div id="' . $fieldname . '_div" class="multilangtext">';

			if ($this->field->params[0] == 'rich') {
				$result .= '<span class="language_label_rich">' . $lang->caption . '</span>';

				$w = 500;
				$h = 200;
				$c = 0;
				$l = 0;

				$editor_name = $this->ct->app->get('editor');
				$editor = Editor::getInstance($editor_name);

				$fullFieldName = $this->prefix . $fieldname;
				$result .= '<div>' . $editor->display($fullFieldName, $value, $w, $h, $c, $l) . '</div>';
			} else {
				$result .= '<textarea name="' . $this->prefix . $fieldname . '" '
					. 'id="' . $this->prefix . $fieldname . '" '
					. 'data-type="' . $this->field->type . '" '
					. 'class="' . $this->cssclass . ' ' . ($this->field->isrequired == 1 ? 'required' : '') . '">' . htmlspecialchars($value ?? '') . '</textarea>'
					. '<span class="language_label">' . $lang->caption . '</span>';

				$result .= ($this->field->isrequired == 1 ? ' ' . $RequiredLabel : '');
			}
			$result .= '</div>';
		}
		return $result;
	}
}