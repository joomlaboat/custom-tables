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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\CTUser;

/* All tags already implemented using Twig */

class tagProcessor_General
{
    public static function process(CT &$ct, &$pagelayout, &$row): void
    {
        tagProcessor_General::TableInfo($ct, $pagelayout);
        $pagelayout = str_replace('{today}', date('Y-m-d', time()), $pagelayout);

        tagProcessor_General::getDate($pagelayout);
        tagProcessor_General::getUser($ct, $pagelayout, $row);
        tagProcessor_General::userid($ct, $pagelayout);
        tagProcessor_General::Itemid($ct, $pagelayout);
        tagProcessor_General::CurrentURL($ct, $pagelayout);
        tagProcessor_General::ReturnTo($ct, $pagelayout);
        tagProcessor_General::WebsiteRoot($pagelayout);
        tagProcessor_General::getGoBackButton($ct, $pagelayout);

        $Layouts = new Layouts($ct);
        $Layouts->processLayoutTag($pagelayout);
    }

    protected static function TableInfo(CT $ct, &$pagelayout): void
    {
        tagProcessor_General::tableDesc($ct, $pagelayout, 'table');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'tabletitle', 'title');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'description', 'description');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'tabledescription', 'description');
    }

    protected static function tableDesc(CT $ct, &$pagelayout, $tag, $default = ''): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace($tag, $options, $pagelayout, '{}');
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
                $vlu = json_encode(Fields::shortFieldObjects($ct->Table->fields));

            if ($extraopt == 'box') {
                $ct->app->enqueueMessage($vlu, 'notice');//, 'error'
                $pagelayout = str_replace($fItem, '', $pagelayout);
            } else
                $pagelayout = str_replace($fItem, $vlu, $pagelayout);

            $i++;
        }
    }

    protected static function getDate(&$pagelayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('date', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($options[$i] != '')
                $vlu = date($options[$i]);//,$phpdate );
            else
                $vlu = JHTML::date();


            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function getUser(CT &$ct, &$pagelayout, &$row): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('user', $options, $pagelayout, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $opts = JoomlaBasicMisc::csv_explode(',', $options[$i]);

            if (isset($opts[1])) {
                $userid_value = $opts[1];

                tagProcessor_Value::processValues($ct, $row, $userid_value);
                tagProcessor_Item::process($ct, $row, $userid_value, '');
                tagProcessor_General::process($ct, $userid_value, $row);
                tagProcessor_Page::process($ct, $userid_value);
                $userid = (int)$userid_value;
            } else {
                $userid = (int)$ct->Env->userid;
            }

            if ($userid != 0) {
                $user_row = (object)CTUser::GetUserRow($userid);

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
                        $vlu = CTUser::GetUserGroups($userid);
                        break;

                    default:
                        $vlu = '';
                        break;
                }
            } else
                $vlu = '';

            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function userid(CT $ct, &$pagelayout): void
    {
        $currentUserId = (int)$ct->Env->userid;
        if ($currentUserId != 0 and count($ct->Env->user->groups) > 0) {
            $pagelayout = str_replace('{currentusertype}', implode(',', array_keys($ct->Env->user->groups)), $pagelayout);
        } else {
            $pagelayout = str_replace('{currentusertype}', '0', $pagelayout);
        }


        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('currentuserid', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pagelayout = str_replace($fItem, $currentUserId, $pagelayout);
            $i++;
        }
    }

    protected static function Itemid(CT $ct, &$pagelayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('itemid', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($ct->Params->ItemId !== null)
                $vlu = $ct->Params->ItemId;
            else
                $vlu = 0;

            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function CurrentURL(CT $ct, &$pagelayout): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('currenturl', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $optionPair = JoomlaBasicMisc::csv_explode(',', $options[$i]);

            if (isset($optionPair[1]) and $optionPair[1] != '') {
                switch ($optionPair[0]) {
                    case 'string':
                    case '':
                        $value = strip_tags($ct->Env->jinput->getString($optionPair[1], ''));
                        break;
                    case 'int':
                        $value = $ct->Env->jinput->getInt($optionPair[1], 0);
                        break;
                    case 'integer'://legacy
                        $value = $ct->Env->jinput->getInt($optionPair[1], 0);
                        break;
                    case 'uint':
                        $value = $ct->Env->jinput->get($optionPair[1], 0, 'UINT');
                        break;
                    case 'float':
                        $value = $ct->Env->jinput->getFloat($optionPair[1], 0);
                        break;
                    case 'word':
                        $value = $ct->Env->jinput->get($optionPair[1], '', 'WORD');
                        break;
                    case 'alnum':
                        $value = $ct->Env->jinput->get($optionPair[1], '', 'ALNUM');
                        break;
                    case 'cmd':
                        $value = $ct->Env->jinput->getCmd($optionPair[1], '');
                        break;
                    case 'base64decode':
                        $value = strip_tags(base64_decode($ct->Env->jinput->get($optionPair[1], '', 'BASE64')));
                        break;
                    case 'base64encode':
                    case 'base64':
                        $value = base64_encode(strip_tags($ct->Env->jinput->getString($optionPair[1], '')));
                        break;
                    case 'set':
                        if (isset($optionPair[2]))
                            $ct->Env->jinput->set($optionPair[1], $optionPair[2]);
                        else
                            $ct->Env->jinput->set($optionPair[1], '');

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


            $pagelayout = str_replace($fItem, $value, $pagelayout);
            $i++;
        }
    }

    protected static function ReturnTo(CT $ct, &$pagelayout): void
    {
        //Deprecated. Use 	{currenturl:base64} instead
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('returnto', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pagelayout = str_replace($fItem, $ct->Env->encoded_current_url, $pagelayout);
            $i++;
        }
    }

    protected static function WebsiteRoot(&$htmlresult): void
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('websiteroot', $options, $htmlresult, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $option = explode(',', $options[$i]);

            if ($option[0] == 'includehost')
                $WebsiteRoot = JURI::root(false);
            else
                $WebsiteRoot = JURI::root(true);

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

    public static function getGoBackButton(CT $ct, &$layout_code): void
    {
        $returnto = base64_decode($ct->Env->jinput->get('returnto', '', 'BASE64'));

        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('gobackbutton', $options, $layout_code, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $opt = '';

            $title = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_GO_BACK');
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

    protected static function renderGoBackButton($returnto, $title, $opt): string
    {
        if ($returnto == '') {
            $gobackbutton = '';
        } else {
            $gobackbutton = '<a href="' . $returnto . '" class="ct_goback" ' . $opt . '><div>' . $title . '</div></a>';
        }
        return $gobackbutton;
    }
}
