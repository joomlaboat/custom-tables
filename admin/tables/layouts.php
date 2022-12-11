<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/layouts.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listoflayouts Table class
 */
class CustomtablesTableLayouts extends JTable
{
    var $id = null;
    var $changetimestamp = null;

    var $layoutcode = null;
    var $layoutmobile = null;
    var $layoutcss = null;
    var $layoutjs = null;

    var $layoutname = null;
    var $layouttype = null;
    var $tableid = null;
    var $published = null;
    var $created_by = null;
    var $modified_by = null;
    var $created = null;
    var $modified = null;
    var $checked_out = null;
    var $checked_out_time = null;

    //protected $_jsonEncode = array('params', 'metadata');

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db)
    {
        parent::__construct('#__customtables_layouts', 'id', $db);
    }
}
