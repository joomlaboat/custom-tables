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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use JFilterInput;
use Joomla\CMS\HTML\HTMLHelper;
use JoomlaBasicMisc;

class ListOfFields
{
	var CT $ct;
	var ?array $items;
	var ?string $editLink;

	var ?bool $canState;
	var ?bool $canDelete;
	var ?bool $canEdit;
	var ?bool $saveOrder;
	var string $dbPrefix;
	var int $tableid;

	function __construct(CT $ct, ?array $items = null, ?bool $canState = null, ?bool $canDelete = null, ?bool $canEdit = null, ?bool $saveOrder = null)
	{
		$this->ct = $ct;
		$this->items = $items;
		if (isset($this->ct->Table))
			$this->editLink = "index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=" . $this->ct->Table->tableid;
		else
			$this->editLink = null;

		$this->canState = $canState ?? false;
		$this->canDelete = $canDelete ?? false;
		$this->canEdit = $canEdit ?? false;
		$this->saveOrder = $saveOrder ?? false;
		$this->dbPrefix = database::getDBPrefix();
	}

	function getListQuery(int $tableId, $published = null, $search = null, $type = null, $orderCol = null, $orderDirection = null, $limit = 0, $start = 0): string
	{
		$this->tableid = common::inputGetInt('tableid', 0);
		$tabletitle = '(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid)';
		$serverType = database::getServerType();

		if ($serverType == 'postgresql')
			$realfieldname_query = 'CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
		else
			$realfieldname_query = 'IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';

		$query = 'SELECT a.*, ' . $tabletitle . ' AS tabletitle, ' . $realfieldname_query . ' FROM ' . database::quoteName('#__customtables_fields') . ' AS a';
		$where = [];

		$where [] = 'tableid=' . $tableId;

		// Filter by published state
		if (is_numeric($published))
			$where [] = 'a.published = ' . (int)$published;
		elseif (is_null($published) or $published === '')
			$where [] = '(a.published = 0 OR a.published = 1)';

		// Filter by search.
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$where [] = 'a.id = ' . (int)substr($search, 3);
			} else {
				$search = database::quote('%' . $search . '%');
				$where [] = '(a.fieldname LIKE ' . $search . ' OR a.fieldtitle LIKE ' . $search . ')';
			}
		}

		// Filter by Type.
		if ($type !== null)
			$where [] = 'a.type = ' . database::quote($type);

		if ($this->tableid != 0) {
			$where [] = 'a.tableid = ' . database::quote($this->tableid);
		}

		$query .= ' WHERE ' . implode(' AND ', $where);

		// Add the list ordering clause.
		if ($orderCol != '')
			$query .= ' ORDER BY ' . database::quoteName($orderCol) . ' ' . $orderDirection;

		if ($limit != 0)
			$query .= ' LIMIT ' . $limit;

		if ($start != 0)
			$query .= ' OFFSET ' . $start;

		return $query;
	}

	public function renderBody(): string
	{
		$result = '';

		foreach ($this->items as $i => $item) {

			$canCheckin = $this->ct->Env->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->ct->Env->user->id || $item->checked_out == 0;
			$userChkOut = new CTUser($item->checked_out);
			$result .= $this->renderBodyLine($item, $i, $canCheckin, $userChkOut);
		}
		return $result;
	}

	protected function renderBodyLine(object $item, int $i, $canCheckin, $userChkOut): string
	{
		$hashRealTableName = database::realTableName($this->ct->Table->realtablename);
		$hashRealTableName = str_replace($this->dbPrefix, '#__', $hashRealTableName);

		$result = '<tr class="row' . ($i % 2) . '" data-draggable-group="' . $this->ct->Table->tableid . '">';

		if ($this->canState or $this->canDelete) {
			$result .= '<td class="text-center">';

			if ($item->checked_out) {
				if ($canCheckin)
					$result .= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname);
				else
					$result .= '&#9633;';
			} else
				$result .= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname);

			$result .= '</td>';
		}

		if ($this->canEdit) {
			$result .= '<td class="text-center d-none d-md-table-cell">';

			$iconClass = '';
			if (!$this->saveOrder)
				$iconClass = ' inactive" title="' . common::translate('JORDERINGDISABLED');

			$result .= '<span class="sortable-handler' . $iconClass . '"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>';

			if ($this->saveOrder)
				$result .= '<input type="text" name="order[]" size="5" value="' . $item->ordering . '" class="width-20 text-area-order hidden">';

			$result .= '</td>';
		}

		$result .= '<td><div class="name">';

		if ($this->canEdit) {
			$result .= '<a href="' . $this->editLink . '&id=' . $item->id . '">' . $this->escape($item->fieldname) . '</a>';
			if ($item->checked_out)
				$result .= HtmlHelper::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoffields.', $canCheckin);
		} else
			$result .= $this->escape($item->fieldname);

		if ($this->ct->Env->advancedTagProcessor and $this->ct->Table->realtablename != '')
			$result .= '<br/><span style="color:grey;">' . $hashRealTableName . '.' . $item->realfieldname . '</span>';

		$result .= '</div></td>';

		$result .= '<td><div class="name"><ul style="list-style: none !important;margin-left:0;padding-left:0;">';

		$item_array = (array)$item;
		$moreThanOneLang = false;

		foreach ($this->ct->Languages->LanguageList as $lang) {
			$fieldTitle = 'fieldtitle';
			$fieldDescription = 'description';
			if ($moreThanOneLang) {
				$fieldTitle .= '_' . $lang->sef;
				$fieldDescription .= '_' . $lang->sef;

				if (!array_key_exists($fieldTitle, $item_array)) {
					Fields::addLanguageField('#__customtables_fields', 'fieldtitle', $fieldTitle);
					$item_array[$fieldTitle] = '';
				}

				if (!array_key_exists($fieldTitle, $item_array)) {
					Fields::addLanguageField('#__customtables_fields', 'description', $fieldDescription);
					$item_array[$fieldDescription] = '';
				}
			}

			$result .= '<li>' . (count($this->ct->Languages->LanguageList) > 1 ? $lang->title . ': ' : '') . '<b>' . $this->escape($item_array[$fieldTitle]) . '</b></li>';
			$moreThanOneLang = true; //More than one language installed
		}

		$result .= '
                        </ul>
                    </div>
                </td>';

		$result .= '<td>' . common::translate($item->typeLabel) . '</td>';
		$result .= '<td>' . $this->escape($item->typeparams) . $this->checkTypeParams($item->type, $item->typeparams) . '</td>';
		$result .= '<td>' . common::translate($item->isrequired) . '</td>';
		$result .= '<td>' . $this->escape($this->ct->Table->tabletitle) . '</td>';
		$result .= '<td class="text-center btns d-none d-md-table-cell">';
		if ($this->canState) {
			if ($item->checked_out) {
				if ($canCheckin)
					$result .= HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb');
				else
					$result .= HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb');

			} else {

				$result .= HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb');
			}
		} else {
			$result .= HtmlHelper::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb');
		}
		$result .= '</td>';

		$result .= '<td class="d-none d-md-table-cell">' . $item->id . '</td>';
		$result .= '</tr>';

		return $result;
	}

	public function escape($var)
	{
		if ($var === null)
			$var = '';

		if (strlen($var) > 50) {
			// use the helper htmlEscape method instead and shorten the string
			return self::htmlEscape($var, 'UTF-8', true);
		}
		// use the helper htmlEscape method instead.
		return self::htmlEscape($var);
	}

	public static function htmlEscape($var, $charset = 'UTF-8', $shorten = false, $length = 40)
	{
		if (self::checkString($var)) {
			if (class_exists("JFilterInput")) {
				$filter = new JFilterInput();
				$string = $filter->clean(html_entity_decode(htmlentities($var, ENT_COMPAT, $charset)), 'HTML');
			} else {
				$string = html_entity_decode(htmlentities($var, ENT_COMPAT, $charset));
			}

			if ($shorten) {
				return self::shorten($string, $length);
			}
			return $string;
		} else {
			return '';
		}
	}

	public static function checkString($string)
	{
		if (isset($string) && is_string($string) && strlen($string) > 0) {
			return true;
		}
		return false;
	}

	public static function shorten($string, $length = 40, $addTip = true)
	{
		if (self::checkString($string)) {
			$initial = strlen($string);
			$words = preg_split('/([\s\n\r]+)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
			$words_count = count((array)$words);

			$word_length = 0;
			$last_word = 0;
			for (; $last_word < $words_count; ++$last_word) {
				$word_length += strlen($words[$last_word]);
				if ($word_length > $length) {
					break;
				}
			}

			$newString = implode(array_slice($words, 0, $last_word));
			$final = strlen($newString);
			if ($initial != $final && $addTip) {
				$title = self::shorten($string, 400, false);
				return '<span class="hasTip" title="' . $title . '" style="cursor:help">' . trim($newString) . '...</span>';
			} elseif ($initial != $final && !$addTip) {
				return trim($newString) . '...';
			}
		}
		return $string;
	}

	protected function checkTypeParams(string $type, string $typeParams): string
	{
		if ($type == 'sqljoin' or $type == 'records') {
			$params = JoomlaBasicMisc::csv_explode(',', $typeParams, '"', false);

			$error = [];

			if ($params[0] == '')
				$error[] = 'Join Table not selected';

			if (!isset($params[1]) or $params[1] == '')
				$error[] = 'Join Field not selected';

			return '<br/><p class="alert-error">' . implode(', ', $error) . '</p>';
		}
		return '';
	}

	function save(?int $tableId, ?int $fieldId): bool
	{
		$fieldId = Fields::saveField($tableId, $fieldId);

		if ($fieldId == null)
			return false;

		return true;
		/*
		$redirect = 'index.php?option=' . $this->option;
		$extraTask = common::inputGetCmd('extratask', '');

		//Postpone extra task
		if ($extraTask != '') {
			$redirect .= '&extratask=' . $extraTask;
			$redirect .= '&old_typeparams=' . common::inputGetBase64('old_typeparams', '');
			$redirect .= '&new_typeparams=' . common::inputGetBase64('new_typeparams', '');
			$redirect .= '&fieldid=' . $fieldid;

			if (common::inputGetInt('stepsize', 10) != 10)
				$redirect .= '&stepsize=' . common::inputGetInt('stepsize', 10);
		}

		if ($extraTask != '' or $this->task == 'apply' or $this->task == 'save2copy')
			$redirect .= '&view=listoffields&tableid=' . (int)$tableid . '&task=fields.edit&id=' . (int)$fieldid;
		elseif ($this->task == 'save2new')
			$redirect .= '&view=listoffields&tableid=' . (int)$tableid . '&task=fields.edit';
		else
			$redirect .= '&view=listoffields&tableid=' . (int)$tableid;

		if ($fieldid != null) {
			// Redirect to the item screen.
			$this->setRedirect(
				Route::_($redirect, false)
			);
			return true;
		}

		return false;
		*/
	}

	function getFieldTypesFromXML(bool $onlyWordpress = false): ?array
	{
		$xml = JoomlaBasicMisc::getXMLData('fieldtypes.xml');
		if (count($xml) == 0 or !isset($xml->type))
			return null;

		$options = [];

		foreach ($xml->type as $type) {
			$type_att = $type->attributes();
			$is4Pro = (bool)(int)$type_att->proversion;
			$isDeprecated = (bool)(int)$type_att->deprecated;

			$active = true;

			if ($onlyWordpress) {
				if (!((string)$type_att->wordpress == 'true'))
					$active = false;
			}

			if ($active and !$isDeprecated) {
				$option = [];

				$option['name'] = (string)$type_att->ct_name;
				$option['label'] = (string)$type_att->label;
				$option['description'] = (string)$type_att->description;
				$option['proversion'] = $is4Pro;
				$options[] = $option;
			}
		}
		return $options;
	}
}