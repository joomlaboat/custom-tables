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

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listoftables Table class
 */
class CustomtablesTableTables extends JTable
{
    var $id = null;
    var $customphp = null;
    var $description = null;
    var $tablecategory = null;
    var $tablename = null;
    var $tabletitle = null;
    var $published = null;
    var $created_by = null;
    var $modified_by = null;
    var $created = null;
    var $modified = null;
    var $checked_out = null;
    var $checked_out_time = null;
    var $allowimportcontent = null;
    var $customtablename = null;
    var $customidfield = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db)
    {
        parent::__construct('#__customtables_tables', 'id', $db);
    }
}
