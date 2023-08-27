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

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Exception;
use JoomlaBasicMisc;
use ESTables;

use Joomla\CMS\Router\Route;
use LayoutProcessor;

class Twig_Record_Tags
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    function id()
    {
        if (!isset($this->ct->Table)) {
            return '{{ record.id }} - Table not loaded.';
        }

        if (is_null($this->ct->Table->record))
            return '';

        return $this->ct->Table->record[$this->ct->Table->realidfieldname];
    }

    function label($allowSortBy = false)
    {
        $forms = new Forms($this->ct);

        $field = ['type' => '_id', 'fieldname' => '_id', 'title' => '#', 'description' => '', 'isrequired' => false];
        return $forms->renderFieldLabel((object)$field, $allowSortBy);
    }

    function link($add_returnto = false, $menu_item_alias = '', $custom_not_base64_returnto = ''): ?string
    {
        if ($this->ct->Table->record === null)
            return '';

        if (count($this->ct->Table->record) == 0)
            return 'record.link tag cannot be used on empty record.';

        $menu_item_id = 0;
        $view_link = '';

        if ($menu_item_alias != "") {
            $menu_item = JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
            if ($menu_item != 0) {
                $menu_item_id = (int)$menu_item['id'];
                $link = $menu_item['link'];

                if ($link != '')
                    $view_link = JoomlaBasicMisc::deleteURLQueryOption($link, 'view');
            }
        }

        if ($view_link == '')
            $view_link = 'index.php?option=com_customtables&amp;view=details';

        if (!is_null($this->ct->Params->ModuleId))
            $view_link .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

        if ($this->ct->Table->alias_fieldname != '') {
            $alias = $this->ct->Table->record[$this->ct->Env->field_prefix . $this->ct->Table->alias_fieldname] ?? '';
            if ($alias != '')
                $view_link .= '&amp;alias=' . $alias;
            else
                $view_link .= '&amp;listing_id=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];
        } else
            $view_link .= '&amp;listing_id=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];

        $view_link .= '&amp;Itemid=' . ($menu_item_id == 0 ? $this->ct->Params->ItemId : $menu_item_id);
        $view_link .= (is_null($this->ct->Params->ModuleId) ? '' : '&amp;ModuleId=' . $this->ct->Params->ModuleId);

        $view_link = JoomlaBasicMisc::deleteURLQueryOption($view_link, 'returnto');

        if ($add_returnto) {
            if ($custom_not_base64_returnto)
                $returnto = base64_encode($custom_not_base64_returnto);
            else
                $returnto = base64_encode($this->ct->Env->current_url . '#a' . $this->ct->Table->record[$this->ct->Table->realidfieldname]);

            $view_link .= ($returnto != '' ? '&amp;returnto=' . $returnto : '');
        }

        return Route::_($view_link);
    }

    function published(string $type = '', string $customTextPositive = "Published", string $customTextNegative = "Unpublished")
    {
        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.published }} - Table not loaded.', 'error');
            return null;
        }

        if (!isset($this->ct->Table->record)) {
            $this->ct->app->enqueueMessage('{{ record.published }} - Record not loaded.', 'error');
            return null;
        }

        if ($type == 'bool' or $type == 'boolean')
            return ((int)$this->ct->Table->record['listing_published'] ? 'true' : 'false');
        elseif ($type == 'number')
            return (int)$this->ct->Table->record['listing_published'];
        elseif ($type == 'custom')
            return $this->ct->Table->record['listing_published'] == 1 ? $customTextPositive : $customTextNegative;
        else
            return (int)$this->ct->Table->record['listing_published'] == 1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');
    }

    function number(): ?int
    {
        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.number }} - Table not loaded.', 'error');
            return null;
        }

        if (!isset($this->ct->Table->record)) {
            $this->ct->app->enqueueMessage('{{ record.number }} - Record not loaded.', 'error');
            return null;
        }

        if (!isset($this->ct->Table->record['_number'])) {
            $this->ct->app->enqueueMessage('{{ record.number }} - Record number not set.', 'error');
            return null;
        }

        return (int)$this->ct->Table->record['_number'];
    }

    function joincount(string $join_table = '', string $filter = ''): ?int
    {
        if ($join_table == '') {
            $this->ct->app->enqueueMessage('{{ record.joincount("' . $join_table . '") }} - Table not specified.', 'error');
            return null;
        }

        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.joincount("' . $join_table . '") }} - Parent table not loaded.', 'error');
            return null;
        }

        $join_table_fields = Fields::getFields($join_table);

        if (count($join_table_fields) == 0) {
            $this->ct->app->enqueueMessage('{{ record.joincount("' . $join_table . '") }} - Table not found or it has no fields.', 'error');
            return null;
        }

        foreach ($join_table_fields as $join_table_field) {
            if ($join_table_field['type'] == 'sqljoin') {
                $typeParams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeParams[0];
                if ($join_table_join_to_table == $this->ct->Table->tablename)
                    return intval($this->advancedJoin('count', $join_table, '_id', $join_table_field['fieldname'], '_id', $filter));
            }
        }

        $this->ct->app->enqueueMessage('{{ record.joincount("' . $join_table . '") }} - Table found but the field that links to this table not found.', 'error');
        return null;
    }

    function advancedJoin($sj_function, $sj_tablename, $field1_findWhat, $field2_lookWhere, $field3_readValue = '_id', $filter = '',
                          $order_by_option = '', $value_option_list = [])
    {
        if ($sj_tablename === null or $sj_tablename == '') return '';

        $tableRow = ESTables::getTableRowByNameAssoc($sj_tablename);

        if (!is_array($tableRow)) return '';

        $field_details = $this->join_getRealFieldName($field1_findWhat, $this->ct->Table->tablerow);
        if ($field_details === null) return '';
        $field1_findWhat_realName = $field_details[0];
        $field1_type = $field_details[1];

        $field_details = $this->join_getRealFieldName($field2_lookWhere, $tableRow);
        if ($field_details === null) return '';
        $field2_lookWhere_realName = $field_details[0];
        $field2_type = $field_details[1];

        $field_details = $this->join_getRealFieldName($field3_readValue, $tableRow);
        if ($field_details === null) return '';
        $field3_readValue_realName = $field_details[0];

        $newCt = new CT();
        $newCt->setTable($tableRow);
        $f = new Filtering($newCt, 2);
        $f->addWhereExpression($filter);
        $additional_where = implode(' AND ', $f->where);

        if ($order_by_option != '') {
            $field_details = $this->join_getRealFieldName($order_by_option, $tableRow);
            $order_by_option_realName = $field_details[0] ?? '';
        } else
            $order_by_option_realName = '';

        $query = $this->join_buildQuery($sj_function, $tableRow, $field1_findWhat_realName, $field1_type, $field2_lookWhere_realName,
            $field2_type, $field3_readValue_realName, $additional_where, $order_by_option_realName);

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) == 0) {
            $vlu = 'no records found';
        } else {
            $row = $rows[0];

            if ($sj_function == 'smart') {
                //TODO: review smart advanced join
                $vlu = $row['vlu'];
                $tempCTFields = Fields::getFields($tableRow['id']);

                foreach ($tempCTFields as $fieldRow) {
                    if ($fieldRow['fieldname'] == $field3_readValue) {
                        $fieldRow['realfieldname'] = 'vlu';
                        $valueProcessor = new Value($this->ct);
                        $vlu = $valueProcessor->renderValue($fieldRow, $row, $value_option_list);
                        break;
                    }
                }
            } else
                $vlu = $row['vlu'];
        }
        return $vlu;
    }

    protected function join_getRealFieldName($fieldName, $tableRow): ?array
    {
        $tableId = $tableRow['id'];

        if ($fieldName == '_id') {
            return [$tableRow['realidfieldname'], '_id'];
        } elseif ($fieldName == '_published') {
            if ($tableRow['published_field_found'])
                return ['published', '_published'];
            else
                $this->ct->app->enqueueMessage('{{ record.join... }} - Table does not have "published" field.', 'error');
        } else {
            $field1_row = Fields::getFieldRowByName($fieldName, $tableId);

            if (is_object($field1_row)) {
                return [$field1_row->realfieldname, $field1_row->type];
            } else
                $this->ct->app->enqueueMessage('{{ record.join... }} - Field "' . $fieldName . '" not found.', 'error');
        }
        return null;
    }

    protected function join_buildQuery($sj_function, $tableRow, $field1_findWhat, $field1_type, $field2_lookWhere, $field2_type, $field3_readValue, $additional_where, $order_by_option): string
    {
        if ($sj_function == 'count')
            $query = 'SELECT count(' . $tableRow['realtablename'] . '.' . $field3_readValue . ') AS vlu ';
        elseif ($sj_function == 'sum')
            $query = 'SELECT sum(' . $tableRow['realtablename'] . '.' . $field3_readValue . ') AS vlu ';
        elseif ($sj_function == 'avg')
            $query = 'SELECT avg(' . $tableRow['realtablename'] . '.' . $field3_readValue . ') AS vlu ';
        elseif ($sj_function == 'min')
            $query = 'SELECT min(' . $tableRow['realtablename'] . '.' . $field3_readValue . ') AS vlu ';
        elseif ($sj_function == 'max')
            $query = 'SELECT max(' . $tableRow['realtablename'] . '.' . $field3_readValue . ') AS vlu ';
        else {
            //need to resolve record value if it's "records" type
            $query = 'SELECT ' . $tableRow['realtablename'] . '.' . $field3_readValue . ' AS vlu '; //value or smart
        }

        $query .= ' FROM ' . $this->ct->Table->realtablename . ' ';
        $sj_tablename = $tableRow['tablename'];

        if ($this->ct->Table->tablename != $sj_tablename) {
            // Join not needed when we are in the same table
            $query .= ' LEFT JOIN ' . $tableRow['realtablename'] . ' ON ';

            if ($field1_type == 'records') {
                if ($field2_type == 'records') {
                    $query .= '1==2'; //todo
                } else {
                    $query .= 'INSTR(' . $this->ct->Table->realtablename . '.' . $field1_findWhat . ',CONCAT(",",' . $tableRow['realtablename'] . '.' . $field2_lookWhere . ',","))';
                }
            } else {
                if ($field2_type == 'records') {
                    $query .= 'INSTR(' . $tableRow['realtablename'] . '.' . $field2_lookWhere
                        . ',  CONCAT(",",' . $this->ct->Table->realtablename . '.' . $field1_findWhat . ',","))';
                } else {
                    $query .= ' ' . $this->ct->Table->realtablename . '.' . $field1_findWhat . ' = '
                        . ' ' . $tableRow['realtablename'] . '.' . $field2_lookWhere;
                }
            }
        }

        $wheres = array();

        if ($this->ct->Table->tablename != $sj_tablename) {
            //don't attach to specific record when it is the same table, example : to find averages
            $wheres[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=' . $this->ct->db->quote($this->ct->Table->record[$this->ct->Table->realidfieldname]);
        }// else {

        if ($additional_where != '')
            $wheres[] = '(' . $additional_where . ')';

        if (count($wheres) > 0)
            $query .= ' WHERE ' . implode(' AND ', $wheres);

        if ($order_by_option != '')
            $query .= ' ORDER BY ' . $tableRow['realtablename'] . '.' . $order_by_option;

        $query .= ' LIMIT 1';

        return $query;
    }

    function joinavg(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('avg', $join_table, $value_field, 'record.joinavg', $filter);
    }

    protected function simple_join($function, $join_table, $value_field, $tag, string $filter = '')
    {
        if ($join_table == '') {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table not specified.', 'error');
            return '';
        }

        if ($value_field == '') {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Value field not specified.', 'error');
            return '';
        }

        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '() }} - Table not loaded.', 'error');
            return '';
        }

        $join_table_fields = Fields::getFields($join_table);

        if (count($join_table_fields) == 0) {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '",value_field_name) }} - Table "' . $join_table . '" not found or it has no fields.', 'error');
            return '';
        }

        $value_field_found = false;
        foreach ($join_table_fields as $join_table_field) {
            if ($join_table_field['fieldname'] == $value_field) {
                $value_field_found = true;
                break;
            }
        }

        if (!$value_field_found) {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '","' . $value_field . '") }} - Value field "' . $value_field . '" not found.', 'error');
            return '';
        }

        foreach ($join_table_fields as $join_table_field) {
            if ($join_table_field['type'] == 'sqljoin') {
                $typeParams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeParams[0];
                if ($join_table_join_to_table == $this->ct->Table->tablename)
                    return $this->advancedJoin($function, $join_table, '_id', $join_table_field['fieldname'], $value_field, $filter);
            }
        }

        $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '") }} - Table found but the field that links to this table not found.', 'error');
        return '';
    }

    function joinmin(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('min', $join_table, $value_field, 'record.joinmin', $filter);
    }

    /* --------------------------- PROTECTED FUNCTIONS ------------------- */

    function joinmax(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('max', $join_table, $value_field, 'record.joinmax', $filter);
    }

    function joinsum(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('sum', $join_table, $value_field, 'record.joinsum', $filter);
    }

    function joinvalue(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('value', $join_table, $value_field, 'record.joinvalue', $filter);
    }

    function jointable($layoutname = '', $filter = '', $orderby = '', $limit = 0): string
    {
        //Example {{ record.tablejoin("InvoicesPage","_published=1","name") }}

        if ($layoutname == '') {
            $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.', 'error');
            return '';
        }

        $layouts = new Layouts($this->ct);

        $pageLayout = $layouts->getLayout($layoutname, false);//It is safier to process layout after rendering the table
        if ($layouts->tableId === null) {
            $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        $join_table_fields = Fields::getFields($layouts->tableId);

        $complete_filter = $filter;

        foreach ($join_table_fields as $join_table_field) {
            if ($join_table_field['type'] == 'sqljoin') {
                $typeParams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeParams[0];
                if ($join_table_join_to_table == $this->ct->Table->tablename) {
                    $complete_filter = $join_table_field['fieldname'] . '=' . $this->ct->Table->record[$this->ct->Table->realidfieldname];
                    if ($filter != '')
                        $complete_filter .= ' and ' . $filter;
                    break;
                }
            }
        }

        $join_ct = new CT;
        $tables = new Tables($join_ct);

        if ($tables->loadRecords($layouts->tableId, $complete_filter, $orderby, $limit)) {
            $twig = new TwigProcessor($join_ct, $pageLayout);

            $value = $twig->process();
            if ($twig->errorMessage !== null)
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

            return $value;
        }

        $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - LCould not load records.', 'error');
        return '';
    }

    function min(string $tableName = '', string $value_field = '', string $filter = ''): ?int
    {
        return $this->countOrSumRecords('min', $tableName, $value_field, $filter);
    }

    protected function countOrSumRecords(string $function = 'count', string $tableName = '', string $fieldName = '', string $filter = ''): ?int
    {
        if ($tableName == '') {
            $this->ct->app->enqueueMessage('{{ record.count("' . $tableName . '") }} - Table not specified.', 'error');
            return null;
        }

        $tableRow = ESTables::getTableRowByNameAssoc($tableName);
        if (!is_array($tableRow)) {
            $this->ct->app->enqueueMessage('{{ record.count("' . $tableName . '") }} - Table not found.', 'error');
            return null;
        }

        if ($fieldName == '') {
            $this->ct->app->enqueueMessage('{{ record.count("' . $fieldName . '") }} - Field not specified.', 'error');
            return null;
        }

        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.count("' . $tableName . '","' . $fieldName . '","' . $filter . '") }} - Parent table not loaded.', 'error');
            return null;
        }

        if ($fieldName == '_id') {
            $fieldRealFieldName = $tableRow['realidfieldname'];
        } elseif ($fieldName == '_published') {
            $fieldRealFieldName = $tableRow['published'];
        } else {
            $tableFields = Fields::getFields($tableName);

            if (count($tableFields) == 0) {
                $this->ct->app->enqueueMessage('{{ record.count("' . $tableName . '") }} - Table not found or it has no fields.', 'error');
                return null;
            }

            $field = null;
            foreach ($tableFields as $tableField) {
                if ($tableField['fieldname'] == $fieldName) {
                    $field = new Field($this->ct, $tableField);
                    break;
                }
            }

            if ($field === null) {
                $this->ct->app->enqueueMessage('{{ record.count("' . $tableName . '") }} - Table found but the field that links to this table not found.', 'error');
                return null;
            }
            $fieldRealFieldName = $field->realfieldname;
        }

        $newCt = new CT();
        $newCt->setTable($tableRow);

        $f = new Filtering($newCt, 2);
        $f->addWhereExpression($filter);
        $additional_where = implode(' AND ', $f->where);
        $query = $this->count_buildQuery($function, $tableRow['realtablename'], $fieldRealFieldName, $additional_where);
        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();

        if (count($rows) == 0)
            return 'no records found';
        else
            return $rows[0]['vlu'];
    }

    protected function count_buildQuery($sj_function, $realTableName, $realFieldName, $additional_where): ?string
    {
        if ($sj_function == 'count')
            $query = 'SELECT count(' . $realFieldName . ') AS vlu ';
        elseif ($sj_function == 'sum')
            $query = 'SELECT sum(' . $realFieldName . ') AS vlu ';
        elseif ($sj_function == 'avg')
            $query = 'SELECT avg(' . $realFieldName . ') AS vlu ';
        elseif ($sj_function == 'min')
            $query = 'SELECT min(' . $realFieldName . ') AS vlu ';
        elseif ($sj_function == 'max')
            $query = 'SELECT max(' . $realFieldName . ') AS vlu ';
        else {
            return null;
        }

        $query .= ' FROM ' . $realTableName . ' ';
        $wheres = array();
        if ($additional_where != '')
            $wheres[] = '(' . $additional_where . ')';

        if (count($wheres) > 0)
            $query .= ' WHERE ' . implode(' AND ', $wheres);

        $query .= ' LIMIT 1';
        return $query;
    }

    function max(string $tableName = '', string $value_field = '', string $filter = ''): ?int
    {
        return $this->countOrSumRecords('max', $tableName, $value_field, $filter);
    }

    function avg(string $tableName = '', string $value_field = '', string $filter = ''): ?int
    {
        return $this->countOrSumRecords('avg', $tableName, $value_field, $filter);
    }

    function sum(string $tableName = '', string $value_field = '', string $filter = ''): ?int
    {
        return $this->countOrSumRecords('sum', $tableName, $value_field, $filter);
    }

    function count(string $tableName = '', string $filter = ''): ?int
    {
        return $this->countOrSumRecords('count', $tableName, '_id', $filter);
    }

    function MissingFields($separator = ','): string
    {
        return implode($separator, $this->MissingFieldsList());
    }

    function MissingFieldsList(): array
    {
        if ($this->ct->Table->isRecordNull())
            return [];

        $fieldTitles = [];
        foreach ($this->ct->Table->fields as $field) {
            if ($field['published'] == 1 and $field['isrequired'] == 1 and !Fields::isVirtualField($field)) {
                $value = $this->ct->Table->record[$field['realfieldname']];
                if ($value === null or $value == '') {
                    if (!array_key_exists('fieldtitle' . $this->ct->Languages->Postfix, $field)) {
                        $fieldTitles[] = 'fieldtitle' . $this->ct->Languages->Postfix . ' - not found';
                    } else {
                        $vlu = $field['fieldtitle' . $this->ct->Languages->Postfix];
                        if ($vlu == '')
                            $fieldTitles[] = $field['fieldtitle'];
                        else
                            $fieldTitles[] = $vlu;
                    }
                }
            }
        }
        return $fieldTitles;
    }

}

class Twig_Table_Tags
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    function recordstotal(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return 'Table not selected';

        return $this->ct->getNumberOfRecords();
    }

    function records(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return -1;

        return $this->ct->Table->recordcount;
    }

    function fields(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return -1;

        return count($this->ct->Table->fields);
    }

    function description()
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return 'Table not selected';

        return $this->ct->Table->tablerow['description' . $this->ct->Table->Languages->Postfix];
    }

    function title(): string
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return 'Table not selected';

        return $this->ct->Table->tabletitle;
    }

    function name(): ?string
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return 'Table not selected';

        return $this->ct->Table->tablename;
    }

    function id(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return -1;

        return $this->ct->Table->tableid;
    }

    function recordsperpage(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return -1;

        return $this->ct->Limit;
    }

    function recordpagestart(): int
    {
        if (!isset($this->ct->Table) or $this->ct->Table->fields === null)
            return -1;

        return $this->ct->LimitStart;
    }
}

class Twig_Tables_Tags
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    function getvalue($table = '', $fieldname = '', $record_id_or_filter = '', $orderby = '')
    {
        $tag = 'tables.getvalue';
        if ($table == '') {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $table . '",value_field_name) }} - Table not specified.', 'error');
            return '';
        }

        if ($fieldname == '') {
            $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $table . '",field_name) }} - Value field not specified.', 'error');
            return '';
        }

        $join_table_fields = Fields::getFields($table);

        $join_ct = new CT;
        $tables = new Tables($join_ct);
        $tableRow = ESTables::getTableRowByNameAssoc($table);
        $join_ct->setTable($tableRow);

        if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
            try {
                $row = $tables->loadRecord($table, $record_id_or_filter);
                if ($row === null)
                    return '';
            } catch (Exception $e) {
                $join_ct->app->enqueueMessage($e->getMessage(), 'error');
                return '';
            }
        } else {

            try {
                if ($tables->loadRecords($table, $record_id_or_filter, $orderby, 1)) {
                    if (count($join_ct->Records) > 0)
                        $row = $join_ct->Records[0];
                    else
                        return '';
                } else
                    return '';
            } catch (Exception $e) {
                $join_ct->app->enqueueMessage($e->getMessage(), 'error');
                return '';
            }
        }

        if (Layouts::isLayoutContent($fieldname)) {

            $twig = new TwigProcessor($join_ct, $fieldname);
            $value = $twig->process($row);

            if ($twig->errorMessage !== null)
                $join_ct->app->enqueueMessage($twig->errorMessage, 'error');

            return $value;

        } else {
            $value_realfieldname = '';
            if ($fieldname == '_id')
                $value_realfieldname = $join_ct->Table->realidfieldname;
            elseif ($fieldname == '_published')
                if ($join_ct->Table->published_field_found) {
                    $value_realfieldname = 'published';
                } else {
                    $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $table . '","published") }} - "published" does not exist in the table.', 'error');
                    return '';
                }
            else {
                foreach ($join_table_fields as $join_table_field) {
                    if ($join_table_field['fieldname'] == $fieldname) {
                        $value_realfieldname = $join_table_field['realfieldname'];
                        break;
                    }
                }
            }

            if (!$value_realfieldname) {
                $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $table . '","' . $fieldname . '") }} - Value field "' . $fieldname . '" not found.', 'error');
                return '';
            }

            return $row[$value_realfieldname];
        }
    }

    function getrecord($layoutname = '', $record_id_or_filter = '', $orderby = ''): string
    {
        if ($layoutname == '') {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout name not specified.', 'error');
            return '';
        }

        if ($record_id_or_filter == '') {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Record id or filter not set.', 'error');
            return '';
        }

        $join_ct = new CT;
        $tables = new Tables($join_ct);

        $layouts = new Layouts($join_ct);
        $pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table

        if ($layouts->tableId === null) {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
            $row = $tables->loadRecord($layouts->tableId, $record_id_or_filter);
            if ($row === null)
                return '';
        } else {
            if ($tables->loadRecords($layouts->tableId, $record_id_or_filter, $orderby, 1)) {
                if (count($join_ct->Records) > 0)
                    $row = $join_ct->Records[0];
                else
                    return '';
            } else
                return '';
        }

        $twig = new TwigProcessor($join_ct, $pageLayout);

        $value = $twig->process($row);
        if ($twig->errorMessage !== null)
            $join_ct->app->enqueueMessage($twig->errorMessage, 'error');

        return $value;
    }

    function getrecords($layoutname = '', $filter = '', $orderby = '', $limit = 0): string
    {
        //Example {{ html.records("InvoicesPage","firstname=john","lastname") }}

        if ($layoutname == '') {
            $this->ct->app->enqueueMessage('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.', 'error');
            return '';
        }

        $join_ct = new CT;
        $tables = new Tables($join_ct);
        $layouts = new Layouts($join_ct);
        $pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
        if ($layouts->tableId === null) {
            $this->ct->app->enqueueMessage('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        if ($tables->loadRecords($layouts->tableId, $filter, $orderby, $limit)) {

            if ($join_ct->Env->legacySupport) {
                $LayoutProc = new LayoutProcessor($join_ct);
                $LayoutProc->layout = $pageLayout;
                $pageLayout = $LayoutProc->fillLayout();
            }

            $twig = new TwigProcessor($join_ct, $pageLayout);

            $value = $twig->process();

            if ($twig->errorMessage !== null)
                $join_ct->app->enqueueMessage($twig->errorMessage, 'error');

            return $value;
        }

        $this->ct->app->enqueueMessage('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Could not load records.', 'error');
        return '';
    }
}