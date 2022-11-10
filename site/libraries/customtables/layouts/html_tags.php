<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use JEventDispatcher;
use JoomlaBasicMisc;
use JESPagination;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use JHTML;
use JPluginHelper;

class Twig_Html_Tags
{
    var CT $ct;
    var bool $isTwig;

    var bool $captcha_found;
    var array $button_objects = []; //Not clear where and how this variable used.

    function __construct(CT &$ct, $isTwig = true)
    {
        $this->ct = &$ct;
        $this->isTwig = $isTwig;

        $this->captcha_found = false;
        $this->button_objects = [];//Not clear where and how this variable used.
    }

    function recordcount()
    {
        if ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != '')
            return '';

        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ html.recordcount }} - Table not loaded.', 'error');
            return '';
        }

        if (!isset($this->ct->Records)) {
            $this->ct->app->enqueueMessage('{{ html.recordcount }} - Records not loaded.', 'error');
            return '';
        }

        $vlu = '<span class="ctCatalogRecordCount">' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FOUND') . ': ' . $this->ct->Table->recordcount
            . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RESULT_S') . '</span>';

        return $vlu;
    }

    function add($Alias_or_ItemId = '')
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin)
            return '';

        $usergroups = $this->ct->Env->user->get('groups');

        $add_userGroup = (int)$this->ct->Params->addUserGroups;

        if (!$this->ct->Env->isUserAdministrator and !in_array($add_userGroup, $usergroups))
            return ''; //Not permitted

        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return ''; //Not permitted

        if (is_numeric($Alias_or_ItemId) and $Alias_or_ItemId > 0)
            $link = '/index.php?option=com_customtables&amp;view=edititem&amp;returnto=' . $this->ct->Env->encoded_current_url . '&amp;Itemid=' . $Alias_or_ItemId;
        elseif ($Alias_or_ItemId != '')
            $link = '/index.php/' . $Alias_or_ItemId . '?returnto=' . $this->ct->Env->encoded_current_url;
        else
            $link = '/index.php?option=com_customtables&amp;view=edititem&amp;returnto=' . $this->ct->Env->encoded_current_url
                . '&amp;Itemid=' . $this->ct->Params->ItemId;

        if (!is_null($this->ct->Params->ModuleId))
            $link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

        if ($this->ct->Env->jinput->getCmd('tmpl', '') != '')
            $link .= '&amp;tmpl=' . $this->ct->Env->jinput->get('tmpl', '', 'CMD');

        if (!is_null($this->ct->Params->ModuleId))
            $link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ADD');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-plus-circle" data-icon="' . $this->ct->Env->toolbaricons . ' fa-plus-circle" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="' . $alt . '" title="' . $alt . '" />';

        $vlu = '<a href="' . URI::root(true) . $link . '" id="ctToolBarAddNew' . $this->ct->Table->tableid . '" class="toolbarIcons">' . $img . '</a>';

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    function importcsv()
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $usergroups = $this->ct->Env->user->get('groups');
        if (!$this->ct->Env->isUserAdministrator and !in_array($this->ct->Params->addUserGroups, $usergroups))
            return ''; //Not permitted

        $max_file_size = JoomlaBasicMisc::file_upload_max_size();

        $fileid = JoomlaBasicMisc::generateRandomString();
        $fieldid = '9999999';//some unique number. TODO
        $objectname = 'importcsv';

        JHtml::_('behavior.formvalidator');

        $urlstr = '/index.php?option=com_customtables&amp;view=fileuploader&amp;tmpl=component&'
            . 'tableid=' . $this->ct->Table->tableid . '&'
            . 'task=importcsv&'
            . $objectname . '_fileid=' . $fileid
            . '&Itemid=' . $this->ct->Params->ItemId
            . (is_null($this->ct->Params->ModuleId) ? '' : '&ModuleId=' . $this->ct->Params->ModuleId)
            . '&fieldname=' . $objectname;

        $vlu = '<div>
                    <div id="ct_fileuploader_' . $objectname . '"></div>
                    <div id="ct_eventsmessage_' . $objectname . '"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        //UploadFileCount=1;
                    	ct_getUploader(' . $fieldid . ',"' . $urlstr . '",' . $max_file_size . ',"csv","ctUploadCSVForm",true,"ct_fileuploader_' . $objectname . '","ct_eventsmessage_' . $objectname . '","' . $fileid . '","'
            . $this->ct->Env->field_input_prefix . $objectname . '","ct_uploadedfile_box_' . $objectname . '")
                    </script>
                    <input type="hidden" name="' . $this->ct->Env->field_input_prefix . $objectname . '" id="' . $this->ct->Env->field_input_prefix . $objectname . '" value="" />
			' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . JoomlaBasicMisc::formatSizeUnits($max_file_size) . '
                    </form>
                </div>
';

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    function pagination($show_arrow_icons = false)
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if ($this->ct->Table->recordcount <= $this->ct->Limit)
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit, '', $this->ct->Env->version, $show_arrow_icons);

        if ($this->ct->Env->version < 4)
            return '<div class="pagination">' . $pagination->getPagesLinks() . '</div>';
        else
            return '<div style="display:inline-block">' . $pagination->getPagesLinks() . '</div>';
    }

    function limit($the_step = 5)
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit, '', $this->ct->Env->version);
        return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW') . ': ' . $pagination->getLimitBox($the_step);
    }

    function orderby()
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        if ($this->ct->Params->forceSortBy !== null and $this->ct->Params->forceSortBy != '')
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_SORT_BY_FIELD_LOCKED'), 'error');

        return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY') . ': ' . OrderingHTML::getOrderBox($this->ct->Ordering);
    }

    function goback($defaultLabel = 'COM_CUSTOMTABLES_GO_BACK', $image_icon = '', $attribute = '', $returnto = '')
    {
        $label = JoomlaBasicMisc::JTextExtended($defaultLabel);

        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        if ($returnto == '')
            $returnto = base64_decode($this->ct->Env->jinput->get('returnto', '', 'BASE64'));

        if ($returnto == '')
            return '';

        if ($attribute == '' and $image_icon == '') {
            if ($this->ct->Env->toolbaricons != '')
                $vlu = '<a href="' . $returnto . '"><i class="ba-btn-transition ' . $this->ct->Env->toolbaricons
                    . ' fa-angle-left" data-icon="' . $this->ct->Env->toolbaricons . ' fa-angle-left" title="'
                    . $label . '" style="margin-right:10px;"></i>' . $label . '</a>';
            else
                $vlu = '<a href="' . $returnto . '" class="ct_goback"><div>' . $label . '</div></a>';
        } else {

            $img = '';
            if (($this->ct->Env->toolbaricons != '' or $image_icon == '') and $attribute == '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-angle-left" data-icon="'
                    . $this->ct->Env->toolbaricons . ' fa-angle-left" title="' . $label . '" style="margin-right:10px;"></i>';
            elseif ($this->ct->Env->toolbaricons == '')
                $img = '<img src="' . $image_icon . '" alt="' . $label . '" />';

            $vlu = '<a href="' . $returnto . '" ' . $attribute . '><div>' . $img . $label . '</div></a>';
        }

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    function batch()
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin)
            return '';

        $buttons = func_get_args();
        if (count($buttons) == 1) {
            if (is_array($buttons[0]))
                $buttons = $buttons[0];
        }

        $available_modes = $this->getAvailableModes();
        if (count($available_modes) == 0)
            return '';

        if (is_array($buttons))
            $buttons_array = $buttons;
        else
            $buttons_array = explode(',', $buttons);

        if (count($buttons_array) == 0)
            $buttons_array = $available_modes;

        $html_buttons = [];

        foreach ($buttons_array as $mode) {
            if ($mode == 'checkbox') {
                $html_buttons[] = '<input type="checkbox" id="esCheckboxAll' . $this->ct->Table->tableid . '" onChange="esCheckboxAllClicked(' . $this->ct->Table->tableid . ')" />';
            } else {
                if (in_array($mode, $available_modes)) {
                    $rid = 'esToolBar_' . $mode . '_box_' . $this->ct->Table->tableid;
                    $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_' . strtoupper($mode) . '_SELECTED');

                    if ($this->ct->Env->toolbaricons != '') {
                        $icons = ['publish' => 'fa-check-circle', 'unpublish' => 'fa-ban', 'refresh' => 'fa-sync', 'delete' => 'fa-trash'];
                        $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' ' . $icons[$mode] . '" data-icon="' . $this->ct->Env->toolbaricons . ' ' . $icons[$mode] . '" title="' . $alt . '"></i>';
                    } else
                        $img = '<img src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/' . $mode . '.png" border="0" alt="' . $alt . '" title="' . $alt . '" />';

                    $link = 'javascript:ctToolBarDO("' . $mode . '", ' . $this->ct->Table->tableid . ')';
                    $html_buttons[] = '<div id="' . $rid . '" class="toolbarIcons"><a href=\'' . $link . '\'>' . $img . '</a></div>';
                }
            }
        }

        if (count($html_buttons) == 0)
            return '';

        $vlu = implode('', $html_buttons);

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    protected function getAvailableModes()
    {
        $available_modes = array();

        $user = Factory::getUser();
        if ($user->id != 0) {
            $publish_userGroup = (int)$this->ct->Params->publishUserGroups;

            if (JoomlaBasicMisc::checkUserGroupAccess($publish_userGroup)) {
                $available_modes[] = 'publish';
                $available_modes[] = 'unpublish';
            }

            $edit_userGroup = (int)$this->ct->Params->editUserGroups;
            if (JoomlaBasicMisc::checkUserGroupAccess($edit_userGroup))
                $available_modes[] = 'refresh';

            $delete_userGroup = (int)$this->ct->Params->deleteUserGroups;
            if (JoomlaBasicMisc::checkUserGroupAccess($delete_userGroup))
                $available_modes[] = 'delete';
        }
        return $available_modes;
    }

    function print($linktype = '', $label = '', $class = 'ctEditFormButton btn button')
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $link = $this->ct->Env->current_url . (strpos($this->ct->Env->current_url, '?') === false ? '?' : '&') . 'tmpl=component&amp;print=1';

        if ($this->ct->Env->jinput->getInt('moduleid', 0) != 0) {
            //search module

            $moduleid = $this->ct->Env->jinput->getInt('moduleid', 0);
            $link .= '&amp;moduleid=' . $moduleid;

            //keyword search
            $inputbox_name = 'eskeysearch_' . $moduleid;
            $link .= '&amp;' . $inputbox_name . '=' . $this->ct->Env->jinput->getString($inputbox_name, '');
        }

        $onClick = 'window.open("' . $link . '","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no");return false;';
        if ($this->ct->Env->print == 1) {
            $vlu = '<p><a href="#" onclick="window.print();return false;"><img src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/print.png" alt="' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT') . '"  /></a></p>';
        } else {
            if ($label == '')
                $label = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT');

            if ($linktype != '')
                $vlu = '<a href="#" onclick=\'' . $onClick . '\'><i class="ba-btn-transition fas fa-print" data-icon="fas fa-print" title="' . $label . '"></i></a>';
            else
                $vlu = '<input type="button" class="' . $class . '" value="' . $label . '" onClick=\'' . $onClick . '\' />';
        }

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    function search($list_of_fields_string_or_array, $class = '', $reload = false, $improved = false): string
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        if (is_array($list_of_fields_string_or_array))
            $list_of_fields_string_array = $list_of_fields_string_or_array;
        else
            $list_of_fields_string_array = explode(',', $list_of_fields_string_or_array);

        if (count($list_of_fields_string_array) == 0) {
            $this->ct->app->enqueueMessage('Search box: Please specify a field name.', 'error');
            return '';
        }

        //Clean list of fields
        $list_of_fields = [];
        foreach ($list_of_fields_string_array as $field_name_string) {
            if ($field_name_string == '_id') {
                $list_of_fields[] = '_id';
            } elseif ($field_name_string == '_published') {
                $list_of_fields[] = '_published';
            } else {
                //Check if field name is exist in selected table
                $fld = Fields::FieldRowByName($field_name_string, $this->ct->Table->fields);

                if (!is_array($fld)) {
                    $this->ct->app->enqueueMessage('Search box: Field name "' . $field_name_string . '" not found.', 'error');
                    return '';
                }

                if (count($fld) > 0)
                    $list_of_fields[] = $field_name_string;
            }
        }

        if (count($list_of_fields) == 0) {
            $this->ct->app->enqueueMessage('Search box: Field name "' . implode(',', $list_of_fields_string_or_array) . '" not found.', 'error');
            return '';
        }

        $vlu = 'Search field name is wrong';

        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'searchinputbox.php');

        $SearchBox = new SearchInputBox($this->ct, 'esSearchBox');

        $fld = [];

        $first_fld = $fld;
        $first_field_type = '';

        foreach ($list_of_fields as $field_name_string) {
            if ($field_name_string == '_id') {
                $fld = array(
                    'id' => 0,
                    'fieldname' => '_id',
                    'type' => '_id',
                    'typeparams' => '',
                    'fieldtitle' . $this->ct->Languages->Postfix => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID'),
                    'realfieldname' => $this->ct->Table->realtablename,
                    'isrequired' => false,
                    'defaultvalue' => null,
                    'valuerule' => null,
                    'valuerulecaption' => null
                );
            } elseif ($field_name_string == '_published') {
                $fld = array(
                    'id' => 0,
                    'fieldname' => '_published',
                    'type' => '_published',
                    'typeparams' => '',
                    'fieldtitle' . $this->ct->Languages->Postfix => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED'),
                    'realfieldname' => 'published',
                    'isrequired' => false,
                    'defaultvalue' => null,
                    'valuerule' => null,
                    'valuerulecaption' => null
                );
            } else {
                //Date search no implemented yet. It will be range search
                $fld = Fields::FieldRowByName($field_name_string, $this->ct->Table->fields);
                if ($fld['type'] == 'date') {
                    $fld['typeparams'] = 'date';
                    $fld['type'] = 'range';
                }
            }

            if ($first_field_type == '') {
                $first_field_type = $fld['type'];
                $first_fld = $fld;
            } else {
                // If field types are mixed then use string search
                if ($first_field_type != $fld['type'])
                    $first_field_type = 'string';
            }
        }

        $first_fld['type'] = $first_field_type;

        if (count($list_of_fields) > 1) {
            $first_fld['fields'] = $list_of_fields;
            $first_fld['typeparams'] = '';
        }

        //Add control elements
        $fieldTitles = $this->getFieldTitles($list_of_fields);
        $field_title = implode(' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR') . ' ', $fieldTitles);

        $cssClass = 'ctSearchBox';
        if ($class != '')
            $cssClass .= ' ' . $class;

        if ($improved)
            $cssClass .= ' ct_improved_selectbox';

        $default_Action = $reload ? ' onChange="ctSearchBoxDo();"' : ' ';//action should be a space not empty or this.value=this.value

        $objectName = $first_fld['fieldname'];

        if (count($first_fld) == 0)
            return 'Unsupported field type or field not found.';

        $vlu = $SearchBox->renderFieldBox('es_search_box_', $objectName, $first_fld,
            $cssClass, '0',
            '', false, '', $default_Action, $field_title);//action should be a space not empty or
        //0 because it's not an edit box, and we pass onChange value even " " is the value;

        $field2search = $this->prepareSearchElement($first_fld);
        $vlu .= '<input type=\'hidden\' ctSearchBoxField=\'' . $field2search . '\' />';

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    protected function getFieldTitles($list_of_fields): array
    {
        $field_titles = [];
        foreach ($list_of_fields as $fieldname) {
            if ($fieldname == '_id')
                $field_titles[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID');
            elseif ($fieldname == '_published')
                $field_titles[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED');
            else {
                foreach ($this->ct->Table->fields as $fld) {
                    if ($fld['fieldname'] == $fieldname) {
                        $field_titles[] = $fld['fieldtitle' . $this->ct->Languages->Postfix];
                        break;
                    }
                }
            }
        }
        return $field_titles;
    }

    protected function prepareSearchElement($fld)
    {
        if (isset($fld['fields']) and count($fld['fields']) > 0) {
            return 'es_search_box_' . $fld['fieldname'] . ':' . implode(';', $fld['fields']) . ':';
        } else {
            if ($fld['type'] == 'customtables') {
                $paramsList = explode(',', $fld['typeparams']);
                if (count($paramsList) > 1) {
                    $root = $paramsList[0];
                    return 'es_search_box_combotree_' . $this->ct->Table->tablename . '_' . $fld['fieldname'] . '_1:' . $fld['fieldname'] . ':' . $root;
                }
            } else
                return 'es_search_box_' . $fld['fieldname'] . ':' . $fld['fieldname'] . ':';
        }

        return '';
    }

    function searchbutton($label = '', $class_ = '')
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $class = 'ctSearchBox';

        if (isset($class_) and $class_ != '')
            $class .= ' ' . $class_;
        else
            $class .= ' btn button-apply btn-primary';

        $default_Label = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SEARCH');

        if ($label == strip_tags($label)) {
            if ($this->ct->Env->toolbaricons != '') {
                $img = '<i class=\'' . $this->ct->Env->toolbaricons . ' fa-search\' data-icon=\'' . $this->ct->Env->toolbaricons . ' fa-search\' title=\'' . $label . '\'></i>';
                $labelHtml = ($label !== '' ? '<span style=\'margin-left:10px;\'>' . $label . '</span>' : '');
            } else {
                $img = '';

                if ($label == '')
                    $label = $default_Label;

                $labelHtml = ($label !== '' ? '<span>' . $label . '</span>' : '');
            }
            return '<button class=\'' . $class . '\' onClick=\'ctSearchBoxDo()\' title=\'' . $default_Label . '\'>' . $img . $labelHtml . '</button>';
        } else {
            return '<button class=\'' . $class . '\' onClick=\'ctSearchBoxDo()\' title=\'' . $default_Label . '\'>' . $label . '</button>';
        }
    }

    function searchreset($label = '', $class_ = '')
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $class = 'ctSearchBox';

        if (isset($class_) and $class_ != '')
            $class .= ' ' . $class_;
        else
            $class .= ' btn button-apply btn-primary';

        $default_Label = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SEARCHRESET');
        if ($label == strip_tags($label)) {
            if ($this->ct->Env->toolbaricons != '') {
                $img = '<i class=\'' . $this->ct->Env->toolbaricons . ' fa-times\' data-icon=\'' . $this->ct->Env->toolbaricons . ' fa-times\' title=\'' . $label . '\'></i>';
                $labelHtml = ($label !== '' ? '<span style=\'margin-left:10px;\'>' . $label . '</span>' : '');
            } else {
                $img = '';

                if ($label == '')
                    $label = $default_Label;

                $labelHtml = ($label !== '' ? '<span>' . $label . '</span>' : '');
            }
            return '<button class=\'' . $class . '\' onClick=\'ctSearchReset()\' title=\'' . $default_Label . '\'>' . $img . $labelHtml . '</button>';
        } else {
            return '<button class=\'' . $class . '\' onClick=\'ctSearchReset()\' title=\'' . $default_Label . '\'>' . $label . '</button>';
        }
    }

    function message($text, $type = 'Message')
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $this->ct->app->enqueueMessage($text, $type);

        return null;
    }

    function navigation($list_type = 'list', $ul_css_class = '')
    {
        if ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != '')
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        $PathValue = $this->CleanNavigationPath($this->ct->Filter->PathValue);
        if (count($PathValue) == 0)
            return '';
        elseif ($list_type == '' or $list_type == 'list') {
            $vlu = '<ul' . ($ul_css_class != '' ? ' class="' . $ul_css_class . '"' : '') . '><li>' . implode('</li><li>', $PathValue) . '</li></ul>';
            return $vlu;
        } elseif ($list_type == 'comma')
            return implode(',', $PathValue);
        else
            return 'navigation: Unknown list type';
    }

    protected function CleanNavigationPath($thePath)
    {
        //Returns a list of unique search path criteria - eleminates duplicates
        $newPath = array();
        if (count($thePath) == 0)
            return $newPath;

        for ($i = count($thePath) - 1; $i >= 0; $i--) {
            $item = $thePath[$i];
            if (count($newPath) == 0)
                $newPath[] = $item;
            else {
                $found = false;
                foreach ($newPath as $newitem) {
                    if (!(strpos($newitem, $item) === false)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found)
                    $newPath[] = $item;
            }
        }
        return array_reverse($newPath);
    }

    function captcha()
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin or (!is_null($this->ct->Params->ModuleId) and $this->ct->Params->ModuleId != 0))
            return '';

        if (!is_null($this->ct->Params->ModuleId))
            return '';

        JHtml::_('behavior.keepalive');

        $p = $this->getReCaptchaParams();
        if ($p === null) {
            $this->ct->app->enqueueMessage('{{ html.captcha }} - Captcha plugin not enabled.', 'error');
            return '';
        }

        $reCaptchaParams = json_decode($p->params);

        if ($reCaptchaParams === null or $reCaptchaParams->public_key == "" or !isset($reCaptchaParams->size)) {
            $this->ct->app->enqueueMessage('{{ html.captcha }} - Captcha Public Key or size not set.', 'error');
            return '';
        }

        JPluginHelper::importPlugin('captcha');

        if ($this->ct->Env->version < 4) {
            $dispatcher = JEventDispatcher::getInstance();
            $dispatcher->trigger('onInit', 'my_captcha_div');
        } else {
            $this->ct->app->triggerEvent('onInit', array(null, 'my_captcha_div', 'class=""'));
        }

        $this->captcha_found = true;

        $vlu = '
    <div id="my_captcha_div"
		class="g-recaptcha"
		data-sitekey="' . $reCaptchaParams->public_key . '"
		data-theme="' . $reCaptchaParams->theme . '"
		data-size="' . $reCaptchaParams->size . '"
		data-callback="recaptchaCallback">
	</div>';

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;

    }

    protected function getReCaptchaParams()
    {
        $db = Factory::getDBO();
        $query = 'SELECT params FROM #__extensions WHERE ' . $db->quoteName("name") . '=' . $db->Quote("plg_captcha_recaptcha") . ' LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) == 0)
            return null;

        return $rows[0];
    }

    /* --------------------------- PROTECTED FUNCTIONS ------------------- */

    function button($type = 'save', $title = '', $redirectlink = null, $optional_class = '')
    {
        if ($this->ct->app->getName() == 'administrator')   //since   3.2
            $formName = 'adminForm';
        else
            $formName = 'eseditForm';

        $formName .= $this->ct->Params->ModuleId;

        if ($this->ct->Env->frmt != '' and $this->ct->Env->frmt != 'html')
            return '';

        if ($this->ct->Env->isPlugin)
            return '';

        if ($redirectlink === null and !is_null($this->ct->Params->returnTo))
            $redirectlink = $this->ct->Params->returnTo;

        switch ($type) {
            case 'save':
                $vlu = $this->renderSaveButton($optional_class, $title, $formName);
                break;

            case 'saveandclose':
                $vlu = $this->renderSaveAndCloseButton($optional_class, $title, $redirectlink, $formName);
                break;

            case 'saveandprint':
                $vlu = $this->renderSaveAndPrintButton($optional_class, $title, $redirectlink, $formName);
                break;

            case 'saveascopy':

                if (!isset($this->ct->Table->record[$this->ct->Table->realidfieldname]) or $this->ct->Table->record[$this->ct->Table->realidfieldname] == 0)
                    $vlu = '';
                else
                    $vlu = $this->renderSaveAsCopyButton($optional_class, $title, $redirectlink, $formName);
                break;

            case 'close':
            case 'cancel':
                $vlu = $this->renderCancelButton($optional_class, $title, $redirectlink, $formName);
                break;

            case 'delete':
                $vlu = $this->renderDeleteButton($optional_class, $title, $redirectlink, $formName);
                break;

            default:
                $vlu = '';

        }//switch

        //Not clear where and how this variable used.
        if ($this->ct->Env->frmt == 'json') {
            $this->button_objects[] = ['type' => $type, 'title' => $title, 'redirectlink' => $redirectlink];
            return $title;
        }

        if ($this->isTwig)
            return $vlu;
        else
            return $vlu;
    }

    protected function renderSaveButton($optional_class, $title, $formName)
    {
        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVE');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        $attribute = '';
        if ($this->captcha_found)
            $attribute = ' disabled="disabled"';

        if ($optional_class != '')
            $the_class = $optional_class;
        else
            $the_class = 'ctEditFormButton btn button-apply btn-success';

        $onclick = 'setTask(event, "saveandcontinue","' . $this->ct->Env->encoded_current_url . '",true,"' . $formName . '");';

        return '<input id="customtables_button_save" type="submit" class="' . $the_class . ' validate"' . $attribute . ' onClick=\'' . $onclick . '\' value="' . $title . '">';
    }

    protected function renderSaveAndCloseButton($optional_class, $title, $redirectlink, $formName)
    {
        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEANDCLOSE');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        $attribute = 'onClick=\'';

        $attribute .= 'setTask(event, "save","' . base64_encode($redirectlink) . '",true,"' . $formName . '");';

        $attribute .= '\'';

        if ($this->captcha_found)
            $attribute .= ' disabled="disabled"';

        if ($optional_class != '')
            $the_class = $optional_class;
        else
            $the_class = 'ctEditFormButton btn button-apply btn-success';

        return '<input id="customtables_button_saveandclose" type="submit" ' . $attribute . ' class="' . $the_class . ' validate" value="' . $title . '" />';
    }

    protected function renderSaveAndPrintButton($optional_class, $title, $redirectlink, $formName)
    {
        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEXT');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        $attribute = 'onClick=\'';
        $attribute .= 'setTask(event, "saveandprint","' . base64_encode($redirectlink) . '",true,"' . $formName . '");';
        $attribute .= '\'';

        if ($this->captcha_found)
            $attribute = ' disabled="disabled"';

        if ($optional_class != '')
            $the_class = $optional_class;
        else
            $the_class = 'ctEditFormButton btn button-apply btn-success';

        return '<input id="customtables_button_saveandprint" type="submit" ' . $attribute . ' class="' . $the_class . ' validate" value="' . $title . '" />';
    }

    protected function renderSaveAsCopyButton($optional_class, $title, $redirectlink, $formName)
    {
        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVEASCOPYANDCLOSE');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        $attribute = '';//onClick="return checkRequiredFields();"';
        if ($this->captcha_found)
            $attribute = ' disabled="disabled"';

        if ($optional_class != '')
            $the_class = $optional_class;//$the_class='ctEditFormButton '.$optional_class;
        else
            $the_class = 'ctEditFormButton btn button-apply btn-success';

        $onclick = 'setTask(event, "saveascopy","' . base64_encode($redirectlink) . '",true,"' . $formName . '");';

        return '<input id="customtables_button_saveandcopy" type="submit" class="' . $the_class . ' validate"' . $attribute . ' onClick=\'' . $onclick . '\' value="' . $title . '">';
    }

    protected function renderCancelButton($optional_class, $title, $redirectlink, $formName)
    {
        if ($this->ct->Env->isModal)
            return '';

        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CANCEL');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        if ($optional_class != '')
            $cancel_class = $optional_class;//$cancel_class='ctEditFormButton '.$optional_class;
        else
            $cancel_class = 'ctEditFormButton btn button-cancel';

        $onclick = 'setTask(event, "cancel","' . base64_encode($redirectlink) . '",true,"' . $formName . '");';
        return '<input id="customtables_button_cancel" type="button" class="' . $cancel_class . '" value="' . $title . '" onClick=\'' . $onclick . '\'>';
    }

    protected function renderDeleteButton($optional_class, $title, $redirectlink, $formName)
    {
        if ($title == '')
            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');

        if ($this->ct->Env->frmt == 'json')
            return $title;

        if ($optional_class != '')
            $class = $optional_class;//$class='ctEditFormButton '.$optional_class;
        else
            $class = 'ctEditFormButton btn button-cancel';

        $result = '<input id="customtables_button_delete" type="button" class="' . $class . '" value="' . $title . '"
				onClick=\'
                if (confirm("' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DO_U_WANT_TO_DELETE') . '"))
                {
                    this.form.task.value="delete";
                    ' . ($redirectlink != '' ? 'this.form.returnto.value="' . base64_encode($redirectlink) . '";' : '') . '
                    this.form.submit();
                }
                \'>
			';

        return $result;
    }

    function tablehead()
    {
        $result = '<thead>';
        $head_columns = func_get_args();

        foreach ($head_columns as $head_column)
            $result .= '<th>' . $head_column . '</th>';

        $result .= '</thead>';

        return $result;
    }

    function recordlist()
    {
        return $this->id_list();
    }

    protected function id_list()
    {
        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.list }} - Table not loaded.', 'error');
            return '';
        }

        if (!isset($this->ct->Records)) {
            $this->ct->app->enqueueMessage('{{ record.list }} - Records not loaded.', 'error');
            return '';
        }

        if ($this->ct->Table->recordlist === null)
            $this->ct->getRecordList();

        return implode(',', $this->ct->Table->recordlist);
    }

    function toolbar()
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';

        if ($this->ct->Env->isPlugin)
            return '';

        $modes = func_get_args();

        $edit_userGroup = (int)$this->ct->Params->editUserGroups;
        $publish_userGroup = (int)$this->ct->Params->publishUserGroups;
        if ($publish_userGroup == 0)
            $publish_userGroup = $edit_userGroup;

        $delete_userGroup = (int)$this->ct->Params->deleteUserGroups;
        if ($delete_userGroup == 0)
            $delete_userGroup = $edit_userGroup;

        $isEditable = CTUser::checkIfRecordBelongsToUser($this->ct, $edit_userGroup);
        $isPublishable = CTUser::checkIfRecordBelongsToUser($this->ct, $publish_userGroup);
        $isDeletable = CTUser::checkIfRecordBelongsToUser($this->ct, $delete_userGroup);

        $RecordToolbar = new RecordToolbar($this->ct, $isEditable, $isPublishable, $isDeletable);

        if (count($modes) == 0)
            $modes = ['edit', 'refresh', 'publish', 'delete'];

        if ($this->ct->Table->record === null)
            return '';

        $icons = [];
        foreach ($modes as $mode)
            $icons[] = $RecordToolbar->render($this->ct->Table->record, $mode);

        return implode('', $icons);
    }

    function base64encode($str)
    {
        return base64_encode($str);
    }

    function checkboxcount()
    {
        return '<span id="ctTable' . $this->ct->Table->tableid . 'CheckboxCount">0</span>';
    }
}
