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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use Joomla\CMS\Factory;

class tagProcessor_If
{
    protected static function processValue(CT &$ct, string $value, ?array &$row): string
    {
        tagProcessor_General::process($ct, $value, $row);
        tagProcessor_Page::process($ct, $value);
        tagProcessor_Item::process($ct, $value, $row, '');
        tagProcessor_Value::processValues($ct, $value, $row, '[]');

        return $value;
    }

    public static function process(CT &$ct, string &$pageLayout, ?array &$row): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('if', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            tagProcessor_If::parseIfStatements($options[$i], $ct, $pageLayout, $row);
            $i++;
        }

        //outdated - obsolete, use Twig if statements instead. Example: {% if record.published == 1 %} ... {% endif %}
        if (!$ct->isRecordNull($row) and isset($row['listing_published'])) {
            //Row Publish Status IF,IFNOT statments
            tagProcessor_If::IFStatment('[_if_published]', '[_endif_published]', $pageLayout, !$row['listing_published'] == 1);
            tagProcessor_If::IFStatment('[_ifnot_published]', '[_endifnot_published]', $pageLayout, $row['listing_published'] == 1);
        } else {
            tagProcessor_If::IFStatment('[_if_published]', '[_endif_published]', $pageLayout, false);
            tagProcessor_If::IFStatment('[_ifnot_published]', '[_endifnot_published]', $pageLayout, true);
        }

        tagProcessor_If::IFUserTypeStatment($pageLayout, $ct->Env->user, $ct->Env->userid);
    }

    protected static function parseIfStatements(string $statement, CT &$ct, string &$htmlresult, ?array &$row): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplaceAdvanced('{if:' . $statement . '}', '{endif}', $options, $htmlresult, '{if:');

        $i = 0;

        foreach ($fList as $fItem) {

            $content = $options[$i];

            $statement_items = tagProcessor_If::ExplodeSmartParams($statement); //"and" and "or" as separators
            $isTrues = array();//false;

            foreach ($statement_items as $item) {
                if ($item[0] == 'or' or $item[0] == 'and') {
                    $equation = $item[1];
                    $opr = tagProcessor_If::getOpr($equation);

                    if ($opr != '') {
                        $pair = JoomlaBasicMisc::csv_explode($opr, $equation, '"', false);//true
                    } else {
                        //this to process bullean values. use example {if:[paid]}<b>Paid</b>{endif} TODO: or {if:paid}<b>Paid</b>{endif}, {if:paid} exuals to {if:[_value:paid]}
                        $opr = '!=';
                        $pair = array($item[1], '0');//boolean
                    }

                    $processed_value1 = tagProcessor_If::processValue($ct, $pair[0], $row);
                    $processed_value2 = tagProcessor_If::processValue($ct, $pair[1], $row);

                    $isTrues[] = [$item[0], tagProcessor_If::doMath($processed_value1, $processed_value2, $opr)];

                }
            }

            $isTrue = tagProcessor_If::doANDORs($isTrues);

            if ($isTrue)
                $htmlresult = str_replace($fItem, $content, $htmlresult);
            else
                $htmlresult = str_replace($fItem, '', $htmlresult);


            $i++;

        }
    }

    public static function ExplodeSmartParams(string $param): array
    {
        $items = array();
        $a = explode(' and ', $param);
        foreach ($a as $b) {
            $c = explode(' or ', $b);
            if (count($c) == 1) {
                $items[] = array('and', $b);
            } else {
                foreach ($c as $d) {
                    $items[] = array('or', $d);
                }
            }
        }
        return $items;
    }

    protected static function getOpr(string $str): string
    {
        $opr = '';

        if (str_contains($str, '<='))
            $opr = '<=';
        elseif (str_contains($str, '>='))
            $opr = '>=';
        elseif (str_contains($str, '!='))
            $opr = '!=';
        elseif (str_contains($str, '=='))
            $opr = '==';
        elseif (str_contains($str, '='))
            $opr = '=';
        elseif (str_contains($str, '<'))
            $opr = '<';
        elseif (str_contains($str, '>'))
            $opr = '>';
        return $opr;
    }

    protected static function doMath(string $value1, string $value2, string $operation): bool
    {
        $value1 = str_replace('"', '', $value1);
        $value2 = str_replace('"', '', $value2);

        if (is_numeric($value1))
            $value1 = (float)$value1;
        elseif (str_contains($value1, ','))
            $value1 = explode(',', $value1);

        if (is_numeric($value2))
            $value2 = (float)$value2;
        elseif (str_contains($value2, ','))
            $value2 = explode(',', $value2);

        if (is_array($value1) and !is_array($value2)) {
            //at least one true
            foreach ($value1 as $val1) {

                if (tagProcessor_If::ifCompare($val1, $value2, $operation))
                    return true;
            }
        } elseif (!is_array($value1) and is_array($value2)) {
            //at least one true
            foreach ($value2 as $val2) {

                if (tagProcessor_If::ifCompare($value1, $val2, $operation))
                    return true;
            }
        } elseif (is_array($value1) and is_array($value2)) {
            //at least one true
            foreach ($value1 as $val1) {
                foreach ($value2 as $val2) {

                    if (tagProcessor_If::ifCompare($val1, $val2, $operation))
                        return true;
                }
            }
        } else
            return tagProcessor_If::ifCompare($value1, $value2, $operation);


        return false;
    }

    protected static function ifCompare(string $value1, string $value2, string $operation): bool
    {
        if ($operation == '>') {
            if ($value1 > $value2)
                return true;
        } elseif ($operation == '<') {
            if ($value1 < $value2)
                return true;
        } elseif ($operation == '=' or $operation == '==') {
            if ($value1 == $value2)
                return true;
        } elseif ($operation == '!=') {
            if ($value1 != $value2)
                return true;
        } elseif ($operation == '>=') {
            if ($value1 >= $value2)
                return true;
        } elseif ($operation == '<=') {
            if ($value1 <= $value2)
                return true;
        }

        return false;
    }//function ExplodeSmartParams($param)

    protected static function doANDORs(array $isTrues): bool
    {

        $true_count = 0;

        foreach ($isTrues as $t) {
            if ($t[0] == 'and') {
                if ($t[1])
                    $true_count++;
            } elseif ($t[0] == 'or') {
                if ($t[1])
                    return true; //if at least one value is true - retrun true
            } else
                return false; //wrong parameter, only "or" and "and" accepted
        }


        if ($true_count == count($isTrues)) //if all true then true
            return true;

        return false;

    }

    //---------------------- old

    public static function IFStatment(string $ifName, string $endIfName, string &$htmlresult, bool $isEmpty)
    {

        if ($isEmpty) {
            while (1) {
                $startIf_ = strpos($htmlresult, $ifName);

                if ($startIf_ === false)
                    break;

                $endif_ = strpos($htmlresult, $endIfName);
                if (!($endif_ === false)) {
                    $p = $endif_ + strlen($endIfName);

                    $htmlresult = substr($htmlresult, 0, $startIf_) . substr($htmlresult, $p);
                }
            }
        } else {
            $htmlresult = str_replace($ifName, '', $htmlresult);
            $htmlresult = str_replace($endIfName, '', $htmlresult);

        }
    }

    public static function IFUserTypeStatment(string &$htmlresult, &$user, $currentUserId)
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('_if_usertype', $options, $htmlresult, '[]');

        if ($currentUserId == 0 or count($user->groups) == 0) {
            foreach ($options as $check_user_type) {
                tagProcessor_If::IFStatment('[_if_usertype:' . $check_user_type . ']', '[_endif_usertype:' . $check_user_type . ']', $htmlresult, true);
                tagProcessor_If::IFStatment('[_ifnot_usertype:' . $check_user_type . ']', '[_endifnot_usertype:' . $check_user_type . ']', $htmlresult, false);
            }
        } else {
            $usertypes = array_keys($user->groups);

            $i = 0;
            foreach ($fList as $fItem) {
                $check_user_type = $options[$i];
                $isEmpty = !in_array($check_user_type, $usertypes);

                tagProcessor_If::IFStatment('[_if_usertype:' . $check_user_type . ']', '[_endif_usertype:' . $check_user_type . ']', $htmlresult, $isEmpty);
                tagProcessor_If::IFStatment('[_ifnot_usertype:' . $check_user_type . ']', '[_endifnot_usertype:' . $check_user_type . ']', $htmlresult, !$isEmpty);

                $i++;
            }
        }
    }
}
