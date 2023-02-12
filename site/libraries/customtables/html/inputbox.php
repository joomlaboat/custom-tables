<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\Registry\Registry;
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;
use CT_FieldTypeTag_image;
use CT_FieldTypeTag_file;
use CT_FieldTypeTag_imagegallery;
use CT_FieldTypeTag_FileBox;

use CustomTables\DataTypes\Tree;

use Joomla\CMS\Factory;
use JoomlaBasicMisc;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Editor\Editor;
use JHTML;

use CTTypes;

if (defined('_JEXEC'))
    JHTML::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');

class Inputbox
{
    var CT $ct;
    var Field $field;
    var ?array $row;

    var string $cssclass;
    var string $attributes;
    var string $onchange;
    var array $option_list;
    var string $place_holder;
    var string $prefix;
    var bool $isTwig;
    protected string $cssStyle;

    function __construct(CT &$ct, $fieldRow, array $option_list = [], $isTwig = true, string $onchange = '')
    {
        $this->ct = &$ct;
        $this->isTwig = $isTwig;

        $this->cssclass = $option_list[0] ?? '';
        $this->attributes = $option_list[1] ?? '';//Optional Parameter
        $this->cssStyle = '';
        $this->onchange = $onchange;

        if (str_contains($this->cssclass, ':'))//it's a style, change it to attribute
        {
            $this->cssStyle = $this->cssclass;
            $this->cssclass = '';
        }

        if (str_contains($this->attributes, 'onchange="') and $this->onchange != '') {
            //if the attributes already contain "onchange" parameter then add onchange value to the attributes parameter
            $this->attributes = str_replace('onchange="', 'onchange="' . $this->onchange, $this->attributes);
        } elseif ($this->attributes != '')
            $this->attributes .= ' onchange="' . $onchange . '"';
        else
            $this->attributes = 'onchange="' . $onchange . '"';

        $this->field = new Field($this->ct, $fieldRow);

        $this->cssclass .= ($this->ct->Env->version < 4 ? ' inputbox' : ' form-control') . ($this->field->isrequired ? ' required' : '');
        $this->option_list = $option_list;
        $this->place_holder = $this->field->title;
    }

    static public function renderTableJoinSelectorJSON(CT &$ct, $key, $obEndClean = true): ?string
    {
        $index = $ct->Env->jinput->getInt('index');

        $selectors = (array)$ct->app->getUserState($key);

        if ($index < 0 or $index >= count($selectors))
            die(json_encode(['error' => 'Index out of range.' . $key]));

        $additional_filter = $ct->Env->jinput->getCmd('filter', '');
        $subFilter = $ct->Env->jinput->getCmd('subfilter');

        return self::renderTableJoinSelectorJSON_Process($ct, $selectors, $index, $additional_filter, $subFilter, $obEndClean);
    }

    static public function renderTableJoinSelectorJSON_Process(CT &$ct, $selectors, $index, $additional_filter, $subFilter, $obEndClean = true): ?string
    {
        $selector = $selectors[$index];

        $tablename = $selector[0];
        if ($tablename === null) {
            if ($obEndClean)
                die(json_encode(['error' => 'Table not selected']));
            else
                return 'Table not selected';
        }

        $ct->getTable($tablename);
        if (is_null($ct->Table->tablename))
            die(json_encode(['error' => 'Table "' . $tablename . '"not found']));

        $fieldname_or_layout = $selector[1];
        if ($fieldname_or_layout === null or $fieldname_or_layout == '')
            $fieldname_or_layout = $ct->Table->fields[0]['fieldname'];//Get first field if not specified

        //$showPublished = 0 - show published
        //$showPublished = 1 - show unpublished
        //$showPublished = 2 - show any
        $showPublished = (($selector[2] ?? '') == 'true' ? 2 : 0); //$selector[2] can be "" or "true" or "false"

        $filter = $selector[3] ?? '';

        $additional_where = '';
        //Find the field name that has a join to the parent (index-1) table
        foreach ($ct->Table->fields as $fld) {
            if ($fld['type'] == 'sqljoin' or $fld['type'] == 'records') {
                $type_params = JoomlaBasicMisc::csv_explode(',', $fld['typeparams']);

                $join_tablename = $type_params[0];
                $join_to_tablename = $selector[5];

                if ($additional_filter != '') {
                    if ($join_tablename == $join_to_tablename) {
                        $filter .= ' and ' . $fld['fieldname'] . '=' . $additional_filter;
                    }
                } else {
                    //Check if this table has self-parent field - the TableJoin field linked with the same table.
                    if ($join_tablename == $tablename) {

                        if ($subFilter == '')
                            $additional_where = '(' . $fld['realfieldname'] . ' IS NULL OR ' . $fld['realfieldname'] . '="")';
                        else
                            $additional_where = $fld['realfieldname'] . '=' . $ct->db->quote($subFilter);
                    }
                }
            }
        }

        $ct->setFilter($filter, $showPublished);
        if ($additional_where != '')
            $ct->Filter->where[] = $additional_where;

        $orderby = $selector[4] ?? '';

        //sorting
        $ct->Ordering->ordering_processed_string = $orderby;
        $ct->Ordering->parseOrderByString();

        $ct->getRecords();

        if (!str_contains($fieldname_or_layout, '{{') and !str_contains($fieldname_or_layout, 'layout')) {
            $fieldname_or_layout_tag = '{{ ' . $fieldname_or_layout . ' }}';
        } else {
            $pair = explode(':', $fieldname_or_layout);

            if (count($pair) == 2) {
                $layout_mode = true;
                if ($pair[0] != 'layout' and $pair[0] != 'tablelesslayout')
                    die(json_encode(['error' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOWN_FIELD_LAYOUT') . ' "' . $fieldname_or_layout . '"']));

                $Layouts = new Layouts($ct);
                $fieldname_or_layout_tag = $Layouts->getLayout($pair[1]);

                if (!isset($fieldname_or_layout_tag) or $fieldname_or_layout_tag == '')
                    die(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND') . ' "' . $pair[1] . '"');
            } else
                $fieldname_or_layout_tag = $fieldname_or_layout;
        }

        $itemLayout = '{"id":"{{ record.id }}","label":"' . $fieldname_or_layout_tag . '"}';
        $pageLayoutContent = '[{% block record %}{% if record.number>1 %},{% endif %}' . $itemLayout . '{% endblock %}]';

        $paramsArray['establename'] = $tablename;

        $params = new Registry;
        $params->loadArray($paramsArray);
        $ct->setParams($params);

        $pathViews = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

        require_once($pathViews . 'json.php');

        $jsonOutput = new ViewJSON($ct);
        return $jsonOutput->render($pageLayoutContent, '', 10, $obEndClean); //10 is the LayoutType = JSON
    }

    function render($value, $row)
    {
        $this->row = $row;
        $this->field = new Field($this->ct, $this->field->fieldrow, $this->row);
        $this->prefix = $this->ct->Env->field_input_prefix . (!$this->ct->isEditForm ? $this->row[$this->ct->Table->realidfieldname] . '_' : '');

        switch ($this->field->type) {
            case 'radio':
                return $this->render_radio($value);

            case 'ordering':
            case 'int':
                return $this->render_int($value);

            case 'float':
                return $this->render_float($value);

            case 'phponadd':
            case 'phponchange':
                return $value . '<input type="hidden" '
                    . 'name="' . $this->prefix . $this->field->fieldname . '" '
                    . 'id="' . $this->prefix . $this->field->fieldname . '" '
                    . 'value="' . $value . '" />';

            case 'phponview':
                return $value;

            case 'string':
                return $this->getTextBox($value);

            case 'alias':
                return $this->render_alias($value);

            case 'multilangstring':
                return $this->getMultilingualString();

            case 'text':
                return $this->render_text($value);

            case 'multilangtext':
                require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . 'multilangtext.php');
                return $this->render_multilangtext();

            case 'checkbox':
                return $this->render_checkbox($value);

            case 'image':
                $image_type_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_image.php';
                require_once($image_type_file);

                return CT_FieldTypeTag_image::renderImageFieldBox($this->field, $this->prefix, $this->row, $this->cssclass, $this->attributes);

            case 'signature':
                return $this->render_signature();

            case 'blob':
            case 'file':
                $file_type_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                require_once($file_type_file);
                return CT_FieldTypeTag_file::renderFileFieldBox($this->ct, $this->field, $this->row);

            case 'userid':
                return $this->getUserBox($value);

            case 'user':
                if ($this->ct->isRecordNull($this->row))
                    $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'STRING');

                return $this->getUserBox($value);

            case 'usergroup':
                if ($this->ct->isRecordNull($this->row))
                    $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'STRING');

                return $this->getUserGroupBox($value);

            case 'usergroups':
                return JHTML::_('ESUserGroups.render',
                    $this->prefix . $this->field->fieldname,
                    $value,
                    $this->field->params
                );

            case 'language':
                if ($this->ct->isRecordNull($this->row)) {
                    $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'STRING');
                    if ($value == '') {
                        //If it's a new record then default language is the current one
                        $langObj = Factory::getLanguage();
                        $value = $langObj->getTag();
                    }
                }

                $lang_attributes = array(
                    'name' => $this->prefix . $this->field->fieldname,
                    'id' => $this->prefix . $this->field->fieldname,
                    'label' => $this->field->title, 'readonly' => false);

                return CTTypes::getField('language', $lang_attributes, $value)->input;

            case 'color':
                return $this->render_color($value);

            case 'filelink':

                if ($this->ct->isRecordNull($this->row))
                    $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'STRING');

                if ($value == '')
                    $value = $this->field->defaultvalue;

                return JHTML::_('ESFileLink.render', $this->prefix . $this->field->fieldname, $value, $this->cssStyle, $this->cssclass, $this->field->params[0], $this->attributes);

            case 'customtables':
                return $this->render_customtables();

            case 'sqljoin':
                return $this->render_tablejoin($value);

            case 'records':
                return $this->render_records($value);

            case 'googlemapcoordinates':
                return JHTML::_('GoogleMapCoordinates.render', $this->prefix . $this->field->fieldname, $value);

            case 'email';
                return '<input '
                    . 'type="text" '
                    . 'name="' . $this->prefix . $this->field->fieldname . '" '
                    . 'id="' . $this->prefix . $this->field->fieldname . '" '
                    . 'class="' . $this->cssclass . '" '
                    . 'value="' . $value . '" maxlength="255" '
                    . $this->attributes . ' '
                    . 'data-label="' . $this->field->title . '"'
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ' />';

            case 'url';
                return $this->render_url($value);

            case 'date';
                return $this->render_date($value);

            case 'time';
                return $this->render_time($value);

            case 'article':
                return JHTML::_('CTArticle.render',
                    $this->prefix . $this->field->fieldname,
                    $value,
                    $this->cssclass,
                    $this->field->params
                );

            case 'imagegallery':
                if (!$this->ct->isRecordNull($this->row))
                    return $this->getImageGallery($this->row[$this->ct->Table->realidfieldname]);
                break;

            case 'filebox':
                if (!$this->ct->isRecordNull($this->row))
                    return $this->getFileBox($this->row[$this->ct->Table->realidfieldname]);
                break;

            case 'multilangarticle':
                if (!$this->ct->isRecordNull($this->row))
                    return $this->render_multilangarticle();
                break;
        }
        return '';
    }

    protected function render_radio($value): string
    {
        $result = '<ul>';
        $i = 0;

        foreach ($this->field->params as $radiovalue) {
            $v = trim($radiovalue);
            $result .= '<li><input type="radio"
									name="' . $this->prefix . $this->field->fieldname . '"
									id="' . $this->prefix . $this->field->fieldname . '_' . $i . '"
									value="' . $v . '" '
                . ($value == $v ? ' checked="checked" ' : '')
                . ' /><label for="' . $this->prefix . $this->field->fieldname . '_' . $i . '">' . $v . '</label></li>';
            $i++;
        }
        $result .= '</ul>';

        return $result;
    }

    protected function render_int(&$value): string
    {
        $result = '';

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'ALNUM');

        if ($value == '')
            $value = (int)$this->field->defaultvalue;
        else
            $value = (int)$value;

        $result .= '<input '
            . 'type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . '" '
            . 'label="' . $this->field->fieldname . '" '
            . 'class="' . $this->cssclass . '" '
            . $this->attributes . ' '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . 'value="' . $value . '" />';

        return $result;
    }

    protected function render_float($value): string
    {
        $result = '';

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->getCmd($this->ct->Env->field_prefix . $this->field->fieldname, '');

        if ($value == '')
            $value = (float)$this->field->defaultvalue;
        else
            $value = (float)$value;

        $result .= '<input '
            . 'type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . '" '
            . 'class="' . $this->cssclass . '" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . $this->attributes . ' ';

        $decimals = intval($this->field->params[0]);
        if ($decimals < 0)
            $decimals = 0;

        if (isset($values[2]) and $values[2] == 'smart')
            $result .= 'onkeypress="ESsmart_float(this,event,' . $decimals . ')" ';

        $result .= 'value="' . $value . '" />';
        return $result;
    }

    protected function getTextBox($value): string
    {
        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->getString($this->ct->Env->field_prefix . $this->field->fieldname, '');

        $autocomplete = false;
        if (isset($this->option_list[2]) and $this->option_list[2] == 'autocomplete')
            $autocomplete = true;

        $result = '<input type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . '" '
            . 'label="' . $this->field->fieldname . '" '
            . ($autocomplete ? 'list="' . $this->prefix . $this->field->fieldname . '_datalist" ' : '')
            . 'class="' . $this->cssclass . '" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . 'value="' . $value . '" ' . ((int)$this->field->params[0] > 0 ? 'maxlength="' . (int)$this->field->params[0] . '"' : 'maxlength="255"') . ' ' . $this->attributes . ' />';

        if ($autocomplete) {

            $query = 'SELECT ' . $this->field->realfieldname . ' FROM ' . $this->ct->Table->realtablename . ' GROUP BY ' . $this->field->realfieldname
                . ' ORDER BY ' . $this->field->realfieldname;

            $this->ct->db->setQuery($query);
            $records = $this->ct->db->loadColumn();

            $result .= '<datalist id="' . $this->prefix . $this->field->fieldname . '_datalist">'
                . (count($records) > 0 ? '<option value="' . implode('"><option value="', $records) . '">' : '')
                . '</datalist>';
        }

        return $result;
    }

    protected function render_alias($value): string
    {
        $maxlength = 0;
        if ($this->field->params !== null and count($this->field->params) > 0)
            $maxlength = (int)$this->field->params[0];

        return '<input type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . '" '
            . 'label="' . $this->field->fieldname . '" '
            . 'class="' . $this->cssclass . '" '
            . ' ' . $this->attributes
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . 'value="' . $value . '" ' . ($maxlength > 0 ? 'maxlength="' . $maxlength . '"' : 'maxlength="255"') . ' ' . $this->attributes . ' />';
    }

    protected function getMultilingualString(): string
    {
        $result = '';
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
                    return $this->getMultilangStringItem($postfix, $lang->sef);
                }
            }
        }

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
				<div class="controls">' . $this->getMultilangStringItem($postfix, $lang->sef) . '</div>
			</div>';
        }
        $result .= '</div>';
        return $result;
    }

    protected function getMultilangStringItem($postfix, $langsef): string
    {
        $attributes_ = '';
        $addDynamicEvent = false;

        if (str_contains($this->attributes, 'onchange="ct_UpdateSingleValue('))//its like a keyword
        {
            $addDynamicEvent = true;
        } else
            $attributes_ = $this->attributes;

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->get($this->prefix . $this->field->fieldname . $postfix, '', 'STRING');
        else
            $value = $this->row[$this->field->realfieldname . $postfix] ?? null;

        if ($addDynamicEvent) {
            $href = 'onchange="ct_UpdateSingleValue(\'' . $this->ct->Env->WebsiteRoot . '\','
                . $this->ct->Params->ItemId . ',\'' . $this->field->fieldname . $postfix . '\','
                . $this->row[$this->ct->Table->realidfieldname] . ',\'' . $langsef . '\',' . (int)$this->ct->Params->ModuleId . ')"';

            $attributes_ = ' ' . $href;
        }

        return '<input type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . $postfix . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . $postfix . '" '
            . 'class="' . $this->cssclass . '" '
            . 'value="' . $value . '" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . ((int)$this->field->params[0] > 0 ? 'maxlength="' . (int)$this->field->params[0] . '" ' : 'maxlength="255" ')
            . $attributes_ . ' />';
    }

    protected function render_text($value): string
    {
        $result = '';
        $fname = $this->prefix . $this->field->fieldname;

        //if(strpos($this->attributes,'onchange="')!==false)
        //$attributes = str_replace('onchange="','onchange="'.$this->onchange,$this->attributes);// onchange event already exists add one before
        //else
        //$attributes = $this->attributes.' onchange="'.$onchange;

        if (in_array('rich', $this->field->params)) {
            $w = 500;
            $h = 200;
            $c = 0;
            $l = 0;

            $editor_name = $this->ct->app->get('editor');
            $editor = Editor::getInstance($editor_name);

            $result .= '<div>' . $editor->display($fname, $value, $w, $h, $c, $l) . '</div>';
        } else {
            $result .= '<textarea name="' . $fname . '" '
                . 'id="' . $fname . '" '
                . 'class="' . $this->cssclass . '" '
                . $this->attributes . ' '
                . 'data-label="' . $this->field->title . '"'
                . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                . '>' . $value . '</textarea>';
        }

        if (in_array('spellcheck', $this->field->params)) {
            $file_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'thirdparty'
                . DIRECTORY_SEPARATOR . 'jsc' . DIRECTORY_SEPARATOR . 'include.js';

            if (file_exists($file_path)) {
                $this->ct->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/thirdparty/jsc/include.js"></script>');
                $this->ct->document->addCustomTag('<script>$Spelling.SpellCheckAsYouType("' . $fname . '");</script>');
                $this->ct->document->addCustomTag('<script>$Spelling.DefaultDictionary = "English";</script>');
            }
        }

        return $result;
    }

    protected function render_multilangtext(): string
    {
        $RequiredLabel = 'Field is required';

        $result = '';

        $firstlanguage = true;
        foreach ($this->ct->Languages->LanguageList as $lang) {
            if ($firstlanguage) {
                $postfix = '';
                $firstlanguage = false;
            } else
                $postfix = '_' . $lang->sef;

            $fieldname = $this->field->fieldname . $postfix;

            if ($this->ct->isRecordNull($this->row)) {
                $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $fieldname, '', 'STRING');
            } else {
                if (array_key_exists($this->ct->Env->field_prefix . $fieldname, $this->row)) {
                    $value = $this->row[$this->ct->Env->field_prefix . $fieldname];
                } else {
                    Fields::addLanguageField($this->ct->Table->realtablename, $this->ct->Env->field_prefix . $this->field->fieldname, $this->ct->Env->field_prefix . $fieldname);

                    $this->ct->app->enqueueMessage('Field "' . $this->ct->Env->field_prefix . $fieldname . '" not yet created. Go to /Custom Tables/Database schema/Checks to create that field.', 'error');
                    $value = '';
                }
            }

            $result .= ($this->field->isrequired ? ' ' . $RequiredLabel : '');

            $result .= '<div id="' . $fieldname . '_div" class="multilangtext">';

            if ($this->field->params[0] == 'rich') {
                $result .= '<span class="language_label_rich">' . $lang->caption . '</span>';

                $w = 500;
                $h = 200;
                $c = 0;
                $l = 0;

                $editor_name = $this->ct->app->get('editor');
                $editor = Editor::getInstance($editor_name);

                $fname = $this->prefix . $fieldname;
                $result .= '<div>' . $editor->display($fname, $value, $w, $h, $c, $l) . '</div>';
            } else {
                $result .= '<textarea name="' . $this->prefix . $fieldname . '" '
                    . 'id="' . $this->prefix . $fieldname . '" '
                    . 'class="' . $this->cssclass . ' ' . ($this->field->isrequired ? 'required' : '') . '">' . $value . '</textarea>'
                    . '<span class="language_label">' . $lang->caption . '</span>';

                $result .= ($this->field->isrequired ? ' ' . $RequiredLabel : '');
            }

            $result .= '</div>';
        }

        return $result;
    }

    protected function render_checkbox($value): string
    {
        $result = '';

        $format = "";
        if (isset($this->option_list[2]) and $this->option_list[2] == 'yesno')
            $format = "yesno";

        if ($format == "yesno") {
            $element_id = $this->prefix . $this->field->fieldname;
            if ($this->ct->Env->version < 4) {
                $result .= '<fieldset id="' . $this->prefix . $this->field->fieldname . '" class="' . $this->cssclass . ' btn-group radio btn-group-yesno" '
                    . 'style="border:none !important;background:none !important;">';

                $result .= '<div style="position: absolute;visibility:hidden !important; display:none !important;">'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '0" '
                    . 'name="' . $element_id . '" '
                    . 'value="1" '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . $this->attributes . ' '
                    . ((int)$value == 1 ? ' checked="checked" ' : '')
                    . ' >'
                    . '</div>'
                    . '<label class="btn' . ((int)$value == 1 ? ' active btn-success' : '') . '" for="' . $element_id . '0" id="' . $element_id . '0_label" >' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') . '</label>';

                $result .= '<div style="position: absolute;visibility:hidden !important; display:none !important;">'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '1" '
                    . 'name="' . $element_id . '" '
                    . $this->attributes . ' '
                    . 'value="0" '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ((int)$value == 0 ? ' checked="checked" ' : '')
                    . ' >'
                    . '</div>'
                    . '<label class="btn' . ((int)$value == 0 ? ' active btn-danger' : '') . '" for="' . $element_id . '1" id="' . $element_id . '1_label">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO') . '</label>';

                $result .= '</fieldset>';
            } else {
                $result .= '<div class="switcher">'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '0" '
                    . 'name="' . $element_id . '" '
                    . $this->attributes . ' '
                    . 'value="0" '
                    . 'class="active " '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ((int)$value == 0 ? ' checked="checked" ' : '')
                    . ' >'
                    . '<label for="' . $element_id . '0">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO') . '</label>'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '1" '
                    . 'name="' . $element_id . '" '
                    . $this->attributes . ' '
                    . 'value="1" '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ((int)$value == 1 ? ' checked="checked" ' : '')
                    . ' >'
                    . '<label for="' . $element_id . '1">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') . '</label>'
                    . '<span class="toggle-outside"><span class="toggle-inside"></span></span>'
                    . '</div>';
            }
        } else {
            if ($this->ct->Env->version < 4) {
                $onchange = $this->prefix . $this->field->fieldname . '_off.value=(this.checked === true ? 0 : 1);';// this is to save unchecked value as well.

                if (str_contains($this->attributes, 'onchange="'))
                    $check_attributes = str_replace('onchange="', 'onchange="' . $onchange, $this->attributes);// onchange event already exists add one before
                else
                    $check_attributes = $this->attributes . 'onchange="' . $onchange;

                $result .= '<input type="checkbox" '
                    . 'id="' . $this->prefix . $this->field->fieldname . '" '
                    . 'name="' . $this->prefix . $this->field->fieldname . '" '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ($value ? ' checked="checked" ' : '')
                    . ($this->cssStyle != '' ? ' class="' . $this->cssStyle . '" ' : '')
                    . ($this->cssclass != '' ? ' class="' . $this->cssclass . '" ' : '')
                    . ($check_attributes != '' ? ' ' . $check_attributes : '')
                    . '>'
                    . '<input type="hidden"'
                    . ' id="' . $this->prefix . $this->field->fieldname . '_off" '
                    . ' name="' . $this->prefix . $this->field->fieldname . '_off" '
                    . ((int)$value == 1 ? ' value="0" ' : 'value="1"')
                    . ' >';
            } else {
                $element_id = $this->prefix . $this->field->fieldname;

                $result .= '<div class="switcher">'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '0" '
                    . 'name="' . $element_id . '" '
                    . 'value="0" '
                    . 'class="active " '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ((int)$value == 0 ? ' checked="checked" ' : '')
                    . ' >'
                    . '<label for="' . $element_id . '0">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO') . '</label>'
                    . '<input type="radio" '
                    . 'id="' . $element_id . '1" '
                    . 'name="' . $element_id . '" '
                    . 'value="1" '
                    . 'data-label="' . $this->field->title . '" '
                    . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
                    . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
                    . ((int)$value == 1 ? ' checked="checked" ' : '') . ' >'
                    . '<label for="' . $element_id . '1">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') . '</label>'
                    . '<span class="toggle-outside"><span class="toggle-inside"></span></span>'
                    . '<input type="hidden"'
                    . ' id="' . $this->prefix . $this->field->fieldname . '_off" '
                    . ' name="' . $this->prefix . $this->field->fieldname . '_off" '
                    . ((int)$value == 1 ? ' value="0" ' : 'value="1"')
                    . ' >'
                    . '</div>'
                    . '
						<script>
							document.getElementById("' . $element_id . '0").onchange = function(){if(this.checked === true)' . $this->prefix . $this->field->fieldname . '_off.value=1;' . $this->onchange . '};
							document.getElementById("' . $element_id . '1").onchange = function(){if(this.checked === true)' . $this->prefix . $this->field->fieldname . '_off.value=0;' . $this->onchange . '};
						</script>
						
						';
            }
        }

        return $result;
    }

    protected function render_signature(): string
    {
        $width = $this->field->params[0] ?? 300;
        $height = $this->field->params[1] ?? 150;
        $format = $this->field->params[3] ?? 'svg';
        if ($format == 'svg-db')
            $format = 'svg';

        //https://github.com/szimek/signature_pad/blob/gh-pages/js/app.js
        //https://stackoverflow.com/questions/46514484/send-signature-pad-to-php-post-method
        //		class="wrapper"
        $result = '
<div class="ctSignature_flexrow" style="width:' . $width . 'px;height:' . $height . 'px;padding:0;">
	<div style="position:relative;display: flex;padding:0;">
		<canvas style="background-color: #ffffff;padding:0;width:' . $width . 'px;height:' . $height . 'px;" '
            . 'id="' . $this->prefix . $this->field->fieldname . '_canvas" '
            . 'class="uneditable-input ' . $this->cssclass . '" '
            . $this->attributes
            . ' >
		</canvas>
		<div class="ctSignature_clear"><button type="button" class="close" id="' . $this->prefix . $this->field->fieldname . '_clear">×</button></div>';
        $result .= '
	</div>
</div>

<input type="text" style="display:none;" name="' . $this->prefix . $this->field->fieldname . '" id="' . $this->prefix . $this->field->fieldname . '" value="" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" > 
<script>
	ctInputbox_signature("' . $this->prefix . $this->field->fieldname . '",' . ((int)$width) . ',' . ((int)$height) . ',"' . $format . '")
</script>
';
        return $result;
    }

    protected function getUserBox(?string $value): string
    {
        $result = '';

        if ($this->ct->Env->userid == 0)
            return '';

        $attributes = 'class="' . $this->cssclass . '" ' . $this->attributes;

        $userGroup = $this->field->params[0] ?? '';

        tagProcessor_General::process($this->ct, $userGroup, $this->row);
        tagProcessor_Item::process($this->ct, $userGroup, $this->row);
        tagProcessor_If::process($this->ct, $userGroup, $this->row);
        tagProcessor_Page::process($this->ct, $userGroup);
        tagProcessor_Value::processValues($this->ct, $userGroup, $this->row);

        $where = '';
        if (isset($this->field->params[3]))
            $where = 'INSTR(name,"' . $this->field->params[3] . '")';

        $result .= JHTML::_('ESUser.render', $this->prefix . $this->field->fieldname, $value ?? '', '', $attributes, $userGroup, '', $where);
        return $result;
    }

    protected function getUserGroupBox($value): string
    {
        $result = '';

        if ($this->ct->Env->userid == 0)
            return '';

        $attributes = 'class="' . $this->cssclass . '" ' . $this->attributes;

        $availableUserGroupsList = ($this->field->params[0] == '' ? [] : $this->field->params);

        if (count($availableUserGroupsList) == 0) {
            $where_string = '#__usergroups.title!=' . $this->ct->db->quote('Super Users');
        } else {
            $where = [];
            foreach ($availableUserGroupsList as $availableusergroup) {
                if ($availableusergroup != '')
                    $where[] = '#__usergroups.title=' . $this->ct->db->quote($availableusergroup);
            }
            $where_string = '(' . implode(' OR ', $where) . ')';
        }

        $result .= JHTML::_('ESUserGroup.render', $this->prefix . $this->field->fieldname, $value, '', $attributes, $where_string);

        return $result;
    }

    protected function render_color($value): string
    {
        $result = '';

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'ALNUM');

        if ($value == '')
            $value = $this->field->defaultvalue;

        if ($value == '')
            $value = '';

        $att = array(
            'name' => $this->prefix . $this->field->fieldname,
            'id' => $this->prefix . $this->field->fieldname,
            'label' => $this->field->title);

        if ($this->option_list[0] == 'transparent') {
            $att['format'] = 'rgba';
            $att['keywords'] = 'transparent,initial,inherit';

            //convert value to rgba: rgba(255, 0, 255, 0.1)

            $colors = array();

            if (strlen($value) >= 6) {
                $colors[] = hexdec(substr($value, 0, 2));
                $colors[] = hexdec(substr($value, 2, 2));
                $colors[] = hexdec(substr($value, 4, 2));
            }

            if (strlen($value) == 8) {
                $a = hexdec(substr($value, 6, 2));
                $colors[] = round($a / 255, 2);
            }
            $value = 'rgba(' . implode(',', $colors) . ')';
        }

        $array_attributes = $this->prepareAttributes($att, $this->attributes);

        $inputbox = CTTypes::getField('color', $array_attributes, $value)->input;

        //Add onChange attribute if not added
        $onChangeAttribute = '';
        foreach ($array_attributes as $key => $value) {
            if ('onChange' == $key) {
                $onChangeAttribute = 'onChange="' . $value . '"';
                break;
            }
        }

        if ($onChangeAttribute != '' and !str_contains($inputbox, 'onChange'))
            $inputbox = str_replace('<input ', '<input ' . $onChangeAttribute, $inputbox);

        $result .= $inputbox;

        return $result;
    }

    protected function prepareAttributes($attributes_, $attributes_str)
    {
        //Used for 'color' field type

        if ($attributes_str != '') {
            $attributesList = JoomlaBasicMisc::csv_explode(' ', $attributes_str, '"', false);
            foreach ($attributesList as $a) {
                $pair = explode('=', $a);

                if (count($pair) == 2) {
                    $att = $pair[0];
                    if ($att == 'onchange')
                        $att = 'onChange';

                    $attributes_[$att] = $pair[1];
                }
            }
        }
        return $attributes_;
    }

    protected function render_customtables(): string
    {
        $result = '';

        if (!isset($this->field->params[1]))
            return 'selector not specified';

        $optionName = $this->field->params[0];
        $parentId = Tree::getOptionIdFull($optionName);

        //$this->field->params[0] is structure parent
        //$this->field->params[1] is selector type (multi or single)
        //$this->field->params[2] is data length
        //$this->field->params[3] is requirement depth

        if ($this->field->params[1] == 'multi') {

            if ($this->ct->isRecordNull($this->row)) {
                $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, null, 'STRING');
                if (!isset($value)) {
                    $value = '';
                    if ($this->field->defaultvalue != '')
                        $value = ',' . $this->field->params[0] . '.' . $this->field->defaultvalue . '.,';
                }
            } else
                $value = $this->row[$this->field->realfieldname];


            $result .= JHTML::_('MultiSelector.render',
                $this->prefix,
                $parentId, $optionName,
                $this->ct->Languages->Postfix,
                $this->ct->Table->tablename,
                $this->field->fieldname,
                $value,
                '',
                $this->place_holder);
        } elseif ($this->field->params[1] == 'single') {
            $v = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, null, 'STRING');

            if (!isset($v)) {
                $v = '';
                if ($this->field->defaultvalue != '')
                    $v = ',' . $this->field->params[0] . '.' . $this->field->defaultvalue . '.,';
            }

            if (isset($this->row[$this->field->realfieldname]))
                $v = $this->row[$this->field->realfieldname];

            $result .= '<div style="float:left;">';
            $result .= JHTML::_('ESComboTree.render',
                $this->prefix,
                $this->ct->Table->tablename,
                $this->field->fieldname,
                $optionName,
                $this->ct->Languages->Postfix,
                $v,
                '',
                '',
                '',
                '',
                $this->field->isrequired,
                (isset($this->field->params[3]) ? (int)$this->field->params[3] : 1),
                $this->place_holder,
                $this->field->valuerule,
                $this->field->valuerulecaption
            );

            $result .= '</div>';
        } else
            $result .= 'selector not specified';

        return $result;
    }

    protected function render_tablejoin($value): string
    {
        $result = '';

        //CT Example: [house:RedHouses,onChange('Alert("Value Changed")'),city=London]

        //$this->option_list[0] - CSS Class
        //$this->option_list[1] - Optional Attributes
        //$this->option_list[2] - Parent Selector - Array
        //$this->option_list[3] - Custom Title Layout

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->getInt($this->ct->Env->field_prefix . $this->field->fieldname, null);

        $sqljoin_attributes = ' data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '"'
            . ' data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '"';

        if ($this->isTwig) {
            //Twig Tag
            //Twig Example: [house:RedHouses,onChange('Alert("Value Changed")'),city=London]

            $result .= JHTML::_('CTTableJoin.render',
                $this->prefix . $this->field->fieldname,
                $this->field,
                ($this->row !== null ? $this->row[$this->ct->Table->realidfieldname] : null),
                $value,
                $this->option_list,
                $this->onchange,
                $sqljoin_attributes);
        } else {
            //CT Tag
            if (isset($this->option_list[2]) and $this->option_list[2] != '')
                $this->field->params[2] = $this->option_list[2];//Overwrites field type filter parameter.

            $sqljoin_attributes .= ' onchange="' . $this->onchange . '"';

            $result .= JHTML::_('ESSQLJoin.render',
                $this->field->params,
                $value,
                false,
                $this->ct->Languages->Postfix,
                $this->prefix . $this->field->fieldname,
                $this->place_holder,
                $this->cssclass,
                $sqljoin_attributes);
        }
        return $result;
    }

    protected function render_records($value): string
    {
        $result = '';

        //records : table, [fieldname || layout:layoutname], [selector: multi || single], filter, |datalength|

        if (count($this->field->params) < 1)
            $result .= 'table not specified';

        if (count($this->field->params) < 2)
            $result .= 'field or layout not specified';

        if (count($this->field->params) < 3)
            $result .= 'selector not specified';

        $esr_table = $this->field->params[0];
        $esr_field = $this->field->params[1] ?? '';

        $esr_selector = $this->field->params[2] ?? '';

        if (count($this->field->params) > 3)
            $esr_filter = $this->field->params[3];
        else
            $esr_filter = '';

        $dynamic_filter = $this->field->params[4] ?? '';

        $sortByField = $this->field->params[5] ?? '';

        $records_attributes = ($this->attributes != '' ? ' ' : '')
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" ';

        $result .= JHTML::_('ESRecords.render',
            $this->field->params,
            $this->prefix . $this->field->fieldname,
            $value,
            $esr_table,
            $esr_field,
            $esr_selector,
            $esr_filter,
            '',
            $this->cssclass . ' ct_improved_selectbox',
            $records_attributes,
            $dynamic_filter,
            $sortByField,
            $this->ct->Languages->Postfix,
            $this->place_holder
        );

        return $result;
    }

    protected function render_url($value): string
    {
        $result = '';
        $filters = array();
        $filters[] = 'url';

        if (isset($this->field->params[1]) and $this->field->params[1] == 'true')
            $filters[] = 'https';

        if (isset($this->field->params[2]) and $this->field->params[2] != '')
            $filters[] = 'domain:' . $this->field->params[2];

        $result .= '<input '
            . 'type="text" '
            . 'name="' . $this->prefix . $this->field->fieldname . '" '
            . 'id="' . $this->prefix . $this->field->fieldname . '" '
            . 'class="' . $this->cssclass . '" '
            . 'value="' . $value . '" maxlength="1024" '
            . 'data-sanitizers="trim" '
            . 'data-filters="' . implode(',', $filters) . '" '
            . 'data-label="' . $this->field->title . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" '
            . $this->attributes
            . ' />';

        return $result;
    }

    protected function render_date(&$value): string
    {
        $result = '';

        if ($value == "0000-00-00" or is_null($value))
            $value = '';

        $attributes = [];
        $attributes['class'] = $this->cssclass;
        $attributes['placeholder'] = $this->place_holder;
        $attributes['onChange'] = '" '
            . 'data-label="' . $this->place_holder . '" '
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption); // closing quote is not needed because

        $attributes['required'] = ($this->field->isrequired ? 'required' : ''); //not working, don't know why.

        $result .= JHTML::calendar($value, $this->prefix . $this->field->fieldname, $this->prefix . $this->field->fieldname,
            '%Y-%m-%d', $attributes);

        return $result;
    }

    protected function render_time(&$value): string
    {
        $result = '';

        if ($this->ct->isRecordNull($this->row))
            $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $this->field->fieldname, '', 'CMD');

        if ($value == '')
            $value = $this->field->defaultvalue;
        else
            $value = (int)$value;

        $time_attributes = ($this->attributes != '' ? ' ' : '')
            . 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
            . 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" ';

        $result .= JHTML::_('CTTime.render', $this->prefix . $this->field->fieldname, $value, $this->cssclass, $time_attributes, $this->field->params, $this->option_list);

        return $result;
    }

    protected function getImageGallery($listing_id): string
    {
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_gallery.php');

        $result = '';

        $getGalleryRows = CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);

        $image_prefix = '';

        if (isset($pair[1]) and (int)$pair[1] < 250)
            $img_width = (int)$pair[1];
        else
            $img_width = 250;

        $imageSRCList = CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $image_prefix, $this->field->fieldname,
            $this->field->params, $this->ct->Table->tableid);

        if (count($imageSRCList) > 0) {

            $result .= '<div style="width:100%;overflow:scroll;border:1px dotted grey;background-image: url(\'' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/bg.png\');">

		<table><tbody><tr>';

            foreach ($imageSRCList as $img) {
                $result .= '<td>';
                $result .= '<a href="' . $img . '" target="_blank"><img src="' . $img . '" style="width:' . $img_width . 'px;" />';
                $result .= '</td>';
            }

            $result .= '</tr></tbody></table>

		</div>';

        } else {
            return 'No Images';
        }

        return $result;
    }

    protected function getFileBox($listing_id): string
    {
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR
            . 'customtables' . DIRECTORY_SEPARATOR . 'datatypes' . DIRECTORY_SEPARATOR . 'filebox.php');

        $manageButton = '';

        $FileBoxRows = CT_FieldTypeTag_FileBox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);

        foreach ($this->ct->Table->fileboxes as $fileBox) {
            if ($fileBox[0] == $this->field->fieldname) {
                $manageButton = CT_FieldTypeTag_FileBox::renderFileBoxIcon($this->ct, $listing_id, $fileBox[0], $fileBox[1]);
                break;
            }
        }

        if (count($FileBoxRows) > 0) {
            $vlu = CT_FieldTypeTag_FileBox::process($FileBoxRows, $this->field, $listing_id, ['', 'icon-filename-link', '32', '_blank', 'ol']);
            $result = '<div style="width:100%;overflow:scroll;background-image: url(\'components/com_customtables/libraries/customtables/media/images/icons/bg.png\');">'
                . $manageButton . '<br/>' . $vlu . '</div>';
        } else
            $result = 'No Files ' . $manageButton;

        return $result;
    }

    protected function render_multilangarticle(): string
    {
        $result = '
		<table>
			<tbody>';

        $firstLanguage = true;
        foreach ($this->ct->Languages->LanguageList as $lang) {
            if ($firstLanguage) {
                $postfix = '';
                $firstLanguage = false;
            } else
                $postfix = '_' . $lang->sef;

            $fieldname = $this->field->fieldname . $postfix;

            if ($this->ct->isRecordNull($this->row))
                $value = $this->ct->Env->jinput->get($this->ct->Env->field_prefix . $fieldname, '', 'STRING');
            else
                $value = $this->row[$this->field->realfieldname . $postfix];

            $result .= '
				<tr>
					<td>' . $lang->caption . '</td>
					<td>:</td>
					<td>';

            $result .= JHTML::_('CTArticle.render',
                $this->prefix . $fieldname,
                $value,
                $this->cssclass,
                $this->field->params
            );

            $result .= '</td>
				</tr>';
        }
        $result .= '</body></table>';

        return $result;
    }

    function getDefaultValueIfNeeded($row)
    {
        $value = null;

        if ($this->ct->isRecordNull($row)) {
            $value = $this->ct->Env->jinput->getString($this->field->realfieldname);

            if ($value == '')
                $value = $this->getWhereParameter($this->field->realfieldname);

            if ($value == '') {
                $value = $this->field->defaultvalue;

                //Process default value, not processing PHP tag
                if ($value != '') {
                    if ($this->ct->Env->legacysupport) {
                        tagProcessor_General::process($this->ct, $value, $row);
                        tagProcessor_Item::process($this->ct, $value, $row);
                        tagProcessor_If::process($this->ct, $value, $row);
                        tagProcessor_Page::process($this->ct, $value);
                        tagProcessor_Value::processValues($this->ct, $value, $row);
                    }

                    $twig = new TwigProcessor($this->ct, $value);
                    $value = $twig->process($row);

                    if ($value != '') {
                        if ($this->ct->Params->allowContentPlugins)
                            JoomlaBasicMisc::applyContentPlugins($value);

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
            if ($this->field->type != 'multilangstring' and $this->field->type != 'multilangtext' and $this->field->type != 'multilangarticle') {
                $value = $row[$this->field->realfieldname] ?? null;
            }
        }
        return $value;
    }

    public function getWhereParameter($field): string
    {
        $f = str_replace($this->ct->Env->field_prefix, '', $field);

        $list = $this->getWhereParameters();

        foreach ($list as $l) {
            $p = explode('=', $l);
            if ($p[0] == $f and isset($p[1]))
                return $p[1];
        }
        return '';
    }

    protected function getWhereParameters(): array
    {
        $value = $this->ct->Env->jinput->get('where', '', 'BASE64');
        $b = base64_decode($value);
        $b = str_replace(' or ', ' and ', $b);
        $b = str_replace(' OR ', ' and ', $b);
        $b = str_replace(' AND ', ' and ', $b);
        return explode(' and ', $b);
    }

}
