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

// No direct access to this file
defined('_JEXEC') or die();

use Exception;

class ImportCSV
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function importCSVFile($filename, $ct_tableid): void
	{
		if (file_exists($filename)) {
			try {
				self::importCSVdata($filename, $ct_tableid);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		} else
			throw new Exception(common::translate('COM_CUSTOMTABLES_FILE_NOT_FOUND'));
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function importCSVData(string $filename, int $ct_tableid): void
	{
		$arrayOfLines = self::getLines($filename);

		if ($arrayOfLines === null)
			throw new Exception(common::translate('COM_CUSTOMTABLES_CSV_FILE_EMPTY'));

		$ct = new CT([], true);
		$ct->getTable($ct_tableid);
		$line = $arrayOfLines[0];
		$prepareFieldList = self::prepareFieldList($line, $ct->Table->fields);
		$fieldList = $prepareFieldList['fieldList'];
		$fields = self::processFieldParams($fieldList, $ct->Table->fields);//return associative array

		if ($prepareFieldList['header'])
			$offset = 1;
		else
			$offset = 0;

		for ($i = $offset; $i < count($arrayOfLines); $i++) {
			if (count($arrayOfLines[$i]) > 0) {
				$result = self::prepareSQLQuery($fieldList, $fields, $arrayOfLines[$i]);
				$listing_id = self::findRecord($ct->Table->realtablename, $ct->Table->realidfieldname, $ct->Table->published_field_found, $result->where);

				if (is_null($listing_id)) {
					try {
						database::insert($ct->Table->realtablename, $result->data);
					} catch (Exception $e) {
						throw new Exception($e->getMessage());
					}
				}
			}
		}
	}

	//https://stackoverflow.com/questions/26717462/php-best-approach-to-detect-csv-delimiter/59581170

	private static function getLines($filename): ?array
	{
		$delimiter = self::detectDelimiter($filename);

		if (($handle = fopen($filename, "r")) !== FALSE) {
			$lines = [];
			$enclosure = "\"";

			while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE)
				$lines[] = $data;

			fclose($handle);
			return $lines;
		}
		return null;
	}

	private static function detectDelimiter($csvFile): string
	{
		//first line is a list of field name, so this approach is ok here
		$delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

		$handle = fopen($csvFile, "r");
		$firstLine = fgets($handle);
		fclose($handle);
		foreach ($delimiters as $delimiter => &$count) {
			$count = count(str_getcsv($firstLine, $delimiter));
		}

		return array_search(max($delimiters), $delimiters);
	}

	private static function prepareFieldList(array $fieldNames, array $fields): array
	{
		$fieldList = array();
		$fieldsFoundCount = 0;

		foreach ($fieldNames as $fieldName_) {
			$index = 0;

			$fieldName = self::removeBomUtf8($fieldName_);
			$fieldName = strtolower(preg_replace("/[^a-zA-Z1-9]/", "", $fieldName));

			$found = false;
			foreach ($fields as $field) {
				if ((int)$field['published'] === 1) {
					$clean_field_name = strtolower(preg_replace("/[^a-zA-Z1-9]/", "", $field['fieldtitle']));

					if ($fieldName_ == '#' or $fieldName_ == '') {
						$fieldList[] = -1;//id field
						$fieldsFoundCount += 1;
						$found = true;
						break;
					} elseif ($clean_field_name == $fieldName or (string)$field['fieldname'] == $fieldName or (string)$field['fieldtitle'] == $fieldName) {
						$fieldList[] = $index;
						$fieldsFoundCount += 1;
						$found = true;
						break;
					}
				}
				$index++;
			}

			if (!$found)
				$fieldList[] = -2;
		}
		return ['fieldList' => $fieldList, 'header' => $fieldsFoundCount > 0];
	}

	private static function removeBomUtf8($s): string
	{
		if (substr($s, 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
			return substr($s, 3);
		} else {
			if (substr($s, 0, 2) == chr(hexdec('FF')) . chr(hexdec('FE')))
				return substr($s, 2);
			return $s;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function processFieldParams(array $fieldList, array $fields): array
	{
		foreach ($fieldList as $f_index) {
			if ($f_index >= 0) {
				$fieldType = $fields[$f_index]['type'];
				if ($fieldType == 'sqljoin' or $fieldType == 'records') {

					$type_params = CTMiscHelper::csv_explode(',', $fields[$f_index]['typeparams']);

					$tableName = $type_params[0];
					$fieldName = $type_params[1];

					$ct = new CT([], true);
					$ct->getTable($tableName);

					if (!$ct->Table) {
						echo common::ctJsonEncode(['error' => 'sqljoin field(' . $fields[$f_index]['fieldtitle'] . ') table not found']);
						die;//Import CSV field error
					}

					$SQJJoinField = $ct->Table->getFieldByName($fieldName);

					$fields[$f_index]['sqljoin'] = (object)[
						'table' => $ct->Table->realtablename,
						'field' => $SQJJoinField['realfieldname'],
						'realidfieldname' => $ct->Table->realidfieldname,
						'published_field_found' => $ct->Table->published_field_found];
				}
			}
		}
		return $fields;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function prepareSQLQuery(array $fieldList, array $fields, $line): object
	{
		$data = [];
		$whereClause = new MySQLWhereClause();

		$i = 0;

		foreach ($fieldList as $f_index) {
			if ($f_index >= 0) {
				$fieldType = $fields[$f_index]['type'];
				$fieldParamsString = $fields[$f_index]['typeparams'];
				$fieldParams = CTMiscHelper::csv_explode(',', $fieldParamsString);

				if ($fieldType == 'sqljoin') {

					if (isset($fields[$f_index]['sqljoin'])) {
						$realtablename = $fields[$f_index]['sqljoin']->table;

						$vlu = self::findSQLJoin(
							$realtablename,
							$fields[$f_index]['sqljoin']->field,
							$fields[$f_index]['sqljoin']->realidfieldname,
							(bool)$fields[$f_index]['sqljoin']->published_field_found,
							$line[$i]);

						if (is_null($vlu))//Join table record doesn't exist
						{
							database::insert($realtablename, [$fields[$f_index]['sqljoin']->field => $line[$i]]);

							$vlu = self::findSQLJoin(
								$realtablename,
								$fields[$f_index]['sqljoin']->field,
								$fields[$f_index]['sqljoin']->realidfieldname,
								(bool)$fields[$f_index]['sqljoin']->published_field_found,
								$line[$i]);
						}

						if ((int)$vlu > 0) {
							$whereClause->addCondition($fields[$f_index]['realfieldname'], (int)$vlu);
							$data[$fields[$f_index]['realfieldname']] = (int)$vlu;
						} else {
							$whereClause->addCondition($fields[$f_index]['realfieldname'], null);
							$data[$fields[$f_index]['realfieldname']] = null;
						}
					}
				} elseif ($fieldType == 'records') {

					if (isset($fields[$f_index]['sqljoin'])) {
						$realtablename = $fields[$f_index]['sqljoin']->table;

						$vlu = self::findSQLRecordJoin(
							$realtablename,
							$fields[$f_index]['sqljoin']->field,
							$fields[$f_index]['sqljoin']->realidfieldname,
							(bool)$fields[$f_index]['sqljoin']->published_field_found,
							$line[$i]);

						if (is_null($vlu)) {

							database::insert($realtablename, [$fields[$f_index]['sqljoin']->field => $line[$i]]);

							$vlu = self::findSQLRecordJoin(
								$realtablename,
								$fields[$f_index]['sqljoin']->field,
								$fields[$f_index]['sqljoin']->realidfieldname,
								(bool)$fields[$f_index]['sqljoin']->published_field_found,
								$line[$i]);
						}

						if (!is_null($vlu) and $vlu != '') {
							$whereClause->addCondition($fields[$f_index]['realfieldname'], '%,' . implode(',', $vlu) . ',%', 'LIKE');
							$data[$fields[$f_index]['realfieldname']] = ',' . implode(',', $vlu) . ',';
						} else {
							$whereClause->addCondition($fields[$f_index]['realfieldname'], null);
							$data[$fields[$f_index]['realfieldname']] = null;
						}
					}
				} elseif ($fieldType == 'date' or $fieldType == 'creationtime' or $fieldType == 'changetime') {
					if (isset($line[$i]) and $line[$i] != '') {

						$dateString = CTMiscHelper::standardizeDate($line[$i]);

						$whereClause->addCondition($fields[$f_index]['realfieldname'], $dateString);
						$data[$fields[$f_index]['realfieldname']] = $dateString;
					} else {
						$whereClause->addCondition($fields[$f_index]['realfieldname'], null);
						$data[$fields[$f_index]['realfieldname']] = null;
					}
				} elseif ($fieldType == 'int' or $fieldType == 'user' or $fieldType == 'userid') {
					if (isset($line[$i]) and $line[$i] != '') {
						$whereClause->addCondition($fields[$f_index]['realfieldname'], (int)$line[$i]);
						$data[$fields[$f_index]['realfieldname']] = (int)$line[$i];
					} else {
						$whereClause->addCondition($fields[$f_index]['realfieldname'], null);
						$data[$fields[$f_index]['realfieldname']] = null;
					}
				} elseif ($fieldType == 'float') {
					if (isset($line[$i]) and $line[$i] != '') {
						$whereClause->addCondition($fields[$f_index]['realfieldname'], (float)$line[$i]);
						$data[$fields[$f_index]['realfieldname']] = (float)$line[$i];
					} else {
						$whereClause->addCondition($fields[$f_index]['realfieldname'], null);
						$data[$fields[$f_index]['realfieldname']] = null;
					}
				} elseif ($fieldType == 'checkbox') {
					if (isset($line[$i]) and $line[$i] != '') {
						if ($line[$i] == 'Yes' or $line[$i] == '1')
							$vlu = 1;
						else
							$vlu = 0;

						$whereClause->addCondition($fields[$f_index]['realfieldname'], $vlu);
						$data[$fields[$f_index]['realfieldname']] = $vlu;
					}
				} elseif ($fieldType == 'time') {
					$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;
					require_once($path . 'time.php');

					$seconds = InputBox_Time::formattedTime2Seconds($line[$i]);
					$ticks = InputBox_Time::seconds2Ticks($seconds, $fieldParams);

					$whereClause->addCondition($fields[$f_index]['realfieldname'], $ticks);
					$data[$fields[$f_index]['realfieldname']] = $ticks;

				} else {

					if (isset($line[$i])) {
						$vlu = $line[$i];
						$whereClause->addCondition($fields[$f_index]['realfieldname'], $vlu);
						$data[$fields[$f_index]['realfieldname']] = $vlu;
					}
				}
			}
			$i++;
		}

		return (object)['data' => $data, 'where' => $whereClause];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function findSQLJoin($realtablename, $join_realfieldname, $realidfieldname, bool $published_field_found, $vlu)
	{
		//TODO method not found
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($join_realfieldname, $vlu);
		return self::findRecord($realtablename, $realidfieldname, $published_field_found, $whereClause);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function findRecord($realtablename, $realidfieldname, bool $published_field_found, MySQLWhereClause $whereClause)
	{
		if ($published_field_found)
			$whereClause->addCondition('published', 1);

		$rows = database::loadAssocList($realtablename, [$realidfieldname], $whereClause, null, null, 1);

		if (count($rows) == 0)
			return null;

		return $rows[0][$realidfieldname];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	private static function findSQLRecordJoin($realtablename, $join_realfieldname, $realidfieldname, bool $published_field_found, $values_str): ?array
	{
		$whereClause = new MySQLWhereClause();
		$values = explode(',', $values_str);

		foreach ($values as $vlu)
			$whereClause->addOrCondition($join_realfieldname, $vlu);


		if ($published_field_found)
			$whereClause->addCondition('published', 1);

		$rows = database::loadAssocList($realtablename, [$realidfieldname], $whereClause);

		if (count($rows) == 0)
			return null;

		$listing_ids = array();
		foreach ($rows as $row)
			$listing_ids[] = $row[$realidfieldname];

		return $listing_ids;
	}
}