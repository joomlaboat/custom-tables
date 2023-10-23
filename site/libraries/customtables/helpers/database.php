<?php

namespace CustomTables;

use Exception;
use Joomla\CMS\Factory;

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
            $db = Factory::getDBO();
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
            $db = Factory::getDBO();
            return $db->serverType == 'postgresql';
        } elseif (defined('WPINC')) {
            if (strpos(DB_HOST, 'mysql') !== false) {
                return 'mysql';
            } elseif (strpos(DB_HOST, 'pgsql') !== false) {
                return 'postgresql';
            } else {
                return 'Unknown';
            }
        }
        return null;
    }

    public static function quote($value)
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
            return $db->quote($value);
        } elseif (defined('WPINC')) {
            global $wpdb;
            return $wpdb->prepare('%s', $value);

            //    %d for integers
            //    %f for floating-point numbers
        }
        return null;
    }

    public static function quoteName($value)
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
            return $db->quoteName($value);
        } elseif (defined('WPINC')) {
            return $value;
        }
        return null;
    }

    public static function loadObjectList($query, $limitStart = null, $limit = null)
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();

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
            $db = Factory::getDBO();
            $db->setQuery($query);
            $db->execute();
        } elseif (defined('WPINC')) {
            global $wpdb;
            $wpdb->query(str_replace('#__', $wpdb->prefix, $query));
            if ($wpdb->last_error !== '')
                throw new Exception($wpdb->last_error);
        }
    }

    public static function insert($query): ?int
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
            $db->setQuery($query);
            $db->execute();
            return $db->insertid();
        } elseif (defined('WPINC')) {
            global $wpdb;
            $wpdb->query(str_replace('#__', $wpdb->prefix, $query));
            if ($wpdb->last_error !== '')
                throw new Exception($wpdb->last_error);
            
            return $wpdb->insert_id;
        }
        return null;
    }

    public static function updateSets(string $tableName, array $sets, array $where): bool
    {
        $query = 'UPDATE ' . $tableName . ' SET ' . implode(',', $sets) . ' WHERE ' . implode(',', $where);
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
            $db->setQuery($query);
            $db->execute();
            return true;
        } elseif (defined('WPINC')) {
            global $wpdb;
            $new_query = str_replace('#__', $wpdb->prefix, $query);
            $wpdb->query($new_query);

            if ($wpdb->last_error !== '')
                throw new Exception($wpdb->last_error);

            return true;
        }
        return false;
    }

    public static function insertSets(string $tableName, array $sets): ?int
    {
        $query = 'INSERT ' . $tableName . ' SET ' . implode(',', $sets);
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
            $db->setQuery($query);
            $db->execute();
            return $db->insertid();
        } elseif (defined('WPINC')) {
            global $wpdb;
            $new_query = str_replace('#__', $wpdb->prefix, $query);
            $wpdb->query($new_query);

            if ($wpdb->last_error !== '')
                throw new Exception($wpdb->last_error);

            return $wpdb->insert_id;
        }
        return null;
    }

    public static function getNumRowsOnly($query): int
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
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

    public static function loadAssocList($query, $limitStart = null, $limit = null)
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
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

            return $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query), ARRAY_A);
        }
        return null;
    }

    public static function loadRowList($query, $limitStart = null, $limit = null): ?array
    {
        if (defined('_JEXEC')) {
            $db = Factory::getDBO();
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
            $db = Factory::getDBO();
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