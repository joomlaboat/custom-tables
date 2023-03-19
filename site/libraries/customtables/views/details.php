<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use JoomlaBasicMisc;
use LayoutProcessor;
use tagProcessor_PHP;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class Details
{
    var CT $ct;
    var string $layoutDetailsContent;
    var ?array $row;
    var int $layoutType;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
        $this->layoutType = 0;
    }

    function load($layoutDetailsContent = null): bool
    {
        if (!$this->loadRecord())
            return false;

        if (is_null($layoutDetailsContent)) {
            $this->layoutDetailsContent = '';

            if ($this->ct->Params->detailsLayout != '') {
                $Layouts = new Layouts($this->ct);
                $this->layoutDetailsContent = $Layouts->getLayout($this->ct->Params->detailsLayout);

                if ($Layouts->layouttype === null) {
                    echo 'Layout "' . $this->ct->Params->detailsLayout . '" not found or the type is not set.';
                    return false;
                }

                $this->layoutType = $Layouts->layouttype;
            }
        } else $this->layoutDetailsContent = $layoutDetailsContent;

        $this->ct->LayoutVariables['layout_type'] = $this->layoutType;

        if (!is_null($this->row)) {
            //Save view log
            $this->SaveViewLogForRecord($this->row);
            $this->UpdatePHPOnView();
        }
        return true;
    }

    protected function loadRecord(): bool
    {
        $filter = '';

        if ($this->ct->Params->listing_id === null and $this->ct->Params->filter != '' and $this->ct->Params->alias == '') {

            $twig = new TwigProcessor($this->ct, $this->ct->Params->filter);
            $filter = $twig->process();

            if ($twig->errorMessage !== null)
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');
        }

        if (!is_null($this->ct->Params->recordsTable) and !is_null($this->ct->Params->recordsUserIdField) and !is_null($this->ct->Params->recordsField)) {
            if (!$this->checkRecordUserJoin($this->ct->Params->recordsTable, $this->ct->Params->recordsUserIdField, $this->ct->Params->recordsField, $this->ct->Params->listing_id)) {
                //YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE';
                $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
                return false;
            }
        }

        $this->ct->getTable($this->ct->Params->tableName, $this->ct->Params->userIdField);

        if ($this->ct->Table->tablename === null)
            return false;

        if (!is_null($this->ct->Params->alias) and $this->ct->Table->alias_fieldname != '')
            $filter = $this->ct->Table->alias_fieldname . '=' . $this->ct->db->quote($this->ct->Params->alias);

        if ($filter != '') {
            if ($this->ct->Params->alias == '') {
                //Parse using layout
                if ($this->ct->Env->legacySupport) {

                    require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
                        . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
                    $LayoutProc = new LayoutProcessor($this->ct);
                    $LayoutProc->layout = $filter;
                    $filter = $LayoutProc->fillLayout(null, null, '[]', true);
                }

                $twig = new TwigProcessor($this->ct, $filter);
                $filter = $twig->process();

                if ($twig->errorMessage !== null)
                    $this->ct->app->enqueueMessage($twig->errorMessage, 'error');
            }

            $this->row = $this->getDataByFilter($filter);
        } else
            $this->row = $this->getDataById($this->ct->Params->listing_id);

        return true;
    }

    protected function checkRecordUserJoin($recordsTable, $recordsUserIdField, $recordsField, $listing_id): bool
    {
        //TODO: avoid es_

        $query = 'SELECT COUNT(*) AS count FROM #__customtables_table_' . $recordsTable . ' WHERE es_' . $recordsUserIdField . '='
            . $this->ct->Env->userid . ' AND INSTR(es_' . $recordsField . ',",' . $listing_id . ',") LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();
        $num_rows = $rows[0]['count'];

        if ($num_rows == 0)
            return false;

        return true;
    }

    protected function getDataByFilter($filter)
    {
        if ($filter != '') {
            $this->ct->setFilter($filter, 2); //2 = Show any - published and unpublished
        } else {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_NOFILTER'), 'error');
            return null;
        }

        $where = count($this->ct->Filter->where) > 0 ? ' WHERE ' . implode(" AND ", $this->ct->Filter->where) : '';

        $this->ct->Ordering->orderby = $this->ct->Table->realidfieldname . ' DESC';
        if ($this->ct->Table->published_field_found)
            $this->ct->Ordering->orderby .= ',published DESC';

        $query = $this->ct->buildQuery($where);
        $query .= ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) < 1)
            return null;

        $row = $rows[0];

        return $this->checkForVersionData($row);
    }

    protected function checkForVersionData($row)
    {
        if ($this->ct->Params->blockExternalVars)
            return $row;

        //get specific Version
        $version = $this->ct->Env->jinput->getInt('version', 0);

        if ($version != 0) {
            //get log field
            $log_field = $this->getTypeFieldName('log');
            if ($log_field != '') {
                $new_row = $this->getVersionData($row, $log_field, $version);
                if (count($new_row) > 0) {
                    $row = $this->makeEmptyRecord($row[$this->ct->Table->realidfieldname], $new_row['listing_published']);

                    //Copy values
                    foreach ($this->ct->Table->fields as $fieldRow) {
                        if (isset($new_row[$fieldRow['realfieldname']]))
                            $row[$fieldRow['realfieldname']] = $new_row[$fieldRow['realfieldname']];
                    }
                }

                //TODO: Looks like unnecessary double check.
                return $this->getVersionData($row, $log_field, $version);
            }
        }
        return $row;
    }

    protected function getTypeFieldName($type)
    {
        foreach ($this->ct->Table->fields as $fieldRow) {
            if ($fieldRow['type'] == $type)
                return $fieldRow['realfieldname'];
        }
        return '';
    }

    protected function getVersionData($row, $log_field, $version)
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
        return null;
    }

    protected function makeEmptyRecord($listing_id, $published): ?array
    {
        $row = null;
        $row[$this->ct->Table->realidfieldname] = $listing_id;
        $row['listing_published'] = $published;

        foreach ($this->ct->Table->fields as $field)
            $row[$field['realfieldname']] = '';

        return $row;
    }

    protected function getDataById($listing_id)
    {
        if (is_numeric($listing_id) and intval($listing_id) == 0) {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_NOFILTER'), 'error');
            return null;
        }

        $query = $this->ct->buildQuery('WHERE id=' . $this->ct->db->quote($listing_id));
        $query .= ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) < 1)
            return null;

        $row = $rows[0];

        return $this->checkForVersionData($row);
    }

    protected function SaveViewLogForRecord($rec): void
    {
        $updateFields = array();

        $allowedTypes = ['lastviewtime', 'viewcount'];

        foreach ($this->ct->Table->fields as $mFld) {
            $t = $mFld['type'];
            if (in_array($t, $allowedTypes)) {

                $allow_count = true;
                $author_user_field = $mFld['typeparams'];

                if (!isset($author_user_field) or $author_user_field == '' or $rec[$this->ct->Env->field_prefix . $author_user_field] == $this->ct->Env->userid)
                    $allow_count = false;

                if ($allow_count) {
                    $n = $this->ct->Env->field_prefix . $mFld['fieldname'];
                    if ($t == 'lastviewtime')
                        $updateFields[] = $n . '="' . date('Y-m-d H:i:s') . '"';
                    elseif ($t == 'viewcount')
                        $updateFields[] = $n . '=' . ((int)($rec[$n]) + 1);
                }
            }
        }

        if (count($updateFields) > 0) {
            $query = 'UPDATE #__customtables_table_' . $this->ct->Table->tablename . ' SET ' . implode(', ', $updateFields) . ' WHERE id=' . $rec[$this->ct->Table->realidfieldname];

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
        }
    }

    protected function UpdatePHPOnView(): bool
    {
        if (!isset($row[$this->ct->Table->realidfieldname]))
            return false;

        foreach ($this->ct->Table->fields as $mFld) {
            if ($mFld['type'] == 'phponview') {
                $fieldname = $mFld['fieldname'];
                $type_params = JoomlaBasicMisc::csv_explode(',', $mFld['typeparams']);
                tagProcessor_PHP::processTempValue($this->ct, $this->row, $fieldname, $type_params);
            }
        }
        return true;
    }

    public function render()
    {
        $layoutDetailsContent = $this->layoutDetailsContent;

        if ($this->ct->Env->legacySupport) {

            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
                . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $layoutDetailsContent;
            $layoutDetailsContent = $LayoutProc->fillLayout($this->row);

        }

        $twig = new TwigProcessor($this->ct, $layoutDetailsContent);
        $layoutDetailsContent = $twig->process($this->row);

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

        if ($this->ct->Params->allowContentPlugins)
            $layoutDetailsContent = JoomlaBasicMisc::applyContentPlugins($layoutDetailsContent);

        return $layoutDetailsContent;
    }
}
