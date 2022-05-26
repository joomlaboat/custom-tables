<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

/**
 * Listoffields Controller
 */
class CustomtablesControllerListofRecords extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFRECORDS';

    /**
     * Proxy for getModel.
     * @since    2.5
     */
    public function getModel($name = 'Records', $prefix = 'CustomtablesModel', $config = array())
    {
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
        return $model;
    }

    public function publish()
    {
        $status = (int)($this->task == 'publish');

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
        //$cid = ArrayHelper::toInteger($cid);

        //Get Edit model
        $paramsArray = $this->getRecordParams($tableid, $tablename, 0);

        $_params = new JRegistry;
        $_params->loadArray($paramsArray);

        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'edititem.php');
        $editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);

        $ct = new CT($_params, false);

        $editModel->load($ct, false);

        foreach ($cid as $id) {
            if ($id != '') {
                if ($editModel->setPublishStatusSingleRecord($id, $status) == -1)
                    break;
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

        $msg = $this->task == 'publish' ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED';

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

    protected function getRecordParams($tableid, $tablename, $recordid)
    {
        $paramsArray = array();

        $paramsArray['listingid'] = $recordid;
        $paramsArray['estableid'] = $tableid;
        $paramsArray['establename'] = $tablename;

        return $paramsArray;
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
        //$cid = ArrayHelper::toInteger($cid);

        //Get Edit model
        $paramsArray = $this->getRecordParams($tableid, $tablename, 0);

        $_params = new JRegistry;
        $_params->loadArray($paramsArray);

        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'edititem.php');
        $editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);

        $ct = new CT($_params, false);

        $editModel->load($ct);

        foreach ($cid as $id) {
            if ($id != '') {
                $ok = $editModel->deleteSingleRecord($id);
                if (!$ok)
                    break;
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listofrecords&tableid=' . (int)$tableid;

        $msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED';

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

    public function ordering()
    {
        $ct = new CT;

        $tableid = $ct->Env->jinput->getInt('tableid');
        $ct->getTable($tableid);

        if ($ct->Table->tablename == '') {
            header("HTTP/1.1 500 Internal Server Error");
            die('Table not selected.');
        }

        $ordering = new CustomTables\Ordering($ct->Table);

        if (!$ordering->saveorder()) {
            header("HTTP/1.1 500 Internal Server Error");
            die('Something went wrong.');
        }
    }
}
