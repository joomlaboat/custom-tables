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
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;

class Inputbox
{
    var CT $ct;
    var Field $field;
    var ?array $row;
    var array $attributes;
    var array $option_list;
    var string $place_holder;
    var string $prefix;
    var bool $isTwig;
    var ?string $defaultValue;

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function __construct(CT &$ct, $fieldRow, array $option_list = [], $isTwig = true, string $onchange = '')
    {
        $this->ct = &$ct;
        $this->isTwig = $isTwig;

        $optional_attributes = str_replace('****quote****', '"', $option_list[1] ?? '');//Optional Parameter
        $this->attributes = CTMiscHelper::parseHTMLAttributes($optional_attributes);

        BaseInputBox::addOnChange($this->attributes, $onchange);

        $CSSClassOrStyle = $option_list[0] ?? '';
        if (str_contains($CSSClassOrStyle, ':'))//it's a style, change it to attribute
            BaseInputBox::addCSSStyle($this->attributes, $CSSClassOrStyle);
        else
            BaseInputBox::addCSSClass($this->attributes, $CSSClassOrStyle);

        $this->field = new Field($this->ct, $fieldRow);

        //Set CSS classes
        if ($this->field->type != "records" and $this->field->type != "radio")
            BaseInputBox::addCSSClass($this->attributes, ($this->ct->Env->version < 4 ? 'inputbox' : 'form-control'));

        //Add attributes
        $this->option_list = $option_list;
        $this->place_holder = $this->field->title;

        $this->attributes['data-type'] = $this->field->type;

        if (!isset($this->attributes['title']))
            $this->attributes['title'] = $this->field->title;

        $this->attributes['data-label'] = $this->field->title;

        if (!isset($this->attributes['placeholder']))
            $this->attributes['placeholder'] = $this->field->title;

        $this->attributes['data-valuerule'] = str_replace('"', '&quot;', $this->field->valuerule ?? '');
        $this->attributes['data-valuerulecaption'] = str_replace('"', '&quot;', $this->field->valuerulecaption ?? '');
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function render(?string $value, ?array $row): ?string
    {
        $this->row = $row;
        $this->field = new Field($this->ct, $this->field->fieldrow, $this->row);
        $this->prefix = $this->ct->Env->field_input_prefix . (!$this->ct->isEditForm ? $this->row[$this->ct->Table->realidfieldname] . '_' : '');
        $this->attributes['name'] = $this->prefix . $this->field->fieldname;
        $this->attributes['id'] = $this->prefix . $this->field->fieldname;

        if ($this->row === null and !isset($this->attributes['placeholder']))
            $this->attributes['placeholder'] = $this->place_holder;

        if ($this->field->defaultvalue !== '' and $value === null) {
            $twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
            $this->defaultValue = $twig->process($this->row);
        } else
            $this->defaultValue = null;


        //Try to instantiate a class dynamically
        $aliasMap = [
            'blob' => 'file',
            'userid' => 'user',
            'ordering' => 'int',
            'googlemapcoordinates' => 'gps',
            'multilangstring' => 'multilingualstring',
            'multilangtext' => 'multilingualtext',
            'sqljoin' => 'tablejoin',
            'records' => 'tablejoinlist'
        ];

        $fieldTypeShort = str_replace('_', '', $this->field->type);
        if (key_exists($fieldTypeShort, $aliasMap))
            $fieldTypeShort = $aliasMap[$fieldTypeShort];

        $additionalFile = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . $fieldTypeShort . '.php';

        if (file_exists($additionalFile)) {
            require_once($additionalFile);
            $className = '\CustomTables\InputBox_' . $fieldTypeShort;
            $inputBoxRenderer = new $className($this->ct, $this->field, $this->row, $this->option_list, $this->attributes);
        }

        switch ($this->field->type) {

            case 'alias':
            case 'article':
            case 'blob':
            case 'checkbox':
            case 'color':
            case 'date':
            case 'email':
            case 'file':
            case 'filebox':
            case 'filelink':
            case 'float':
            case 'googlemapcoordinates':
            case 'int':
            case 'image':
            case 'imagegallery':
            case 'language':
            case 'multilangstring':
            case 'multilangtext':
            case 'ordering':
            case 'radio':
            case 'signature':
            case 'string':
            case 'text':
            case 'time':
            case 'url':
            case 'user':
            case 'userid':
            case 'usergroup':
            case 'usergroups':
                return $inputBoxRenderer->render($value, $this->defaultValue);

            case 'sqljoin':
                if (!$this->isTwig)
                    return 'Old Table Join tags no longer supported';

                //if (defined('_JEXEC')) {
                $path = CUSTOMTABLES_PRO_PATH . 'inputbox' . DIRECTORY_SEPARATOR;

                if (file_exists($path . 'tablejoin.php')) {
                    require_once($path . 'tablejoin.php');

                    $inputBoxRenderer = new ProInputBoxTableJoin($this->ct, $this->field, $this->row, $this->option_list, $this->attributes);
                    return $inputBoxRenderer->render($value, $this->defaultValue);
                } else {
                    return common::translate('COM_CUSTOMTABLES_AVAILABLE');
                }
            //} else {
            //    return 'Table Join field type is not supported by WordPress version of the Custom Tables yet.';
            //}

            case 'records':

                if (defined('_JEXEC')) {
                    $path = CUSTOMTABLES_PRO_PATH . 'inputbox' . DIRECTORY_SEPARATOR;

                    if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
                        require_once($path . 'tablejoin.php');
                        require_once($path . 'tablejoinlist.php');

                        $inputBoxRenderer = new ProInputBoxTableJoinList($this->ct, $this->field, $this->row, $this->option_list, $this->attributes);
                        return $inputBoxRenderer->render($value, $this->defaultValue);
                    } else {
                        return common::translate('COM_CUSTOMTABLES_AVAILABLE');
                    }
                } else {
                    return 'Table Join List field type is not supported by WordPress version of the Custom Tables yet.';
                }
        }
        return '';
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function getDefaultValueIfNeeded($row)
    {
        $value = null;

        if ($this->ct->isRecordNull($row)) {
            $value = common::inputPostString($this->field->realfieldname, null, 'create-edit-record');

            if ($value == '') {
                $f = str_replace($this->ct->Table->fieldPrefix, '', $this->field->realfieldname);//legacy support
                $value = common::getWhereParameter($f);
            }

            if ($value == '') {
                $value = $this->field->defaultvalue;

                //Process default value, not processing PHP tag
                if ($value != '') {
                    if ($this->ct->Env->legacySupport) {
                        tagProcessor_General::process($this->ct, $value, $row);
                        tagProcessor_Item::process($this->ct, $value, $row);
                        tagProcessor_If::process($this->ct, $value, $row);
                        tagProcessor_Page::process($this->ct, $value);
                        tagProcessor_Value::processValues($this->ct, $value, $row);
                    }

                    $twig = new TwigProcessor($this->ct, $value);
                    $value = $twig->process($row);

                    if ($twig->errorMessage !== null)
                        $this->ct->errors[] = $twig->errorMessage;

                    if ($value != '') {
                        if ($this->ct->Params->allowContentPlugins)
                            CTMiscHelper::applyContentPlugins($value);

                        if ($this->field->type == 'alias') {
                            $listing_id = $row[$this->ct->Table->realidfieldname] ?? 0;
                            $saveField = new SaveFieldQuerySet($this->ct, $this->ct->Table->record, false);
                            $saveField->field = $this->field;
                            $value = $saveField->prepare_alias_type_value($listing_id, $value);
                        }
                    }
                }
            }
        } else {
            if ($this->field->type != 'multilangstring' and $this->field->type != 'multilangtext') {// and $this->field->type != 'multilangarticle') {
                $value = $row[$this->field->realfieldname] ?? null;
            }
        }
        return $value;
    }
}

abstract class BaseInputBox
{
    protected CT $ct;
    protected Field $field;
    protected ?array $row;
    protected array $attributes;
    protected array $option_list;

    function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
    {
        $this->ct = $ct;
        $this->field = $field;
        $this->row = $row;
        $this->option_list = $option_list;
        $this->attributes = $attributes;

        if ($this->field->isrequired == 1)
            self::addCSSClass($this->attributes, 'required');
    }

    public static function addCSSClass(array &$attributes, string $className): void
    {
        if (empty($className))
            return;

        if (isset($attributes['class'])) {
            $classes = explode(' ', $attributes['class']);
            if (!in_array($className, $classes)) {
                $classes [] = $className;
                $attributes['class'] = common::convertClassString(implode(' ', $classes));
            }
        } else {
            $attributes['class'] = common::convertClassString($className);
        }
    }

    /**
     * Removes a CSS class from the 'class' attribute in the provided attributes array.
     *
     * This method will remove the specified CSS class from the 'class' attribute
     * if it exists. If the 'class' attribute becomes empty after removal, it will
     * be unset from the attributes array.
     *
     * @param array $attributes The attributes array, passed by reference.
     * @param string $className The CSS class name to remove.
     *
     * @return void
     *
     * @since 3.3.3
     */
    public static function removeCSSClass(array &$attributes, string $className): void
    {
        if (empty($className) || !isset($attributes['class'])) {
            return;
        }

        $classes = explode(' ', $attributes['class']);
        $filteredClasses = array_filter($classes, function ($class) use ($className) {
            return $class !== $className;
        });

        if (empty($filteredClasses)) {
            unset($attributes['class']);
        } else {
            $attributes['class'] = implode(' ', $filteredClasses);
        }
    }

    public static function addOnChange(array &$attributes, string $onchange): void
    {
        if (empty($onchange))
            return;

        if (isset($attributes['onchange']) and $attributes['onchange'] !== '') {
            if (substr($attributes['onchange'], strlen($attributes['onchange']) - 1, 1) == ';')
                $attributes['onchange'] .= $onchange;
            else
                $attributes['onchange'] .= ';' . $onchange;
        } else {
            $attributes['onchange'] = $onchange;
        }
    }

    public static function addCSSStyle(array &$attributes, string $style): void
    {
        if (empty($style))
            return;

        if (isset($attributes['style'])) {
            $styles = explode(';', $attributes['style']);
            if (!in_array($style, $styles)) {
                $styles [] = $style;
                $attributes['style'] = implode(' ', $styles);
            }
        } else {
            $attributes['style'] = $style;
        }
    }

    public static function selectBoxAddCSSClass(&$attributes, $joomlaVersion): void
    {
        if ($joomlaVersion < 4)
            self::addCSSClass($attributes, 'inputbox');
        else
            self::addCSSClass($attributes, 'form-select');
    }

    public static function inputBoxAddCSSClass(&$attributes, $joomlaVersion): void
    {
        if ($joomlaVersion < 4)
            self::addCSSClass($attributes, 'inputbox');
        else
            self::addCSSClass($attributes, 'form-control');
    }

    function renderSelect(string $value, array $options): string
    {
        // Start building the select element with attributes
        $select = '<select ' . self::attributes2String($this->attributes) . '>';

        // Optional default option
        $selected = ($value == '' ? ' selected' : '');
        $select .= '<option value=""' . $selected . '> - ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

        // Generate options for each file in the folder
        foreach ($options as $option) {
            $selected = ($option->id == $value) ? ' selected' : '';
            $select .= '<option value="' . $option->id . '"' . $selected . '>' . $option->name . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    public static function attributes2String(array $attributes): string
    {
        $result = '';
        foreach ($attributes as $key => $attr)
            $result .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr ?? '') . '"';

        return $result;
    }
}
