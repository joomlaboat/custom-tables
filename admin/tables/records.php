<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listofrecords Table class
 */
class CustomtablesTableRecords extends JTable
{
    //protected $_jsonEncode = array('params', 'metadata');

    /**
     * Constructor
     *
     * @param object Database connector object
     */

    function __construct(&$db)
    {
        $jinput = Factory::getApplication()->input;
        $tableid = $jinput->getInt('tableid', 0);

        if ($tableid != 0) {
            $table = ESTables::getTableRowByID($tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                return null;
            }
        }

        if ($table->customtablename != '')
            $realtablename = $table->customtablename;
        else
            $realtablename = '#__customtables_table_' . $table->tablename;

        parent::__construct($realtablename, 'id', $db);
    }

}
