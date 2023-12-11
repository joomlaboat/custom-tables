<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

class database
{
	public function __construct()
	{
	}

	public static function getDBPrefix(): ?string
	{
		if (defined('_JEXEC')) {
			$conf = Factory::getConfig();
			return $conf->get('dbprefix');
		} elseif (defined('WPINC')) {
			global $wpdb;
			return $wpdb->prefix;
		}
		return null;
	}

	public static function realTableName($tableName): ?string
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			return str_replace('#__', $db->getPrefix(), $tableName);
		} elseif (defined('WPINC')) {
			global $wpdb;
			return str_replace('#__', $wpdb->prefix, $tableName);
		}
		return null;
	}


	public static function getDataBaseName(): ?string
	{
		if (defined('_JEXEC')) {
			$conf = Factory::getConfig();
			return $conf->get('db');
		} elseif (defined('WPINC')) {
			return DB_NAME;
		}
		return null;
	}

	public static function getServerType(): ?string
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			return $db->serverType == 'postgresql';
		} elseif (defined('WPINC')) {
			if (str_contains(DB_HOST, 'mysql')) {
				return 'mysql';
			} elseif (str_contains(DB_HOST, 'pgsql')) {
				return 'postgresql';
			} else {
				return 'Unknown';
			}
		}
		return null;
	}

	public static function loadObjectList($query, $limitStart = null, $limit = null)
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			if ($limitStart !== null and $limit !== null)
				$db->setQuery($query, $limitStart, $limit);
			elseif ($limitStart !== null)
				$db->setQuery($query, $limitStart);
			else
				$db->setQuery($query);

			return $db->loadObjectList();
		} elseif (defined('WPINC')) {
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
		return null;
	}

	public static function setQuery($query): void
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			$db->setQuery($query);
			$db->execute();
		} elseif (defined('WPINC')) {
			global $wpdb;
			$wpdb->query(str_replace('#__', $wpdb->prefix, $query));
			if ($wpdb->last_error !== '')
				throw new Exception($wpdb->last_error);
		}
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
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);

			// Construct the insert statement
			$columns = array();
			$values = array();

			foreach ($data as $key => $value) {
				$columns[] = $db->quoteName($key);

				if ($value === null)
					$values[] = 'NULL';
				else
					$values[] = $db->quote($value);
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
		} elseif (defined('WPINC')) {
			global $wpdb;
			$wpdb->insert(str_replace('#__', $wpdb->prefix, $tableName), $data);

			if ($wpdb->last_error !== '')
				throw new Exception($wpdb->last_error);

			$id = $wpdb->insert_id;
			if (!$id)
				return null;

			return $id;
		}
		return null;
	}

	public static function quoteName($value)
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			return $db->quoteName($value);
		} elseif (defined('WPINC')) {
			return $value;
		}
		return null;
	}

	public static function quote($value, bool $row = true): ?string
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			return $db->quote($value);
		} elseif (defined('WPINC')) {

			global $wpdb;

			if ($row)
				return '\'' . esc_sql($value) . '\'';
			else
				return $wpdb->prepare('%s', $value);

			//    %d for integers
			//    %f for floating-point numbers
		}
		return null;
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

		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);

			// Construct the update statement
			$fields = array();
			foreach ($data as $key => $value) {
				if ($value === null)
					$fields[] = $db->quoteName($key) . ' = NULL';
				else
					$fields[] = $db->quoteName($key) . ' = ' . $db->quote($value);
			}

			$conditions = array();
			foreach ($where as $key => $value) {
				$conditions[] = $db->quoteName($key) . ' = ' . $db->quote($value);
			}

			$query->update($db->quoteName($tableName))
				->set($fields)
				->where($conditions);

			$db->setQuery($query);

			try {
				$db->execute();
				return true; // Update successful
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

		} elseif (defined('WPINC')) {
			global $wpdb;
			$wpdb->update(str_replace('#__', $wpdb->prefix, $tableName), $data, $where);
			if ($wpdb->last_error !== '')
				throw new Exception($wpdb->last_error);

			return true;
		}
		return false;
	}

	public static function getNumRowsOnly($query): int
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			$db->setQuery($query);
			$db->execute();
			return $db->getNumRows();
		} elseif (defined('WPINC')) {
			global $wpdb;
			$wpdb->query(str_replace('#__', $wpdb->prefix, $query));
			return $wpdb->num_rows;
		}
		return -1;
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
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			if ($limitStart !== null and $limit !== null)
				$db->setQuery($query, $limitStart, $limit);
			elseif ($limitStart !== null)
				$db->setQuery($query, $limitStart);
			else
				$db->setQuery($query);

			return $db->loadAssocList();
		} elseif (defined('WPINC')) {
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
		return null;
	}

	public static function loadRowList($query, $limitStart = null, $limit = null): ?array
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			if ($limitStart !== null and $limit !== null)
				$db->setQuery($query, $limitStart, $limit);
			elseif ($limitStart !== null)
				$db->setQuery($query, $limitStart);
			else
				$db->setQuery($query);

			return $db->loadRowList();
		} elseif (defined('WPINC')) {
			global $wpdb;

			if ($limit !== null)
				$query .= ' LIMIT ' . $limit;

			if ($limitStart !== null)
				$query .= ' OFFSET ' . $limitStart;

			return $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query), ARRAY_N);
		}
		return null;
	}

	public static function loadColumn($query, $limitStart = null, $limit = null): ?array
	{
		if (defined('_JEXEC')) {

			$version_object = new Version;
			$version = (int)$version_object->getShortVersion();

			if ($version < 4)
				$db = Factory::getDbo();
			else
				$db = Factory::getContainer()->get('DatabaseDriver');

			if ($limitStart !== null and $limit !== null)
				$db->setQuery($query, $limitStart, $limit);
			elseif ($limitStart !== null)
				$db->setQuery($query, $limitStart);
			else
				$db->setQuery($query);

			return $db->loadColumn();
		} elseif (defined('WPINC')) {
			global $wpdb;

			if ($limit !== null)
				$query .= ' LIMIT ' . $limit;

			if ($limitStart !== null)
				$query .= ' OFFSET ' . $limitStart;

			return $wpdb->get_col(str_replace('#__', $wpdb->prefix, $query));
		}
		return null;
	}

}