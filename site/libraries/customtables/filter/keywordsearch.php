<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\CT;
use CustomTables\Ordering;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ordering.php');

class CustomTablesKeywordSearch
{
    var CT $ct;
    var $PathValue;
    var $groupby;
    var $esordering;

    function __construct(&$ct)
    {
        $this->ct = $ct;
        $this->PathValue = [];

        $this->groupby = '';
        $this->esordering = '';
    }

    function getRowsByKeywords($keywords, &$record_count, $limit, $limitstart)
    {
        $result_rows = array();
        $listing_ids = array();

        if (!Factory::getApplication()->input->getString('esfieldlist', ''))
            return $result_rows;

        if ($keywords == '')
            return $result_rows;


        $keywords = trim(preg_replace("/[^a-zA-Z\dáéíóúýñÁÉÍÓÚÝÑ [:punct:]]/", "", $keywords));

        $keywords = str_replace('\\', '', $keywords);


        $mod_fieldlist = explode(',', Factory::getApplication()->input->getString('esfieldlist', ''));

        //Strict (all words in a serash must be there)
        $result_rows = $this->getRowsByKeywords_Processor($keywords, $mod_fieldlist, 'AND');


        //At least one word is match
        if (count($result_rows) == 0)
            $result_rows = $this->getRowsByKeywords_Processor($keywords, $mod_fieldlist, 'OR');


        $record_count = count($result_rows);


        //Process Limit
        $result_rows = $this->processLimit($result_rows, $limit, $limitstart);


        return $result_rows;
    }

    function getRowsByKeywords_Processor($keywords, $mod_fieldlist, $AndOrOr)
    {
        $keyword_arr = explode(' ', $keywords);
        $count = 0;

        $result_rows = array();
        $listing_ids = array();

        if ($AndOrOr == 'OR')
            $AndOrOr_text = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR');

        if ($AndOrOr == 'AND')
            $AndOrOr_text = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AND');


        foreach ($mod_fieldlist as $mod_field) {
            $where = '';
            $inner = '';
            $f = trim($mod_field);
            $fieldrow = ESTables::FieldRowByName($f, $this->ct->Table->fields);//2011.6.1

            //exact match
            $fields = array();
            if (isset($fieldrow['type']) and isset($fieldrow['fieldname']))
                $where = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'], '[[:<:]]' . $keywords . '[[:>:]]', $inner, $this->ct->Languages->Postfix);

            if ($where != '')
                $this->getKeywordSearch($inner, $where, $result_rows, $count, $listing_ids);

            $this->PathValue[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS') . ' "' . $keywords . '"';

            if (count($keyword_arr) > 1) //Do not search because there is only one keyword, and it's already checked
            {
                $where = '';
                $inner = '';

                $where_arr = array();
                $inner_arr = array();

                $kw_text_array = array();
                foreach ($keyword_arr as $kw) {
                    $inner = '';
                    $w = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'], '[[:<:]]' . $kw . '[[:>:]]', $inner);
                    if ($w != '') {
                        $where_arr[] = $w;
                        if (!in_array($inner, $inner_arr)) {
                            $inner_arr[] = $inner;
                            $kw_text_array[] = $kw;
                        }
                    }//if($w!='')
                }

                $where = implode(' ' . $AndOrOr . ' ', $where_arr);
                $inner = implode(' ', $inner_arr);

                if ($where != '')
                    $this->getKeywordSearch($inner, $where, $result_rows, $count, $listing_ids);

                $this->PathValue[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
            }

            $where = '';
            $inner = '';
            $where_arr = array();
            $inner_arr = array();

            $kw_text_array = array();
            foreach ($keyword_arr as $kw) {
                $inner = '';

                if (isset($fieldrow['type']) and isset($fieldrow['fieldname']))
                    $w = $this->getRowsByKeywords_ProcessTypes($fieldrow['type'], $fieldrow['fieldname'], $fieldrow['typeparams'], '[[:<:]]' . $kw, $inner);
                else
                    $w = '';

                if ($w != '') {
                    $where_arr[] = $w;
                    if (!in_array($inner, $inner_arr)) {
                        $inner_arr[] = $inner;
                        $kw_text_array[] = $kw;
                    }
                }
            }

            $where = implode(' ' . $AndOrOr . ' ', $where_arr);
            $inner = implode(' ', $inner_arr);

            $where = str_replace('\\', '', $where);

            if ($where != '')
                $this->getKeywordSearch($inner, $where, $result_rows, $count, $listing_ids);

            $this->PathValue[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
        }

        // -------------------
        foreach ($mod_fieldlist as $mod_field) {
            if (isset($fieldrow['fieldtitle' . $this->ct->Languages->Postfix]))
                $fields[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];

            $where = '';
            $f = trim($mod_field);
            $fieldrow = ESTables::FieldRowByName($f, $this->ct->Table->fields);//2011.6.1

            //any
            $keyword_arr = explode(' ', $keywords);
            $where = '';
            $inner = '';
            $inner_arr = array();
            $where_arr = array();
            $fieldTypeFound = false;

            $kw_text_array = array();

            foreach ($keyword_arr as $kw) {
                $kw_text_array[] = $kw;
                $t = '';
                if (isset($fieldrow['type']))
                    $t = $fieldrow['type'];

                switch ($t) {
                    case 'email':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'url':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'string':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'phponadd':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'phponchange':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'text':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'multilangstring':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . $this->ct->Languages->Postfix . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'multilangtext':
                        $where_arr[] = ' INSTR(es_' . $fieldrow['fieldname'] . $this->ct->Languages->Postfix . ', "' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'records':
                        $typeParamsArrayy = explode(',', $fieldrow['typeparams']);
                        $filtertitle = '';
                        if (count($typeParamsArrayy) < 1)
                            $filtertitle .= 'table not specified';

                        if (count($typeParamsArrayy) < 2)
                            $filtertitle .= 'field or layout not specified';

                        if (count($typeParamsArrayy) < 3)
                            $filtertitle .= 'selector not specified';

                        $esr_table = '#__customtables_table_' . $typeParamsArrayy[0];
                        $esr_field = $typeParamsArrayy[1];

                        $inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'] . ',concat(",",' . $esr_table . '.id,","))';
                        if (!in_array($inner, $inner_arr))
                            $inner_arr[] = $inner;

                        $where_arr[] = 'instr(' . $esr_table . '.es_' . $esr_field . ',"' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'sqljoin':
                        Factory::getApplication()->enqueueMessage('Search box not ready yet.', 'error');

                        $typeParamsArrayy = explode(',', $fieldrow['typeparams']);
                        $filtertitle = '';
                        if (count($typeParamsArrayy) < 1)
                            $filtertitle .= 'table not specified';

                        if (count($typeParamsArrayy) < 2)
                            $filtertitle .= 'field or layout not specified';

                        if (count($typeParamsArrayy) < 3)
                            $filtertitle .= 'selector not specified';

                        $esr_table = '#__customtables_table_' . $typeParamsArrayy[0];
                        $esr_field = $typeParamsArrayy[1];

                        $inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'] . ',concat(",",' . $esr_table . '.id,","))';
                        if (!in_array($inner, $inner_arr))
                            $inner_arr[] = $inner;

                        $where_arr[] = 'instr(' . $esr_table . '.es_' . $esr_field . ',"' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'customtables':
                        $inner = 'INNER JOIN #__customtables_options ON instr(#__customtables_options.familytreestr, #__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'] . ')';
                        if (!in_array($inner, $inner_arr))
                            $inner_arr[] = $inner;

                        $where_arr[] = 'instr(#__customtables_options.title' . $this->ct->Languages->Postfix . ',"' . $kw . '")';
                        $fieldTypeFound = true;
                        break;

                    case 'user':
                        $inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'];
                        if (!in_array($inner, $inner_arr))
                            $inner_arr[] = $inner;

                        $where_arr[] = ' #__users.name REGEXP "' . $kw . '"';
                        $fieldTypeFound = true;
                        break;

                    case 'userid':
                        $inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldrow['fieldname'];
                        if (!in_array($inner, $inner_arr))
                            $inner_arr[] = $inner;

                        $where_arr[] = ' #__users.name REGEXP "' . $kw . '"';
                        $fieldTypeFound = true;
                        break;
                }
            }

            $where = implode(' ' . $AndOrOr . ' ', $where_arr);
            $inner = implode(' ', $inner_arr);
            $where = str_replace('\\', '', $where);

            if ($where != '')
                $this->getKeywordSearch($inner, $where, $result_rows, $count, $listing_ids);

            $this->PathValue[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS') . ' "' . implode('" ' . $AndOrOr_text . ' "', $kw_text_array) . '"';
        }
        return $result_rows;
    }

    function getRowsByKeywords_ProcessTypes($fieldType, $fieldname, $typeParams, $regexpression, &$inner)
    {
        $where = '';
        $inner = '';


        switch ($fieldType) {
            case 'string':

                $where = ' es_' . $fieldname . ' REGEXP "' . $regexpression . '"';

                break;

            case 'phponadd':

                $where = ' es_' . $fieldname . ' REGEXP "' . $regexpression . '"';

                break;

            case 'phponchange':

                $where = ' es_' . $fieldname . ' REGEXP "' . $regexpression . '"';

                break;

            case 'text':

                $where = ' es_' . $fieldname . ' REGEXP "' . $regexpression . '"';
                break;

            case 'multilangstring':

                $where = ' es_' . $fieldname . $this->ct->Languages->Postfix . ' REGEXP "' . $regexpression . '"';
                break;

            case 'multilangtext':

                $where = ' es_' . $fieldname . $this->ct->Languages->Postfix . ' REGEXP "' . $regexpression . '"';
                break;


            case 'records':

                $typeParamsArrayy = explode(',', $typeParams);

                if (count($typeParamsArrayy) < 3)
                    return '';

                $esr_table = '#__customtables_table_' . $typeParamsArrayy[0];
                $esr_field = $typeParamsArrayy[1];

                $inner = 'INNER JOIN ' . $esr_table . ' ON instr(#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname . ',concat(",",' . $esr_table . '.id,","))';
                $where = ' ' . $esr_table . '.es_' . $esr_field . ' REGEXP "' . $regexpression . '"';

                break;

            case 'sqljoin':
                Factory::getApplication()->enqueueMessage('Search box not ready yet.', 'error');
                break;

            case 'customtables':
                $esr_table = '#__customtables_options';
                $inner = 'INNER JOIN ' . $esr_table . ' ON instr(' . $esr_table . '.familytreestr, #__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname . ')';
                $where = ' ' . $esr_table . '.title' . $this->ct->Languages->Postfix . ' REGEXP "' . $regexpression . '"';
                break;

            case 'user':
                $inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname;
                $where = ' #__users.name REGEXP "' . $regexpression . '"';
                break;

            case 'userid':

                $inner = 'INNER JOIN #__users ON #__users.id=#__customtables_table_' . $this->ct->Table->tablename . '.es_' . $fieldname;
                $where = ' #__users.name REGEXP "' . $regexpression . '"';

                break;

        }
        return $where;

    }

    function getKeywordSearch($inner_str, $where, &$result_rows, &$count, &$listing_ids)
    {
        $db = Factory::getDBO();
        $inner = array($inner_str);
        $tablename = '#__customtables_table_' . $this->ct->Table->tablename;
        $query = 'SELECT *, ' . $tablename . '.id AS listing_id, ' . $tablename . '.published As  listing_published ';

        $ordering = array();

        if ($this->groupby != '')
            $ordering[] = $this->ct->Env->field_prefix . $this->groupby;

        if ($this->esordering)
            Ordering::getOrderingQuery($ordering, $query, $inner, $this->esordering, $this->ct->Languages->Postfix, $tablename);

        $query .= ' FROM ' . $tablename . ' ';

        $query .= implode(' ', $inner) . ' ';

        $query .= ' WHERE ' . $where . ' ';

        $query .= ' GROUP BY listing_id ';

        if (count($ordering) > 0)
            $query .= ' ORDER BY ' . implode(',', $ordering);

        $db->setQuery($query);
        $rows = $db->loadAssocList();

        foreach ($rows as $row) {
            if (in_array($row[$this->ct->Table->realidfieldname], $listing_ids))
                $exist = true;
            else
                $exist = false;

            if (!$exist) {
                $result_rows[] = $row;
                $listing_ids[] = $row[$this->ct->Table->realidfieldname];
                $count++;
            }
        }
    }

    function processLimit($result_rows, $limit, $limitstart)
    {
        $result_rows_new = array();
        for ($i = $limitstart; $i < $limitstart + $limit; $i++)
            $result_rows_new[] = $result_rows[$i];

        return $result_rows_new;
    }
}
