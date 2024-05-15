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

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\CTUser;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/* All tags already implemented using Twig */

class tagProcessor_General
{
    public static function process(CT &$ct, string &$pageLayout, ?array &$row): void
    {
        tagProcessor_General::TableInfo($ct, $pageLayout);
        $pageLayout = str_replace('{today}', common::currentDate(), $pageLayout);

        tagProcessor_General::getDate($pageLayout);
        tagProcessor_General::getUser($ct, $pageLayout, $row);
        tagProcessor_General::userid($ct, $pageLayout);
        tagProcessor_General::Itemid($ct, $pageLayout);
        tagProcessor_General::CurrentURL($ct, $pageLayout);
        tagProcessor_General::ReturnTo($ct, $pageLayout);
        tagProcessor_General::WebsiteRoot($pageLayout);
        tagProcessor_General::getGoBackButton($ct, $pageLayout);

        $Layouts = new Layouts($ct);
        $Layouts->processLayoutTag($pageLayout);
    }

    protected static function TableInfo(CT $ct, string &$pageLayout): void
    {
        tagProcessor_General::tableDesc($ct, $pageLayout, 'table');
        tagProcessor_General::tableDesc($ct, $pageLayout, 'tabletitle', 'title');
        tagProcessor_General::tableDesc($ct, $pageLayout, 'description', 'description');
        tagProcessor_General::tableDesc($ct, $pageLayout, 'tabledescription', 'description');
    }

    protected static function tableDesc(CT $ct, string &$pageLayout, string $tag, string $default = ''): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace($tag, $options, $pageLayout, '{}');
        $i = 0;
        foreach ($fList as $fItem) {
            $vlu = '';

            $opts = explode(',', $options[$i]);
            $extraopt = '';
            if ($default == '') {
                $task = $opts[0];

                if (isset($opts[1]))
                    $extraopt = $opts[1];
            } else {
                $extraopt = $opts[0];
                $task = $default;
            }

            if ($task == 'id')
                $vlu = $ct->Table->tablerow['id'];
            elseif ($task == 'title')
                $vlu = $ct->Table->tablerow['tabletitle' . $ct->Languages->Postfix];
            elseif ($task == 'description')
                $vlu = $ct->Table->tablerow['description' . $ct->Languages->Postfix];
            elseif ($task == 'fields')
                $vlu = common::ctJsonEncode(Fields::shortFieldObjects($ct->Table->fields));

            if ($extraopt == 'box') {
                $ct->messages[] = $vlu;
                $pageLayout = str_replace($fItem, '', $pageLayout);
            } else
                $pageLayout = str_replace($fItem, $vlu, $pageLayout);

            $i++;
        }
    }

    protected static function getDate(string &$pageLayout): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('date', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($options[$i] != '')
                $vlu = common::currentDate($options[$i]);
            else
                $vlu = common::currentDate();

            $pageLayout = str_replace($fItem, $vlu, $pageLayout);
            $i++;
        }
    }

    protected static function getUser(CT &$ct, string &$pageLayout, ?array &$row): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('user', $options, $pageLayout, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $opts = CTMiscHelper::csv_explode(',', $options[$i]);

            if (isset($opts[1])) {
                $userid_value = $opts[1];

                tagProcessor_Value::processValues($ct, $userid_value, $row);
                tagProcessor_Item::process($ct, $userid_value, $row, '');
                tagProcessor_General::process($ct, $userid_value, $row);
                tagProcessor_Page::process($ct, $userid_value);
                $userid = (int)$userid_value;
            } else {
                $userid = (int)$ct->Env->user->id;
            }

            if ($userid != 0) {
                $user_row = (object)CTUser::GetUserRow($userid);

                if ($user_row === null) {
                    $vlu = 'user: ' . $userid . ' not found.';
                } else {

                    switch ($opts[0]) {
                        case 'name':
                            $vlu = $user_row->name;
                            break;

                        case 'username':
                            $vlu = $user_row->username;
                            break;

                        case 'email':
                            $vlu = $user_row->email;
                            break;

                        case 'id':
                            $vlu = $userid;
                            break;

                        case 'lastvisitDate':
                            $vlu = $user_row->lastvisitDate;

                            if ($vlu == '0000-00-00 00:00:00')
                                $vlu = 'Never';


                            break;

                        case 'registerDate':
                            $vlu = $user_row->registerDate;

                            if ($vlu == '0000-00-00 00:00:00')
                                $vlu = 'Never';

                            break;

                        case 'usergroupsid':
                            $vlu = implode(',', array_keys($ct->Env->user->groups));
                            break;

                        case 'usergroups':
                            $vlu = implode(',', CTUser::GetUserGroups($userid));
                            break;

                        default:
                            $vlu = '';
                            break;
                    }
                }
            } else
                $vlu = '';

            $pageLayout = str_replace($fItem, $vlu, $pageLayout);
            $i++;
        }
    }

    protected static function userid(CT $ct, string &$pageLayout): void
    {
        $currentUserId = (int)$ct->Env->user->id;
        if ($currentUserId != 0 and count($ct->Env->user->groups) > 0) {
            $pageLayout = str_replace('{currentusertype}', implode(',', array_keys($ct->Env->user->groups)), $pageLayout);
        } else {
            $pageLayout = str_replace('{currentusertype}', '0', $pageLayout);
        }


        $options = array();
        $fList = CTMiscHelper::getListToReplace('currentuserid', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pageLayout = str_replace($fItem, $currentUserId, $pageLayout);
            $i++;
        }
    }

    protected static function Itemid(CT $ct, string &$pageLayout): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('itemid', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($ct->Params->ItemId !== null)
                $vlu = $ct->Params->ItemId;
            else
                $vlu = 0;

            $pageLayout = str_replace($fItem, $vlu, $pageLayout);
            $i++;
        }
    }

    protected static function CurrentURL(CT $ct, string &$pageLayout): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('currenturl', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $optionPair = CTMiscHelper::csv_explode(',', $options[$i]);

            if (isset($optionPair[1]) and $optionPair[1] != '') {
                switch ($optionPair[0]) {
                    case 'string':
                    case '':
                        $value = common::ctStripTags(common::inputGetString($optionPair[1], ''));
                        break;
                    case 'int':
                        $value = common::inputGetInt($optionPair[1], 0);
                        break;
                    case 'integer'://legacy
                        $value = common::inputGetInt($optionPair[1], 0);
                        break;
                    case 'uint':
                        $value = common::inputGet($optionPair[1], 0, 'UINT');
                        break;
                    case 'float':
                        $value = common::inputGetFloat($optionPair[1], 0);
                        break;
                    case 'word':
                        $value = common::inputGet($optionPair[1], '', 'WORD');
                        break;
                    case 'alnum':
                        $value = common::inputGet($optionPair[1], '', 'ALNUM');
                        break;
                    case 'cmd':
                        $value = common::inputGetCmd($optionPair[1], '');
                        break;
                    case 'base64decode':
                        $value = common::ctStripTags(base64_decode(common::inputGet($optionPair[1], '', 'BASE64')));
                        break;
                    case 'base64encode':
                    case 'base64':
                        $value = base64_encode(common::ctStripTags(common::inputGetString($optionPair[1], '')));
                        break;
                    case 'set':
                        if (isset($optionPair[2]))
                            common::inputSet($optionPair[1], $optionPair[2]);
                        else
                            common::inputSet($optionPair[1], '');

                        $value = '';
                        break;
                    default:
                        $value = 'Query unknown output type.';
                        break;
                }
            } else {
                switch ($optionPair[0]) {
                    case '':
                        $value = $ct->Env->current_url;
                        break;
                    case 'base64encode':
                    case 'base64':
                        $value = base64_encode($ct->Env->current_url);
                        break;
                    default:
                        $value = 'Output type not selected.';
                        break;
                }
            }


            $pageLayout = str_replace($fItem, $value, $pageLayout);
            $i++;
        }
    }

    protected static function ReturnTo(CT $ct, string &$pageLayout): void
    {
        //Deprecated. Use 	{currenturl:base64} instead
        $options = array();
        $fList = CTMiscHelper::getListToReplace('returnto', $options, $pageLayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pageLayout = str_replace($fItem, $ct->Env->encoded_current_url, $pageLayout);
            $i++;
        }
    }

    protected static function WebsiteRoot(string &$htmlresult): void
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('websiteroot', $options, $htmlresult, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $option = explode(',', $options[$i]);

            if ($option[0] == 'includehost')
                $WebsiteRoot = Uri::root(false);
            else
                $WebsiteRoot = common::UriRoot(true);

            $noTrailingSlash = false;
            if (isset($option[1]) and $option[1] == 'notrailingslash')
                $noTrailingSlash = true;

            if ($noTrailingSlash) {
                $l = strlen($WebsiteRoot);
                if ($WebsiteRoot != '' and $WebsiteRoot[$l - 1] == '/')
                    $WebsiteRoot = substr($WebsiteRoot, 0, $l - 1);//delete trailing slash
            } else {
                if ($WebsiteRoot == '' or $WebsiteRoot[strlen($WebsiteRoot) - 1] != '/') //Root must have the slash charachter "/" in the end
                    $WebsiteRoot .= '/';
            }

            $htmlresult = str_replace($fItem, $WebsiteRoot, $htmlresult);
            $i++;
        }

    }

    public static function getGoBackButton(CT $ct, string &$layout_code): void
    {
        $returnto = common::getReturnToURL();

        $options = array();
        $fList = CTMiscHelper::getListToReplace('gobackbutton', $options, $layout_code, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $opt = '';

            $title = common::translate('COM_CUSTOMTABLES_GO_BACK');
            $pair = explode(',', $options[$i]);

            if (isset($pair[1]) and $pair[1] != '')
                $opt = $pair[1];
            if (isset($pair[2]) and $pair[2] != '') {
                if ($pair[2] == '-')
                    $title = '';
                else
                    $title = $pair[2];

            }

            if (isset($pair[3]) and $pair[3] != '')
                $returnto = $pair[3];

            if ($ct->Env->print == 1)
                $goBackButton = '';
            else
                $goBackButton = tagProcessor_General::renderGoBackButton($returnto, $title, $opt);

            $layout_code = str_replace($fItem, $goBackButton, $layout_code);
            $i++;
        }
    }

    protected static function renderGoBackButton(string $returnto, string $title, string $opt): string
    {
        if ($returnto == '') {
            $gobackbutton = '';
        } else {
            $gobackbutton = '<a href="' . $returnto . '" class="ct_goback" ' . $opt . '><div>' . $title . '</div></a>';
        }
        return $gobackbutton;
    }
}
