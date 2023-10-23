<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use Joomla\CMS\Factory;

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listofrecords Table class
 */
class CustomtablesTableRecords extends JTable
{
    var $id = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */

    function __construct(&$db)
    {
        $tableid = common::inputGetInt('tableid', 0);
        if ($tableid != 0) {
            $table = ESTables::getTableRowByID($tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found.', 'error');
                return null;
            }
        } else {
            Factory::getApplication()->enqueueMessage('Table ID cannot be 0.', 'error');
            return null;
        }

        if ($table->customtablename != '')
            $realtablename = $table->customtablename;
        else
            $realtablename = '#__customtables_table_' . $table->tablename;

        parent::__construct($realtablename, 'id', $db);
    }
}