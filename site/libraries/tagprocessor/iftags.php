<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\CT;
use CustomTables\CTMiscHelper;

class tagProcessor_If
{
    /**
     * @throws Exception
     *
     * @since 1.0.0
     */
    protected static function processValue(CT &$ct, string $value, ?array &$row): string
    {
        tagProcessor_General::process($ct, $value, $row);
        tagProcessor_Page::process($ct, $value);
        tagProcessor_Item::process($ct, $value, $row, '');
        tagProcessor_Value::processValues($ct, $value, $row);

        return $value;
    }

    /**
     * @throws Exception
     *
     * @since 1.0.0
     */
    public static function process(CT &$ct, string &$pageLayout, ?array &$row): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('if', $options, $pageLayout, '{}');

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

        tagProcessor_If::IFUserTypeStatment($pageLayout, $ct->Env->user, $ct->Env->user->id);
    }

    /**
     * @throws Exception
     *
     * @since 1.0.0
     */
    protected static function parseIfStatements(string $statement, CT &$ct, string &$htmlResult, ?array &$row): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplaceAdvanced('{if:' . $statement . '}', '{endif}', $options, $htmlResult, '{if:');

        $i = 0;

        foreach ($fList as $fItem) {

            $content = $options[$i];
            $items = CTMiscHelper::ExplodeSmartParamsArray($statement);
            $isTrues = array();//false;

            foreach ($items as $item) {
                $processed_value1 = tagProcessor_If::processValue($ct, $item['field'], $row);
                $processed_value2 = tagProcessor_If::processValue($ct, $item['value'], $row);
                $isTrues[] = [$item['logic'], tagProcessor_If::doMath($processed_value1, $processed_value2, $item['comparison'])];
            }

            $isTrue = tagProcessor_If::doANDORs($isTrues);

            if ($isTrue)
                $htmlResult = str_replace($fItem, $content, $htmlResult);
            else
                $htmlResult = str_replace($fItem, '', $htmlResult);

            $i++;
        }
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
    }

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

    //---------------------- old

    /**
     * @throws Exception
     *
     * @since 1.0.0
     */
    public static function IFUserTypeStatment(string &$htmlresult, $user, $currentUserId)
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('_if_usertype', $options, $htmlresult, '[]');

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
}
