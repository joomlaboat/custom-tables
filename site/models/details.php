<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\TwigProcessor;

use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'tables');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

class CustomTablesModelDetails extends JModelLegacy
{
    var CT $ct;
    var string $filter;

    function __construct()
    {
        parent::__construct();
    }

    function load(CT &$ct)
    {
        $this->ct = $ct;

        $this->filter = '';

        if ($this->ct->Params->filter != '' and $this->ct->Params->alias == '') {

            $twig = new TwigProcessor($this->ct, $this->ct->Params->filter);
            $this->filter = $twig->process();
        }

        if (!is_null($this->ct->Params->recordsTable) and !is_null($this->ct->Params->recordsUserIdField) and !is_null($this->ct->Params->recordsField)) {
            if (!$this->checkRecordUserJoin($this->ct->Params->recordsTable, $this->ct->Params->recordsUserIdField, $this->ct->Params->recordsField, $this->ct->Params->listing_id)) {
                //YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE';
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
                return false;
            }
        }

        $this->setId($this->ct->Params->listing_id);

        $this->ct->getTable($this->ct->Params->tableName, $this->ct->Params->userIdField);

        if ($this->ct->Table->tablename == '')
            return;

        if (!is_null($this->ct->Params->alias) and $this->ct->Table->alias_fieldname != '')
            $this->filter = $this->ct->Table->alias_fieldname . '=' . $this->ct->db->quote($this->ct->Params->alias);

        if ($this->filter != '' and $this->ct->Params->alias == '') {
            //Parse using layout
            if ($this->ct->Env->legacysupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $this->filter;
                $this->filter = $LayoutProc->fillLayout(array(), null, '[]', true);
            }

            $twig = new TwigProcessor($this->ct, $this->filter);
            $this->filter = $twig->process();
        }
    }

    //TODO avoid es_
    function checkRecordUserJoin($recordstable, $recordsuseridfield, $recordsfield, $listing_id): bool
    {
        $query = 'SELECT COUNT(*) AS count FROM #__customtables_table_' . $recordstable . ' WHERE es_' . $recordsuseridfield . '='
            . $this->ct->Env->userid . ' AND INSTR(es_' . $recordsfield . ',",' . $listing_id . ',") LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();
        $num_rows = $rows[0]['count'];

        if ($num_rows == 0)
            return false;

        return true;
    }

    function setId($listing_id)
    {
        $this->_id = $listing_id;
        $this->_data = null;
    }

    function & getData()
    {
        $db = Factory::getDBO();

        if ($this->_id == 0) {
            $this->_id = 0;

            if ($this->filter != '') {
                $this->ct->setFilter($this->filter, 2); //2 = Show any - published and unpublished
            } else {
                Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_NOFILTER'), 'error');
                $row = [];
                return $row;//field not found. compatibility trick
            }

            $where = count($this->ct->Filter->where) > 0 ? ' WHERE ' . implode(" AND ", $this->ct->Filter->where) : '';

            $this->ct->Ordering->orderby = $this->ct->Table->realidfieldname . ' DESC';
            if ($this->ct->Table->published_field_found)
                $this->ct->Ordering->orderby .= ',published DESC';

            $query = $this->ct->buildQuery($where);
            $query .= ' LIMIT 1';
        } else {
            //show exact record
            $query = $this->ct->buildQuery('WHERE id=' . $this->_id);
            $query .= ' LIMIT 1';
        }

        $db->setQuery($query);
        $rows = $db->loadAssocList();

        if (count($rows) < 1) {
            $a = array();
            return $a;
        }

        $row = $rows[0];

        //get specific Version
        $version = Factory::getApplication()->input->get('version', 0, 'INT');
        if ($version != 0) {
            //get log field
            $log_field = $this->getTypeFieldName('log');
            if ($log_field != '') {
                $new_row = $this->getVersionData($row, $log_field, $version);
                if (count($new_row) > 0) {
                    $row = $this->makeEmptyRecord($row[$this->ct->Table->realidfieldname], $new_row['listing_published']);

                    //Copy values
                    foreach ($this->ct->Table->fields as $ESField) {
                        if (isset($new_row[$ESField['realfieldname']]))
                            $row[$ESField['realfieldname']] = $new_row[$ESField['realfieldname']];
                    }
                }

                $versioned_data = $this->getVersionData($row, $log_field, $version);
                return $versioned_data;
            }
        }

        return $row;
    }

    function getTypeFieldName($type)
    {
        foreach ($this->ct->Table->fields as $ESField) {
            if ($ESField['type'] == $type)
                return $ESField['realfieldname'];
        }
        return '';
    }

    function getVersionData(&$row, $log_field, $version)
    {
        $creation_time_field = $this->getTypeFieldName('changetime');
        $versions = explode(';', $row[$log_field]);

        if ($version <= count($versions)) {
            if (count($versions) > 1 and $version > 1)
                $data_editor = explode(',', $versions[$version - 2]);
            else
                $data_editor = [''];

            $data_content = explode(',', $versions[$version - 1]); // version 1, 1 - 1 = 0; where 0 is the index

            if ($data_content[3] != '') {
                $obj = json_decode(base64_decode($data_content[3]), true);
                $new_row = $obj[0];
                $new_row['listing_published'] = $row['listing_published'];
                $new_row[$this->ct->Table->realidfieldname] = $row[$this->ct->Table->realidfieldname];
                $new_row[$log_field] = $row[$log_field];


                if ($creation_time_field) {
                    $timestamp = date('Y-m-d H:i:s', (int)$data_editor[0]);
                    $new_row[$creation_time_field] = $timestamp; //time (int)
                }

                return $new_row;
            }

        }
        return array();
    }

    function makeEmptyRecord($listing_id, $published)
    {
        $row = [];
        $row[$this->ct->Table->realidfieldname] = $listing_id;
        $row['listing_published'] = $published;

        foreach ($this->ct->Table->fields as $field)
            $row[$field['realfieldname']] = '';

        return $row;
    }
}
