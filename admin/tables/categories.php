<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/categories.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
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
 * Listofcategories Table class
 */
class CustomtablesTableCategories extends JTable
{
    var $id = null;
    var $categoryname = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db)
    {
        parent::__construct('#__customtables_categories', 'id', $db);
    }
}
