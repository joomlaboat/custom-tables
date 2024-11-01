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

class InputBox_multilingualstring extends BaseInputBox
{
    function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
    {
        parent::__construct($ct, $field, $row, $option_list, $attributes);
        self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
    }

    function render(?string $value, ?string $defaultValue): string
    {
        //$value not used here, it read value in getMultilingualStringIte

        $elementId = $this->attributes['id'];

        $result = '';

        //Specific language selected
        if (isset($this->option_list[4])) {
            $language = $this->option_list[4];

            $firstLanguage = true;
            foreach ($this->ct->Languages->LanguageList as $lang) {
                if ($firstLanguage) {
                    $postfix = '';
                    $firstLanguage = false;
                } else
                    $postfix = '_' . $lang->sef;

                if ($language == $lang->sef) {
                    //show single edit box
                    return $this->getMultilingualStringItem($elementId, $postfix, $lang->sef, $defaultValue);
                }
            }
        }

        $this->attributes['type'] = 'text';
        $this->attributes['maxlength'] = (($this->field->params !== null and count($this->field->params) > 0 and (int)$this->field->params[0] > 0) ? (int)$this->field->params[0] : 255);

        //show all languages
        $result .= '<div class="form-horizontal">';

        $firstLanguage = true;
        foreach ($this->ct->Languages->LanguageList as $lang) {
            if ($firstLanguage) {
                $postfix = '';
                $firstLanguage = false;
            } else
                $postfix = '_' . $lang->sef;

            $result .= '
	<div class="control-group">
		<div class="control-label">' . $lang->caption . '</div>
		<div class="controls">' . $this->getMultilingualStringItem($elementId, $postfix, $lang->sef, $defaultValue) . '</div>
	</div>';
        }
        $result .= '</div>';
        return $result;
    }

    protected function getMultilingualStringItem(string $elementId, string $postfix, string $langSEF, ?string $defaultValue): string
    {
        $value = $this->row[$this->field->realfieldname . $postfix] ?? null;
        if ($value === null) {
            $value = common::inputGetString($this->ct->Table->fieldPrefix . $this->field->fieldname . $postfix, '');
            if ($value == '')
                $value = $defaultValue;
        }

        $attributes = $this->attributes;
        $attributes['id'] = $elementId . $postfix;
        $attributes['name'] = $elementId . $postfix;
        $attributes['value'] = $value;

        if (str_contains(($this->attributes['onchange'] ?? ''), 'ct_UpdateSingleValue(')) {

            $attributes['onchange'] = "ct_UpdateSingleValue('" . $this->ct->Env->WebsiteRoot . "',"
                . $this->ct->Params->ItemId . ",'" . $this->field->fieldname . $postfix . "',"
                . "'" . $this->row[$this->ct->Table->realidfieldname] . "',"
                . "'" . $langSEF . "',"
                . (int)$this->ct->Params->ModuleId . ")";
        }

        return '<input ' . self::attributes2String($attributes) . ' />';
    }
}