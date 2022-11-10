<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\RecordToolbar;
use CustomTables\CTUser;
use CustomTables\Twig_Record_Tags;
use CustomTables\TwigProcessor;

class tagProcessor_Item
{
    public static function RenderResultLine(CT &$ct, int $layoutType, TwigProcessor $twig, ?array &$row): string
    {
        if ($ct->Env->print)
            $viewLink = '';
        else {
            $returnto = $ct->Env->current_url . '#a' . $row[$ct->Table->realidfieldname];

            if ($row !== null)
                $ct->Table->record = $row;

            $ct_record = new Twig_Record_Tags($ct);

            $viewLink = $ct_record->link(true, '', $returnto);

            if ($ct->Env->jinput->getCmd('tmpl') != '')
                $viewLink .= '&amp;tmpl=' . $ct->Env->jinput->getCmd('tmpl');
        }

        $layout = '';
        $htmlresult = '';
        $LayoutProc = new LayoutProcessor($ct);
        $htmlresult = $twig->process($row);

        if ($layoutType == 2) {

            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
            $prefix = 'table_' . $ct->Table->tablename . '_' . $row[$ct->Table->realidfieldname] . '_';
            tagProcessor_Edit::process($ct, $htmlresult, $row, $prefix);//Process edit form layout

            $LayoutProc->layout = $htmlresult;//Temporary replace original layout with processed result
            $htmlresult = $LayoutProc->fillLayout($row, null, '||', false, true);//Process field values
        } else {

            $LayoutProc->layout = $htmlresult;//Layout was modified by Twig
            $htmlresult = $LayoutProc->fillLayout($row, $viewLink);
        }

        return $htmlresult;
    }

    public static function process(CT &$ct, string &$htmlresult, ?array &$row, bool $add_label = false): bool
    {
        if ($ct->Table === null)
            return false;

        if ($row !== null)
            $ct->Table->record = $row;

        $ct_record = new Twig_Record_Tags($ct);

        tagProcessor_Item::processLink($ct_record, $htmlresult); //Twig version added - original replaced
        tagProcessor_Item::processNoReturnLink($ct_record, $htmlresult); //Twig version added - original replaced
        tagProcessor_Field::process($ct, $htmlresult, $add_label); //Twig version added - original not changed

        if ($ct->Env->advancedtagprocessor)
            tagProcessor_Server::process($htmlresult); //Twig version added - original not changed

        tagProcessor_Shopping::getShoppingCartLink($ct, $htmlresult, $row);

        //Listing ID
        $listing_id = 0;

        if (isset($row) and isset($row[$ct->Table->realidfieldname]))
            $listing_id = (int)$row[$ct->Table->realidfieldname];

        $htmlresult = str_replace('{id}', $listing_id, $htmlresult); //Twig version added - original not changed
        $htmlresult = str_replace('{number}', ($row['_number'] ?? ''), $htmlresult); //Twig version added - original not changed

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::processPublishStatus($htmlresult, $row); //Twig version added - original not changed

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::GetSQLJoin($ct_record, $htmlresult);

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::GetCustomToolBar($ct, $htmlresult, $row);

        CT_FieldTypeTag_ct::ResolveStructure($ct, $htmlresult);
        return true;
    }

    protected static function processLink(Twig_Record_Tags $ct_record, string &$pageLayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('link', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $vlu = $ct_record->link(true, $options[$i]);

            $pageLayout = str_replace($fItem, $vlu, $pageLayout);
            $i++;
        }
    }

    protected static function processNoReturnLink(Twig_Record_Tags $ct_record, string &$pageLayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('linknoreturn', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $vlu = $ct_record->link(false, $options[$i]);

            $pageLayout = str_replace($fItem, $vlu, $pageLayout);
            $i++;
        }
    }

    protected static function processPublishStatus(string &$htmlresult, ?array $row): void
    {
        $htmlresult = str_replace('{_value:published}', $row['listing_published'] == 1, $htmlresult);

        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('published', $options, $htmlresult, '{}');

        $i = 0;
        foreach ($fList as $fItem) {

            if ($options[$i] == 'number')
                $vlu = (int)$row['listing_published'];
            elseif ($options[$i] == 'boolean')
                $vlu = $row['listing_published'] == 1 ? 'true' : 'false';
            else
                $vlu = $row['listing_published'] == 1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');

            $htmlresult = str_replace($fItem, $vlu, $htmlresult);
            $i++;
        }
    }

    protected static function GetSQLJoin(Twig_Record_Tags $ct_record, string &$htmlresult): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('sqljoin', $options, $htmlresult, '{}');
        if (count($fList) == 0)
            return;

        $i = 0;
        foreach ($fList as $fItem) {
            $opts = JoomlaBasicMisc::csv_explode(',', $options[$i]);

            if (count($opts) >= 5) //don't even try if less than 5 parameters
            {
                $sj_function = $opts[0];
                $sj_tablename = $opts[1];
                $field1_findWhat = $opts[2];
                $field2_lookWhere = $opts[3];

                $opt4_pair = JoomlaBasicMisc::csv_explode(':', $opts[4]);
                $FieldName = $opt4_pair[0]; //The field to get value from
                if (isset($opt4_pair[1])) //Custom parameters
                {
                    $field_option = $opt4_pair[1];
                    $value_option_list = explode(',', $field_option);
                } else {
                    $value_option_list = [];
                }

                $field3_readValue = $FieldName;
                $additional_where = $opts[5] ?? '';
                $order_by_option = $opts[6] ?? '';

                $vlu = $ct_record->advancedjoin($sj_function, $sj_tablename, $field1_findWhat, $field2_lookWhere, $field3_readValue, $additional_where, $order_by_option, $value_option_list);

                $htmlresult = str_replace($fItem, $vlu, $htmlresult);
                $i++;
            }
        }
    }

    protected static function GetCustomToolBar(CT &$ct, string &$htmlresult, ?array $row): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('toolbar', $options, $htmlresult, '{}');

        if (count($fList) == 0)
            return;

        $edit_userGroup = (int)$ct->Params->editUserGroups;
        $publish_userGroup = (int)$ct->Params->publishUserGroups;
        if ($publish_userGroup == 0)
            $publish_userGroup = $edit_userGroup;

        $delete_userGroup = (int)$ct->Params->deleteUserGroups;
        if ($delete_userGroup == 0)
            $delete_userGroup = $edit_userGroup;

        $isEditable = CTUser::checkIfRecordBelongsToUser($ct, $edit_userGroup);
        $isPublishable = CTUser::checkIfRecordBelongsToUser($ct, $publish_userGroup);
        $isDeletable = CTUser::checkIfRecordBelongsToUser($ct, $delete_userGroup);

        $RecordToolbar = new RecordToolbar($ct, $isEditable, $isPublishable, $isDeletable);

        $i = 0;
        foreach ($fList as $fItem) {
            if ($ct->Env->print == 1) {
                $htmlresult = str_replace($fItem, '', $htmlresult);
            } else {
                $modes = explode(',', $options[$i]);
                if (count($modes) == 0 or $options[$i] == '')
                    $modes = ['edit', 'refresh', 'publish', 'delete'];

                $icons = [];
                foreach ($modes as $mode)
                    $icons[] = $RecordToolbar->render($row, $mode);

                $vlu = implode('', $icons);
                $htmlresult = str_replace($fItem, $vlu, $htmlresult);
            }

            $i++;
        }
    }
}
