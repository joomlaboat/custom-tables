<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
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
use Joomla\Database\DatabaseInterface;

class MySQLWhereClause
{
	public array $conditions = [];
	public array $orConditions = [];
	public array $nestedConditions = [];
	public array $nestedOrConditions = [];

	public function hasConditions(): bool
	{
		if (count($this->conditions) > 0)
			return true;

		if (count($this->orConditions) > 0)
			return true;

		if (count($this->nestedConditions) > 0)
			return true;

		if (count($this->nestedOrConditions) > 0)
			return true;

		return false;
	}

	public function addConditionsFromArray(array $conditions): void
	{
		foreach ($conditions as $fieldName => $fieldValue) {
			// Assuming default operator is '=' if not specified
			$this->addCondition($fieldName, $fieldValue);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	public function addCondition($fieldName, $fieldValue, string $operator = '=', bool $sanitized = false, ?string $join_realtablename = null, ?string $join_real_id_field_name = null, ?string $join_real_field_name = null): void
	{
		$operator = strtoupper($operator);

		$possibleOperators = ['=', '>', '<', '!=', '>=', '<=', 'LIKE', 'NULL', 'NOT NULL', 'INSTR', 'NOT INSTR',
			'IN',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOIN_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOIN_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_CONTAIN'
		];

		if (!in_array($operator, $possibleOperators)) {
			throw new Exception('SQL Where Clause operator "' . common::ctStripTags($operator) . '" not recognized.');
		}

		$this->conditions[] = [
			'field' => $fieldName,
			'value' => $fieldValue,
			'operator' => $operator,
			'sanitized' => $sanitized,
			'join_realtablename' => $join_realtablename,
			'join_real_id_field_name' => $join_real_id_field_name,
			'join_real_field_name' => $join_real_field_name
		];
	}

	/**
	 * @throws Exception
	 * @since 3.2.8
	 */
	public function addOrCondition($fieldName, $fieldValue, string $operator = '=', bool $sanitized = false, ?string $join_realtablename = null, ?string $join_real_id_field_name = null, ?string $join_real_field_name = null): void
	{
		$operator = strtoupper($operator);

		$possibleOperators = ['=', '>', '<', '!=', '>=', '<=', 'LIKE', 'NULL', 'NOT NULL', 'INSTR', 'NOT INSTR',
			'IN',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOIN_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_EQUAL',
			'MULTI_FIELD_SEARCH_TABLEJOIN_CONTAIN',
			'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_CONTAIN'
		];

		if (!in_array($operator, $possibleOperators)) {
			throw new Exception('SQL Where Clause operator "' . common::ctStripTags($operator) . '" not recognized.');
		}

		$this->orConditions[] = [
			'field' => $fieldName,
			'value' => $fieldValue,
			'operator' => $operator,
			'sanitized' => $sanitized,
			'join_realtablename' => $join_realtablename,
			'join_real_id_field_name' => $join_real_id_field_name,
			'join_real_field_name' => $join_real_field_name
		];
	}

	public function addNestedCondition(MySQLWhereClause $condition): void
	{
		$this->nestedConditions[] = $condition;
	}

	public function addNestedOrCondition(MySQLWhereClause $orCondition): void
	{
		$this->nestedOrConditions[] = $orCondition;
	}

	public function __toString(): string
	{
		return $this->getWhereClause();// Returns the "where" clause with %d,%f,%s placeholders
	}

	public function getWhereClause(string $logicalOperator = 'AND'): string
	{
		$where = [];

		// Process regular conditions
		if (count($this->conditions))
			$where [] = self::getWhereClauseMergeConditions($this->conditions);

		// Process OR conditions
		if (count($this->orConditions) > 0) {
			if (count($this->orConditions) === 1)
				$where [] = self::getWhereClauseMergeConditions($this->orConditions, 'OR');
			else
				$where [] = '(' . self::getWhereClauseMergeConditions($this->orConditions, 'OR') . ')';
		}
		// Process nested conditions
		foreach ($this->nestedConditions as $nestedCondition) {
			$where [] = '(' . $nestedCondition->getWhereClause() . ')';
		}

		$orWhere = [];
		foreach ($this->nestedOrConditions as $nestedOrCondition) {
			if ($nestedOrCondition->countConditions() == 1)
				$orWhere [] = $nestedOrCondition->getWhereClause('OR');
			else
				$orWhere [] = '(' . $nestedOrCondition->getWhereClause('OR') . ')';
		}

		if (count($orWhere) > 0) {
			if (count($orWhere) > 1)
				$where [] = '(' . implode(' OR ', $orWhere) . ')';
			else
				$where [] = implode(' OR ', $orWhere);
		}

		return implode(' ' . $logicalOperator . ' ', $where);
	}

	protected function getWhereClauseMergeConditions($conditions, $logicalOperator = 'AND'): string
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get('DatabaseDriver');

		$where = [];

		foreach ($conditions as $condition) {
			if ($condition['value'] === null) {
				$where [] = $condition['field'] . ' IS NULL';
			} elseif ($condition['operator'] == 'NULL') {
				$where [] = $condition['field'] . ' IS NULL';
			} elseif ($condition['operator'] == 'NOT NULL') {
				$where [] = $condition['field'] . ' IS NOT NULL';
			} elseif ($condition['operator'] == 'LIKE') {
				$where [] = $condition['field'] . ' LIKE ' . $db->quote($condition['value']);
			} elseif ($condition['operator'] == 'INSTR') {
				if ($condition['sanitized']) {
					$where [] = 'INSTR(' . $condition['field'] . ',' . $condition['value'] . ')';
				} else {
					$where [] = 'INSTR(' . $condition['field'] . ',' . $db->quote($condition['value']) . ')';
				}
			} elseif ($condition['operator'] == 'NOT INSTR') {
				if ($condition['sanitized']) {
					$where [] = '!INSTR(' . $condition['field'] . ',' . $condition['value'] . ')';
				} else {
					$where [] = '!INSTR(' . $condition['field'] . ',' . $db->quote($condition['value']) . ')';
				}
			} elseif ($condition['operator'] == 'REGEXP') {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' REGEXP ' . $condition['value'];
				} else {
					$where [] = $condition['field'] . ' REGEXP ' . $db->quote($condition['value']);
				}
			} elseif ($condition['operator'] == 'IN') {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' IN ' . $condition['value'];
				} else {
					$where [] = $db->quote($condition['field']) . ' IN ' . $condition['value'];
				}

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOIN_EQUAL') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' ' . $condition['field'] . '=' . $condition['join_real_id_field_name']
					. ' AND ' . $condition['join_real_field_name'] . '=' . $db->quote($condition['value']) . ' LIMIT 1) IS NOT NULL';

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_EQUAL') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' ' . $condition['field'] . '=' . $condition['join_real_id_field_name']
					. ' AND ' . $condition['join_real_field_name'] . '!=' . $db->quote(['value']) . ' LIMIT 1) IS NOT NULL';

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOIN_CONTAIN') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' ' . $condition['field'] . '=' . $condition['join_real_id_field_name']
					. ' AND INSTR(' . $condition['join_real_field_name'] . ',' . $db->quote($condition['value']) . ') LIMIT 1) IS NOT NULL';
			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOIN_NOT_CONTAIN') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' ' . $condition['field'] . '=' . $condition['join_real_id_field_name']
					. ' AND !INSTR(' . $condition['join_real_field_name'] . ',' . $db->quote($condition['value']) . ') LIMIT 1) IS NOT NULL';

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOINLIST_EQUAL') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' INSTR(' . $condition['field'] . ',CONCAT(",",join_table.' . $condition['join_real_id_field_name'] . ',",")) AND ' . $condition['join_real_field_name'] . '=' . $db->quote($condition['value']) . ' LIMIT 1) IS NOT NULL';

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_EQUAL') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' INSTR(' . $condition['field'] . ',CONCAT(",",join_table.' . $condition['join_real_id_field_name'] . ',",")) AND ' . $condition['join_real_field_name'] . '!=' . $db->quote(['value']) . ' LIMIT 1) IS NOT NULL';

			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOINLIST_CONTAIN') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' INSTR(' . $condition['field'] . ',CONCAT(",",join_table.' . $condition['join_real_id_field_name'] . ',",")) AND INSTR(' . $condition['join_real_field_name'] . ',' . $db->quote($condition['value']) . ') LIMIT 1) IS NOT NULL';
			} elseif ($condition['operator'] == 'MULTI_FIELD_SEARCH_TABLEJOINLIST_NOT_CONTAIN') {
				$where [] = '(SELECT join_table.' . $condition['join_real_id_field_name']
					. ' FROM ' . $condition['join_realtablename'] . ' AS join_table WHERE'
					. ' INSTR(' . $condition['field'] . ',CONCAT(",",join_table.' . $condition['join_real_id_field_name'] . ',",")) AND !INSTR(' . $condition['join_real_field_name'] . ',' . $db->quote($condition['value']) . ') LIMIT 1) IS NOT NULL';
			} else {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' ' . $condition['operator'] . ' ' . $condition['value'];
				} else {

					$where_string = $condition['field'] . ' ' . $condition['operator'] . ' ';

					if (is_bool($condition['value']))
						$where_string .= $condition['value'] ? 'TRUE' : 'FALSE';
					elseif (is_int($condition['value']))
						$where_string .= $condition['value'];
					elseif (is_float($condition['value']))
						$where_string .= $condition['value'];
					else
						$where_string .= $db->quote($condition['value']);

					$where [] = $where_string;
				}
			}
		}
		return implode(' ' . $logicalOperator . ' ', $where);
	}

	public function countConditions(): int
	{
		return count($this->conditions) + count($this->orConditions) + count($this->nestedConditions) + count($this->nestedOrConditions);
	}
}

class database
{
	public static function realTableName($tableName): ?string
	{
		$db = self::getDB();
		return str_replace('#__', $db->getPrefix(), $tableName);
	}

	/**
	 * @return_type depends on the version of Joomla
	 * @since 3.2.4
	 */
	public static function getDB()
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4)
			return Factory::getDbo();
		else
			return Factory::getContainer()->get(DatabaseInterface::class);
	}

	/**
	 * Inserts data into a database table in a cross-platform manner (Joomla and WordPress).
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array of data to insert. Keys represent column names, values represent values to be inserted.
	 *
	 * @return int or null The ID of the last inserted record, or null if the insert operation failed.
	 * @throws Exception If an error occurs during the insert operation.
	 *
	 * @since 3.1.8
	 */
	public static function insert(string $tableName, array $data): ?int
	{
		$db = self::getDB();

		$db->setQuery("SET NAMES 'utf8mb4'");
		$db->execute();

		$query = $db->getQuery(true);

		// Construct the insert statement
		$columns = array();
		$values = array();

		foreach ($data as $key => $value) {
			$columns[] = $db->quoteName($key);

			if (is_array($value) and count($value) == 2 and $value[1] == 'sanitized') {
				$values[] = $value[0];
			} else {
				if ($value === null)
					$values[] = 'NULL';
				elseif (is_bool($value))
					$values[] = $value ? 'TRUE' : 'FALSE';
				elseif (is_int($value))
					$values[] = $value;
				elseif (is_float($value))
					$values[] = $value;
				else
					$values[] = $db->quote($value);
			}
		}

		$query->insert($db->quoteName($tableName))
			->columns($columns)
			->values(implode(',', $values));

		$db->setQuery($query);

		try {
			$db->execute();
			return $db->insertid(); // Return the last inserted ID
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public static function quote($value): ?string
	{
		$db = self::getDB();
		return $db->quote($value);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getVersion(): ?float
	{
		$db = self::getDB();
		$result = $db->loadAssocList('select @@version');
		return floatval($result[0]['@@version']);
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function loadAssocList(string  $table, array $selects, MySQLWhereClause $whereClause,
										 ?string $order = null, ?string $orderBy = null,
										 ?int    $limit = null, ?int $limitStart = null,
										 string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'ARRAY_A', $groupBy, $returnQueryString);
	}

	public static function loadObjectList(string  $table, array $selectsRaw, MySQLWhereClause $whereClause,
										  ?string $order = null, ?string $orderBy = null,
										  ?int    $limit = null, ?int $limitStart = null,
										  string  $output_type = 'OBJECT', string $groupBy = null,
										  bool    $returnQueryString = false)
	{
		$db = self::getDB();
		$query = $db->getQuery(true);

		//Select columns sanitation
		$selects_sanitized = self::sanitizeSelects($selectsRaw, $table);

		$query->select($selects_sanitized);
		$query->from($table);

		if ($whereClause->hasConditions())
			$query->where($whereClause->getWhereClause());

		if (!empty($groupBy))
			$query->group($groupBy);

		if (!empty($order))
			$query->order($order . (($orderBy !== null and strtolower($orderBy) == 'desc') ? ' DESC' : ''));

		if ($returnQueryString)
			return $query;

		if ($limitStart !== null and $limit !== null)
			$query->setLimit($limit, $limitStart);
		elseif ($limitStart !== null and $limit === null)
			$query->setLimit(20000, $limitStart);
		if ($limitStart === null and $limit !== null)
			$query->setLimit($limit);

		try {
			$db->setQuery($query);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if ($output_type == 'OBJECT')
			return $db->loadObjectList();
		else if ($output_type == 'ROW_LIST')
			return $db->loadRowList();
		else if ($output_type == 'COLUMN')
			return $db->loadColumn();
		else
			return $db->loadAssocList();
	}

	public static function sanitizeSelects(array $selectsRaw, string $realTableName): string
	{
		$serverType = database::getServerType();
		$selects = [];

		foreach ($selectsRaw as $select) {

			if (is_array($select) and count($select) == 2 and $select[0] == 'REAL_FIELD_NAME') {
				$fieldPrefix = preg_replace('/[^a-zA-Z0-9_#]/', '', $select[1]);

				if ($serverType == 'postgresql') {
					$selects[] = 'CONCAT("' . $fieldPrefix . '",fieldname) AS realfieldname';
				} else {
					$selects[] = 'CONCAT("' . $fieldPrefix . '",fieldname) AS realfieldname';
				}

			} elseif (is_array($select) and count($select) >= 3) {
				$selectTable_safe = preg_replace('/[^a-zA-Z0-9_#]/', '', $select[1]);//Joomla way
				$selectField = preg_replace('/[^a-zA-Z0-9_]/', '', $select[2]);
				$asValue = preg_replace('/[^a-zA-Z0-9_]/', '', $select[3] ?? 'vlu');
				$variable = preg_replace('/[^a-zA-Z0-9_]/', '', $select[4] ?? '');

				if ($select[0] == 'COUNT')
					$selects[] = 'COUNT(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'SUM')
					$selects[] = 'SUM(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'AVG')
					$selects[] = 'AVG(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'MIN')
					$selects[] = 'MIN(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'MAX')
					$selects[] = 'MAX(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'VALUE')
					$selects[] = '`' . $selectTable_safe . '`.`' . $selectField . '` AS ' . $asValue;
				elseif ($select[0] == 'OCTET_LENGTH')
					$selects[] = 'OCTET_LENGTH(`' . $selectTable_safe . '`.`' . $selectField . '`) AS ' . $asValue;
				elseif ($select[0] == 'SUBSTRING_255')
					$selects[] = 'SUBSTRING(`' . $selectTable_safe . '`.`' . $selectField . '`,1,255) AS ' . $asValue;
				elseif ($select[0] == 'CUSTOM_FIELD')
					$selects[] = '(SELECT value FROM #__fields_values WHERE #__fields_values.field_id=#__fields.id AND #__fields_values.item_id=' . $variable . ') AS ' . $asValue;

			} elseif ($select == '*') {
				$selects[] = '*';
			} elseif ($select == 'LISTING_PUBLISHED') {
				$selects[] = '`' . $realTableName . '`.`published` AS listing_published';
			} elseif ($select == 'LISTING_PUBLISHED_1') {
				$selects[] = '1 AS listing_published';
			} elseif ($select == 'COUNT_ROWS') {
				$selects[] = 'COUNT(*) AS record_count';
			} elseif ($select == 'MODIFIED_BY') {
				$selects[] = '(SELECT name FROM #__users AS u WHERE u.id=a.modified_by LIMIT 1) AS modifiedby';
			} elseif ($select == 'LAYOUT_SIZE') {
				$selects[] = 'LENGTH(layoutcode) AS layout_size';
			} elseif ($select == 'GROUP_TITLE') {
				$selects[] = '(SELECT `title` FROM `#__usergroups` AS g WHERE g.id = m.group_id LIMIT 1) AS group_title';
			} elseif ($select == 'TABLE_TITLE') {
				$selects[] = '(SELECT tabletitle FROM `#__customtables_tables` AS tables WHERE tables.id=a.tableid) AS tabletitle';
			} elseif ($select == 'TABLE_NAME') {
				$selects[] = '(SELECT tablename FROM `#__customtables_tables` AS tables WHERE tables.id=a.tableid) AS TABLE_NAME';
			} elseif ($select == 'FIELD_NAME') {
				$selects[] = '(SELECT fieldname FROM #__customtables_fields AS fields WHERE fields.published=1 AND fields.tableid=a.tableid LIMIT 1) AS FIELD_NAME';
			} elseif ($select == 'USER_NAME') {
				$selects[] = '(SELECT name FROM #__users AS users WHERE users.id=a.userid) AS USER_NAME';
			} elseif ($select == 'CATEGORY_NAME') {
				$selects[] = '(SELECT `categoryname` FROM `#__customtables_categories` AS categories WHERE categories.id=tablecategory LIMIT 1) AS categoryname';
			} elseif ($select == 'FIELD_COUNT') {
				$selects[] = '(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1) AS fieldcount';
			} elseif ($select == 'MODIFIED_TIMESTAMP') {
				if ($serverType == 'postgresql')
					$selects [] = 'CASE WHEN modified IS NULL THEN extract(epoch FROM created) ELSE extract(epoch FROM modified) AS modified_timestamp';
				else
					$selects [] = 'IF(modified IS NULL,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(modified)) AS modified_timestamp';
			} elseif ($select == 'REAL_TABLE_NAME') {
				if ($serverType == 'postgresql') {
					$selects[] = 'CASE WHEN customtablename!="" THEN customtablename ELSE CONCAT("#__customtables_table_", tablename) END AS realtablename';
				} else {
					$selects[] = 'IF((customtablename IS NOT NULL AND customtablename!=""), customtablename, CONCAT("#__customtables_table_", tablename)) AS realtablename';
				}
			} elseif ($select == 'COLUMN_IS_UNSIGNED') {
				$selects[] = 'IF(COLUMN_TYPE LIKE "%unsigned", "YES", "NO") AS COLUMN_IS_UNSIGNED';
			} elseif ($select == 'REAL_ID_FIELD_NAME') {
				if ($serverType == 'postgresql') {
					$selects[] = 'CASE WHEN customidfield!="" THEN customidfield ELSE "id" END AS realidfieldname';
				} else {
					$selects[] = 'IF(customidfield!="", customidfield, "id") AS realidfieldname';
				}
			} elseif ($select == 'PUBLISHED_FIELD_FOUND') {
				$selects[] = '1 AS published_field_found';

			} else {

				$parts = explode('.', $select);
				if (count($parts) == 2) {
					$selectTable_safe = preg_replace('/[^a-zA-Z0-9_#]/', '', $parts[0]);

					if ($parts[1] == '*')
						$selects[] = "`" . $selectTable_safe . "`.*";
					else {

						$partsAs = explode(' AS ', $parts[1]);
						if (count($partsAs) == 2) {
							$column_name_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $partsAs[0]);
							$as_name = preg_replace('/[^a-zA-Z0-9_]/', '', $partsAs[1]);
							$selects[] = "`" . $selectTable_safe . "`.`" . $column_name_safe . "` AS " . $as_name;
						} else {
							$column_name_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[1]);
							$selects[] = "`" . $selectTable_safe . "`.`" . $column_name_safe . "`";
						}
					}
				} else {
					$partsAs = explode(' AS ', $select);
					if (count($partsAs) == 2) {
						$column_name_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $partsAs[0]);
						$as_name = preg_replace('/[^a-zA-Z0-9_]/', '', $partsAs[1]);
						$selects[] = "" . $column_name_safe . " AS " . $as_name;
					} else {
						$column_name_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $select);
						$selects[] = "`" . $column_name_safe . "`";
					}
				}
			}
		}
		return implode(',', $selects);
	}

	public static function getServerType(): ?string
	{
		$db = self::getDB();
		return $db->serverType;
	}

	public static function loadRowList(string  $table, array $selects, MySQLWhereClause $whereClause,
									   ?string $order = null, ?string $orderBy = null,
									   ?int    $limit = null, ?int $limitStart = null,
									   string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'ROW_LIST', $groupBy, $returnQueryString);
	}

	public static function loadColumn(string  $table, array $selects, MySQLWhereClause $whereClause,
									  ?string $order = null, ?string $orderBy = null,
									  ?int    $limit = null, ?int $limitStart = null,
									  string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'COLUMN', $groupBy, $returnQueryString);
	}

	public static function getTableStatus(string $tablename, string $type = 'table')
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		$database = $config->get('db');
		$dbPrefix = $config->get('dbprefix');

		$db = self::getDB();

		if ($type == 'gallery')
			$realTableName = $dbPrefix . 'customtables_gallery_' . $tablename;
		elseif ($type == 'filebox')
			$realTableName = $dbPrefix . 'customtables_filebox_' . $tablename;
		elseif ($type == 'native')
			$realTableName = $tablename;
		elseif ($type == 'table')
			$realTableName = $dbPrefix . 'customtables_table_' . $tablename;
		elseif ($type == 'categories')
			$realTableName = $dbPrefix . 'customtables_categories';
		else
			$realTableName = $dbPrefix . 'customtables_' . $tablename;

		$db->setQuery('SHOW TABLE STATUS FROM ' . $db->quoteName($database) . ' LIKE ' . $db->quote($realTableName));
		return $db->loadObjectList();
	}

	public static function getTableIndex(string $tableName, string $fieldName)
	{
		$db = self::getDB();

		$db->setQuery('SHOW INDEX FROM ' . $tableName . ' WHERE Key_name = "' . $fieldName . '"');
		return $db->loadObjectList();
	}

	public static function showCreateTable($tableName): array
	{
		$db = self::getDB();

		$db->setQuery("SHOW CREATE TABLE " . $tableName);
		return $db->loadAssocList();
	}

	/**
	 * Updates data in a database table in a cross-platform manner (Joomla and WordPress).
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array $data An associative array of data to update. Keys represent column names, values represent new values.
	 * @param array $where An associative array specifying which rows to update. Keys represent column names, values represent conditions for the update.
	 *
	 * @return bool True if the update operation is successful, otherwise false.
	 * @throws Exception If an error occurs during the update operation.
	 *
	 * @since 3.1.8
	 */
	public static function update(string $tableName, array $data, MySQLWhereClause $whereClause): bool
	{
		if (!$whereClause->hasConditions()) {
			throw new Exception('Update database table records without WHERE clause is prohibited.');
		}

		if (count($data) == 0)
			return true;

		$db = self::getDB();
		
		$db->setQuery("SET NAMES 'utf8mb4'");
		$db->execute();

		$fields = self::prepareFields($db, $data);

		$query = $db->getQuery(true);

		$query->update($db->quoteName($tableName))
			->set($fields)
			->where($whereClause->getWhereClause());

		$db->setQuery($query);

		try {
			$db->execute();
			return true; // Update successful
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	protected static function prepareFields($db, $data): array
	{
		// Construct the update statement
		$fields = array();
		foreach ($data as $key => $value) {
			if (is_array($value) and count($value) == 2 and $value[1] == 'sanitized') {
				$fields[] = $db->quoteName($key) . '=' . $value[0];
			} else {
				if ($value === null)
					$valueCleaned = 'NULL';
				elseif (is_bool($value))
					$valueCleaned = $value ? 'TRUE' : 'FALSE';
				elseif (is_int($value))
					$valueCleaned = $value;
				elseif (is_float($value))
					$valueCleaned = $value;
				else
					$valueCleaned = $db->quote($value);

				$fields[] = $db->quoteName($key) . '=' . $valueCleaned;
			}
		}
		return $fields;
	}

	public static function deleteRecord(string $tableName, string $realIdFieldName, $id): void
	{
		$db = self::getDB();

		$query = $db->getQuery(true);
		$query->delete($tableName);

		if (is_int($id))
			$query->where($db->quoteName($realIdFieldName) . '=' . $id);
		else
			$query->where($db->quoteName($realIdFieldName) . '=' . $db->quote($id));

		$db->setQuery($query);
		$db->execute();
	}

	public static function deleteTableLessFields(): void
	{
		$db = self::getDB();
		$db->setQuery('DELETE FROM #__customtables_fields AS f WHERE (SELECT id FROM #__customtables_tables AS t WHERE t.id = f.tableid) IS NULL');
		$db->execute();
	}

	public static function dropTableIfExists(string $tablename, string $type = 'table'): void
	{
		$db = self::getDB();

		if ($type == 'gallery')
			$realTableName = '#__customtables_gallery_' . $tablename;
		elseif ($type == 'filebox')
			$realTableName = '#__customtables_filebox_' . $tablename;
		else
			$realTableName = '#__customtables_table_' . $tablename;

		$serverType = self::getServerType();

		if ($serverType == 'postgresql') {

			$db->setQuery('DROP TABLE IF EXISTS ' . $realTableName);
			$db->execute();

			$db->setQuery('DROP SEQUENCE IF EXISTS ' . $realTableName . '_seq CASCADE');
			$db->execute();

		} else {
			$query = 'DROP TABLE IF EXISTS ' . $db->quoteName($realTableName);
			$db->setQuery($query);
			$db->execute();
		}
	}

	public static function dropColumn(string $realTableName, string $columnName): void
	{
		$db = self::getDB();

		$db->setQuery('SET foreign_key_checks = 0');
		$db->execute();

		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' DROP COLUMN ' . $db->quoteName($columnName));
		$db->execute();

		$db->setQuery('SET foreign_key_checks = 1');
		$db->execute();
	}

	public static function addForeignKey(string $realTableName, string $columnName, string $join_with_table_name, string $join_with_table_field): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ADD FOREIGN KEY (' . $db->quoteName($columnName) . ') REFERENCES '
			. $db->quoteName(self::getDataBaseName() . '.' . $join_with_table_name) . ' (' . $db->quoteName($join_with_table_field) . ') ON DELETE RESTRICT ON UPDATE RESTRICT');
		$db->execute();
	}

	public static function getDataBaseName(): ?string
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		return $config->get('db');
	}

	public static function dropForeignKey(string $realTableName, string $constrance): void
	{
		$db = self::getDB();

		$db->setQuery('SET foreign_key_checks = 0');
		$db->execute();

		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' DROP FOREIGN KEY ' . $constrance);
		$db->execute();

		$db->setQuery('SET foreign_key_checks = 1');
		$db->execute();
	}

	public static function setTableInnoDBEngine(string $realTableName): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $realTableName . ' ENGINE = InnoDB');
		$db->execute();
	}

	public static function changeTableComment(string $realTableName, string $comment): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $realTableName . ' COMMENT ' . $db->quote($comment));
		$db->execute();
	}

	public static function addIndex(string $realTableName, string $columnName): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ADD INDEX (' . $db->quoteName($columnName) . ')');
		$db->execute();
	}

	public static function addColumn(string  $realTableName, string $columnName, string $type, ?bool $nullable = null, ?string $extra = null,
									 ?string $comment = null): void
	{
		$db = self::getDB();

		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ADD COLUMN ' . $db->quoteName($columnName) . ' ' . $type
			. ($nullable !== null ? ($nullable ? ' NULL' : ' NOT NULL') : '')
			. ($extra !== null ? ' ' . $extra : '')
			. ($comment !== null ? ' COMMENT ' . database::quote($comment) : ''));
		$db->execute();
	}

	public static function createTable(string $realTableName, string $privateKey, array $columns, string $comment,
									   ?array $keys = null, string $primaryKeyType = 'int UNSIGNED NOT NULL AUTO_INCREMENT'): void
	{
		$db = self::getDB();

		if (self::getServerType() == 'postgresql') {

			$db->setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $realTableName . '_seq');
			$db->execute();

			$primaryKeyTypeString = str_replace('AUTO_INCREMENT', 'DEFAULT nextval (\'' . $realTableName . '_seq\')', $primaryKeyType);

			$allColumns = array_merge([$privateKey . ' ' . $primaryKeyTypeString], $columns);

			$query = 'CREATE TABLE IF NOT EXISTS ' . $realTableName . '(' . implode(',', $allColumns) . ')';
			$db->setQuery($query);
			$db->execute();

			$db->setQuery('ALTER SEQUENCE ' . $realTableName . '_seq RESTART WITH 1');
			$db->execute();

		} else {

			$allColumns = array_merge(['`' . $privateKey . '` ' . $primaryKeyType], $columns, ['PRIMARY KEY (`' . $privateKey . '`)']);

			if ($keys !== null)
				$allColumns = array_merge($allColumns, $keys);

			$query = 'CREATE TABLE IF NOT EXISTS ' . $realTableName
				. '(' . implode(',', $allColumns) . ') ENGINE=InnoDB COMMENT="' . $comment . '"'
				. ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;';

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.5
	 */
	public static function copyCTTable(string $newTableName, string $oldTableName): void
	{
		$db = self::getDB();

		$realNewTableName = '#__customtables_table_' . $newTableName;

		if (self::getServerType() == 'postgresql') {

			$db->setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $realNewTableName . '_seq');
			$db->execute();

			$db->setQuery('CREATE TABLE ' . $realNewTableName . ' AS TABLE #__customtables_table_' . $oldTableName);
			$db->execute();

			$db->setQuery('ALTER SEQUENCE ' . $realNewTableName . '_seq RESTART WITH 1');
			$db->execute();
		} else {
			$db->setQuery('CREATE TABLE ' . $realNewTableName . ' AS SELECT * FROM #__customtables_table_' . $oldTableName);
			$db->execute();
		}

		$db->setQuery('ALTER TABLE ' . $realNewTableName . ' ADD PRIMARY KEY (id)');
		$db->execute();

		$PureFieldType = [
			'data_type' => 'int',
			'is_unsigned' => true,
			'is_nullable' => false,
			'autoincrement' => true
		];
		database::changeColumn($realNewTableName, 'id', 'id', $PureFieldType, 'Primary Key');
	}

	/**
	 * @throws Exception
	 * @since 3.2.5
	 */
	public static function changeColumn(string $realTableName, string $oldColumnName, string $newColumnName, array $PureFieldType, ?string $comment = null): void
	{
		if (!str_contains($realTableName, 'customtables_'))
			throw new Exception('Only CustomTables tables can be modified.');

		$possibleTypes = ['varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'blob', 'mediumblob',
			'longblob', 'char', 'int', 'bigint', 'numeric', 'decimal', 'smallint', 'tinyint', 'date', 'TIMESTAMP', 'datetime'];

		if (!in_array($PureFieldType['data_type'], $possibleTypes))
			throw new Exception('Change Column type: unsupported column type "' . $PureFieldType['data_type'] . '"');

		$db = self::getDB();

		if (self::getServerType() == 'postgresql') {

			if ($oldColumnName != $newColumnName)
				$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' RENAME COLUMN ' . $db->quoteName($oldColumnName) . ' TO ' . $db->quoteName($newColumnName));

			$db->execute();

			$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ALTER COLUMN'
				. ' ' . $db->quoteName($newColumnName)
				. ' TYPE ' . $PureFieldType['data_type']
			);
			$db->execute();
		} else {

			$type = $PureFieldType['data_type'];
			if (($PureFieldType['length'] ?? '') != '') {
				if (str_contains($PureFieldType['length'] ?? '', ',')) {
					$parts = explode(',', $PureFieldType['length']);
					$partsInt = [];
					foreach ($parts as $part)
						$partsInt[] = (int)$part;

					$type .= '(' . implode(',', $partsInt) . ')';
				} else
					$type .= '(' . (int)$PureFieldType['length'] . ')';
			}

			if ($PureFieldType['is_unsigned'] ?? false)
				$type .= ' UNSIGNED';

			$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' CHANGE ' . $db->quoteName($oldColumnName) . ' ' . $db->quoteName($newColumnName)
				. ' ' . $type
				. (($PureFieldType['is_nullable'] ?? false) ? ' NULL' : ' NOT NULL')
				. (($PureFieldType['default'] ?? '') != "" ? ' DEFAULT ' . (is_numeric($PureFieldType['default']) ? $PureFieldType['default'] : $db->quote($PureFieldType['default'])) : '')
				. (($PureFieldType['autoincrement'] ?? false) ? ' AUTO_INCREMENT' : '')
				. ' COMMENT ' . $db->quote($comment));

			$db->execute();
		}
	}

	public static function showTables()
	{
		$db = self::getDB();
		return $db->loadAssocList('SHOW TABLES');
	}

	public static function renameTable(string $oldCTTableName, string $newCTTableName, string $type = 'table'): void
	{
		$db = self::getDB();
		$database = self::getDataBaseName();

		if ($type == 'gallery') {
			$oldTableName = '#__customtables_gallery_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_gallery_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		} elseif ($type == 'filebox') {
			$oldTableName = '#__customtables_filebox_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_filebox_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		} else {
			$oldTableName = '#__customtables_table_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_table_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		}

		$db->setQuery('RENAME TABLE ' . $db->quoteName($database . '.' . $oldTableName) . ' TO '
			. $db->quoteName($database . '.' . $newTableName));
		$db->execute();
	}

	public static function getDBPrefix(): ?string
	{
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$config = Factory::getContainer()->get('config');
		else
			$config = Factory::getConfig();

		return $config->get('dbprefix');
	}

	public static function getExistingFields(string $tablename, $add_table_prefix = true): array
	{
		$db = self::getDB();
		$dbName = self::getDataBaseName();
		$tablename = preg_replace('/[^a-zA-Z0-9_#]/', '', $tablename);

		if ($add_table_prefix)
			$realtablename = $db->getPrefix() . 'customtables_table_' . $tablename;
		else
			$realtablename = str_replace('#__', $db->getPrefix(), $tablename);

		$serverType = database::getServerType();

		if ($serverType == 'postgresql') {
			$db->setQuery('SELECT `column_name`,`data_type`,`is_nullable`,`column_default` FROM'
				. ' `information_schema.columns` WHERE `table_name`=', $db->quote($realtablename));

			$results = $db->loadAssocList();

		} else {

			$selects = [
				'COLUMN_NAME AS column_name',
				'DATA_TYPE AS data_type',
				'COLUMN_TYPE AS column_type',
				'COLUMN_IS_UNSIGNED',
				'IS_NULLABLE AS is_nullable',
				'COLUMN_DEFAULT AS column_default',
				'EXTRA AS extra'];

			$selectsSafe = database::sanitizeSelects($selects, 'information_schema.COLUMNS');
			$query = 'SELECT ' . $selectsSafe . ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA="' . $dbName . '" AND TABLE_NAME=' . $db->quote($realtablename);

			$db->setQuery($query);
			$results = $db->loadAssocList();
		}
		return $results;
	}
}