<?php
/**
 * CustomTables WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('WPINC')) {
	die('Restricted access');
}

use Exception;

class database
{
	public function __construct()
	{
	}

	public static function getDBPrefix(): ?string
	{
		global $wpdb;
		return $wpdb->prefix;
	}

	public static function realTableName($tableName): ?string
	{
		global $wpdb;
		return str_replace('#__', $wpdb->prefix, $tableName);
	}


	public static function getDataBaseName(): ?string
	{
		return DB_NAME;
	}

	public static function getServerType(): ?string
	{
		if (str_contains(DB_HOST, 'mysql')) {
			return 'mysql';
		} elseif (str_contains(DB_HOST, 'pgsql')) {
			return 'postgresql';
		} else {
			return 'Unknown';
		}
	}

	public static function loadObjectList($query, $limitStart = null, $limit = null)
	{
		global $wpdb;

		if ($limit !== null)
			$query .= ' LIMIT ' . $limit;

		if ($limitStart !== null)
			$query .= ' OFFSET ' . $limitStart;

		$results = $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query));
		if ($wpdb->last_error !== '')
			throw new Exception($wpdb->last_error);

		return $results;
	}

	public static function setQuery($query): void
	{
		global $wpdb;
		$wpdb->query(str_replace('#__', $wpdb->prefix, $query));
		if ($wpdb->last_error !== '')
			throw new Exception($wpdb->last_error);
	}

	/**
	 * Inserts data into a database table in a cross-platform manner (Joomla and WordPress).
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array of data to insert. Keys represent column names, values represent values to be inserted.
	 *
	 * @return int|null The ID of the last inserted record, or null if the insert operation failed.
	 * @throws Exception If an error occurs during the insert operation.
	 *
	 * @since 3.1.8
	 */
	public static function insert(string $tableName, array $data): ?int
	{
		global $wpdb;
		$wpdb->insert(str_replace('#__', $wpdb->prefix, $tableName), $data);

		if ($wpdb->last_error !== '')
			throw new Exception($wpdb->last_error);

		$id = $wpdb->insert_id;
		if (!$id)
			return null;

		return $id;
	}

	public static function quoteName($value)
	{
		return $value;
	}

	public static function quote($value, bool $row = true): ?string
	{
		global $wpdb;

		if ($row)
			return '\'' . esc_sql($value) . '\'';
		else
			return $wpdb->prepare('%s', $value);

		//    %d for integers
		//    %f for floating-point numbers
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
	public static function update(string $tableName, array $data, array $where): bool
	{
		if (count($data) == 0)
			return true;


		global $wpdb;
		$wpdb->update(str_replace('#__', $wpdb->prefix, $tableName), $data, $where);
		if ($wpdb->last_error !== '')
			throw new Exception($wpdb->last_error);

		return true;
	}

	public static function getNumRowsOnly($query): int
	{
		global $wpdb;
		$wpdb->query(str_replace('#__', $wpdb->prefix, $query));
		return $wpdb->num_rows;
	}

	public static function getVersion(): ?float
	{
		$result = self::loadAssocList('select @@version');
		return floatval($result[0]['@@version']);
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function loadAssocList($query, $limitStart = null, $limit = null)
	{
		global $wpdb;

		if ($limit !== null)
			$query .= ' LIMIT ' . $limit;

		if ($limitStart !== null)
			$query .= ' OFFSET ' . $limitStart;

		$result = $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query), ARRAY_A);
		if ($wpdb->last_error !== '')
			throw new Exception($wpdb->last_error);

		return $result;
	}

	public static function loadRowList($query, $limitStart = null, $limit = null): ?array
	{
		global $wpdb;

		if ($limit !== null)
			$query .= ' LIMIT ' . $limit;

		if ($limitStart !== null)
			$query .= ' OFFSET ' . $limitStart;

		return $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query), ARRAY_N);
	}

	public static function loadColumn($query, $limitStart = null, $limit = null): ?array
	{
		global $wpdb;

		if ($limit !== null)
			$query .= ' LIMIT ' . $limit;

		if ($limitStart !== null)
			$query .= ' OFFSET ' . $limitStart;

		return $wpdb->get_col(str_replace('#__', $wpdb->prefix, $query));
	}

}