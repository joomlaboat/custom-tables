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

use JoomlaBasicMisc;
use LayoutProcessor;
use tagProcessor_PHP;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class Details
{
	var CT $ct;
	var string $layoutDetailsContent;
	var ?array $row;
	var int $layoutType;
	var ?string $pageLayoutNameString;
	var ?string $pageLayoutLink;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
		$this->layoutType = 0;
	}

	function load($layoutDetailsContent = null): bool
	{
		if (!$this->loadRecord())
			return false;

		$this->pageLayoutNameString = null;
		$this->pageLayoutLink = null;

		if (is_null($layoutDetailsContent)) {
			$this->layoutDetailsContent = '';

			if ($this->ct->Params->detailsLayout != '') {
				$Layouts = new Layouts($this->ct);
				$this->layoutDetailsContent = $Layouts->getLayout($this->ct->Params->detailsLayout);
				$this->pageLayoutNameString = $this->ct->Params->detailsLayout;
				$this->pageLayoutLink = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

				if ($Layouts->layoutType === null) {
					echo 'Layout "' . $this->ct->Params->detailsLayout . '" not found or the type is not set.';
					return false;
				}

				$this->layoutType = $Layouts->layoutType;
			} else {
				$Layouts = new Layouts($this->ct);
				$this->layoutDetailsContent = $Layouts->createDefaultLayout_Details($this->ct->Table->fields);
				$this->pageLayoutNameString = 'Default Details Layout';
				$this->pageLayoutLink = null;
			}
		} else $this->layoutDetailsContent = $layoutDetailsContent;

		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;

		if (!is_null($this->row)) {
			//Save view log
			$this->SaveViewLogForRecord($this->row);
			$this->UpdatePHPOnView();
		}
		return true;
	}

	protected function loadRecord(): bool
	{
		$filter = '';

		if ($this->ct->Params->listing_id === null and $this->ct->Params->filter != '' and $this->ct->Params->alias == '') {

			$twig = new TwigProcessor($this->ct, $this->ct->Params->filter);
			$filter = $twig->process();

			if ($twig->errorMessage !== null) {
				$this->ct->errors[] = $twig->errorMessage;
				return false;
			}
		}

		if (!is_null($this->ct->Params->recordsTable) and !is_null($this->ct->Params->recordsUserIdField) and !is_null($this->ct->Params->recordsField)) {
			if (!$this->checkRecordUserJoin($this->ct->Params->recordsTable, $this->ct->Params->recordsUserIdField, $this->ct->Params->recordsField, $this->ct->Params->listing_id)) {
				//YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE;
				$this->ct->errors[] = common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED');
				return false;
			}
		}

		$this->ct->getTable($this->ct->Params->tableName, $this->ct->Params->userIdField);

		if ($this->ct->Table->tablename === null)
			return false;

		if (!is_null($this->ct->Params->alias) and $this->ct->Table->alias_fieldname != '')
			$filter = $this->ct->Table->alias_fieldname . '=' . database::quote($this->ct->Params->alias);

		if ($filter != '') {
			if ($this->ct->Params->alias == '') {
				//Parse using layout
				if ($this->ct->Env->legacySupport) {

					require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
						. DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
					$LayoutProc = new LayoutProcessor($this->ct);
					$LayoutProc->layout = $filter;
					$filter = $LayoutProc->fillLayout(null, null, '[]', true);
				}

				$twig = new TwigProcessor($this->ct, $filter);
				$filter = $twig->process();

				if ($twig->errorMessage !== null) {
					$this->ct->errors[] = $twig->errorMessage;
					return false;
				}
			}

			$this->row = $this->getDataByFilter($filter);
		} else
			$this->row = $this->getDataById($this->ct->Params->listing_id);

		return true;
	}

	protected function checkRecordUserJoin($recordsTable, $recordsUserIdField, $recordsField, $listing_id): bool
	{
		//TODO: avoid es_

		$query = 'SELECT COUNT(*) AS count FROM #__customtables_table_' . $recordsTable . ' WHERE es_' . $recordsUserIdField . '='
			. $this->ct->Env->user->id . ' AND INSTR(es_' . $recordsField . ',",' . $listing_id . ',") LIMIT 1';

		$rows = database::loadAssocList($query);
		$num_rows = $rows[0]['count'];

		if ($num_rows == 0)
			return false;

		return true;
	}

	protected function getDataByFilter($filter)
	{
		if ($filter != '') {
			$this->ct->setFilter($filter, 2); //2 = Show any - published and unpublished
		} else {
			$this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_NOFILTER');
			return null;
		}

		$where = count($this->ct->Filter->where) > 0 ? ' WHERE ' . implode(" AND ", $this->ct->Filter->where) : '';

		$this->ct->Ordering->orderby = $this->ct->Table->realidfieldname . ' DESC';
		if ($this->ct->Table->published_field_found)
			$this->ct->Ordering->orderby .= ',published DESC';

		$query = $this->ct->buildQuery($where);
		$query .= ' LIMIT 1';
		$rows = database::loadAssocList($query);

		if (count($rows) < 1)
			return null;

		$row = $rows[0];

		if (isset($row)) {
			$record = new record($this->ct);
			return $record->getSpecificVersionIfSet($row);
		}

		return $row;
	}

	protected function getDataById($listing_id)
	{
		if (is_numeric($listing_id) and intval($listing_id) == 0) {
			$this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_NOFILTER');
			return null;
		}

		$query = $this->ct->buildQuery('WHERE id=' . database::quote($listing_id));
		$query .= ' LIMIT 1';
		$rows = database::loadAssocList($query);

		if (count($rows) < 1)
			return null;

		$row = $rows[0];

		if (isset($row)) {
			$record = new record($this->ct);
			return $record->getSpecificVersionIfSet($row);
		}

		return $row;
	}

	protected function SaveViewLogForRecord($rec): void
	{
		$updateFields = array();
		$allowedTypes = ['lastviewtime', 'viewcount'];

		foreach ($this->ct->Table->fields as $mFld) {
			$t = $mFld['type'];
			if (in_array($t, $allowedTypes)) {

				$allow_count = true;
				$author_user_field = $mFld['typeparams'];

				if (!isset($author_user_field) or $author_user_field == '' or $rec[$this->ct->Env->field_prefix . $author_user_field] == $this->ct->Env->user->id)
					$allow_count = false;

				if ($allow_count) {
					$n = $this->ct->Env->field_prefix . $mFld['fieldname'];
					if ($t == 'lastviewtime')
						$updateFields[] = $n . '="' . date('Y-m-d H:i:s') . '"';
					elseif ($t == 'viewcount')
						$updateFields[] = $n . '=' . ((int)($rec[$n]) + 1);
				}
			}
		}

		if (count($updateFields) > 0) {
			$query = 'UPDATE #__customtables_table_' . $this->ct->Table->tablename . ' SET ' . implode(', ', $updateFields) . ' WHERE id=' . $rec[$this->ct->Table->realidfieldname];
			database::setQuery($query);
		}
	}

	protected function UpdatePHPOnView(): bool
	{
		if (!isset($row[$this->ct->Table->realidfieldname]))
			return false;

		foreach ($this->ct->Table->fields as $mFld) {
			if ($mFld['type'] == 'phponview') {
				$fieldname = $mFld['fieldname'];
				$type_params = JoomlaBasicMisc::csv_explode(',', $mFld['typeparams']);
				tagProcessor_PHP::processTempValue($this->ct, $this->row, $fieldname, $type_params);
			}
		}
		return true;
	}

	public function render(): string
	{
		$layoutDetailsContent = $this->layoutDetailsContent;

		if ($this->ct->Env->legacySupport) {

			require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
				. 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

			$LayoutProc = new LayoutProcessor($this->ct);
			$LayoutProc->layout = $layoutDetailsContent;
			$layoutDetailsContent = $LayoutProc->fillLayout($this->row);
		}

		$twig = new TwigProcessor($this->ct, $layoutDetailsContent, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);
		$layoutDetailsContent = $twig->process($this->row);

		if ($twig->errorMessage !== null)
			$this->ct->errors[] = $twig->errorMessage;

		if ($this->ct->Params->allowContentPlugins)
			$layoutDetailsContent = JoomlaBasicMisc::applyContentPlugins($layoutDetailsContent);

		return $layoutDetailsContent;
	}
}
