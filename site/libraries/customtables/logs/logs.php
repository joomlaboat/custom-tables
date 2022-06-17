<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use \Joomla\CMS\Factory;

trait Logs
{
    public function saveLog($listing_id, $action)
    {
        // 1 - New
        // 2 - Edit
        // 3 - Publish
        // 4 - Unpublish
        // 5 - Delete
        // 6 - Image Uploaded
        // 7 - Image Deleted
        // 8 - File Uploaded
        // 9 - File Deleted

        $db = Factory::getDBO();

        $sets = array();
        $sets[] = (int)$this->Env->userid;
        $sets[] = 'NOW()';
        $sets[] = (int)$this->tableid;
        $sets[] = (int)$listing_id;
        $sets[] = (int)$action;
        $sets[] = (int)Factory::getApplication()->input->get('Itemid', 0, 'INT');

        //Value from sets
        $fields = ['userid', 'datetime', 'tableid', 'listingid', 'action', 'Itemid'];
        $query = 'INSERT INTO #__customtables_log (' . implode(',', $fields) . ') VALUES (' . implode(',', $sets) . ')';

        $db->setQuery($query);
        $db->execute();
    }
}
