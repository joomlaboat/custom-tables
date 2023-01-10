<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\DataTypes\Tree;
use ESTables;
use JoomlaBasicMisc;
use LayoutProcessor;
use Joomla\CMS\Factory;
use JHTML;

if (defined('_JEXEC'))
    JHTML::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');

class Filtering
{
    var CT $ct;
    var array $PathValue;
    var array $where;
    var int $showPublished;

    function __construct(CT $ct, int $showPublished = 0)
    {
        $this->ct = $ct;
        $this->PathValue = [];
        $this->where = [];
        $this->showPublished = $showPublished;

        if ($this->ct->Table->published_field_found) {
            //$showPublished = 0 - show published
            //$showPublished = 1 - show unpublished
            //$shoPublished = 2 - show any

            if ($this->showPublished == 1)
                $this->where[] = $this->ct->Table->realtablename . '.published=0';
            elseif ($this->showPublished != 2)
                $this->where[] = $this->ct->Table->realtablename . '.published=1';
        }
    }

    function addQueryWhereFilter(): void
    {
        if ($this->ct->Env->jinput->get('where', '', 'BASE64')) {
            $decodedURL = $this->ct->Env->jinput->get('where', '', 'BASE64');
            $decodedURL = urldecode($decodedURL);
            $decodedURL = str_replace(' ', '+', $decodedURL);
            $filter_string = $this->sanitizeAndParseFilter(base64_decode($decodedURL));

            if ($filter_string != '')
                $this->addWhereExpression($filter_string);
        }
    }

    function sanitizeAndParseFilter($paramWhere, $parse = false): string
    {
        if ($parse) {
            //Parse using layout, has no effect to layout itself
            if ($this->ct->Env->legacysupport) {

                require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');

                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $paramWhere;
                $paramWhere = $LayoutProc->fillLayout();
            }

            $twig = new TwigProcessor($this->ct, $paramWhere);
            $paramWhere = $twig->process();

            if ($this->ct->Params->allowContentPlugins)
                $paramWhere = JoomlaBasicMisc::applyContentPlugins($paramWhere);
        }

        $paramWhere = str_ireplace('*', '=', $paramWhere);
        $paramWhere = str_ireplace('\\', '', $paramWhere);
        $paramWhere = str_ireplace('drop ', '', $paramWhere);
        $paramWhere = str_ireplace('select ', '', $paramWhere);
        $paramWhere = str_ireplace('delete ', '', $paramWhere);
        $paramWhere = str_ireplace('update ', '', $paramWhere);
        $paramWhere = str_ireplace('grant ', '', $paramWhere);
        return str_ireplace('insert ', '', $paramWhere);
    }

    function addWhereExpression(?string $param): void
    {
        if ($param === null or $param == '')
            return;

        $param = $this->sanitizeAndParseFilter($param, true);

        $wheres = [];

        $items = $this->ExplodeSmartParams($param);

        $logic_operator = '';

        foreach ($items as $item) {
            $logic_operator = $item[0];
            $comparison_operator_str = $item[1];
            $comparison_operator = '';
            $multi_field_where = [];

            if ($logic_operator == 'or' or $logic_operator == 'and') {
                if (!(!str_contains($comparison_operator_str, '<=')))
                    $comparison_operator = '<=';
                elseif (!(!str_contains($comparison_operator_str, '>=')))
                    $comparison_operator = '>=';
                elseif (str_contains($comparison_operator_str, '!=='))
                    $comparison_operator = '!==';
                elseif (!(!str_contains($comparison_operator_str, '!=')))
                    $comparison_operator = '!=';
                elseif (str_contains($comparison_operator_str, '=='))
                    $comparison_operator = '==';
                elseif (str_contains($comparison_operator_str, '='))
                    $comparison_operator = '=';
                elseif (!(!str_contains($comparison_operator_str, '<')))
                    $comparison_operator = '<';
                elseif (!(!str_contains($comparison_operator_str, '>')))
                    $comparison_operator = '>';

                if ($comparison_operator != '') {
                    $whr = JoomlaBasicMisc::csv_explode($comparison_operator, $comparison_operator_str, '"', false);

                    if (count($whr) == 2) {
                        $fieldNamesString = trim(preg_replace("/[^a-zA-Z\d,:\-_;]/", "", trim($whr[0])));

                        $fieldNames = explode(';', $fieldNamesString);
                        $value = trim($whr[1]);

                        if ($this->ct->Env->legacysupport) {

                            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'
                                . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'layout.php');
                            $LayoutProc = new LayoutProcessor($this->ct);
                            $LayoutProc->layout = $value;
                            $value = $LayoutProc->fillLayout();
                        }

                        $twig = new TwigProcessor($this->ct, $value);
                        $value = $twig->process();

                        foreach ($fieldNames as $fieldname_) {
                            $fieldname_parts = explode(':', $fieldname_);
                            $fieldname = $fieldname_parts[0];
                            $field_extra_param = '';
                            if (isset($fieldname_parts[1]))
                                $field_extra_param = $fieldname_parts[1];

                            if ($fieldname == '_id') {
                                $fieldrow = array(
                                    'fieldname' => '_id',
                                    'type' => '_id',
                                    'typeparams' => '',
                                    'realfieldname' => $this->ct->Table->realidfieldname,
                                );
                            } elseif ($fieldname == '_published') {
                                $fieldrow = array(
                                    'fieldname' => '_published',
                                    'type' => '_published',
                                    'typeparams' => '',
                                    'realfieldname' => 'published'
                                );
                            } else {
                                $fieldrow = Fields::FieldRowByName($fieldname, $this->ct->Table->fields);
                            }

                            if (!is_null($fieldrow)) {
                                $w = $this->processSingleFieldWhereSyntax($fieldrow, $comparison_operator, $fieldname, $value, $field_extra_param);
                                if ($w != '')
                                    $multi_field_where[] = $w;
                            }
                        }
                    }
                }
            }

            if (count($multi_field_where) == 1)
                $wheres[] = implode(' OR ', $multi_field_where);
            elseif (count($multi_field_where) > 1)
                $wheres[] = '(' . implode(' OR ', $multi_field_where) . ')';
        }

        if ($logic_operator == '') {
            $this->ct->app->enqueueMessage(JoomlaBasicMisc::JTextExtended('Search parameter "' . $param . '" is incorrect'), 'error');
            return;
        }

        if (count($wheres) > 0) {
            if ($logic_operator == 'or' and count($wheres) > 1)
                $this->where[] = '(' . implode(' ' . $logic_operator . ' ', $wheres) . ')';
            else
                $this->where[] = implode(' ' . $logic_operator . ' ', $wheres);
        }
    }

    function ExplodeSmartParams($param): array
    {
        $items = array();

        if ($param === null)
            return $items;

        $a = JoomlaBasicMisc::csv_explode(' and ', $param, '"', true);
        foreach ($a as $b) {
            $c = JoomlaBasicMisc::csv_explode(' or ', $b, '"', true);

            if (count($c) == 1)
                $items[] = array('and', $b);
            else {
                foreach ($c as $d)
                    $items[] = array('or', $d);
            }
        }
        return $items;
    }

    function processSingleFieldWhereSyntax($fieldrow, $comparison_operator, $fieldname, $value, $field_extra_param = '')
    {
        $c = '';

        switch ($fieldrow['type']) {
            case '_id':

                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $vList = explode(',', $value);
                $cArr = array();
                foreach ($vList as $vL) {
                    $cArr[] = 'id' . $comparison_operator . (int)$vL;

                    $this->PathValue[] = 'ID ' . $comparison_operator . ' ' . (int)$vL;
                }
                if (count($cArr) == 1)
                    $c = $cArr[0];
                else
                    $c = '(' . implode(' OR ', $cArr) . ')';

                break;

            case '_published':

                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $c = 'published' . $comparison_operator . (int)$value;
                $this->PathValue[] = 'Published ' . $comparison_operator . ' ' . (int)$value;

                break;

            case 'userid':
            case 'user':
                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $c = $this->Search_User($value, $fieldrow, $comparison_operator, $field_extra_param);
                break;

            case 'usergroup':
                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $c = $this->Search_UserGroup($value, $fieldrow, $comparison_operator);
                break;

            case 'float':
            case 'viewcount':
            case 'image':
            case 'int':
                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $c = $this->Search_Number($value, $fieldrow, $comparison_operator);
                break;

            case 'checkbox':

                $vList = explode(',', $value);
                $cArr = array();
                foreach ($vList as $vL) {

                    if ($vL == 'true' or $vL == '1') {
                        $cArr[] = $fieldrow['realfieldname'] . '=1';
                        $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
                    } else {
                        $cArr[] = $fieldrow['realfieldname'] . '=0';

                        $this->PathValue[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT') . ' ' . $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];
                    }
                }
                if (count($cArr) == 1)
                    $c = $cArr[0];
                else
                    $c = '(' . implode(' OR ', $cArr) . ')';

                break;

            case 'range':

                $c = $this->getRangeWhere($fieldrow, $value);
                break;

            case 'email':
            case 'url':
            case 'string':
            case 'phponchange':
            case 'text':
            case 'phponadd':

                $c = $this->Search_String($value, $fieldrow, $comparison_operator);
                break;

            case 'md5':
            case 'alias':

                $c = $this->Search_Alias($value, $fieldrow, $comparison_operator);
                break;

            case 'lastviewtime':
            case 'changetime':
            case 'creationtime':
            case 'date':

                $c = $this->Search_Date($fieldname, $value, $comparison_operator);
                break;

            case 'multilangtext':
            case 'multilangstring':

                $c = $this->Search_String($value, $fieldrow, $comparison_operator, true);
                break;

            case 'customtables':

                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $vList = explode(',', $value);
                $cArr = array();
                foreach ($vList as $vL) {
                    //--------

                    $v = trim($vL);
                    if ($v != '') {

                        //to fix the line
                        if ($v[0] != ',')
                            $v = ',' . $v;

                        if ($v[strlen($v) - 1] != '.')
                            $v .= '.';

                        if ($comparison_operator == '=') {
                            $cArr[] = 'instr(' . $fieldrow['realfieldname'] . ',' . $this->ct->db->quote($v) . ')';

                            $vTitle = Tree::getMultyValueTitles($v, $this->ct->Languages->Postfix, 1, ' - ');
                            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ': ' . implode(',', $vTitle);
                        } elseif ($comparison_operator == '!=') {
                            $cArr[] = '!instr(' . $fieldrow['realfieldname'] . ',' . $this->ct->db->quote($v) . ')';

                            $vTitle = Tree::getMultyValueTitles($v, $this->ct->Languages->Postfix, 1, ' - ');
                            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ': ' . implode(',', $vTitle);
                        }
                    }
                }

                if (count($cArr) == 1)
                    $c = $cArr[0];
                else
                    $c = '(' . implode(' OR ', $cArr) . ')';

                break;

            case 'records':

                $vList = explode(',', $this->getString_vL($value));
                $cArr = array();
                foreach ($vList as $vL) {
                    // Filter Title
                    $typeParamsArray = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams'], '"', false);

                    $filterTitle = '';
                    if (count($typeParamsArray) < 1)
                        $filterTitle .= 'table not specified';

                    if (count($typeParamsArray) < 2)
                        $filterTitle .= 'field or layout not specified';

                    if (count($typeParamsArray) < 3)
                        $filterTitle .= 'selector not specified';

                    $esr_table = $typeParamsArray[0];
                    $esr_table_full = $this->ct->Table->realtablename;
                    $esr_field = $typeParamsArray[1];
                    $esr_selector = $typeParamsArray[2];

                    if (count($typeParamsArray) > 3)
                        $esr_filter = $typeParamsArray[3];
                    else
                        $esr_filter = '';


                    $filterTitle .= JHTML::_('ESRecordsView.render',
                        $vL,
                        $esr_table,
                        $esr_field,
                        $esr_selector,
                        $esr_filter);

                    $opt_title = '';

                    if ($esr_selector == 'multi' or $esr_selector == 'checkbox' or $esr_selector == 'multibox') {
                        if ($comparison_operator == '!=')
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_CONTAINS');
                        elseif ($comparison_operator == '=')
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS');
                        elseif ($comparison_operator == '==')
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IS');
                        elseif ($comparison_operator == '!==')
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ISNOT');
                        else
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOWN_OPERATION');
                    } elseif ($esr_selector == 'radio' or $esr_selector == 'single')
                        $opt_title = ':';


                    $valueNew = $this->getInt_vL($vL);

                    if ($valueNew !== '') {

                        if ($comparison_operator == '!=')
                            $cArr[] = '!instr(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ',' . $this->ct->db->quote(',' . $valueNew . ',') . ')';
                        elseif ($comparison_operator == '!==')
                            $cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '!=' . $this->ct->db->quote(',' . $valueNew . ',');//not exact value
                        elseif ($comparison_operator == '=')
                            $cArr[] = 'instr(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ',' . $this->ct->db->quote(',' . $valueNew . ',') . ')';
                        elseif ($comparison_operator == '==')
                            $cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '=' . $this->ct->db->quote(',' . $valueNew . ',');//exact value
                        else
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOWN_OPERATION');


                        if ($comparison_operator == '!=' or $comparison_operator == '=') {
                            $this->PathValue[] = $fieldrow['fieldtitle'
                                . $this->ct->Languages->Postfix]
                                . ' '
                                . $opt_title
                                . ' '
                                . $filterTitle;
                        }
                    }
                }
                if (count($cArr) == 1)
                    $c = $cArr[0];
                elseif (count($cArr) > 1)
                    $c = '(' . implode(' OR ', $cArr) . ')';


                break;
            case 'sqljoin':

                if ($comparison_operator == '==')
                    $comparison_operator = '=';

                $vList = explode(',', $this->getString_vL($value));
                $cArr = array();

                foreach ($vList as $vL) {

                    // Filter Title
                    $typeParamsArray = explode(',', $fieldrow['typeparams']);
                    $filterTitle = '';
                    if (count($typeParamsArray) < 1)
                        $filterTitle .= 'table not specified';

                    if (count($typeParamsArray) < 2)
                        $filterTitle .= 'field or layout not specified';

                    $esr_table = $typeParamsArray[0];
                    $esr_table_full = $this->ct->Table->realtablename;
                    $esr_field = $typeParamsArray[1];

                    $esr_filter = $typeParamsArray[2] ?? '';

                    $valueNew = $vL;

                    $filterTitle .= JHTML::_('ESSQLJoinView.render',
                        $vL,
                        $esr_table,
                        $esr_field,
                        $esr_filter,
                        $this->ct->Languages->Postfix);

                    if ($valueNew != '') {
                        if ($comparison_operator == '!=') {
                            $opt_title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT');

                            $cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '!=' . $this->ct->db->quote($valueNew);
                            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix]
                                . ' '
                                . $opt_title
                                . ' '
                                . $filterTitle;
                        } elseif ($comparison_operator == '=') {
                            $opt_title = ':';

                            $integerValueNew = $valueNew;
                            if ($integerValueNew == 0 or $integerValueNew == -1) {
                                $cArr[] = '(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . '=0 OR ' . $esr_table_full . '.' . $fieldrow['realfieldname'] . '="" OR '
                                    . $esr_table_full . '.' . $fieldrow['realfieldname'] . ' IS NULL)';
                            } else
                                $cArr[] = $esr_table_full . '.' . $fieldrow['realfieldname'] . '=' . $this->ct->db->quote($valueNew);

                            $this->PathValue[] = $fieldrow['fieldtitle'
                                . $this->ct->Languages->Postfix]
                                . ''
                                . $opt_title
                                . ' '
                                . $filterTitle;
                        }
                    }
                }

                if (count($cArr) == 1)
                    $c = $cArr[0];
                elseif (count($cArr) > 1)
                    $c = '(' . implode(' OR ', $cArr) . ')';

                break;
        }
        return $c;
    }

    function Search_User($value, $fieldrow, $comparison_operator, $field_extra_param = '')
    {
        $v = $this->getString_vL($value);

        $vList = explode(',', $v);
        $cArr = array();

        if ($field_extra_param == 'usergroups') {
            foreach ($vList as $vL) {
                if ($vL != '') {
                    $select1 = '(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1)';
                    $cArr[] = '(SELECT m.group_id FROM #__user_usergroup_map AS m WHERE user_id=' . $fieldrow['realfieldname'] . ' AND '
                        . $select1 . $comparison_operator . $this->ct->db->quote($v) . ')';

                    $filterTitle = JHTML::_('ESUserView.render', $vL);
                    $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
                }
            }
        } else {
            foreach ($vList as $vL) {
                if ($vL != '') {
                    if ((int)$vL == 0 and $comparison_operator == '=')
                        $cArr[] = '(' . $fieldrow['realfieldname'] . '=0 OR ' . $fieldrow['realfieldname'] . ' IS NULL)';
                    else
                        $cArr[] = $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;

                    $filterTitle = JHTML::_('ESUserView.render', $vL);
                    $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
                }
            }
        }

        if (count($cArr) == 0)
            return '';
        elseif (count($cArr) == 1)
            return $cArr[0];
        else
            return '(' . implode(' AND ', $cArr) . ')';
    }

    function getString_vL($vL): string
    {
        if (str_contains($vL, '$get_')) {
            $getPar = str_replace('$get_', '', $vL);
            //$v=$this->ct->app->input->get($getPar,'','STRING');
            $v = (string)preg_replace('/[^A-Z\d_.,-]/i', '', $this->ct->app->input->getString($getPar));
        } else
            $v = $vL;

        $v = str_replace('$', '', $v);
        $v = str_replace('"', '', $v);
        $v = str_replace("'", '', $v);
        $v = str_replace('/', '', $v);
        $v = str_replace('\\', '', $v);
        return str_replace('&', '', $v);
    }

    function Search_UserGroup($value, $fieldrow, $comparison_operator)
    {
        $v = $this->getString_vL($value);

        $vList = explode(',', $v);
        $cArr = array();
        foreach ($vList as $vL) {
            if ($vL != '') {
                $cArr[] = $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;
                $filterTitle = JHTML::_('ESUserGroupView.render', $vL);
                $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . ' ' . $comparison_operator . ' ' . $filterTitle;
            }
        }

        if (count($cArr) == 0)
            return '';
        elseif (count($cArr) == 1)
            return $cArr[0];
        else
            return '(' . implode(' AND ', $cArr) . ')';
    }

    function Search_Number($value, $fieldrow, $comparison_operator)
    {
        if ($comparison_operator == '==')
            $comparison_operator = '=';

        $v = $this->getString_vL($value);

        $vList = explode(',', $v);
        $cArr = array();
        foreach ($vList as $vL) {
            if ($vL != '') {
                $cArr[] = $fieldrow['realfieldname'] . $comparison_operator . (int)$vL;

                $opt_title = ' ' . $comparison_operator;
                if ($comparison_operator == '=')
                    $opt_title = ':';

                $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . (int)$vL;
            }
        }

        if (count($cArr) == 0)
            return '';

        if (count($cArr) == 1)
            return $cArr[0];
        else
            return '(' . implode(' OR ', $cArr) . ')';
    }

    function getRangeWhere($fieldrow, $value): string
    {
        $fieldTitle = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix];

        if ($fieldrow['typeparams'] == 'date')
            $valueArr = explode('-to-', $value);
        else
            $valueArr = explode('-', $value);

        if ($valueArr[0] == '' and $valueArr[1] == '')
            return '';

        $range = explode('_r_', $fieldrow['fieldname']);
        if (count($range) == 1)
            return '';

        $valueTitle = '';
        $rangeWhere = '';

        $from_field = '';
        $to_field = '';
        if (isset($range[0])) {
            $from_field = $range[0];
            if (isset($range[1]) and $range[1] != '')
                $to_field = $range[1];
            else
                $to_field = $from_field;
        }

        if ($from_field == '' and $to_field == '')
            return '';

        if ($fieldrow['typeparams'] == 'date') {
            $v_min = $this->ct->db->quote($valueArr[0]);
            $v_max = $this->ct->db->quote($valueArr[1]);
        } else {
            $v_min = (float)$valueArr[0];
            $v_max = (float)$valueArr[1];
        }

        if ($valueArr[0] != '' and $valueArr[1] != '')
            $rangeWhere = '(es_' . $from_field . '>=' . $v_min . ' AND es_' . $to_field . '<=' . $v_max . ')';
        elseif ($valueArr[0] != '' and $valueArr[1] == '')
            $rangeWhere = '(es_' . $from_field . '>=' . $v_min . ')';
        elseif ($valueArr[1] != '' and $valueArr[0] == '')
            $rangeWhere = '(es_' . $from_field . '<=' . $v_max . ')';

        if ($rangeWhere == '')
            return '';

        if ($valueArr[0] != '')
            $valueTitle .= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FROM') . ' ' . $valueArr[0] . ' ';

        if ($valueArr[1] != '')
            $valueTitle .= JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_TO') . ' ' . $valueArr[1];

        $this->PathValue[] = $fieldTitle . ': ' . $valueTitle;

        return $rangeWhere;
    }

    function Search_String($value, $fieldrow, $comparison_operator, $isMultilingual = false): string
    {
        $realfieldname = $fieldrow['realfieldname'] . ($isMultilingual ? $this->ct->Languages->Postfix : '');

        $v = $this->getString_vL($value);

        if ($comparison_operator == '=' and $v != "") {
            $PathValue = [];

            $vList = explode(',', $v);
            $cArr = array();
            foreach ($vList as $vL) {
                //this method breaks search sentence to words and creates the LIKE where filter
                $new_v_list = array();
                $v_list = explode(' ', $vL);
                foreach ($v_list as $vl) {

                    if ($this->ct->db->serverType == 'postgresql')
                        $new_v_list[] = 'CAST ( ' . $this->ct->db->quoteName($realfieldname) . ' AS text ) LIKE ' . $this->ct->db->quote('%' . $vl . '%');
                    else
                        $new_v_list[] = $this->ct->db->quoteName($realfieldname) . ' LIKE ' . $this->ct->db->quote('%' . $vl . '%');

                    $PathValue[] = $vl;
                }

                if (count($new_v_list) > 1)
                    $cArr[] = '(' . implode(' AND ', $new_v_list) . ')';
                else
                    $cArr[] = implode(' AND ', $new_v_list);
            }

            $opt_title = ':';
            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . implode(', ', $PathValue);

            if (count($cArr) > 1)
                return '(' . implode(' OR ', $cArr) . ')';
            else
                return implode(' OR ', $cArr);


        } else {
            //search exactly what requested
            if ($comparison_operator == '==')
                $comparison_operator = '=';

            if ($v == '' and $comparison_operator == '=')
                $where = '(' . $this->ct->db->quoteName($realfieldname) . ' IS NULL OR ' . $this->ct->db->quoteName($realfieldname) . '=' . $this->ct->db->quote('') . ')';
            elseif ($v == '' and $comparison_operator == '!=')
                $where = '(' . $this->ct->db->quoteName($realfieldname) . ' IS NOT NULL AND ' . $this->ct->db->quoteName($realfieldname) . '!=' . $this->ct->db->quote('') . ')';
            else
                $where = $this->ct->db->quoteName($realfieldname) . $comparison_operator . $this->ct->db->quote($v);

            $opt_title = ' ' . $comparison_operator;
            if ($comparison_operator == '=')
                $opt_title = ':';

            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . ($v == '' ? 'NOT SELECTED' : $v);

            return $where;
        }
    }

    function Search_Alias($value, $fieldrow, $comparison_operator)
    {
        if ($comparison_operator == '==')
            $comparison_operator = '=';

        $v = $this->getString_vL($value);

        $vList = explode(',', $v);
        $cArr = array();
        foreach ($vList as $vL) {
            if ($vL == "null" and $comparison_operator == '=')
                $cArr[] = '(' . $fieldrow['realfieldname'] . '=' . $this->ct->db->quote('') . ' OR ' . $fieldrow['realfieldname'] . ' IS NULL)';
            else
                $cArr[] = $fieldrow['realfieldname'] . $comparison_operator . $this->ct->db->quote($vL);

            $opt_title = ' ' . $comparison_operator;
            if ($comparison_operator == '=')
                $opt_title = ':';

            $this->PathValue[] = $fieldrow['fieldtitle' . $this->ct->Languages->Postfix] . $opt_title . ' ' . $vL;
        }

        if (count($cArr) == 1)
            return $cArr[0];
        else
            return '(' . implode(' AND ', $cArr) . ')';
    }

    function Search_Date($fieldname, $value, $comparison_operator): string
    {
        $fieldrow1 = Fields::FieldRowByName($fieldname, $this->ct->Table->fields);

        if (!is_null($fieldrow1)) {
            $title1 = $fieldrow1['fieldtitle' . $this->ct->Languages->Postfix];
        } else
            $title1 = $fieldname;

        $fieldrow2 = Fields::FieldRowByName($value, $this->ct->Table->fields);

        if (!is_null($fieldrow2))
            $title2 = $fieldrow2['fieldtitle' . $this->ct->Languages->Postfix];
        else
            $title2 = $value;

        //Breadcrumbs
        $this->PathValue[] = $title1 . ' ' . $comparison_operator . ' ' . $title2;

        $value1 = $this->processDateSearchTags($fieldname, $fieldrow1, $this->ct->Table->realtablename);
        $value2 = $this->processDateSearchTags($value, $fieldrow2, $this->ct->Table->realtablename);

        if ($value2 == 'NULL' and $comparison_operator == '=')
            $query = $value1 . ' IS NULL';
        elseif ($value2 == 'NULL' and $comparison_operator == '!=')
            $query = $value1 . ' IS NOT NULL';
        else
            $query = $value1 . ' ' . $comparison_operator . ' ' . $value2;

        return $query;
    }

    function processDateSearchTags($value, $fieldrow, $esr_table_full): string
    {
        $v = str_replace('"', '', $value);
        $v = str_replace("'", '', $v);
        $v = str_replace('/', '', $v);
        $v = str_replace('\\', '', $v);
        $value = str_replace('&', '', $v);

        if ($fieldrow) {
            //field
            $options = explode(':', $value);

            if (isset($options[1]) and $options[1] != '') {
                $option = trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[1]));
                //https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
                return 'DATE_FORMAT(' . $esr_table_full . '.' . $fieldrow['realfieldname'] . ', ' . $this->ct->db->quote($option) . ')';//%m/%d/%Y %H:%i
            } else
                return $esr_table_full . '.' . $fieldrow['realfieldname'];
        } else {
            //value
            if ($value == '{year}')
                return 'year()';

            if ($value == '{month}')
                return 'month()';

            if ($value == '{day}')
                return 'day()';

            if (trim(strtolower($value)) == 'null')
                return 'NULL';

            $options = array();
            $fList = JoomlaBasicMisc::getListToReplace('now', $options, $value, '{}');

            $i = 0;

            foreach ($fList as $fItem) {
                $option = trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[$i]));

                //https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
                if ($option != '')
                    $v = 'DATE_FORMAT(now(), ' . $this->ct->db->quote($option) . ')';//%m/%d/%Y %H:%i
                else
                    $v = 'now()';

                $value = str_replace($fItem, $v, $value);
                $i++;
            }

            if (count($fList) > 0)// or trim(strtolower($value))=="null")
                return $value;
            else

                return $this->ct->db->quote($value);
        }
    }

    function getInt_vL($vL)
    {
        if (str_contains($vL, '$get_')) {
            $getPar = str_replace('$get_', '', $vL);
            $a = $this->ct->Env->jinput->get($getPar, '', 'CMD');
            if ($a == '')
                return '';
            return $this->ct->Env->jinput->getInt($getPar);
        }

        return $vL;
    }

    function getCmd_vL($vL)
    {
        if (str_contains($vL, '$get_')) {
            $getPar = str_replace('$get_', '', $vL);
            return $this->ct->Env->jinput->get($getPar, '', 'CMD');
        }

        return $vL;
    }

}//end class

class LinkJoinFilters
{
    static public function getFilterBox($tableName, $dynamic_filter_fieldname, $control_name, $filterValue, $control_name_postfix = ''): string
    {
        $fieldrow = Fields::getFieldRowByName($dynamic_filter_fieldname, 0, $tableName);

        if ($fieldrow === null)
            return '';

        if ($fieldrow->type == 'sqljoin' or $fieldrow->type == 'records')
            return LinkJoinFilters::getFilterElement_SqlJoin($fieldrow->typeparams, $control_name, $filterValue, $control_name_postfix);

        return '';
    }

    static protected function getFilterElement_SqlJoin($typeParams, $control_name, $filterValue, $control_name_postfix = ''): string
    {
        $db = Factory::getDBO();
        $result = '';
        $pair = JoomlaBasicMisc::csv_explode(',', $typeParams, '"', false);

        $tablename = $pair[0];
        if (isset($pair[1]))
            $field = $pair[1];
        else
            return '<p style="color:white;background-color:red;">sqljoin: field not set</p>';

        $tableRow = ESTables::getTableRowByNameAssoc($tablename);
        if (!is_array($tableRow))
            return '<p style="color:white;background-color:red;">sqljoin: table "' . $tablename . '" not found</p>';

        $fieldrow = Fields::getFieldRowByName($field, $tableRow['id']);
        if (!is_object($fieldrow))
            return '<p style="color:white;background-color:red;">sqljoin: field "' . $field . '" not found</p>';

        $selects = [];
        $selects[] = $tableRow['realtablename'] . '.' . $tableRow['realidfieldname'];

        $where = '';
        if ($tableRow['published_field_found']) {
            $selects[] = $tableRow['realtablename'] . '.published AS listing_published';
            $where = 'WHERE ' . $tableRow['realtablename'] . '.published=1';
        } else {
            $selects[] = '1 AS listing_published';
        }

        $selects[] = $tableRow['realtablename'] . '.' . $fieldrow->realfieldname;

        $query = 'SELECT ' . implode(',', $selects) . ' FROM ' . $tableRow['realtablename'] . ' ' . $where . ' ORDER BY ' . $fieldrow->realfieldname;

        $db->setQuery($query);
        $records = $db->loadAssocList();

        $result .= '
		<script>
			ctTranslates["COM_CUSTOMTABLES_SELECT"] = "- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT') . '";
			ctInputboxRecords_current_value["' . $control_name . '"]="";
		</script>
		';

        $result .= '<select id="' . $control_name . 'SQLJoinLink" onchange="ctInputbox_UpdateSQLJoinLink(\'' . $control_name . '\',\'' . $control_name_postfix . '\')">';
        $result .= '<option value="">- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT') . '</option>';

        foreach ($records as $row) {
            if ($row[$tableRow['realidfieldname']] == $filterValue or str_contains($filterValue, ',' . $row[$tableRow['realidfieldname']] . ','))
                $result .= '<option value="' . $row[$tableRow['realidfieldname']] . '" selected>' . $row[$fieldrow->realfieldname] . '</option>';
            else
                $result .= '<option value="' . $row[$tableRow['realidfieldname']] . '">' . $row[$fieldrow->realfieldname] . '</option>';
        }
        $result .= '</select>
';

        return $result;
    }

}
