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

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Exception;
use Joomla\CMS\Language\Text;
use JoomlaBasicMisc;
use Joomla\CMS\Factory;
use JHTML;

JHTML::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');

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

    function renderFieldBox($prefix, $objName, $fieldrow, $cssclass, $index, $where, $innerJoin, $whereList, $default_Action, $field_title = null): string
    {
        $this->field = new Field($this->ct, $fieldrow);
        $place_holder = $this->field->title;

        if ($field_title === null)
            $field_title = $place_holder;

        $result = '';
        $value = Factory::getApplication()->input->getCmd($prefix . $objName);

        if ($value == '') {
            if (isset($fieldrow['fields']) and count($fieldrow['fields']) > 0)
                $where_name = implode(';', $fieldrow['fields']);
            else
                $where_name = $this->field->fieldname;

            $value = $this->getWhereParameter($where_name);
        }

        $objName_ = $prefix . $objName;

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-control';

        switch ($this->field->type) {

            case '_published':
                $result .= $this->getPublishedBox($default_Action, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'int':
            case '_id':
                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '"'
                    . ' value="' . htmlspecialchars($value ?? '') . '" placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'float':
                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" value="' . htmlspecialchars($value) . '"'
                    . ' value="' . htmlspecialchars($value ?? '') . '" placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)" '
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'phponchange':
            case 'phponadd':
            case 'multilangstring':
            case 'text':
            case 'string':

                $length = count($this->field->params) > 0 ? (int)($this->field->params[0] ?? 255) : 255;
                if ($length == 0)
                    $length = 1024;

                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" '
                    . ' value="' . htmlspecialchars($value ?? '') . '" maxlength="' . $length . '"'
                    . ' placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'multilangtext':

                $length = count($this->field->params) > 0 ? (int)($this->field->params[0] ?? 255) : 255;
                if ($length == 0)
                    $length = 1024;

                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" '
                    . ' value="' . htmlspecialchars($value ?? '') . '" maxlength="' . $length . '"'
                    . ' placeholder="' . $field_title . '" onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'checkbox':
                $result .= $this->getCheckBox($default_Action, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'range':
                $result .= $this->getRangeBox($fieldrow, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'radio':
                $result .= $this->getRadioBox($default_Action, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'customtables':
                $result .= $this->getCustomTablesBox($prefix, $innerJoin, $default_Action, $index, $where, $whereList, $value, $cssclass, $place_holder);
                break;

            case 'user':
            case 'userid':
                $result .= $this->getUserBox($default_Action, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'usergroups':
            case 'usergroup':
                $result .= $this->getUserGroupBox($default_Action, $index, $where, $whereList, $objName_, $value, $cssclass);
                break;

            case 'records':
                $result .= $this->getRecordsBox($default_Action, $whereList, $objName_, $value, $cssclass);
                break;

            case 'sqljoin':
                $result .= $this->getTableJoinBox($default_Action, $objName_, $value, $cssclass);
                break;

            case 'email';
                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" '
                    . ' placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' value="' . htmlspecialchars($value ?? '') . '" maxlength="255"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'url';
                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" '
                    . ' placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' value="' . htmlspecialchars($value ?? '') . '" maxlength="1024"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'virtual';
                $result .= '<input type="text" name="' . $objName_ . '" id="' . $objName_ . '" class="' . $cssclass . ' ' . $default_class . '" '
                    . ' placeholder="' . $field_title . '"'
                    . ' onkeypress="es_SearchBoxKeyPress(event)"'
                    . ' value="' . htmlspecialchars($value) . '" maxlength="1024"'
                    . ' data-type="' . $this->field->type . '" />';
                break;

            case 'date';
                $result .= JHTML::calendar($value, $objName_, $objName_);
                break;
        }
        return $result;
    }

    protected function getWhereParameter($field): string
    {
        $f = str_replace($this->ct->Env->field_prefix, '', $field);//legacy support

        $list = $this->getWhereParameters();

        foreach ($list as $l) {
            $p = explode('=', $l);
            $fld_name = str_replace('_t_', '', $p[0]);
            $fld_name = str_replace('_r_', '', $fld_name); //range

            if ($fld_name == $f and isset($p[1]))
                return $p[1];

        }
        return '';
    }

    protected function getWhereParameters(): array
    {
        $value = Factory::getApplication()->input->getString('where');
        $value = str_replace('update', '', $value);
        $value = str_replace('select', '', $value);
        $value = str_replace('drop', '', $value);
        $value = str_replace('grant', '', $value);
        $value = str_replace('user', '', $value);

        $b = base64_decode($value);
        $b = str_replace(' or ', ' and ', $b);
        $b = str_replace(' OR ', ' and ', $b);
        $b = str_replace(' AND ', ' and ', $b);
        return explode(' and ', $b);
    }

    protected function getPublishedBox($default_Action, $index, $where, $whereList, $objectName, $value, $cssclass): string
    {
        $result = '';

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-select';

        $published = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED');
        $unpublished = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNPUBLISHED');
        $any = $published . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AND') . ' ' . $unpublished;
        $translations = array($any, $published, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNPUBLISHED'));
        $onchange = $this->getOnChangeAttributeString($default_Action, $index, $where, $whereList);

        $result .= '<select'
            . ' id="' . $objectName . '"'
            . ' name="' . $objectName . '"'
            . ' ' . $onchange
            . ' class="' . $cssclass . ' ' . $default_class . '"'
            . ' data-type="checkbox">'
            . '<option value="" ' . ($value == '' ? 'SELECTED' : '') . '>' . $translations[0] . '</option>'
            . '<option value="1" ' . ($value == '1' ? 'SELECTED' : '') . '>' . $translations[1] . '</option>'
            . '<option value="0" ' . ($value == '0' ? 'SELECTED' : '') . '>' . $translations[2] . '</option>'
            . '</select>';

        return $result;
    }

    protected function getOnChangeAttributeString($default_Action, $index, $where, $whereList): string
    {
        if ($default_Action != '')
            return $default_Action;

        return ' onChange="' . $this->moduleName . '_onChange('
            . $index . ','
            . 'this.value,'
            . '\'' . $this->field->fieldname . '\','
            . '\'' . urlencode($where) . '\','
            . '\'' . urlencode($whereList) . '\','
            . '\'' . $this->ct->Languages->Postfix . '\''
            . ')"';
    }

    protected function getCheckBox($default_Action, $index, $where, $whereList, $objectName, $value, $cssclass): string
    {
        $result = '';

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-select';

        $translations = array(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ANY'), JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES'), JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO'));
        $onchange = $this->getOnChangeAttributeString($default_Action, $index, $where, $whereList);

        $result .= '<select'
            . ' id="' . $objectName . '"'
            . ' name="' . $objectName . '"'
            . ' ' . $onchange
            . ' class="' . $cssclass . ' ' . $default_class . '"'
            . ' data-type="checkbox">'
            . '<option value="" ' . ($value == '' ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[0] . '</option>'
            . '<option value="1" ' . (($value == '1' or $value == 'true') ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[1] . '</option>'
            . '<option value="0" ' . (($value == '0' or $value == 'false') ? 'SELECTED' : '') . '>' . $this->field->title . ' - ' . $translations[2] . '</option>'
            . '</select>';

        return $result;
    }

    protected function getRangeBox($fieldrow, $index, $where, $whereList, $objectName, $value, $cssclass): string
    {
        $result = '';

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-control';

        $value_min = ''; //TODO: Check this
        $value_max = '';

        if ($this->field->params == 'date')
            $d = '-to-';
        elseif ($this->field->params == 'float')
            $d = '-';
        else
            return 'Cannot search by date';

        $values = explode($d, $value);
        $value_min = $values[0];

        if (isset($values[1]))
            $value_max = $values[1];

        if ($value_min == '')
            $value_min = $this->ct->Env->jinput->getString($objectName . '_min');

        if ($value_max == '')
            $value_max = $this->ct->Env->jinput->getString($objectName . '_max');

        //header function

        $js = '
	function Update' . $objectName . 'Values()
	{
		var o=document.getElementById("' . $objectName . '");
		var v_min=document.getElementById("' . $objectName . '_min").value
		var v_max=document.getElementById("' . $objectName . '_max").value;
		o.value=v_min+"' . $d . '"+v_max;

		//' . $this->moduleName . '_onChange(' . $index . ',v_min+"' . $d . '"+v_max,"' . $this->field->fieldname . '","' . urlencode($where) . '","' . urlencode($whereList) . '");
	}
';
        $this->ct->document->addCustomTag('<script>' . $js . '</script>');
        //end of header function

        $attribs = 'onChange="Update' . $objectName . 'Values()" class="' . $default_class . '" ';

        $result .= '<input type="hidden"'
            . ' id="' . $objectName . '" '
            . ' name="' . $objectName . '" '
            . ' value="' . $value_min . $d . $value_max . '" '
            . ' onkeypress="es_SearchBoxKeyPress(event)"'
            . ' data-type="range" />';

        $result .= '<table class="es_class_min_range_table" style="border: none;" class="' . $cssclass . '" ><tbody><tr><td style="vertical-align: middle;">';

        //From
        if ($fieldrow['typeparams'] == 'date') {
            $result .= JHTML::calendar($value_min, $objectName . '_min', $objectName . '_min', '%Y-%m-%d', $attribs);
        } else {
            $result .= '<input type="text"'
                . ' id="' . $objectName . '_min" '
                . ' name="' . $objectName . '_min" '
                . 'value="' . $value_min . '" '
                . ' onkeypress="es_SearchBoxKeyPress(event)" '
                . ' ' . str_replace('class="', 'class="es_class_min_range ', $attribs)
                . ' data-type="range" />';
        }

        $result .= '</td><td style="text-align:center;">-</td><td style="text-align:left;vertical-align: middle;width: 140px;">';

        //TODO: check if this is correct
        if ($fieldrow['typeparams'] == 'date') {
            $result .= JHTML::calendar($value_max, $objectName . '_max', $objectName . '_max', '%Y-%m-%d', $attribs);
        } else {
            $result .= '<input type="text"'
                . ' id="' . $objectName . '_max"'
                . ' name="' . $objectName . '_max"'
                . ' value="' . $value_max . '"'
                . ' onkeypress="es_SearchBoxKeyPress(event)"'
                . ' ' . str_replace('class="', 'class="es_class_min_range ', $attribs)
                . ' data-type="range" />';
        }
        return $result . '</td></tr></tbody></table>';
    }

    protected function getRadioBox($default_Action, $index, $where, $whereList, $objName, $value, $cssclass)
    {
        if ($this->ct->Env->version < 4)
            $cssclass = 'class="inputbox ' . $cssclass . '" ';
        else
            $cssclass = 'class="form-control ' . $cssclass . '" ';

        $onchange = $this->getOnChangeAttributeString($default_Action, $index, $where, $whereList);
        $options = [];
        $options[] = ['id' => '', 'data-type' => 'radio', 'name' => '- ' . Text::_('COM_CUSTOMTABLES_SELECT') . ' ' . $this->field->title];
        foreach ($this->field->params as $param)
            $options[] = ['id' => $param, 'data-type' => 'radio', 'name' => $param];

        return JHTML::_('select.genericlist', $options, $objName, $cssclass . ' ' . $onchange . ' ', 'id', 'name', $value, $objName);
    }

    protected function getCustomTablesBox($prefix, $innerJoin, $default_Action, $index, $where, $whereList, $value, $cssclass, $place_holder = ''): string
    {
        $result = '';
        $optionname = $this->field->params[0];

        if ($default_Action != '') {
            $onchange = $default_Action;
            $requirementDepth = 1;
        } else {
            $onchange = $this->moduleName . '_onChange('
                . $index . ','
                . 'me.value,'
                . '\'' . $this->field->fieldname . '\','
                . '\'' . urlencode($where) . '\','
                . '\'' . urlencode($whereList) . '\','
                . '\'' . $this->ct->Languages->Postfix . '\''
                . ')';

            $requirementDepth = 0;
        }

        $result .= JHTML::_('ESComboTree.render',
            $prefix,
            $this->ct->Table->tablename,
            $this->field->fieldname,
            $optionname,
            $this->ct->Languages->Postfix,
            $value,
            $cssclass,
            $onchange,
            $where,
            $innerJoin, false, $requirementDepth,
            $place_holder,
            '',
            '');

        return $result;
    }

    protected function getUserBox($default_Action, $index, $where, $whereList, $objName, $value, $cssclass)
    {
        $result = '';
        $mysqlJoin = $this->ct->Table->realtablename . ' ON ' . $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__users.id';
        $onchange = $this->getOnChangeAttributeString($default_Action, $index, $where, $whereList);

        if ($this->ct->Env->version < 4)
            $default_class = 'inputbox';
        else
            $default_class = 'form-control';

        if ($this->ct->Env->user->id != 0)
            $result = JHTML::_('ESUser.render', $objName, $value, '', 'class="' . $cssclass . ' ' . $default_class . '" ',
                ($this->field->params[0] ?? ''), $onchange, $where, $mysqlJoin);

        return $result;
    }

    protected function getUserGroupBox($default_Action, $index, $where, $whereList, $objectName, $value, $cssclass)
    {
        $result = '';
        $mysqlJoin = $this->ct->Table->realtablename . ' ON ' . $this->ct->Table->realtablename . '.' . $this->field->realfieldname . '=#__usergroups.id';

        if ($this->ct->Env->version < 4)
            $cssclass = 'class="inputbox ' . $cssclass . '" ';
        else
            $cssclass = 'class="form-control ' . $cssclass . '" ';

        $user = Factory::getUser();

        if ($default_Action != '') {
            $onchange = $default_Action;
        } else {
            $onchange = ' onChange=   "' . $this->moduleName . '_onChange('
                . $index . ','
                . 'this.value,'
                . '\'' . $this->field->fieldname . '\','
                . '\'' . urlencode($where) . '\','
                . '\'' . urlencode($whereList) . '\','
                . '\'' . $this->ct->Languages->Postfix . '\''
                . ')"';
        }

        if ($user->id != 0)
            $result = JHTML::_('ESUserGroup.render', $objectName, $value, '', $cssclass, $onchange, $where, $mysqlJoin);

        return $result;
    }

    protected function getRecordsBox($default_Action, $whereList, $objectName, $value, $cssclass): string
    {
        $result = '';

        if (count($this->field->params) < 1)
            $result .= 'table not specified';

        if (count($this->field->params) < 2)
            $result .= 'field or layout not specified';

        if (count($this->field->params) < 3)
            $result .= 'selector not specified';

        $esr_table = $this->field->params[0];
        $esr_field = $this->field->params[1];
        $esr_selector = $this->field->params[2];

        if ($whereList != '')
            $esr_filter = $whereList;
        elseif (count($this->field->params) > 3)
            $esr_filter = $this->field->params[3];
        else
            $esr_filter = '';

        $dynamic_filter = '';

        $sortByField = '';
        if (isset($this->field->params[5]))
            $sortByField = $this->field->params[5];

        /*
        $v = [];
        $v[] = $index;
        $v[] = 'this.value';
        $v[] = '"' . $this->field->fieldname . '"';
        $v[] = '"' . urlencode($where) . '"';
        $v[] = '"' . urlencode($whereList) . '"';
        $v[] = '"' . $this->ct->Languages->Postfix . '"';
        */

        if ($default_Action != '' and $default_Action != ' ')
            $onchange = $default_Action;
        else
            $onchange = ' onkeypress="es_SearchBoxKeyPress(event)"';

        if (is_array($value))
            $value = implode(',', $value);

        $real_selector = $esr_selector;//TODO: check if this is correct
        $real_selector = 'single';

        $result .= JHTML::_('ESRecords.render', $this->field->params, $objectName,
            $value, $esr_table, $esr_field, $real_selector, $esr_filter, '',
            $cssclass, $onchange, $dynamic_filter, $sortByField,
            $this->ct->Languages->Postfix, $this->field->title);

        return $result;
    }

    protected function getTableJoinBox($default_Action, $objectName, $value, $cssclass)
    {
        $result = '';

        if ($default_Action != '' and $default_Action != ' ')
            $onchange = $default_Action;
        else
            $onchange = ' onkeypress="es_SearchBoxKeyPress(event)"';

        if (is_array($value))
            $value = implode(',', $value);

        if ($this->ct->Env->version < 4) {
            if (!str_contains($cssclass, 'inputbox'))
                $cssclass .= ' inputbox';
        } else {
            if (!str_contains($cssclass, 'form-select'))
                $cssclass .= ' form-select';//form-control
        }

        if ($this->field->layout !== null)
            $this->field->params[1] = 'tablelesslayout:' . $this->field->layout;

        try {
            $result .= JHTML::_('ESSQLJoin.render', $this->field->params, $value, true, $this->ct->Languages->Postfix, $objectName,
                $this->field->title,
                ' ' . $cssclass . ' es_class_sqljoin', $onchange, true);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
        return $result;
    }
}
