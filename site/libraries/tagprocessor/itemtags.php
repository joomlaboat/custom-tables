<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\RecordToolbar;
use CustomTables\CTUser;
use CustomTables\Twig_Record_Tags;
use Joomla\CMS\Factory;

class tagProcessor_Item
{
    public static function RenderResultLine(CT &$ct, $layoutType, &$twig, &$row)
    {
        if ($ct->Env->print)
            $viewlink = '';
        else {
            $returnto = $ct->Env->current_url . '#a' . $row[$ct->Table->realidfieldname];

            if ($row !== null)
                $ct->Table->record = $row;

            $ct_record = new Twig_Record_Tags($ct);

            $viewlink = $ct_record->link(true, '', $returnto);

            if ($ct->Env->jinput->getCmd('tmpl') != '')
                $viewlink .= '&amp;tmpl=' . $ct->Env->jinput->getCmd('tmpl');
        }

        $layout = '';

        $htmlresult = '';

        $LayoutProc = new LayoutProcessor($ct);

        if ($layoutType == 2) {
            $htmlresult = $twig->process($row);

            require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
            $prefix = 'table_' . $ct->Table->tablename . '_' . $row[$ct->Table->realidfieldname] . '_';
            tagProcessor_Edit::process($ct, $htmlresult, $row, $prefix);//Process edit form layout

            $LayoutProc->layout = $htmlresult;//Temporary replace original layout with processed result
            $htmlresult = $LayoutProc->fillLayout($row, null, '||', false, true);//Process field values
        } else {
            $htmlresult = $twig->process($row);

            $LayoutProc->layout = $htmlresult;//Layout was modified by Twig
            $htmlresult = $LayoutProc->fillLayout($row, $viewlink, '[]', false);
        }

        return $htmlresult;
    }

    public static function process(CT &$ct, &$row, &$htmlresult, $aLink, $add_label = false)
    {
        if (is_null($ct->Table))
            return false;

        if (!is_null($row))
            $ct->Table->record = $row;

        $ct_record = new Twig_Record_Tags($ct);

        tagProcessor_Item::processLink($ct_record, $row, $htmlresult); //Twig version added - original replaced
        tagProcessor_Item::processNoReturnLink($ct_record, $row, $htmlresult); //Twig version added - original replaced
        tagProcessor_Field::process($ct, $htmlresult, $add_label); //Twig version added - original not changed

        if ($ct->Env->advancedtagprocessor)
            tagProcessor_Server::process($htmlresult); //Twig version added - original not changed

        tagProcessor_Shopping::getShoppingCartLink($ct, $htmlresult, $row);

        //Listing ID
        $listing_id = 0;

        if (isset($row) and isset($row[$ct->Table->realidfieldname]))
            $listing_id = (int)$row[$ct->Table->realidfieldname];

        $htmlresult = str_replace('{id}', $listing_id, $htmlresult); //Twig version added - original not changed
        $htmlresult = str_replace('{number}', (isset($row['_number']) ? $row['_number'] : ''), $htmlresult); //Twig version added - original not changed

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::processPublishStatus($row, $htmlresult); //Twig version added - original not changed

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::GetSQLJoin($ct_record, $htmlresult);

        if (isset($row) and isset($row['listing_published']))
            tagProcessor_Item::GetCustomToolBar($ct, $htmlresult, $row);

        CT_FieldTypeTag_ct::ResolveStructure($ct, $htmlresult);
    }//function GetSQLJoin(&$htmlresult)

    protected static function processLink(&$ct_record, &$row, &$pagelayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('link', $options, $pagelayout, '{}', ':', '"');

        $i = 0;

        foreach ($fList as $fItem) {
            $vlu = $ct_record->link(true, $options[$i]);

            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function processNoReturnLink(&$ct_record, &$row, &$pagelayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('linknoreturn', $options, $pagelayout, '{}', ':', '"');

        $i = 0;

        foreach ($fList as $fItem) {
            $vlu = $ct_record->link(false, $options[$i]);

            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function processPublishStatus(&$row, &$htmlresult): void
    {
        $htmlresult = str_replace('{_value:published}', $row['listing_published'] == 1, $htmlresult);

        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('published', $options, $htmlresult, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $vlu = '';
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

    protected static function GetSQLJoin($ct_record, &$htmlresult): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('sqljoin', $options, $htmlresult, '{}');
        if (count($fList) == 0)
            return;

        $db = Factory::getDBO();
        $i = 0;
        foreach ($fList as $fItem) {
            $opts = JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

            if (count($opts) >= 5) //dont even try if less than 5 parameters
            {
                $field2_type = '';
                $order_by_option = '';

                $isOk = true;

                $sj_function = $opts[0];
                $sj_tablename = $opts[1];
                $field1_findwhat = $opts[2];
                $field2_lookwhere = $opts[3];

                $opt4_pair = JoomlaBasicMisc::csv_explode(':', $opts[4], '"', false);
                $FieldName = $opt4_pair[0]; //The field to get value from
                if (isset($opt4_pair[1])) //Custom parameters
                {
                    $field_option = $opt4_pair[1];
                    $value_option_list = explode(',', $field_option);
                } else {
                    $field_option = '';
                    $value_option_list = [];
                }

                $field3_readvalue = $FieldName;

                $additional_where = $opts[5] ?? '';
                $order_by_option = $opts[6] ?? '';

                $vlu = $ct_record->advancedjoin($sj_function, $sj_tablename, $field1_findwhat, $field2_lookwhere, $field3_readvalue, $additional_where, $order_by_option, $value_option_list);

                $htmlresult = str_replace($fItem, $vlu, $htmlresult);
                $i++;
            }//if(count($opts)=5)
        }//foreach($fList as $fItem)
    }

    protected static function GetCustomToolBar(CT &$ct, &$htmlresult, &$row): void
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

        $RecordToolbar = new RecordToolbar($ct, $isEditable, $isPublishable, $isDeletable, $ct->Env->ItemId);

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
