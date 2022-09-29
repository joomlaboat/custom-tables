<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

/**
 * Listoffields Controller
 */
class CustomtablesControllerListoffields extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFFIELDS';

    public function publish()
    {
        if ($this->task == 'publish')
            $status = 1;
        elseif ($this->task == 'unpublish')
            $status = 0;
        elseif ($this->task == 'trash')
            $status = -2;
        else
            return;

        $tableid = $this->input->get('tableid', 0, 'int');

        if ($tableid != 0) {
            $table = ESTables::getTableRowByID($tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                return;
            } else {
                $tablename = $table->tablename;
            }
        }

        $cid = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);

        $ok = true;

        foreach ($cid as $id) {
            if ((int)$id != 0) {
                $id = (int)$id;
                $isok = $this->setPublishStatusSingleRecord($id, $status);
                if (!$isok) {
                    $ok = false;
                    break;
                }
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoffields&tableid=' . (int)$tableid;

        if ($this->task == 'trash')
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_TRASHED';
        elseif ($this->task == 'publish')
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_PUBLISHED';
        else
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_UNPUBLISHED';

        if (count($cid) == 1)
            $msg .= '_1';

        Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg, count($cid)), 'success');

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }

    protected function setPublishStatusSingleRecord($id, $status)
    {
        $db = Factory::getDBO();

        $query = 'UPDATE #__customtables_fields SET published=' . (int)$status . ' WHERE id=' . (int)$id;

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    public function delete()
    {
        $tableid = $this->input->get('tableid', 0, 'int');

        if ($tableid != 0) {
            $table = ESTables::getTableRowByID($tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                return;
            } else {
                $tablename = $table->tablename;
            }
        }

        $cid = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);

        $ok = true;

        foreach ($cid as $id) {
            if ((int)$id != 0) {
                $id = (int)$id;
                $isok = $this->deleteSingleRecord($id);
                if (!$isok) {
                    $ok = false;
                    break;
                }
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoffields&tableid=' . (int)$tableid;

        $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_DELETED';
        if (count($cid) == 1)
            $msg .= '_1';

        Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg, count($cid)), 'success');

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }

    protected function deleteSingleRecord($id)
    {
        $db = Factory::getDBO();

        $query = 'DELETE FROM #__customtables_fields WHERE id=' . (int)$id;

        $db->setQuery($query);
        $db->execute();

        return true;
    }
}
