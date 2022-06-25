<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use JoomlaBasicMisc;
use ESTables;

use Joomla\CMS\Router\Route;

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
            $this->ct->app->enqueueMessage('{{ record.id }} - Table not loaded.', 'error');
            return '';
        }

        if (is_null($this->ct->Table->record)) {
            //$this->ct->app->enqueueMessage('{{ record.id }} - Record not loaded.', 'error');
            return '';
        }

        return $this->ct->Table->record[$this->ct->Table->realidfieldname];
    }

    function label($allowSortBy = false)
    {
        $forms = new Forms($this->ct);

        $field = ['type' => '_id', 'fieldname' => '_id', 'title' => '#', 'description' => '', 'isrequired' => false];
        $vlu = $forms->renderFieldLabel((object)$field, $allowSortBy);
        return $vlu;
    }

    function link($add_returnto = false, $menu_item_alias = '', $custom_not_base64_returnto = '')
    {
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

    function published($type, $second_variable = null)
    {
        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ record.published }} - Table not loaded.', 'error');
            return null;
        }

        if (!isset($this->ct->Table->record)) {
            $this->ct->app->enqueueMessage('{{ record.published }} - Record not loaded.', 'error');
            return null;
        }

        if ($type == 'yesno' or $type == '')
            return (int)$this->ct->Table->record['listing_published'] == 1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');
        elseif ($type == 'bool' or $type == 'boolean')
            return ((int)$this->ct->Table->record['listing_published'] ? 'true' : 'false');
        elseif ($type == 'number')
            return (int)$this->ct->Table->record['listing_published'];
        else {
            if ($second_variable !== null)
                return $this->ct->Table->record['listing_published'] == 1 ? $second_variable : '';
            else
                return (int)$this->ct->Table->record['listing_published'];
        }
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
                $typeparams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeparams[0];
                if ($join_table_join_to_table == $this->ct->Table->tablename)
                    return intval($this->advancedjoin('count', $join_table, '_id', $join_table_field['fieldname'], '_id', $filter));
            }
        }

        $this->ct->app->enqueueMessage('{{ record.joincount("' . $join_table . '") }} - Table found but the field that links to this table not found.', 'error');
        return null;
    }

    function advancedjoin($sj_function, $sj_tablename, $field1_findwhat, $field2_lookwhere, $field3_readvalue = '_id', $filter = '',
                          $order_by_option = '', $value_option_list = [])
    {
        if ($sj_tablename == '') return '';

        $tablerow = ESTables::getTableRowByNameAssoc($sj_tablename);

        if (!is_array($tablerow)) return '';

        $field_details = $this->join_getRealFieldName($field1_findwhat, $this->ct->Table->tablerow);
        if ($field_details === null) return '';
        $field1_findwhat_realname = $field_details[0];
        $field1_type = $field_details[1];

        $field_details = $this->join_getRealFieldName($field2_lookwhere, $tablerow);
        if ($field_details === null) return '';
        $field2_lookwhere_realname = $field_details[0];
        $field2_type = $field_details[1];

        $field_details = $this->join_getRealFieldName($field3_readvalue, $tablerow);
        if ($field_details === null) return '';
        $field3_readvalue_realname = $field_details[0];

        $sj_realtablename = $tablerow['realtablename'];
        $sj_realidfieldname = $tablerow['realidfieldname'];
        $additional_where = $this->join_processWhere($filter, $sj_realtablename, $sj_realidfieldname);

        if ($order_by_option != '') {
            $field_details = $this->join_getRealFieldName($order_by_option, $tablerow);
            $order_by_option_realname = $field_details[0] ?? '';
        } else
            $order_by_option_realname = '';


        $query = $this->join_buildQuery($sj_function, $tablerow, $field1_findwhat_realname, $field1_type, $field2_lookwhere_realname,
            $field2_type, $field3_readvalue_realname, $additional_where, $order_by_option_realname);

        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();

        if (count($rows) == 0) {
            $vlu = 'no records found';
        } else {
            $row = $rows[0];

            if ($sj_function == 'smart') {
                //TODO: review smart advanced join

                $vlu = $row['vlu'];

                $temp_ctfields = Fields::getFields($tablerow['id']);

                foreach ($temp_ctfields as $fieldRow) {
                    if ($fieldRow['fieldname'] == $field3_readvalue) {
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

    protected function join_getRealFieldName($fieldname, $tablerow)
    {
        $tableid = $tablerow['id'];

        if ($fieldname == '_id') {
            return [$tablerow['realidfieldname'], '_id'];
        } elseif ($fieldname == '_published') {
            if ($tablerow['published_field_found'])
                return ['published', '_published'];
            else
                $this->ct->app->enqueueMessage('{{ record.join... }} - Table doesn\' have "published" field.', 'error');
        } else {
            $field1_row = Fields::getFieldRowByName($fieldname, $tableid);
            if (is_object($field1_row)) {
                return [$field1_row->realfieldname, $field1_row->type];
            } else
                $this->ct->app->enqueueMessage('{{ record.join... }} - Field "' . $fieldname . '" not found.', 'error');
        }
        return null;
    }

    protected function join_processWhere($additional_where, $sj_realtablename, $sj_realidfieldname)
    {
        if ($additional_where == '')
            return '';

        $w = array();

        $af = explode(' ', $additional_where);
        foreach ($af as $a) {
            $b = strtolower(trim($a));
            if ($b != '') {
                if ($b != 'and' and $b != 'or') {
                    $b = str_replace('$now', 'now()', $b);

                    //read $get_ values
                    $b = $this->join_ApplyQueryGetValue($b, $sj_realtablename, $sj_realidfieldname);

                    $w[] = $b;
                } else
                    $w[] = $b;
            }
        }
        return implode(' ', $w);
    }

    protected function join_ApplyQueryGetValue($str, $sj_realtablename, $sj_realidfieldname): string
    {
        $list = explode('$get_', $str);
        if (count($list) == 2) {
            $q = $list[1];

            $v = $this->ct->Env->jinput->getString($q);
            $v = str_replace('"', '', $v);
            $v = str_replace("'", '', $v);

            if (strpos($v, ',')) {
                $f = $sj_realtablename . '.es_' . str_replace('$get_' . $q, '', $str);
                $values = explode(',', $v);


                $vls = array();
                foreach ($values as $v1) {
                    $vls[] = $f . '"' . $v1 . '"';
                }

                return '(' . implode(' or ', $vls) . ')';
            }

            return $sj_realtablename . '.es_' . str_replace('$get_' . $q, '"' . $v . '"', $str);
        } else {
            if (str_contains($str, '_id'))
                return $sj_realtablename . '.' . str_replace('_id', $sj_realidfieldname, $str);
            elseif (str_contains($str, '_published'))
                return $sj_realtablename . '.' . str_replace('_published', 'published', $str);//TODO replace publish with realpublish field name
        }

        $str = str_replace('!=null', ' IS NOT NULL', $str);
        $str = str_replace('=null', ' IS NULL', $str);

        return $sj_realtablename . '.es_' . $str;
    }

    protected function join_buildQuery($sj_function, $tablerow, $field1_findwhat, $field1_type, $field2_lookwhere, $field2_type, $field3_readvalue, $additional_where, $order_by_option)
    {
        if ($sj_function == 'count')
            $query = 'SELECT count(' . $tablerow['realtablename'] . '.' . $field3_readvalue . ') AS vlu ';
        elseif ($sj_function == 'sum')
            $query = 'SELECT sum(' . $tablerow['realtablename'] . '.' . $field3_readvalue . ') AS vlu ';
        elseif ($sj_function == 'avg')
            $query = 'SELECT avg(' . $tablerow['realtablename'] . '.' . $field3_readvalue . ') AS vlu ';
        elseif ($sj_function == 'min')
            $query = 'SELECT min(' . $tablerow['realtablename'] . '.' . $field3_readvalue . ') AS vlu ';
        elseif ($sj_function == 'max')
            $query = 'SELECT max(' . $tablerow['realtablename'] . '.' . $field3_readvalue . ') AS vlu ';
        else {
            //need to resolve record value if it's "records" type
            $query = 'SELECT ' . $tablerow['realtablename'] . '.' . $field3_readvalue . ' AS vlu '; //value or smart
        }

        $query .= ' FROM ' . $this->ct->Table->realtablename . ' ';

        $sj_tablename = $tablerow['tablename'];

        if ($this->ct->Table->tablename != $sj_tablename) {
            // Join not needed when we are in the same table
            $query .= ' LEFT JOIN ' . $tablerow['realtablename'] . ' ON ';

            if ($field1_type == 'records') {
                if ($field2_type == 'records') {
                    $query .= '1==2'; //todo
                } else {
                    $query .= 'INSTR(' . $this->ct->Table->realtablename . '.' . $field1_findwhat . ',CONCAT(",",' . $tablerow['realtablename'] . '.' . $field2_lookwhere . ',","))';
                }
            } else {
                if ($field2_type == 'records') {
                    $query .= 'INSTR(' . $tablerow['realtablename'] . '.' . $field2_lookwhere
                        . ',  CONCAT(",",' . $this->ct->Table->realtablename . '.' . $field1_findwhat . ',","))';
                } else {
                    $query .= ' ' . $this->ct->Table->realtablename . '.' . $field1_findwhat . ' = '
                        . ' ' . $tablerow['realtablename'] . '.' . $field2_lookwhere;
                }
            }
        }

        $wheres = array();

        if ($this->ct->Table->tablename != $sj_tablename) {
            //don't attach to specific record when it is the same table, example : to find averages
            $wheres[] = $this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'] . '=' . $this->ct->db->quote($this->ct->Table->record[$this->ct->Table->realidfieldname]);
        }// else {
        //$wheres[]='#__customtables_table_'.$sj_tablename.'.published=1';//to join with published record only, preferably set in parameters
        //}

        if ($additional_where != '')
            $wheres[] = '(' . $additional_where . ')';

        if (count($wheres) > 0)
            $query .= ' WHERE ' . implode(' AND ', $wheres);

        if ($order_by_option != '')
            $query .= ' ORDER BY ' . $tablerow['realtablename'] . '.' . $order_by_option;

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
                $typeparams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeparams[0];
                if ($join_table_join_to_table == $this->ct->Table->tablename)
                    return $this->advancedjoin($function, $join_table, '_id', $join_table_field['fieldname'], $value_field, $filter);
            }
        }

        $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $join_table . '") }} - Table found but the field that links to this table not found.', 'error');
        return '';
    }

    /* --------------------------- PROTECTED FUNCTIONS ------------------- */

    function joinmin(string $join_table = '', string $value_field = '', string $filter = '')
    {
        return $this->simple_join('min', $join_table, $value_field, 'record.joinmin', $filter);
    }

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

    function jointable($layoutname = '', $filter = '', $orderby = '', $limit = 0)
    {
        //Example {{ record.tablejoin("InvoicesPage","_published=1","name") }}

        if ($layoutname == '') {
            $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.', 'error');
            return '';
        }

        $layouts = new Layouts($this->ct);

        $pagelayout = $layouts->getLayout($layoutname, false);//It is safier to process layout after rendering the table
        if ($layouts->tableid === null) {
            $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        $join_table_fields = Fields::getFields($layouts->tableid);

        $complete_filter = $filter;

        foreach ($join_table_fields as $join_table_field) {
            if ($join_table_field['type'] == 'sqljoin') {
                $typeparams = JoomlaBasicMisc::csv_explode(',', $join_table_field['typeparams'], '"', false);
                $join_table_join_to_table = $typeparams[0];
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

        if ($tables->loadRecords($layouts->tableid, $complete_filter, $orderby, $limit)) {
            $twig = new TwigProcessor($join_ct, $pagelayout);
            $vlu = $twig->process();

            //return $vlu;
            return $vlu;
        }

        $this->ct->app->enqueueMessage('{{ record.tablejoin("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - LCould not load records.', 'error');
        return '';
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

        if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
            $row = $tables->loadRecord($table, $record_id_or_filter);
            if ($row === null)
                return '';
        } else {
            if ($tables->loadRecords($table, $record_id_or_filter, $orderby, 1)) {
                if (count($join_ct->Records) > 0)
                    $row = $join_ct->Records[0];
                else
                    return '';
            } else
                return '';
        }

        if (Layouts::isLayoutContent($fieldname)) {

            $twig = new TwigProcessor($join_ct, $fieldname);
            return $twig->process($row);

        } else {
            $value_realfieldname = '';
            foreach ($join_table_fields as $join_table_field) {
                if ($join_table_field['fieldname'] == $fieldname) {
                    $value_realfieldname = $join_table_field['realfieldname'];
                    break;
                }
            }

            if (!$value_realfieldname) {
                $this->ct->app->enqueueMessage('{{ ' . $tag . '("' . $table . '","' . $fieldname . '") }} - Value field "' . $fieldname . '" not found.', 'error');
                return '';
            }

            return $row[$value_realfieldname];
        }
    }

    function getrecord($layoutname = '', $record_id_or_filter = '', $orderby = '')
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
        $pagelayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table


        if ($layouts->tableid === null) {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
            $row = $tables->loadRecord($layouts->tableid, $record_id_or_filter);
            if ($row === null)
                return '';
        } else {
            if ($tables->loadRecords($layouts->tableid, $record_id_or_filter, $orderby, 1)) {
                if (count($join_ct->Records) > 0)
                    $row = $join_ct->Records[0];
                else
                    return '';
            } else
                return '';
        }

        $twig = new TwigProcessor($join_ct, $pagelayout);
        $vlu = $twig->process($row);

        return $vlu;
    }

    function getrecords($layoutname = '', $filter = '', $orderby = '', $limit = 0)
    {
        //Example {{ html.records("InvoicesPage","firstname=john","lastname") }}

        if ($layoutname == '') {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.', 'error');
            return '';
        }

        $join_ct = new CT;
        $tables = new Tables($join_ct);
        $layouts = new Layouts($join_ct);
        $pagelayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
        if ($layouts->tableid === null) {
            $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        if ($tables->loadRecords($layouts->tableid, $filter, $orderby, $limit)) {
            $twig = new TwigProcessor($join_ct, $pagelayout);
            return $twig->process();
        }

        $this->ct->app->enqueueMessage('{{ html.records("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Could not load records.', 'error');
        return '';
    }
}