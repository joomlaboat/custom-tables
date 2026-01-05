<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage controllers/listoffields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\ExportTables;

use CustomTables\Fields;
use CustomTables\TableHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfTables extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFTABLES';

	/**
	 * @throws Exception
	 *
	 * @since 3.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public function getModel($name = 'Tables', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function export()
	{
		$cid = common::inputPostArray('cid', []);
		$cid = ArrayHelper::toInteger($cid);

		$download_file = ExportTables::export($cid, CUSTOMTABLES_ABSPATH . 'images' . DIRECTORY_SEPARATOR);

		if ($download_file !== null) {
			$message = 'COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_EXPORTED';

			if (count($cid) == 1)
				$message .= '_1';

			$message = common::translate($message, count($cid));
			$message .= '&nbsp;&nbsp;<a href="' . $download_file['link'] . '" title="File: ' . $download_file['filename'] . '" download="' . $download_file['filename'] . '" data-download="' . $download_file['filename'] . '" target="_blank">Download (Click Save Link As...)</a>';
		} else {
			$message = common::translate('COM_CUSTOMTABLES_TABLES_UNABLETOEXPORT');
		}

		Factory::getApplication()->enqueueMessage($message, 'success');

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoftables';

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	public function createFromSchema()
	{
		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoftables';

		$schema = common::inputPostString('schema');
		$data = $this->parseCreateTableQuery($schema);
		$tableId = TableHelper::getTableID($data['tablename']);

		if ($tableId !== 0) {
			common::enqueueMessage('Table ' . common::translate('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS'));
			return;
		}

		$newTableData = ['tablename' => $data['tablename'], 'tabletitle' => $data['tabletitle']];
		if ($data['customidfield'] !== null and $data['customidfield'] !== 'id') {
			$newTableData['customidfield'] = $data['customidfield'];
		}

		if (!empty($data['customidfieldtype']))
			$newTableData['customidfieldtype'] = $data['customidfieldtype'];

		if (!empty($data['primarykeypattern']))
			$newTableData['primarykeypattern'] = $data['primarykeypattern'];

		try {
			$dbPrefix = database::getDBPrefix();
			$tableId = database::insert('#__customtables_tables', $newTableData);

			$primaryKeyType = trim(($data['customidfieldtype'] ?? '') . ' ' . ($data['primarykeypattern'] ?? ''));
			if ($primaryKeyType === '')
				$primaryKeyType = 'int UNSIGNED NOT NULL AUTO_INCREMENT';

			TableHelper::createTableIfNotExists($dbPrefix, $data['tablename'], $data['tabletitle'], '', $data['customidfield'], $primaryKeyType);


		} catch (Exception $e) {
			common::enqueueMessage($e->getMessage());
			return;
		}

		//Skip primary key field
		$fieldsToIgnore = [$data['customidfield'], 'published'];

		foreach ($data['columns'] as $column) {

			if (!in_array($column['fieldname'], $fieldsToIgnore)) {
				$ct_field_type = Fields::convertMySQLFieldTypeToCT($column['type']);

				$fieldData = [];

				$fieldNameParts = explode('_', $column['fieldname']);
				if (count($fieldNameParts) > 1) {
					$prefix = $fieldNameParts[0] . '_';
					$fieldName = str_replace($prefix, '', $column['fieldname']);
				} else
					$fieldName = $column['fieldname'];

				if (empty($column['fieldtitle']))
					$column['fieldtitle'] = $fieldName;

				$fieldData['published'] = 1;
				$fieldData['tableid'] = $tableId;
				$fieldData['fieldname'] = $fieldName;
				$fieldData['fieldtitle'] = $column['fieldtitle'];
				$fieldData['type'] = $ct_field_type['type'];
				$fieldData['typeparams'] = $ct_field_type['typeparams'] ?? '';
				$fieldData['isrequired'] = 0;

				try {
					$fieldId = Fields::saveField($tableId, null, $fieldData);
				} catch (Throwable $e) {
					common::enqueueMessage('Add field details: ' . $e->getMessage());
				}
			}

		}

		common::enqueueMessage('Table created successfully', 'success');

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}

	function parseCreateTableQuery($query)
	{
		// Get table name - handles both with and without IF NOT EXISTS
		preg_match('/CREATE TABLE(?:\s+IF NOT EXISTS)?\s*`([^`]+)`\s*\((.*?)\)[^)]*$/s', $query, $matches);
		$tableName = $matches[1] ?? '';
		$columnsString = $matches[2] ?? '';

		// Get table comment
		preg_match("/COMMENT='([^']+)'$/", $query, $commentMatch);
		$tableComment = $commentMatch[1] ?? '';

		// Remove prefix if exists - now matches any prefix ending with customtables_table_
		$cleanTableName = preg_replace('/^.*?customtables_table_/', '', $tableName);

		// Get primary key field
		preg_match('/PRIMARY\s+KEY\s*\(`([^`]+)`\)/i', $columnsString, $pkMatch);
		$primaryKey = $pkMatch[1] ?? '';

		// Parse columns
		$tableColumns = [];
		$columnDefinitions = array_filter(array_map('trim', explode(',', $columnsString)));

		foreach ($columnDefinitions as $columnDef) {
			if (preg_match('/`([^`]+)`\s+([^,]+?)(?:\s+COMMENT\s*\'([^\']+)\')?(?:\s*,|\s*$)/i', $columnDef, $colMatch)) {
				$name = $colMatch[1];
				$type = trim($colMatch[2]);
				$comment = $colMatch[3] ?? '';

				// Skip PRIMARY KEY and other constraints
				if (!preg_match('/^PRIMARY\s+KEY|^KEY|^UNIQUE|^CONSTRAINT/i', $type)) {
					$tableColumns[] = [
						'fieldname' => $name,
						'type' => $type,
						'fieldtitle' => $comment
					];
				}
			}
		}

		$customIdFieldType = null;
		$primaryKeyPattern = null;

		foreach ($tableColumns as $column) {
			if ($column['fieldname'] == $primaryKey) {
				if (str_contains(strtoupper($column['type']), 'AUTO_INCREMENT')) {
					$customIdFieldType = trim(str_replace('AUTO_INCREMENT', '', $column['type']));
					$primaryKeyPattern = 'AUTO_INCREMENT';
				} else {
					$customIdFieldType = $column['type'];
				}
			}
		}

		return [
			'tablename' => $cleanTableName,
			'tabletitle' => $tableComment,
			'columns' => $tableColumns,
			'customidfield' => $primaryKey,
			'customidfieldtype' => $customIdFieldType,
			'primarykeypattern' => $primaryKeyPattern
		];
	}
}
