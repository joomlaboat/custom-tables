<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/categories.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\Registry\Registry;

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listoffields Table class
 */
class CustomtablesTableFields extends JTable
{
    var $id = null;
    var $tableid = null;
    var $allowordering = null;
    var $defaultvalue = null;
    var $fieldname = null;
    var $customfieldname = null;
    var $fieldtitle = null;
    var $description = null;
    var $isrequired = null;
    var $isdisabled = null;
    var $savevalue = null;
    var $alwaysupdatevalue = null;
    var $type = null;
    var $typeparams = null;
    var $valuerule = null;
    var $valuerulecaption = null;
    var $published = null;
    var $parentid = null;
    var $created_by = null;
    var $modified_by = null;
    var $created = null;
    var $modified = null;
    var $checked_out = null;
    var $checked_out_time = null;
    var $ordering = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db)
    {
        parent::__construct('#__customtables_fields', 'id', $db);
    }
}
