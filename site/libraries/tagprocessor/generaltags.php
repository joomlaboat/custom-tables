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
use CustomTables\Layouts;
use CustomTables\CTUser;
use Joomla\CMS\Factory;

/* All tags already implemented using Twig */

class tagProcessor_General
{
    public static function process(CT &$ct, &$pagelayout, &$row)
    {
        tagProcessor_General::TableInfo($ct, $pagelayout);
        $pagelayout = str_replace('{today}', date('Y-m-d', time()), $pagelayout);

        tagProcessor_General::getDate($pagelayout);
        tagProcessor_General::getUser($ct, $pagelayout, $row);
        tagProcessor_General::userid($pagelayout);
        tagProcessor_General::Itemid($ct, $pagelayout);
        tagProcessor_General::CurrentURL($ct, $pagelayout);
        tagProcessor_General::ReturnTo($ct, $pagelayout);
        tagProcessor_General::WebsiteRoot($pagelayout);
        tagProcessor_General::getGoBackButton($ct, $pagelayout);

        $Layouts = new Layouts($ct);
        $Layouts->processLayoutTag($pagelayout);
    }

    protected static function TableInfo(CT &$ct, &$pagelayout)
    {
        tagProcessor_General::tableDesc($ct, $pagelayout, 'table');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'tabletitle', 'title');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'description', 'description');
        tagProcessor_General::tableDesc($ct, $pagelayout, 'tabledescription', 'description');

    }

    protected static function tableDesc(CT &$ct, &$pagelayout, $tag, $default = '')
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
                Factory::getApplication()->enqueueMessage($vlu, 'notice');//, 'error'
                $pagelayout = str_replace($fItem, '', $pagelayout);
            } else
                $pagelayout = str_replace($fItem, $vlu, $pagelayout);

            $i++;
        }
    }

    protected static function getDate(&$pagelayout)
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('date', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($options[$i] != '')
                $vlu = date($options[$i]);//,$phpdate );
            else
                $vlu = JHTML::date('now');


            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function getUser(CT &$ct, &$pagelayout, &$row)
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('user', $options, $pagelayout, '{}');
        $user = Factory::getUser();
        $i = 0;
        foreach ($fList as $fItem) {
            $opts = JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

            if (isset($opts[1])) {
                $userid_value = $opts[1];

                tagProcessor_Value::processValues($ct, $row, $userid_value, '[]');
                tagProcessor_Item::process($ct, $row, $userid_value, '');
                tagProcessor_General::process($ct, $userid_value, $row);
                tagProcessor_Page::process($ct, $userid_value);
                $userid = (int)$userid_value;
            } else {
                $userid = (int)$user->get('id');
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
                        $vlu = implode(',', array_keys($user->groups));
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

    protected static function userid(&$pagelayout)
    {
        $user = Factory::getUser();
        $currentuserid = (int)$user->get('id');
        if ($currentuserid != 0 and count($user->groups) > 0) {
            $pagelayout = str_replace('{currentusertype}', implode(',', array_keys($user->groups)), $pagelayout);
        } else {
            $pagelayout = str_replace('{currentusertype}', '0', $pagelayout);
        }


        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('currentuserid', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pagelayout = str_replace($fItem, $currentuserid, $pagelayout);
            $i++;
        }
    }

    protected static function Itemid(CT &$ct, &$pagelayout)
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('itemid', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            if ($ct->Env != null and $ct->Env->ItemId != null)
                $vlu = $ct->Env->ItemId;
            else
                $vlu = 0;

            $pagelayout = str_replace($fItem, $vlu, $pagelayout);
            $i++;
        }
    }

    protected static function CurrentURL(CT &$ct, &$pagelayout)
    {
        $jinput = Factory::getApplication()->input;

        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('currenturl', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $optpair = JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);//explode(',',$options[$i]);
            $value = '';

            if (isset($optpair[1]) and $optpair[1] != '') {
                switch ($optpair[0]) {
                    case '':
                        $value = strip_tags($jinput->getString($optpair[1], ''));
                    case 'int':
                        $value = $jinput->getInt($optpair[1], 0);
                        break;
                    case 'integer'://legacy
                        $value = $jinput->getInt($optpair[1], 0);
                        break;
                    case 'uint':
                        $value = $jinput->get($optpair[1], 0, 'UINT');
                        break;
                    case 'float':
                        $value = $jinput->getFloat($optpair[1], 0);
                        break;
                    case 'string':
                        $value = strip_tags($jinput->getString($optpair[1], ''));
                        break;
                    case 'word':
                        $value = $jinput->get($optpair[1], '', 'WORD');
                        break;
                    case 'alnum':
                        $value = $jinput->get($optpair[1], '', 'ALNUM');
                        break;
                    case 'cmd':
                        $value = $jinput->getCmd($optpair[1], '');
                        break;
                    case 'base64decode':
                        $value = strip_tags(base64_decode($jinput->get($optpair[1], '', 'BASE64')));
                        break;
                    case 'base64':
                        $value = base64_encode(strip_tags($jinput->getString($optpair[1], '')));
                        break;
                    case 'base64encode':
                        $value = base64_encode(strip_tags($jinput->getString($optpair[1], '')));
                        break;
                    case 'set':
                        if (isset($optpair[2]))
                            $jinput->set($optpair[1], $optpair[2]);
                        else
                            $jinput->set($optpair[1], '');

                        $value = '';
                        break;
                    default:
                        $value = 'Query unknown output type.';
                        break;
                }
            } else {
                switch ($optpair[0]) {
                    case '':
                        $value = $ct->Env->current_url;
                        break;
                    case 'base64':
                        $value = base64_encode($ct->Env->current_url);
                        break;
                    case 'base64encode':
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

    protected static function ReturnTo(CT &$ct, &$pagelayout)
    {
        //Depricated. Use 	{currenturl:base64} instead
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('returnto', $options, $pagelayout, '{}');

        $i = 0;

        foreach ($fList as $fItem) {
            $pagelayout = str_replace($fItem, $ct->Env->encoded_current_url, $pagelayout);
            $i++;
        }
    }

    protected static function WebsiteRoot(&$htmlresult)
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

            $notrailingslash = false;
            if (isset($option[1]) and $option[1] == 'notrailingslash')
                $notrailingslash = true;

            if ($notrailingslash) {
                $l = strlen($WebsiteRoot);
                if ($WebsiteRoot != '' and $WebsiteRoot[$l - 1] == '/')
                    $WebsiteRoot = substr($WebsiteRoot, 0, $l - 1);//delete trailing slash
            } else {
                if ($WebsiteRoot == '' or $WebsiteRoot[strlen($WebsiteRoot) - 1] != '/') //Root must have slash / in the end
                    $WebsiteRoot .= '/';
            }

            $htmlresult = str_replace($fItem, $WebsiteRoot, $htmlresult);
            $i++;
        }

    }

    public static function getGoBackButton(CT &$ct, &$layout_code)
    {
        $returnto = base64_decode(Factory::getApplication()->input->get('returnto', '', 'BASE64'));

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
                $gobackbutton = '';
            else
                $gobackbutton = tagProcessor_General::renderGoBackButton($returnto, $title, $opt);

            $layout_code = str_replace($fItem, $gobackbutton, $layout_code);
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
