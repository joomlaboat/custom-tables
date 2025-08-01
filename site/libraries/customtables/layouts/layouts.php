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

/* All tags already implemented using Twig */

// no direct access
defined('_JEXEC') or die();

use Exception;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Layouts
{
	var CT $ct;
	var ?int $tableId;
	var ?int $layoutId;
	var ?int $layoutType;
	var ?string $pageLayoutNameString;
	var ?string $pageLayoutLink;
	var ?string $layoutCode;
	var ?string $layoutCodeCSS;
	var ?string $layoutCodeJS;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
		$this->tableId = null;
		$this->layoutType = null;
		$this->pageLayoutNameString = null;
		$this->pageLayoutLink = null;
		$this->layoutCode = null;
		$this->layoutCodeCSS = null;
		$this->layoutCodeJS = null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	/*
	function processLayoutTag(string &$htmlResult): bool
	{
		$options = array();
		$fList = CTMiscHelper::getListToReplace('layout', $options, $htmlResult, '{}');

		if (count($fList) == 0)
			return false;

		$i = 0;
		foreach ($fList as $fItem) {
			$parts = CTMiscHelper::csv_explode(',', $options[$i]);
			$layoutname = $parts[0];

			$ProcessContentPlugins = false;
			if (isset($parts[1]) and $parts[1] == 'process')
				$ProcessContentPlugins = true;

			$layout = $this->getLayout($layoutname);

			if ($ProcessContentPlugins)
				CTMiscHelper::applyContentPlugins($layout);

			$htmlResult = str_replace($fItem, $layout, $htmlResult);
			$i++;
		}

		return true;
	}
	*/

	public function deleteLayoutFiles(string $layoutName): bool
	{
		if ($this->ct->Env->folderToSaveLayouts === null)
			return false;

		$fileNames = ['.html', '_mobile.html', '.css', '.js'];
		foreach ($fileNames as $fileName) {
			$path = $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $layoutName . $fileName;
			if (file_exists($path)) {
				try {
					@unlink($path);
				} catch (Exception $e) {
					throw new Exception($path . ': ' . $e->getMessage());
				}
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function storeAsFile($data): void
	{
		if ($this->ct->Env->folderToSaveLayouts !== null) {
			$this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutcode'], $data['layoutname'] . '.html');
			$this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutmobile'], $data['layoutname'] . '_mobile.html');
			$this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutcss'], $data['layoutname'] . '.css');
			$this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutjs'], $data['layoutname'] . '.js');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function storeLayoutAsFile(int $layout_id, string $layoutName, ?string $layoutCode, string $filename): bool
	{
		$layoutCode = trim($layoutCode ?? '');
		$path = $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename;

		if ($layoutCode == '') {
			if (file_exists($path))
				try {
					unlink($path);
				} catch (Exception $e) {
					throw new Exception($path . ': ' . $e->getMessage());
				}

			return true;
		}

		try {
			common::saveString2File($path, $layoutCode);
			@$file_ts = filemtime($path);
		} catch (Exception $e) {
			throw new Exception($path . ': ' . $e->getMessage());
		}

		if ($file_ts == '') {
			throw new Exception($path . ': No permission -  file not saved');
		} else {

			$data = ['modified' => common::formatDateFromTimeStamp($file_ts)];
			$whereClauseUpdate = new MySQLWhereClause();

			if ($layout_id == 0) {
				$whereClauseUpdate->addCondition('layoutname', $layoutName);
			} else {
				$whereClauseUpdate->addCondition('id', $layout_id);
			}
			database::update('#__customtables_layouts', $data, $whereClauseUpdate);
		}
		return true;
	}

	public function layoutTypeTranslation(): array
	{
		if (defined('_JEXEC')) {
			return array(
				CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG => 'COM_CUSTOMTABLES_LAYOUTS_SIMPLE_CATALOG', //1
				CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_PAGE', //5
				CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_ITEM', //6
				CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM => 'COM_CUSTOMTABLES_LAYOUTS_EDIT_FORM', //2
				CUSTOMTABLES_LAYOUT_TYPE_DETAILS => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS', //4
				3 => 'COM_CUSTOMTABLES_LAYOUTS_RECORD_LINK', //unused old type
				CUSTOMTABLES_LAYOUT_TYPE_EMAIL => 'COM_CUSTOMTABLES_LAYOUTS_EMAIL_MESSAGE', //7
				CUSTOMTABLES_LAYOUT_TYPE_XML => 'COM_CUSTOMTABLES_LAYOUTS_XML', //8
				CUSTOMTABLES_LAYOUT_TYPE_CSV => 'COM_CUSTOMTABLES_LAYOUTS_CSV', //9
				CUSTOMTABLES_LAYOUT_TYPE_JSON => 'COM_CUSTOMTABLES_LAYOUTS_JSON' //10
			);
		}

		return array(
			CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG => 'Catalog', //1
			//5 => 'Catalog Page',
			//6 => 'Catalog Item',
			CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM => 'Edit Form', //2
			CUSTOMTABLES_LAYOUT_TYPE_DETAILS => 'Details', //4
			//3 => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS',
			CUSTOMTABLES_LAYOUT_TYPE_EMAIL => 'Email Message', //7
			CUSTOMTABLES_LAYOUT_TYPE_XML => 'XML File', //8
			CUSTOMTABLES_LAYOUT_TYPE_CSV => 'CSV File', //9
			CUSTOMTABLES_LAYOUT_TYPE_JSON => 'JSON File' //10
		);
	}

	/**
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws LoaderError
	 * @throws Exception
	 * @since 3.0.0
	 */
	function parseRawLayoutContent(string $content, bool $applyContentPlugins = true): string
	{
		$twig = new TwigProcessor($this->ct, $content);

		try {
			$content = $twig->process($this->ct->Table->record);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ($applyContentPlugins and $this->ct->Params->allowContentPlugins)
			$content = CTMiscHelper::applyContentPlugins($content);

		return $content;
	}

	/**
	 * Used in WordPress version
	 * @throws Exception
	 * @since 3.2.2
	 */
	function renderMixedLayout($layoutId, ?int $layoutType = null, ?string $task = null): array
	{
		if (!empty($layoutId)) {
			$this->getLayout($layoutId);
			if ($this->layoutType === null)
				return ['success' => false, 'message' => 'Layout "' . $layoutId . '" not found', 'short' => 'error'];
			if ($this->ct->Table->fields === null)
				return ['success' => false, 'message' => 'Table not selected or not found', 'short' => 'error'];
		} elseif ($task !== 'cancel') {
			if ($this->ct->Table === null) {
				$Itemid = common::inputGetInt('Itemid');

				return ['success' => false,
					'message' => 'Table not selected. Layout ID: ' . $layoutId . ', task: ' . $task . ', $Itemid: ' . $Itemid,
					'short' => 'error'];
			}

			if ($layoutType === null) {
				if ($task == 'saveandcontinue' or $task == 'save' or $task == 'saveascopy')
					$layoutType = CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM;
			}

			if ($layoutType == CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG or $layoutType == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE)
				$this->layoutCode = $this->createDefaultLayout_SimpleCatalog($this->ct->Table->fields);
			elseif ($layoutType == CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM) {
				if (defined('_JEXEC'))
					$this->layoutCode = $this->createDefaultLayout_Edit($this->ct->Table->fields);
				elseif (defined('WPINC'))
					$this->layoutCode = $this->createDefaultLayout_Edit_WP($this->ct->Table->fields);
			} elseif ($layoutType == CUSTOMTABLES_LAYOUT_TYPE_DETAILS or $layoutType == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM or $layoutType == 3) //3 is old unused type
				$this->layoutCode = $this->createDefaultLayout_Details($this->ct->Table->fields);
			elseif ($layoutType == CUSTOMTABLES_LAYOUT_TYPE_EMAIL)
				$this->layoutCode = $this->createDefaultLayout_Email($this->ct->Table->fields);
			elseif ($layoutType == CUSTOMTABLES_LAYOUT_TYPE_CSV)
				$this->layoutCode = $this->createDefaultLayout_CSV($this->ct->Table->fields);
		}

		if ($task !== 'none') {

			// Some API controllers do not use tasks at all. "record" - detailed view for example
			if (empty($task))
				$task = common::inputPostCmd('task', null, 'create-edit-record');

			if (empty($task))
				$task = common::inputGetCmd('task');


			if (!empty($task) and $task !== 'new') {

				$output = $this->doTasks($task);

				$link = common::getReturnToURL(true, null, 'create-edit-record');
				if ($link === null)
					$link = $this->ct->Params->returnTo;

				if (!empty($link) and $this->ct->Table !== null)
					$link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);

				if (empty($output['redirect']) and !empty($link))
					$output['redirect'] = $link;

				return $output;
			}
		}

		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;

		if (in_array($this->layoutType, [
			CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG,
			CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE,
			CUSTOMTABLES_LAYOUT_TYPE_XML,
			CUSTOMTABLES_LAYOUT_TYPE_CSV,
			CUSTOMTABLES_LAYOUT_TYPE_JSON
		])) {

			$output['content'] = $this->renderCatalog();
		} elseif ($this->layoutType == CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM) {

			if ($task == 'new') {

				$this->ct->Table->record = null;
			} else {
				if ($this->ct->Table->record === null) {

					if (empty($this->ct->Params->listing_id))
						$this->ct->Params->listing_id = common::inputGetCmd('listing_id');

					if (!empty($this->ct->Params->listing_id) or !empty($this->ct->Params->filter))
						$this->ct->getRecord();
				}
			}

			$editForm = new Edit($this->ct);
			$editForm->layoutContent = $this->layoutCode;

			if ($this->ct->Env->clean == 0)
				$formLink = common::curPageURL();
			else
				$formLink = null;

			if ($this->ct->Env->isModal)
				$formName = 'ctEditModalForm';
			else
				$formName = 'ctEditForm';

			if (!empty($this->ct->Params->ModuleId))
				$formName .= $this->ct->Params->ModuleId;

			if ($this->ct->CheckAuthorization($this->ct->Table->record === null ? CUSTOMTABLES_ACTION_ADD : CUSTOMTABLES_ACTION_EDIT)) {
				$output['content'] = $editForm->render($this->ct->Table->record,
					$formLink,
					$formName,
					$this->ct->Env->clean == 0);
			} else {
				return ['success' => false, 'message' => common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'short' => 'error'];
			}

			if (isset($this->ct->LayoutVariables['captcha']) and $this->ct->LayoutVariables['captcha'])
				$output['captcha'] = true;

			$output['fieldtypes'] = $this->ct->editFieldTypes;
		} elseif ($this->layoutType == CUSTOMTABLES_LAYOUT_TYPE_DETAILS or $this->layoutType == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM) {
			$output['content'] = $this->renderDetailedLayout();
		} else {
			return ['success' => false, 'message' => 'Unknown Layout Type', 'short' => 'error'];
		}

		if ($this->ct->Env->clean == 0) {

			if (isset($this->ct->LayoutVariables['style']))
				$this->layoutCodeCSS = ($this->layoutCodeCSS ?? '') . $this->ct->LayoutVariables['style'];

			if (!empty($this->layoutCodeCSS)) {
				$twig = new TwigProcessor($this->ct, $this->layoutCodeCSS, false);
				$output['style'] = $twig->process($this->ct->Table->record ?? null);
			}

			if (isset($this->ct->LayoutVariables['script']))
				$this->layoutCodeJS = ($this->layoutCodeJS ?? '') . $this->ct->LayoutVariables['script'];

			if (!empty($this->layoutCodeJS)) {
				$twig = new TwigProcessor($this->ct, $this->layoutCodeJS, false);
				$output['script'] = $twig->process($this->ct->Table->record ?? null);
			}
			$output['scripts'] = $this->ct->LayoutVariables['scripts'] ?? null;
			$output['styles'] = $this->ct->LayoutVariables['styles'] ?? null;
			$output['jslibrary'] = $this->ct->LayoutVariables['jslibrary'] ?? null;
		}
		$output['success'] = true;

		return $output;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getLayout($layoutNameOrId, bool $processLayoutTag = true, bool $checkLayoutFile = true): string
	{
		$this->layoutId = null;
		$whereClause = new MySQLWhereClause();

		if (is_int($layoutNameOrId)) {
			if ($layoutNameOrId == 0)
				return '';

			$whereClause->addCondition('id', $layoutNameOrId);
		} else {
			if ($layoutNameOrId == '')
				return '';

			if (self::isLayoutContent($layoutNameOrId)) {
				$this->layoutType = 0;
				return $layoutNameOrId;
			}
			$whereClause->addCondition('layoutname', $layoutNameOrId);
		}

		$selects = [
			'id',
			'tableid',
			'layoutname',
			'layoutcode',
			'layoutmobile',
			'layoutcss',
			'layoutjs',
			'layouttype',
			'MODIFIED_TIMESTAMP',
			'params'
		];

		$rows = database::loadAssocList('#__customtables_layouts', $selects, $whereClause, null, null, 1);
		if (count($rows) != 1)
			return '';

		$row = $rows[0];
		$this->tableId = (int)$row['tableid'];

		if ($this->ct->Table === null)
			$this->ct->getTable($this->tableId);

		$this->layoutId = (int)$row['id'];
		$this->layoutType = (int)$row['layouttype'];
		$this->ct->LayoutVariables['layout_type'] = $this->layoutId;

		if (!empty($row['params'])) {
			try {
				$params = json_decode($row['params'], true);
				if (isset($params['mimetype'])) {
					if ($params['mimetype'] == 'txt')
						$this->ct->Env->frmt = 'txt';
					elseif ($params['mimetype'] == 'csv')
						$this->ct->Env->frmt = 'csv';
					elseif ($params['mimetype'] == 'json')
						$this->ct->Env->frmt = 'json';
					elseif ($params['mimetype'] == 'xml')
						$this->ct->Env->frmt = 'xml';

					if ($this->ct->Env->frmt != 'html' and $params['mimetype'] != '')
						$this->ct->Env->clean = true;
				}

				if (!$this->ct->Env->advancedTagProcessor) {
					//Do not apply Layout params in Free version as they are inaccessible.
					$params['filter'] = null;
					$params['addusergroups'] = null;
					$params['editusergroups'] = null;
					$params['publishusergroups'] = null;
					$params['deleteusergroups'] = null;
					$params['publishstatus'] = null;
					$params['mimetype'] = null;
				}

				$this->ct->Params->setParams($params);
			} catch (Exception $e) {

			}
		}

		if ($this->ct->Env->isMobile and trim($row['layoutmobile']) != '') {
			$layoutCode = $row['layoutmobile'];
			if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null and is_string($layoutNameOrId)) {
				$content = $this->getLayoutFileContent($row['id'], $layoutNameOrId, $layoutCode, $row['modified_timestamp'], $layoutNameOrId . '_mobile.html', 'layoutmobile');
				if ($content != null)
					$layoutCode = $content;
			}
		} else {
			$layoutCode = $row['layoutcode'];
			if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null and is_string($layoutNameOrId)) {
				$content = $this->getLayoutFileContent($row['id'], $layoutNameOrId, $layoutCode, $row['modified_timestamp'], $layoutNameOrId . '.html', 'layoutcode');
				if ($content != null)
					$layoutCode = $content;
			}
		}

		if ($layoutCode === null)
			return '';

		//Get all layouts recursively
		if ($this->ct->Env->advancedTagProcessor and $this->ct->Env->clean == 0)
			$this->addCSSandJSIfNeeded($row, $checkLayoutFile);

		$this->pageLayoutNameString = $row['layoutname'];
		$this->pageLayoutLink = common::UriRoot(true, true) . 'administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $row['id'];
		$this->layoutCode = $layoutCode;
		return $layoutCode;
	}

	public static function isLayoutContent($layout): bool
	{
		if (str_contains($layout, '[') or str_contains($layout, '{'))
			return true;

		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function getLayoutFileContent(int $layout_id, string $layoutName, string $layoutCode, int $db_layout_ts, string $filename, string $fieldName): ?string
	{
		if (file_exists($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename)) {
			$file_ts = filemtime($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

			if ($db_layout_ts == 0) {
				$whereClause = new MySQLWhereClause();
				$whereClause->addCondition('id', $layout_id);
				$rows = database::loadAssocList('#__customtables_layouts', ['MODIFIED_TIMESTAMP'], $whereClause, null, null, 1);

				if (count($rows) != 0) {
					$row = $rows[0];
					$db_layout_ts = $row['modified_timestamp'];
				}
			}

			if ($file_ts > $db_layout_ts) {
				$content = common::getStringFromFile($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

				$data = [
					$fieldName => $content,
					'modified' => common::formatDateFromTimeStamp($file_ts)
				];

				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $layout_id);
				database::update('#__customtables_layouts', $data, $whereClauseUpdate);
				return $content;
			}
		} else {
			$this->storeLayoutAsFile($layout_id, $layoutName, $layoutCode, $filename);
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function addCSSandJSIfNeeded(array $layoutRow, bool $checkLayoutFile = true): void
	{
		//Get CSS content
		$this->layoutCodeCSS = trim($layoutRow['layoutcss'] ?? '');

		if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
			$content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $this->layoutCodeCSS, $layoutRow['modified_timestamp'], $layoutRow['layoutname'] . '.css', 'layoutcss');
			if ($content != null)
				$this->layoutCodeCSS = $content;
		}

		if (empty($this->layoutCodeCSS))
			$this->layoutCodeCSS = null;

		//Get JS content
		$this->layoutCodeJS = trim($layoutRow['layoutjs'] ?? '');
		if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
			$content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $this->layoutCodeJS, $layoutRow['modified_timestamp'], $layoutRow['layoutname'] . '.js', 'layoutjs');
			if ($content != null)
				$this->layoutCodeJS = $content;
		}

		if (empty($this->layoutCodeJS))
			$this->layoutCodeJS = null;
	}

	function createDefaultLayout_SimpleCatalog(array $fields, bool $addToolbar = true): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'simple_catalog.php');
		return createLayout_SimpleCatalog($fields, $addToolbar, $addToolbar);
	}

	function createDefaultLayout_Edit(array $fields, bool $addToolbar = true): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'edit.php');
		return createLayout_Edit($fields, $addToolbar);
	}

	function createDefaultLayout_Edit_WP(array $fields, bool $addToolbar = true, bool $addLegend = true, bool $addGoBack = true): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'edit_wp.php');
		return createLayout_Edit_WP($fields, $addToolbar, $addLegend, $addGoBack, $this->ct->Table->fieldInputPrefix);
	}

	function createDefaultLayout_Details(array $fields): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_DETAILS;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'details.php');
		return createLayout_Details($fields);
	}

	function createDefaultLayout_Email(array $fields): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_DETAILS;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'email.php');
		return createLayout_Email($fields);
	}

	function createDefaultLayout_CSV($fields): string
	{
		$this->layoutType = CUSTOMTABLES_LAYOUT_TYPE_CSV;
		require_once('defaults' . DIRECTORY_SEPARATOR . 'csv.php');
		return createLayout_CSV($fields);
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTasks($task): array
	{
		if ($task == 'delete') {
			return $this->doTask_delete();
		} elseif ($task == 'refresh') {
			return $this->doTask_refresh();
		} elseif ($task == 'copy') {
			return $this->doTask_copy();
		} elseif ($task == 'publish') {
			return $this->doTask_publish(1);
		} elseif ($task == 'unpublish') {
			return $this->doTask_publish(0);
		} elseif ($task == 'cancel') {
			return $this->doTask_cancel();
		} elseif ($task == 'saveandcontinue' or $task == 'save' or $task == 'saveascopy') {
			return $this->doTask_save($task);
		} elseif ($task == 'createuser') {
			return $this->doTask_createuser();
		} elseif ($task == 'setorderby') {
			return $this->doTask_setorderby();
		} elseif ($task == 'setlimit') {
			return $this->doTask_setlimit();
		}
		return ['success' => false, 'message' => 'Unknown task', 'short' => 'unknown'];
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTask_delete(): array
	{
		$listing_ids = $this->getListingIds();

		if (count($listing_ids) > 0) {

			$count = 0;
			$record = new record($this->ct);

			foreach ($listing_ids as $listing_id) {
				try {
					$record->delete($listing_id);
					$count += 1;
				} catch (Exception $e) {
					return ['success' => false, 'message' => 'Delete records: ' . $e->getMessage(), 'short' => 'error', 'id' => $listing_id];
				}
			}

			$message = ($count == 1 ? common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED_1') :
				common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED', $count));

			return ['success' => true, 'message' => $message, 'short' => 'deleted', 'id' => (count($listing_ids) > $listing_ids ? null : $listing_ids[0])];
		}

		return ['success' => false, 'message' => 'Records not selected', 'short' => 'error'];
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function getListingIds(): array
	{
		//Joomla back-end many
		$listing_ids = common::inputPostArray('cid', []);

		if (count($listing_ids) == 0) {
			//Joomla front-end many
			$listing_ids_str = common::inputPostString('ids', null, 'create-edit-record');
			if ($listing_ids_str === null)
				$listing_ids_str = common::inputGetString('ids', null, 'create-edit-record');

			if ($listing_ids_str != null) {
				$listing_ids_ = explode(',', $listing_ids_str);

				$listing_ids = [];

				foreach ($listing_ids_ as $listing_id_) {
					$listing_id = trim(preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id_));
					if ($listing_id !== '')
						$listing_ids[] = $listing_id;
				}
			}

			if (count($listing_ids) == 0) {
				if (common::inputPostCmd('listing_id', null, 'create-edit-record') !== null) {
					$listing_id_ = common::inputPostCmd('listing_id', null, 'create-edit-record');
					$listing_id = trim(preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id_));

					if ($listing_id !== '')
						$listing_ids = [$listing_id];
				}
			}

			if (count($listing_ids) == 0) {
				if (common::inputGetCmd('listing_id') !== null) {
					$listing_id_ = common::inputGetCmd('listing_id');
					$listing_id = trim(preg_replace("/[^a-zA-Z_\d-]/", "", $listing_id_));

					if ($listing_id !== '')
						$listing_ids = [$listing_id];
				}
			}
		}
		return $listing_ids;
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTask_refresh(): array
	{
		$listing_ids = $this->getListingIds();

		if (count($listing_ids) > 0) {

			$count = 0;
			$record = new record($this->ct);

			foreach ($listing_ids as $listing_id) {
				try {
					$record->refresh($listing_id);
					$count += 1;
				} catch (Exception $e) {
					return ['success' => false, 'message' => $e->getMessage(), 'short' => 'error', 'id' => $listing_id];
				}
			}

			$message = ($count == 1 ? common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_REFRESHED_1') :
				common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_REFRESHED', $count));

			return ['success' => true, 'message' => $message, 'short' => 'refreshed', 'id' => (count($listing_ids) > $listing_ids ? null : $listing_ids[0])];

		}

		return ['success' => false, 'message' => 'Records not selected', 'short' => 'error'];
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.2.
	 */
	private function doTask_copy(): array
	{
		$listing_id = common::inputGetCmd('listing_id');
		if (empty($listing_id))
			return ['success' => false, 'message' => 'Record not selected', 'short' => 'error'];

		$record = new record($this->ct);

		try {
			$record->copy($listing_id);
		} catch (Exception $e) {
			return ['success' => false, 'message' => $e->getMessage(), 'short' => 'error', 'id' => $record->listing_id, 'original_id' => $listing_id];
		}

		return ['success' => true, 'message' => common::translate('COM_CUSTOMTABLES_RECORDS_COPIED'), 'short' => 'copied', 'id' => $record->listing_id];
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTask_publish(int $status): array
	{
		$listing_ids = $this->getListingIds();

		if (count($listing_ids) > 0) {

			$count = 0;
			$record = new record($this->ct);

			foreach ($listing_ids as $listing_id) {
				try {
					$record->publish($listing_id, $status);
					$count += 1;
				} catch (Exception $e) {
					return ['success' => false, 'message' => $e->getMessage(), 'short' => 'error', 'id' => $listing_id];
				}
			}

			if ($status == 1) {
				$message = ($count == 1 ? common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED_1') :
					common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED', $count));

				$statusMessage = 'published';
			} else {
				$message = ($count == 1 ? common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED_1') :
					common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED', $count));

				$statusMessage = 'unpublished';
			}

			return ['success' => true, 'message' => $message, 'short' => $statusMessage, 'id' => (count($listing_ids) > $listing_ids ? null : $listing_ids[0])];
		}

		return ['success' => false, 'message' => 'Records not selected', 'short' => 'error'];
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTask_cancel(): array
	{
		$link = common::getReturnToURL(true, null, 'create-edit-record');
		if ($link === null)
			$link = $this->ct->Params->returnTo;

		if ($this->ct->Table !== null)
			$link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);

		return ['success' => true, 'message' => common::translate('COM_CUSTOMTABLES_EDIT_CANCELED'), 'short' => 'canceled', 'redirect' => $link];
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function doTask_save(string $task): array
	{
		$record = new record($this->ct);
		$record->editForm->layoutContent = $this->layoutCode;
		$listing_id = common::inputGetCmd('id');

		try {
			$ok = $record->save($listing_id, $task == 'saveascopy');
		} catch (Exception $e) {

			if ($this->ct->Env->debug)
				$message = $e->getMessage() . ', ' . $e->getFile() . ', ' . $e->getLine();// . $e->getTraceAsString();
			else
				$message = $e->getMessage();

			$output = ['success' => false, 'message' => $message, 'short' => 'error'];
			if ($record->unauthorized) {
				$returnToEncoded = common::makeReturnToURL();
				$output['redirect'] = $this->ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $returnToEncoded;
			}
			return $output;
		}

		if ($ok) {
			//Success

			try {
				$twig = new TwigProcessor($this->ct, $this->ct->Params->msgItemIsSaved);
				$output['message'] = $twig->process($this->ct->Table->record);
			} catch (Exception $e) {
				$output['message'] = $e->getMessage();
			}

			$action = $record->isItNewRecord ? 'create' : 'update';

			if ($this->ct->Env->advancedTagProcessor and !empty($this->ct->Table->tablerow['customphp'])) {

				try {
					$customPHP = new CustomPHP($this->ct, $action);
					$customPHP->executeCustomPHPFile($this->ct->Table->tablerow['customphp'], $record->row_new, $record->row_old);
				} catch (Exception $e) {
					$output['message'] = 'Custom PHP file: ' . $this->ct->Table->tablerow['customphp'] . ' (' . $e->getMessage() . ')';
				}
			}

			$link = common::getReturnToURL(true, null, 'create-edit-record');

			if ($link === null)
				$link = $this->ct->Params->returnTo;

			if ($task == 'saveandcontinue') {
				$link = CTMiscHelper::deleteURLQueryOption($link, "listing_id");

				if (!str_contains($link, "?"))
					$link .= '?';
				else
					$link .= '&';

				$link .= 'listing_id=' . $record->listing_id;
			}

			$output['redirect'] = $link;

			$editForm = new Edit($this->ct);
			$editForm->layoutContent = $this->layoutCode;
			$data = $editForm->render($this->ct->Table->record, null, 'ctEditForm', false);

			$output['success'] = true;
			$output['action'] = $action;
			$output['id'] = $this->ct->Table->record[$this->ct->Table->realidfieldname];
			$output['content'] = $data;
			$output['short'] = $action == 'create' ? 'created' : 'updated';
		} else {
			if ($record->incorrectCaptcha)
				$output = ['success' => false, 'message' => common::translate('COM_CUSTOMTABLES_INCORRECT_CAPTCHA'), 'short' => 'error'];
			else
				$output = ['success' => false, 'message' => 'error', 'short' => 'error'];
		}
		return $output;
	}

	/**
	 * @throws Exception
	 * @since 3.2.
	 */
	private function doTask_createuser(): array
	{
		$listing_ids = $this->getListingIds();

		if (count($listing_ids) > 0) {

			$count = 0;
			$record = new record($this->ct);

			foreach ($listing_ids as $listing_id) {
				try {
					$this->ct->Params->listing_id = $listing_id;
					$this->ct->getRecord();

					if ($this->ct->Table->record === null)
						return ['success' => false, 'message' => 'User record ID: "' . $this->ct->Params->listing_id . '" not found.', 'short' => 'error'];

					$fieldRow = $this->ct->Table->getFieldByName($this->ct->Table->useridfieldname);

					$saveField = new SaveFieldQuerySet($this->ct, $this->ct->Table->record, false);
					$field = new Field($this->ct, $fieldRow);

					try {
						$saveField->Try2CreateUserAccount($field);
					} catch (Exception $e) {
						return ['success' => false, 'message' => common::translate('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED')
							. ' ' . $e->getMessage(), 'short' => 'error'];
					}

					$record->refresh($this->ct->Params->listing_id);

					$count += 1;
				} catch (Exception $e) {
					return ['success' => false, 'message' => $e->getMessage(), 'short' => 'error'];
				}
			}

			$message = ($count == 1 ? common::translate('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT_1') :
				common::translate('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT'));

			return ['success' => true, 'message' => $message, 'short' => 'user_created'];
		}
		return ['success' => false, 'message' => 'Records not selected', 'short' => 'error'];
	}

	/**
	 * @throws Exception
	 * @since 3.5.4
	 */
	private function doTask_setorderby(): array
	{
		$order_by = common::inputGetString('orderby', '');
		$order_by = trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $order_by));

		if (defined('_JEXEC'))
			common::setUserState('com_customtables.orderby_' . $this->ct->Params->ItemId, $order_by);
		elseif (defined('WPINC'))
			common::setUserState('com_customtables.orderby_' . $this->tableId, $order_by);
		else
			throw new Exception('doTask_setorderby not supported in this version');

		$link = common::curPageURL();

		$link = CTMiscHelper::deleteURLQueryOption($link, 'task');
		$link = CTMiscHelper::deleteURLQueryOption($link, 'orderby');

		return ['success' => true, 'message' => null, 'short' => 'order_by set', 'redirect' => $link];
	}

	/**
	 * @throws Exception
	 * @since 3.5.4
	 */
	private function doTask_setlimit(): array
	{
		$limit = common::inputGetInt('limit', 0);

		if (defined('_JEXEC'))
			common::setUserState('com_customtables.limit_' . $this->ct->Params->ItemId, $limit);
		elseif (defined('WPINC'))
			common::setUserState('com_customtables.limit_' . $this->tableId, $limit);
		else
			throw new Exception('doTask_setlimit not supported in this version');

		$link = common::curPageURL();

		$link = CTMiscHelper::deleteURLQueryOption($link, 'task');
		$link = CTMiscHelper::deleteURLQueryOption($link, 'limit');

		return ['success' => true, 'message' => null, 'short' => 'order_by set', 'redirect' => $link];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function renderCatalog(): string
	{
		// -------------------- Table
		if ($this->ct->Table === null) {
			$this->ct->getTable($this->ct->Params->tableName);

			if ($this->ct->Table === null)
				throw new Exception('Catalog View: Table not selected.');
		}

		// --------------------- Filter
		$this->ct->setFilter($this->ct->Params->filter, $this->ct->Params->showPublished);
		$this->ct->Filter->addQueryWhereFilter();

		if (!$this->ct->Params->blockExternalVars)
			$this->ct->Filter->addQueryWhereFilter();

		// --------------------- Shopping Cart
		if ($this->ct->Params->showCartItemsOnly) {
			$cookieValue = common::inputCookieGet($this->ct->Params->showCartItemsPrefix . $this->ct->Table->tablename);

			if (isset($cookieValue)) {
				if ($cookieValue == '') {
					$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
				} else {
					$items = explode(';', $cookieValue);

					foreach ($items as $item) {
						$pair = explode(',', $item);
						$this->ct->Filter->whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], (int)$pair[0]);
					}
				}
			} else {
				//Show only shopping cart items. TODO: check the query
				$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
			}
		}

		if (!empty($this->ct->Params->listing_id))
			$this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], $this->ct->Params->listing_id);

		// --------------------- Sorting
		$this->ct->Ordering->parseOrderByParam();

		// --------------------- Limit
		if (!empty($this->ct->Params->listing_id))
			$this->ct->applyLimits(1);
		else
			$this->ct->applyLimits();

		$this->ct->LayoutVariables['layout_type'] = $this->layoutType;

		// -------------------- Load Records
		if (!$this->ct->getRecords())
			throw new Exception(common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'));

		// -------------------- Parse Layouts
		$twig = new TwigProcessor($this->ct, $this->layoutCode, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);

		try {
			$pageLayout = $twig->process();
		} catch (Exception $e) {
			if ($this->ct->Env->debug)
				$message = $e->getMessage() . '<br/>' . $e->getFile() . '<br/>' . $e->getLine();// . $e->getTraceAsString();
			else
				$message = $e->getMessage();

			throw new Exception($message);
		}

		if (defined('_JEXEC')) {
			if ($this->ct->Params->allowContentPlugins)
				$pageLayout = CTMiscHelper::applyContentPlugins($pageLayout);
		}
		return $pageLayout;
	}

	/**
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws LoaderError
	 * @throws Exception
	 * @since 3.5.0
	 */
	private function renderDetailedLayout(): ?string
	{
		//Details or Catalog Item
		if ($this->ct->Table->record === null) {

			if (empty($this->ct->Params->listing_id) and !empty(common::inputGetCmd('listing_id')))
				$this->ct->Params->listing_id = common::inputGetCmd('listing_id');

			$this->ct->getRecord();
		}

		if ($this->ct->Table->record === null)
			throw new Exception(common::translate('COM_CUSTOMTABLES_NOTFOUND'));

		return $this->renderDetailedLayoutDO();
	}

	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws LoaderError
	 * @throws Exception
	 * @since 3.0.0
	 */
	public function renderDetailedLayoutDO(): string
	{
		try {
			$twig = new TwigProcessor($this->ct, $this->layoutCode, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);
			$layoutDetailsContent = $twig->process($this->ct->Table->record);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ($this->ct->Params->allowContentPlugins)
			$layoutDetailsContent = CTMiscHelper::applyContentPlugins($layoutDetailsContent);

		if (!is_null($this->ct->Table->record)) {
			//Save view log
			$this->SaveViewLogForRecord();
		}

		return $layoutDetailsContent;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function SaveViewLogForRecord(): void
	{
		$updateFields = [];

		foreach ($this->ct->Table->fields as $field) {
			if ($field['type'] == 'lastviewtime')
				$updateFields[$field['realfieldname']] = common::currentDate();
			elseif ($field['type'] == 'viewcount')
				$updateFields[$field['realfieldname']] = ((int)($this->ct->Table->record[$field['realfieldname']]) + 1);
		}

		if (count($updateFields) > 0) {
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $this->ct->Table->record[$this->ct->Table->realidfieldname]);
			database::update($this->ct->Table->realtablename, $updateFields, $whereClauseUpdate);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getLayoutRowById(int $layoutId): ?array
	{
		$selects = [
			'id',
			'tableid',
			'layoutname',
			'layoutcode',
			'layoutmobile',
			'layoutcss',
			'layoutjs',
			'layouttype',
			'MODIFIED_TIMESTAMP',
			'params'
		];

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', $layoutId);

		$rows = database::loadAssocList('#__customtables_layouts', $selects, $whereClause, null, null, 1);
		if (count($rows) != 1)
			return null;

		return $rows[0];
	}
}
