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
use Exception;

defined('_JEXEC') or die();

class SearchInputBox
{
    var CT $ct;
    var string $moduleName;
    var Field $field;

    function __construct(CT $ct, string $moduleName)
    {
        $this->ct = $ct;
        $this->moduleName = $moduleName;
    }

    /**
     * @throws Exception
     * @since 3.2.1
     */
    function renderFieldBox(string  $prefix, string $objName, array $fieldrow, string $cssclass, $index, string $where, string $whereList,
                            ?string $onchange, ?string $field_title = null): string
    {
        $this->field = new Field($this->ct, $fieldrow);
        $place_holder = $this->field->title;

        if ($field_title === null)
            $field_title = $place_holder;

        $attributes['data-label'] = ($field_title ?? '' != "") ? $field_title : $this->field->title;

        if (!empty($onchange))
            BaseInputBox::addOnChange($attributes, $onchange);

        if (!empty($cssclass))
            BaseInputBox::addCSSClass($attributes, $cssclass);

        $attributes['data-type'] = $this->field->type;

        if (in_array($this->field->type, ['phponchange', 'phponadd', 'multilangstring', 'text', 'multilangtext', 'string'])) {
            $length = (($this->field->params !== null and count($this->field->params) > 0) ? (int)($this->field->params[0] ?? 255) : 255);
            if ($length == 0)
                $length = 1024;
            $attributes['maxlength'] = $length;
        } elseif (in_array($this->field->type, ['url', 'virtual', 'email'])) {
            $attributes['maxlength'] = 1024;
        }

        $result = '';
        $value = common::inputGetCmd($prefix . $objName);

        if ($value == '') {
            if (isset($fieldrow['fields']) and count($fieldrow['fields']) > 0)
                $where_name = implode(';', $fieldrow['fields']);
            else
                $where_name = $this->field->fieldname;

            $f = str_replace($this->ct->Env->field_prefix, '', $where_name);//legacy support
            $value = common::getWhereParameter($f);
        }

        $objName_ = $prefix . $objName;

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-control';

        //Try to instantiate a class dynamically
        $aliasMap = ['sqljoin' => 'tablejoin',
            'records' => 'tablejoinlist',
            'userid' => 'user',
            'usergroups' => 'usergroup',
            'int' => 'string',
            'float' => 'string',
            '_id' => 'string',
            //'phponchange'=>'string',
            //'phponadd'=>'string',
            'multilangstring' => 'string',
            'text' => 'string',
            'multilangtext' => 'string',
            'url' => 'string',
            'virtual' => 'string',
            'email' => 'string'
        ];

        if (isset($this->field->fieldrow['fields']) and is_array($this->field->fieldrow['fields']) and count($this->field->fieldrow['fields']) > 1)
            $fieldTypeShort = 'string';
        else
            $fieldTypeShort = str_replace('_', '', $this->field->type);

        if (key_exists($fieldTypeShort, $aliasMap))
            $fieldTypeShort = $aliasMap[$fieldTypeShort];

        $additionalFile = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
            . DIRECTORY_SEPARATOR . 'searchbox' . DIRECTORY_SEPARATOR . $fieldTypeShort . '.php';

        if (file_exists($additionalFile)) {
            require_once($additionalFile);
            $className = '\CustomTables\Search_' . $fieldTypeShort;
            $searchBoxRenderer = new $className($this->ct, $this->field, $this->moduleName, $attributes, $index, $where, $whereList, $objName_);
            return $searchBoxRenderer->render($value);
        }

        return 'SearchBox: Type "' . $this->field->type . ' is unknown or unsupported.';
    }


}

abstract class BaseSearch
{
    protected CT $ct;
    protected Field $field;
    protected string $moduleName;
    protected array $attributes;
    protected int $index;
    protected string $where;
    protected string $whereList;
    protected string $objectName;

    function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
    {
        if (trim($attributes['onchange'] ?? '') == '')
            $attributes['onchange'] = null;

        $this->ct = $ct;
        $this->field = $field;
        $this->moduleName = $moduleName;
        $this->attributes = $attributes;
        $this->index = $index;
        $this->where = $where;
        $this->whereList = $whereList;
        $this->objectName = $objectName;
    }

    function attributes2String(?array $attributes = null): string
    {
        if ($attributes === null)
            $attributes = $this->attributes;

        $result = '';
        foreach ($attributes as $key => $attr) {
            $result .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr) . '"';
        }
        return $result;
    }
    /*
        protected function getOnChangeAttributeString(): void
        {
            if (isset($this->attributes['onchange']) or $this->attributes['onchange'] !== null)
                rurn;

            /*
            $this->attributes['onchange'] = $this->moduleName . '_onChange('
                . $this->index . ','
                . 'this.value,'
                . '\'' . $this->field->fieldname . '\','
                . '\'' . urlencode($this->where) . '\','
                . '\'' . urlencode($this->whereList) . '\','
                . '\'' . $this->ct->Languages->Postfix . '\''
                . ')';

    }*/
}