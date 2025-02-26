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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use JESPagination;
use Joomla\CMS\Router\Route;

class Twig_HTML_Tags
{
	var CT $ct;
	var bool $isTwig;
	var array $button_objects = []; //Not clear where and how this variable used.

	function __construct(CT &$ct, $isTwig = true)
	{
		$this->ct = &$ct;
		$this->isTwig = $isTwig;

		$this->ct->LayoutVariables['captcha'] = null;
		$this->button_objects = [];//Not clear where and how this variable used.
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function recordcount(): string
	{
		if ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != '')
			return '';

		if (!isset($this->ct->Table))
			throw new Exception('{{ html.recordcount }} - Table not loaded.');

		if (!isset($this->ct->Records))
			throw new Exception('{{ html.recordcount }} - Records not loaded.');

		return '<span class="ctCatalogRecordCount">' . common::translate('COM_CUSTOMTABLES_FOUND') . ': ' . $this->ct->Table->recordcount
			. ' ' . common::translate('COM_CUSTOMTABLES_RESULT_S') . '</span>';
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function add($Alias_or_ItemId = '', bool $isModal = false): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($Alias_or_ItemId !== '')
			$this->ct->Params->loadParameterUsingMenuAlias($Alias_or_ItemId);

		if (!$this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_ADD))
			return ''; //Not permitted

		if (defined('_JEXEC')) {

			$link = common::UriRoot(true, true);

			if ($Alias_or_ItemId !== '' and is_numeric($Alias_or_ItemId) and (int)$Alias_or_ItemId > 0)
				$link .= 'index.php?option=com_customtables'
					. '&amp;view=edititem'
					. '&amp;Itemid=' . $Alias_or_ItemId;
			elseif ($Alias_or_ItemId !== '') {
				$link .= 'index.php/' . $Alias_or_ItemId;
				$link = CTMiscHelper::deleteURLQueryOption($link, 'option');
				$link = CTMiscHelper::deleteURLQueryOption($link, 'edit');
				$link .= (str_contains($link, '?') ? '&amp;' : '?') . 'option=com_customtables';
				$link .= '&amp;view=edititem';

			} else {
				$link .= 'index.php?option=com_customtables'
					. '&amp;view=edititem'
					. '&amp;Itemid=' . $this->ct->Params->ItemId;
			}
			$link .= '&amp;task=new';

			if ($isModal) {
				$tmp_current_url = common::makeReturnToURL($this->ct->Env->current_url);//To have the returnto link that may include listing_id param.
				$link .= '&amp;returnto=' . $tmp_current_url;

				$link = 'javascript:ctEditModal(\'' . $link . '\',null)';
			} else {
				$link .= '&amp;returnto=' . $this->ct->Env->encoded_current_url;
			}

			//if (!empty($this->ct->Params->ModuleId))
			//	$link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

			if (common::inputGetCmd('tmpl', '') != '')
				$link .= '&amp;tmpl=' . common::inputGetCmd('tmpl', '');

			//if (!empty($this->ct->Params->ModuleId))
			//$link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;
		} elseif (defined('WPINC')) {
			$link = common::curPageURL();
			$link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);
			$link .= (str_contains($link, '?') ? '&amp;' : '?') . 'view' . $this->ct->Table->tableid . '=edititem';

			$link .= '&amp;task=new';

			if (!empty($this->ct->Env->encoded_current_url))
				$link .= '&amp;returnto=' . $this->ct->Env->encoded_current_url;
		} else {
			return '{{ html.add }} not supported.';
		}

		$icon = Icons::iconNew($this->ct->Env->toolbarIcons);
		return '<a href="' . $link . '" id="ctToolBarAddNew' . $this->ct->Table->tableid . '" class="toolbarIcons">' . $icon . '</a>';
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function importcsv(): string
	{
		if (defined('WPINC')) {
			return 'The tag "{{ html.importcsv() }}" not yet supported by WordPress version of the Custom Tables.';
		}

		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if (!$this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_ADD))
			return ''; //Not permitted

		$max_file_size = CTMiscHelper::file_upload_max_size();

		$fileid = common::generateRandomString();
		$fieldid = '9999999';//some unique number. TODO
		$objectName = 'importcsv';

		HTMLHelper::_('behavior.formvalidator');

		$url_string = Route::_('index.php?option=com_customtables&amp;view=fileuploader&amp;tmpl=component'
			. '&amp;tableid=' . $this->ct->Table->tableid
			. '&amp;task=importcsv'
			. '&amp;' . $objectName . '_fileid=' . $fileid
			. '&amp;Itemid=' . $this->ct->Params->ItemId
			. (is_null($this->ct->Params->ModuleId) ? '' : '&amp;ModuleId=' . $this->ct->Params->ModuleId)
			. '&amp;fieldname=' . $objectName);

		return '<div>
                    <div id="ct_fileuploader_' . $objectName . '"></div>
                    <div id="ct_eventsmessage_' . $objectName . '"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        //UploadFileCount=1;
                    	ct_getUploader(' . $fieldid . ',"' . $url_string . '",' . $max_file_size . ',"csv","ctUploadCSVForm",true,"ct_fileuploader_' . $objectName . '","ct_eventsmessage_' . $objectName . '","' . $fileid . '","'
			. $this->ct->Table->fieldInputPrefix . $objectName . '","ct_uploadedfile_box_' . $objectName . '")
                    </script>
                    <input type="hidden" name="' . $this->ct->Table->fieldInputPrefix . $objectName . '" id="' . $this->ct->Table->fieldInputPrefix . $objectName . '" value="" />
                    <input type="hidden" name="' . $this->ct->Table->fieldInputPrefix . $objectName . '_filename" id="' . $this->ct->Table->fieldInputPrefix . $objectName . '_filename" value="" />
			' . common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($max_file_size) . '
                    </form>
                </div>
';
	}

	function pagination($show_arrow_icons = false): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if ($this->ct->Table->recordcount <= $this->ct->Limit)
			return '';

		if (!empty($this->ct->Params->ModuleId))
			return '';

		if (defined('_JEXEC')) {
			$pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit, '', $show_arrow_icons);
		} elseif (defined('WPINC')) {
			return '{{ html.pagination }} not supported in WordPress version';
		} else {
			return '{{ html.pagination }} not supported in this type of CMS';
		}

		if (CUSTOMTABLES_JOOMLA_MIN_4)
			return '<div style="display:inline-block;">' . $pagination->getPagesLinks() . '</div>';
		else
			return '<div class="pagination">' . $pagination->getPagesLinks() . '</div>';
	}

	function limit($the_step = 5, $showLabel = false, $CSS_Class = null): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		$result = '';
		if ($showLabel)
			$result .= common::translate('COM_CUSTOMTABLES_SHOW') . ': ';

		if ($CSS_Class === null) {
			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$CSS_Class = 'form-select';
			else
				$CSS_Class = 'inputbox';
		}

		$result .= $this->getLimitBox((int)$the_step, $CSS_Class);
		return $result;
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page.
	 *
	 * @return  string   The HTML for the limit # input box.
	 * @since   3.5.4
	 */
	protected function getLimitBox(int $the_step, string $CSS_Class): string
	{
		$all = false;

		if ($the_step < 1)
			$the_step = 1;

		if ($the_step > 1000)
			$the_step = 1000;

		$limit = (int)max($this->ct->Limit, 0);

		// If we are viewing all records set the view all flag to true.
		if ($limit == 0)
			$all = true;

		// Initialise variables.
		$limitOptions = [];

		// Make the option list.
		for ($i = $the_step; $i <= $the_step * 6; $i += $the_step)
			$limitOptions [] = ['value' => $i, 'label' => $i];

		$limitOptions [] = ['value' => $the_step * 10, 'label' => $the_step * 10];
		$limitOptions [] = ['value' => $the_step * 20, 'label' => $the_step * 20];
		$selected = $all ? 0 : $limit;

		$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
		// Build the select list.

		$options = [];

		foreach ($limitOptions as $limitOption) {
			$isSelected = ($selected === $limitOption['value']) ? ' selected' : '';
			$options[] = '<option value="' . htmlspecialchars($limitOption['value'], ENT_QUOTES) . '"' . $isSelected . '>'
				. htmlspecialchars($limitOption['label'] ?? '', ENT_QUOTES) . '</option>';
		}

		return '<select name="limit" id="limit" onChange="ctLimitChanged(this.value, ' . $moduleIDString . ')" class="' . $CSS_Class . '">'
			. PHP_EOL . implode(PHP_EOL, $options) . PHP_EOL . '</select>';
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function orderby($listOfFields = null, $showLabel = false, $CSS_Class = null): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if ($this->ct->Params->forceSortBy !== null and $this->ct->Params->forceSortBy != '')
			throw new Exception(common::translate('COM_CUSTOMTABLES_ERROR_SORT_BY_FIELD_LOCKED'));

		$result = '';
		if ($showLabel)
			$result .= common::translate('COM_CUSTOMTABLES_ORDER_BY') . ': ';

		if ($CSS_Class === null) {
			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$CSS_Class = 'form-select';
			else
				$CSS_Class = 'inputbox';
		}

		$result .= $this->getOrderBox($this->ct->Ordering, $listOfFields, $CSS_Class);
		return $result;
	}

	protected function getOrderBox(Ordering $ordering, ?string $listOfFields, string $CSS_Class): string
	{
		$listOfFields_Array = !empty($listOfFields) ? explode(",", $listOfFields) : [];
		$lists = $ordering->getSortByFields();

		// Initialize the sorting options with a default "Order By" placeholder
		$fieldsToSort = [
			['value' => '', 'label' => ' - ' . common::translate('COM_CUSTOMTABLES_ORDER_BY')]
		];

		// Filter sorting fields if a list is provided
		if (!empty($listOfFields_Array)) {
			foreach ($lists as $list) {

				$fieldName = trim(strtok($list['value'], " ")); // Extract first part before space

				if (in_array($fieldName, $listOfFields_Array, true))
					$fieldsToSort[] = ['value' => $list['value'], 'label' => $list['label']];
			}
		} else {
			$fieldsToSort = array_merge($fieldsToSort, $lists);
		}

		$moduleIDString = $ordering->Params->ModuleId ?? 'null';
		$options = [];

		foreach ($fieldsToSort as $sortField) {
			$isSelected = ($ordering->ordering_processed_string === $sortField['value']) ? ' selected' : '';
			$options[] = '<option value="' . htmlspecialchars($sortField['value'], ENT_QUOTES) . '"' . $isSelected . '>'
				. htmlspecialchars($sortField['label'] ?? '', ENT_QUOTES) . '</option>';
		}

		return '<select name="esordering" id="esordering" onChange="ctOrderChanged(this.value, ' . $moduleIDString . ')" class="' . $CSS_Class . '">'
			. PHP_EOL . implode(PHP_EOL, $options) . PHP_EOL . '</select>';
	}


	/**
	 * $returnto must be provided already decoded
	 *
	 * @throws Exception
	 * @since 3.0.0
	 */
	function goback($defaultLabel = null, $image_icon = '', $attribute = '', string $returnto = '', string $class = ''): string //WordPress Ready
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if ($returnto == '')
			$returnto = common::getReturnToURL() ?? '';

		if ($returnto == '')
			$returnto = $this->ct->Params->returnTo;

		if ($returnto == '')
			return '';

		if ($defaultLabel === null or $defaultLabel == common::ctStripTags($defaultLabel)) {

			if ($defaultLabel === null)
				$label = common::translate('COM_CUSTOMTABLES_GO_BACK');
			else
				$label = $defaultLabel;

			$icon = Icons::iconGoBack($this->ct->Env->toolbarIcons, $label, $image_icon);

			if ($label === '')
				$content = $icon;
			else
				$content = $icon . '<span>' . $label . '</span>';

			return '<a href="' . $returnto . '"' . (!empty($attribute) ? ' ' . $attribute : '') . (!empty($class) ? ' class="' . $class . '"' : '') . '>' . $content . '</a>';
		} else {
			return '<a href="' . $returnto . '"' . (!empty($attribute) ? ' ' . $attribute : '') . (!empty($class) ? ' class="' . $class . '"' : '') . '>' . $defaultLabel . '</a>';
		}
	}

	function batch(): string
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

		if (count($buttons) == 0)
			$buttons = $available_modes;

		$html_buttons = [];

		foreach ($buttons as $mode) {
			if ($mode == 'checkbox') {
				$html_buttons[] = '<input type="checkbox" id="esCheckboxAll' . $this->ct->Table->tableid . '" onChange="esCheckboxAllClicked(' . $this->ct->Table->tableid . ')" />';
			} else {
				if (in_array($mode, $available_modes)) {
					$rid = 'esToolBar_' . $mode . '_box_' . $this->ct->Table->tableid;

					switch ($mode) {
						case 'publish':
							$icons = Icons::iconPublished($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_PUBLISH_SELECTED'));
							break;
						case 'unpublish':
							$icons = Icons::iconUnpublished($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_UNPUBLISH_SELECTED'));
							break;
						case 'refresh':
							$icons = Icons::iconRefresh($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_REFRESH_SELECTED'));
							break;
						case 'delete':
							$icons = Icons::iconDelete($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_DELETE_SELECTED'));
							break;
						default:
							return 'unsupported batch toolbar icon.';
					}
					$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
					$link = 'javascript:ctToolBarDO("' . $mode . '", ' . $this->ct->Table->tableid . ', ' . $moduleIDString . ')';
					$html_buttons[] = '<div id="' . $rid . '" class="toolbarIcons"><a href=\'' . $link . '\'>' . $icons . '</a></div>';
				}
			}
		}

		if (count($html_buttons) == 0)
			return '';

		return implode('', $html_buttons);
	}

	protected function getAvailableModes(): array
	{
		$available_modes = array();
		$publish_userGroups = $this->ct->Params->publishUserGroups;

		if ($this->ct->Env->user->checkUserGroupAccess($publish_userGroups)) {
			$available_modes[] = 'publish';
			$available_modes[] = 'unpublish';
		}

		$edit_userGroups = $this->ct->Params->editUserGroups;

		if ($this->ct->Env->user->checkUserGroupAccess($edit_userGroups)) {
			$available_modes[] = 'edit';
			$available_modes[] = 'refresh';
		}

		$delete_userGroups = $this->ct->Params->deleteUserGroups;
		if ($this->ct->Env->user->checkUserGroupAccess($delete_userGroups))
			$available_modes[] = 'delete';

		return $available_modes;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function print($linkType = '', $defaultLabel = null, $class = 'ctEditFormButton btn button-apply btn-primary'): string
	{
		if ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != '')
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if (empty($defaultLabel))
			$label = common::translate('COM_CUSTOMTABLES_PRINT');
		else
			$label = $defaultLabel;

		$icon = Icons::iconPrint($this->ct->Env->toolbarIcons, $label);

		if ($this->ct->Env->print == 1)
			return '<p><a class="no-print" href="#" onclick="window.print();return false;">' . $icon . '</a></p>';

		$link = $this->ct->Env->current_url . (!str_contains($this->ct->Env->current_url, '?') ? '?' : '&') . 'tmpl=component&amp;print=1';

		if (common::inputGetInt('moduleid', 0) != 0) {
			//search module

			$moduleid = common::inputGetInt('moduleid', 0);
			$link .= '&amp;moduleid=' . $moduleid;

			//keyword search
			$inputbox_name = 'eskeysearch_' . $moduleid;
			$link .= '&amp;' . $inputbox_name . '=' . common::inputGetString($inputbox_name, '');
		}

		$onClick = 'window.open("' . $link . '","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no");return false;';

		return $this->renderButtonOrIcon($linkType, $label, $class, $icon, $onClick);
	}

	protected function renderButtonOrIcon($linkType, $label, $class, $icon, $onClick): string
	{
		if ($linkType == 'linkicon')
			return '<a href="#" onclick=\'' . $onClick . '\'>' . $icon . '</a>';
		elseif ($linkType == 'linklabel')
			return '<a href="#" onclick=\'' . $onClick . '\'><span>' . $label . '</span></a>';
		elseif ($linkType == 'linkiconlabel')
			return '<a href="#" onclick=\'' . $onClick . '\'>' . $icon . '<span>' . $label . '</span></a>';
		elseif ($linkType == 'buttonicon')
			return '<button class="' . $class . '" onclick=\'' . $onClick . '\' title="' . $label . '">' . $icon . '</button>';
		elseif ($linkType == 'buttonlabel')
			return '<button class="' . $class . '" onclick=\'' . $onClick . '\' title="' . $label . '"><span>' . $label . '</span></button>';
		elseif ($linkType == 'buttoniconlabel')
			return '<button class="' . $class . '" onclick=\'' . $onClick . '\' title="' . $label . '">' . $icon . '<span>' . $label . '</span></button>';
		else
			return '<button class="' . $class . '" onclick=\'' . $onClick . '\' title="' . $label . '">' . $icon . '<span>' . $label . '</span></button>';
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function search($list_of_fields_string_or_array = null, $class = '', $reload = false, $improved = '', $matchType = "", $stringLength = ""): string
	{
		$fld = null;

		if (is_string($reload))
			$reload = $reload == 'reload';

		if ($list_of_fields_string_or_array === null)
			return '{{ html.search() }} tag requires at least one field name.';

		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		if (is_array($list_of_fields_string_or_array))
			$list_of_fields_string_array = $list_of_fields_string_or_array;
		else
			$list_of_fields_string_array = explode(',', $list_of_fields_string_or_array);

		if (count($list_of_fields_string_array) == 0)
			throw new Exception('Search box: Please specify a field name.');

		$first_fld_layout = null;

		//Clean list of fields
		$list_of_fields = [];

		$wrong_list_of_fields = [];
		foreach ($list_of_fields_string_array as $field_name_string_pair) {

			$field_name_pair = explode(':', $field_name_string_pair);
			$field_name_string = $field_name_pair[0];
			if ($first_fld_layout === null and isset($field_name_pair[1]))
				$first_fld_layout = $field_name_pair[1];

			if ($field_name_string == '_id') {
				$list_of_fields[] = '_id';
			} elseif ($field_name_string == '_published') {
				$list_of_fields[] = '_published';
			} else {
				//Check if field name is exist in selected table
				$fld = $this->ct->Table->getFieldByName($field_name_string);

				if (!is_array($fld))
					throw new Exception('Search box: Field name "' . $field_name_string . '" not found.');

				if (count($fld) > 0)
					$list_of_fields[] = $field_name_pair[0];
				else
					$wrong_list_of_fields[] = $field_name_string_pair;
			}
		}

		if (count($list_of_fields) == 0)
			throw new Exception('Search box: Field' . (count($wrong_list_of_fields) > 0 ? 's' : '') . ' "' . implode(',', $wrong_list_of_fields) . '" not found.');

		$vlu = 'Search field name is wrong';

		require_once(CUSTOMTABLES_LIBRARIES_PATH
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'searchinputbox.php');

		$SearchBox = new SearchInputBox($this->ct, 'esSearchBox');
		$first_fld = [];
		$first_field_type = '';

		foreach ($list_of_fields as $field_name_string) {
			if ($field_name_string == '_id') {
				$fld = array(
					'id' => 0,
					'fieldname' => '_id',
					'type' => '_id',
					'typeparams' => '',
					'fieldtitle' . $this->ct->Languages->Postfix => common::translate('COM_CUSTOMTABLES_ID'),
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
					'fieldtitle' . $this->ct->Languages->Postfix => common::translate('COM_CUSTOMTABLES_PUBLISHED'),
					'realfieldname' => 'listing_published',
					'isrequired' => false,
					'defaultvalue' => null,
					'valuerule' => null,
					'valuerulecaption' => null
				);
			}

			if ($fld !== null) {
				if ($first_field_type == '') {
					$first_field_type = $fld['type'];//TODO: Verify this part, something not right here
					$first_fld = $fld;
				} else {
					// If field types are mixed then use string search
					if ($first_field_type != $fld['type'])
						$first_field_type = 'string';
				}
			}
		}
		$first_fld['type'] = $first_field_type;

		if (count($list_of_fields) > 1) {
			$first_fld['fields'] = $list_of_fields;
			$first_fld['typeparams'] = '';
		}

		$first_fld['layout'] = $first_fld_layout;

		//Add control elements
		$fieldTitles = $this->getFieldTitles($list_of_fields);
		$field_title = implode(' ' . common::translate('COM_CUSTOMTABLES_OR') . ' ', $fieldTitles);
		$cssClass = 'ctSearchBox';

		if ($class != '')
			$cssClass .= ' ' . $class;

		if ($improved == 'improved')
			$cssClass .= ' ct_improved_selectbox';
		elseif ($improved == 'virtualselect')
			$cssClass .= ($cssClass == '' ? '' : ' ') . ' ct_virtualselect_selectbox';

		$onchange = $reload ? 'ctSearchBoxDo();' : null;//action should be a space not empty or this.value=this.value

		$objectName = $first_fld['fieldname'];

		if (count($first_fld) == 0)
			return 'Unsupported field type or field not found.';

		$vlu = $SearchBox->renderFieldBox($this->ct->Table->fieldInputPrefix . 'search_box_', $objectName, $first_fld,
			$cssClass, '0',
			'', '', $onchange, $field_title, $matchType, $stringLength);//action should be a space not empty or
		//0 because it's not an edit box, and we pass onChange value even " " is the value;

		$field2search = $this->prepareSearchElement($first_fld);
		$vlu .= '<input type=\'hidden\' ctSearchBoxField=\'' . $field2search . '\' />';

		return $vlu;
	}

	protected function getFieldTitles($list_of_fields): array
	{
		$field_titles = [];
		foreach ($list_of_fields as $fieldname_string) {

			$fieldname_pair = explode(':', $fieldname_string);
			$fieldname = $fieldname_pair[0];

			if ($fieldname == '_id')
				$field_titles[] = common::translate('COM_CUSTOMTABLES_ID');
			elseif ($fieldname == '_published')
				$field_titles[] = common::translate('COM_CUSTOMTABLES_PUBLISHED');
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

	protected function prepareSearchElement($fld): string
	{
		if (isset($fld['fields']) and count($fld['fields']) > 0) {
			return $this->ct->Table->fieldInputPrefix . 'search_box_' . $fld['fieldname'] . ':' . implode(';', $fld['fields']) . ':';
		} else {
			return $this->ct->Table->fieldInputPrefix . 'search_box_' . $fld['fieldname'] . ':' . $fld['fieldname'] . ':';
		}
	}

	function searchbutton($linkType = '', $defaultLabel = null, $class_ = ''): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		$class = 'ctSearchBox';

		if (!empty($class_))
			$class .= ' ' . $class_;
		else
			$class .= ' btn button-apply btn-primary';

		if (empty($defaultLabel))
			$label = common::translate('COM_CUSTOMTABLES_SEARCH');
		else
			$label = $defaultLabel;

		$icon = Icons::iconSearch($this->ct->Env->toolbarIcons, $label);

		$onClick = 'ctSearchBoxDo()';

		return $this->renderButtonOrIcon($linkType, $label, $class, $icon, $onClick);
	}

	function searchreset($label = '', $class_ = ''): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		$class = 'ctSearchBox';

		if (isset($class_) and $class_ != '')
			$class .= ' ' . $class_;
		else
			$class .= ' btn button-apply btn-primary';

		$default_Label = common::translate('COM_CUSTOMTABLES_SEARCHRESET');
		if ($label == common::ctStripTags($label)) {
			if ($this->ct->Env->toolbarIcons != '') {
				$img = '<i class=\'' . $this->ct->Env->toolbarIcons . ' fa-times\' data-icon=\'' . $this->ct->Env->toolbarIcons . ' fa-times\' title=\'' . $label . '\'></i>';
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

	function message($text, $type = 'notice'): ?string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		common::enqueueMessage($text, $type);
		return null;
	}

	function navigation($list_type = 'list', $ul_css_class = ''): string
	{
		if ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != '')
			return '';

		if (!empty($this->ct->Params->ModuleId))
			return '';

		$PathValue = $this->CleanNavigationPath($this->ct->Filter->PathValue);
		if (count($PathValue) == 0)
			return '';
		elseif ($list_type == '' or $list_type == 'list') {
			return '<ul' . ($ul_css_class != '' ? ' class="' . $ul_css_class . '"' : '') . '><li>' . implode('</li><li>', $PathValue) . '</li></ul>';
		} elseif ($list_type == 'comma')
			return implode(',', $PathValue);
		else
			return 'navigation: Unknown list type';
	}

	protected function CleanNavigationPath($thePath): array
	{
		//Returns a list of unique search path criteria - eliminates duplicates
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
					if (str_contains($newitem, $item)) {
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

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function captcha(): string
	{
		if (!$this->ct->Env->advancedTagProcessor)
			return '{{ html.captcha }} - Captcha Available in PRO Version only.';

		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		if ($this->ct->Env->isPlugin or !empty($this->ct->Params->ModuleId))
			return '';

		$site_key = null;
		$secret_key = null;

		$functionParams = func_get_args();
		if (isset($functionParams[0]))
			$site_key = $functionParams[0];

		if (isset($functionParams[1]))
			$secret_key = $functionParams[1];

		if (defined('_JEXEC')) {
			$app = Factory::getApplication();
			$document = $app->getDocument();
			$document->addCustomTag('<script src="https://www.google.com/recaptcha/api.js"></script>');
		}

		$this->ct->LayoutVariables['captcha'] = true;
		$this->ct->LayoutVariables['captcha_secret_key'] = $secret_key;

		if ($site_key === null)
			return 'The tag "{{ html.captcha(SITE_KEY) }}" please provide the reCaptcha Site Key';

		if ($secret_key === null)
			return 'The tag "{{ html.captcha(SITE_KEY, SECRET_KEY) }}" please provide the reCaptcha Secret Key';

		return '
    <div id="my_captcha_div"
		class="g-recaptcha"
		data-sitekey="' . $site_key . '"
		data-callback="recaptchaCallback">
	</div>';
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function button($type = 'save', $title = '', $redirectlink = null, $optional_class = '')
	{
		if (defined('_JEXEC')) {
			if (common::clientAdministrator())   //since   3.2
				$formName = 'adminForm';
			else {
				if ($this->ct->Env->isModal)
					$formName = 'ctEditModalForm';
				else {
					$formName = 'ctEditForm';
					$formName .= $this->ct->Params->ModuleId;
				}
			}

		} elseif (defined('WPINC')) {
			$formName = 'ctEditForm';
		} else {
			return '{{ html.button }} is not available in this environment';
		}

		if ($this->ct->Env->frmt != '' and $this->ct->Env->frmt != 'html')
			return '1';

		if ($this->ct->Env->isPlugin)
			return '2';

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
				$vlu = $this->renderDeleteButton($optional_class, $title, $redirectlink);
				break;

			default:
				$vlu = '';

		}//switch

		//Not clear where and how this variable used.
		if ($this->ct->Env->frmt == 'json') {
			$this->button_objects[] = ['type' => $type, 'title' => $title, 'redirectlink' => $redirectlink];
			return $title;
		}

		return $vlu;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderSaveButton($optional_class, $title, $formName): string
	{
		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_SAVE');

		return $this->renderButtonHTML($optional_class, $title, $formName, "customtables_button_save", $this->ct->Env->encoded_current_url,
			true, "saveandcontinue");
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderButtonHTML($optional_class, string $title, $formName, string $buttonId,
										string $redirect, bool $checkCaptcha, string $task): string
	{
		if ($this->ct->Env->frmt == 'json')
			return $title;

		$attribute = '';
		if ($checkCaptcha and ($this->ct->LayoutVariables['captcha'] ?? null))
			$attribute = ' disabled="disabled"';

		if ($optional_class != '')
			$the_class = $optional_class;
		else
			$the_class = 'ctEditFormButton btn button-apply btn-success';

		$the_class .= ' validate';

		$isModal = ($this->ct->Env->isModal ? 'true' : 'false');
		$parentField = common::inputGetCmd('parentfield');

		if ($parentField === null)
			$onclick = 'setTask(event, "' . $task . '","' . $redirect . '",true,"' . $formName . '",' . $isModal . ',null,' . ($this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId) . ');';
		else
			$onclick = 'setTask(event, "' . $task . '","' . $redirect . '",true,"' . $formName . '",' . $isModal . ',"' . $parentField . '"' . ($this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId) . ');';

		return '<input id="' . $buttonId . '" type="submit" class="' . common::convertClassString($the_class) . '"' . $attribute . ' onClick=\'' . $onclick . '\' value="' . $title . '">';
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderSaveAndCloseButton($optional_class, $title, $redirectLink, $formName): string
	{
		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_SAVEANDCLOSE');

		$returnToEncoded = common::makeReturnToURL($redirectLink);
		return $this->renderButtonHTML($optional_class, $title, $formName, "customtables_button_saveandclose", $returnToEncoded, true, "save");
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderSaveAndPrintButton($optional_class, $title, $redirectLink, $formName): string
	{
		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_NEXT');

		$returnToEncoded = common::makeReturnToURL($redirectLink);

		return $this->renderButtonHTML($optional_class, $title, $formName, "customtables_button_saveandprint", $returnToEncoded, true, "saveandprint");
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderSaveAsCopyButton($optional_class, $title, $redirectLink, $formName): string
	{
		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_SAVEASCOPYANDCLOSE');

		$returnToEncoded = common::makeReturnToURL($redirectLink);

		return $this->renderButtonHTML($optional_class, $title, $formName, "customtables_button_saveandcopy", $returnToEncoded, true, "saveascopy");
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function renderCancelButton($optional_class, $title, $redirectLink, $formName): string
	{
		if ($this->ct->Env->isModal)
			return '';

		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_CANCEL');

		if ($optional_class != '')
			$cancel_class = $optional_class;
		else
			$cancel_class = 'ctEditFormButton btn button-cancel';

		$returnToEncoded = common::makeReturnToURL($redirectLink);
		return $this->renderButtonHTML($cancel_class, $title, $formName, "customtables_button_cancel", $returnToEncoded, false, "cancel");
	}

	protected function renderDeleteButton($optional_class, $title, $redirectLink)
	{
		if ($title == '')
			$title = common::translate('COM_CUSTOMTABLES_DELETE');

		if ($this->ct->Env->frmt == 'json')
			return $title;

		if ($optional_class != '')
			$class = $optional_class;
		else
			$class = 'ctEditFormButton btn button-cancel';

		$returnToEncoded = common::makeReturnToURL($redirectLink) ?? '';

		return '<input id="customtables_button_delete" type="button" class="' . $class . '" value="' . $title . '"
				onClick=\'
                if (confirm("' . common::translate('COM_CUSTOMTABLES_DO_U_WANT_TO_DELETE') . '"))
                {
                    this.form.task.value="delete";
                    ' . ($returnToEncoded != '' ? 'this.form.returnto.value="' . $returnToEncoded . '";' : '') . '
                    this.form.submit();
                }
                \'>' . PHP_EOL;
	}

	function tablehead(): string
	{
		$result = '<thead>';
		$head_columns = func_get_args();

		foreach ($head_columns as $head_column)
			$result .= '<th>' . $head_column . '</th>';

		$result .= '</thead>';

		return $result;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function recordlist(): string
	{
		return $this->id_list();
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function id_list(): string
	{
		if (!isset($this->ct->Table))
			throw new Exception('{{ record.list }} - Table not loaded.');

		if (!isset($this->ct->Records))
			throw new Exception('{{ record.list }} - Records not loaded.');

		if ($this->ct->Table->recordlist === null)
			$this->ct->getRecordList();

		return implode(',', $this->ct->Table->recordlist);
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	function toolbar(): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return '';

		$modes = func_get_args();

		$isAddable = $this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_ADD);
		$isEditable = $this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT);
		$isPublishable = $this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_PUBLISH);
		$isDeletable = $this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_DELETE);

		$RecordToolbar = new RecordToolbar($this->ct, $isAddable, $isEditable, $isPublishable, $isDeletable);

		if (count($modes) == 0)
			$modes = ['edit', 'refresh', 'publish', 'delete'];

		if ($this->ct->Table->record === null)
			return '';

		$icons = [];
		foreach ($modes as $mode)
			$icons[] = $RecordToolbar->render($this->ct->Table->record, $mode);

		return implode('', $icons);
	}

	function checkboxcount(): string
	{
		return '<span id="ctTable' . $this->ct->Table->tableid . 'CheckboxCount">0</span>';
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function paypal(): ?string
	{
		//string $business_email, string $item_name, float $price, bool $isProduction = true
		$functionParams = func_get_args();

		if (!isset($functionParams[0]) or $functionParams[0] == '') {
			throw new Exception('{{ html.paypal(email) }} business email address is required.');
		} else
			$business_email = $functionParams[0];

		if (!isset($functionParams[1]) or $functionParams[1] == '') {
			throw new Exception('{{ html.paypal(email,item_name) }} item name is required.');
		} else
			$item_name = $functionParams[1];

		if (!isset($functionParams[2]) or $functionParams[2] == '') {
			throw new Exception('{{ html.paypal(email,item_name,price) }} price must be more than zero.');
		} else
			$price = (float)$functionParams[2];

		if (isset($functionParams[3]) and $functionParams[3]) {
			//Sandbox
			$PayPalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			//Production
			$PayPalURL = 'https://www.paypal.com/cgi-bin/webscr';
		}

		return '<form action="' . $PayPalURL . '" method="post">
    <!-- Identify your business so that you can collect the payments. -->
    <input type="hidden" name="business" value="' . $business_email . '" />

    <!-- Specify a Buy Now button. -->
    <input type="hidden" name="cmd" value="_xclick" />

    <!-- Specify details about the item that buyers will purchase. -->
    <input type="hidden" name="item_name" value="' . $item_name . '" />
    <input type="hidden" name="amount" value="' . $price . '" />
    <input type="hidden" name="currency_code" value="USD" />

    <!-- Display the payment button. -->
    <input type="image" name="submit" style="border:none;display:inline-block;" src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/paypal-checkout-button.png"
           alt="Buy Now">
    <div style="position:absolute;"><img style="border:none;width:1px;height:1px;display:inline-block;margin:0;"
                                         alt="PayPal Buy Now" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif"
                                         class="button"></div>
</form>';
	}
}
