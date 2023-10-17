<?php

namespace CustomTables;

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

            return $wpdb->get_results(str_replace('#__', $wpdb->prefix, $query));
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
        }
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
}