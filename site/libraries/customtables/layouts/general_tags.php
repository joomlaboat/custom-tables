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

use Joomla\Input\Input;
use JoomlaBasicMisc;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use LayoutProcessor;

class Twig_Fields_Tags
{
    var CT $ct;
    var bool $isTwig;

    function __construct(CT &$ct, bool $isTwig = true)
    {
        $this->ct = &$ct;
        $this->isTwig = $isTwig;
    }

    function json(): string
    {
        return json_encode(Fields::shortFieldObjects($this->ct->Table->fields));
    }

    function list($param = 'fieldname'): array
    {
        $available_params = ['fieldname', 'title', 'defaultvalue', 'description', 'isrequired', 'isdisabled', 'type', 'typeparams', 'valuerule', 'valuerulecaption'];

        if (!in_array($param, $available_params)) {
            $this->ct->app->enqueueMessage('{{ fields.array("' . $param . '") }} - Unknown parameter.', 'error');
            return [];
        }

        $fields = Fields::shortFieldObjects($this->ct->Table->fields);
        $list = [];
        foreach ($fields as $field)
            $list[] = $field[$param];

        return $list;
    }

    function count(): int
    {
        return count($this->ct->Table->fields);
    }
}

class Twig_User_Tags
{
    var CT $ct;
    var int $user_id;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
        $this->user_id = (int)$this->ct->Env->userid;
    }

    function name($user_id = 0): string
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return '';

        $userRow = CTUser::GetUserRow($user_id);
        if ($userRow !== null)
            return $userRow['name'];

        return 'user: ' . $user_id . ' not found.';
    }

    function username($user_id = 0): string
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return '';

        $userRow = CTUser::GetUserRow($user_id);
        if ($userRow !== null)
            return $userRow['username'];

        return 'user: ' . $user_id . ' not found.';
    }

    function email($user_id = 0): string
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return '';

        $userRow = CTUser::GetUserRow($user_id);
        if ($userRow !== null)
            return $userRow['email'];

        return 'user: ' . $user_id . ' not found.';
    }

    function id(): int
    {
        if ($this->user_id == 0)
            return 0;

        return $this->user_id;
    }

    function lastvisitdate($user_id = 0): string
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return '';

        $userRow = CTUser::GetUserRow($user_id);
        if ($userRow !== null) {
            if ($userRow['lastvisitDate'] == '0000-00-00 00:00:00')
                return 'Never';
            else
                return $userRow['lastvisitDate'];
        }

        return 'user: ' . $user_id . ' not found.';
    }

    function registerdate($user_id = 0): string
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return '';

        $userRow = CTUser::GetUserRow($user_id);
        if ($userRow !== null) {
            if ($userRow['registerDate'] == '0000-00-00 00:00:00')
                return 'Never';
            else
                return $userRow['registerDate'];
        }

        return 'user: ' . $user_id . ' not found.';
    }

    function usergroups($user_id = 0): array
    {
        if ($user_id == 0)
            $user_id = $this->user_id;

        if ($user_id == 0)
            return [];

        return explode(',', CTUser::GetUserGroups($user_id));
    }
}

class Twig_Url_Tags
{
    var CT $ct;
    var bool $isTwig;
    var Input $jinput;

    function __construct(CT &$ct, $isTwig = true)
    {
        $this->ct = &$ct;
        $this->isTwig = $isTwig;
        $this->jinput = $this->ct->app->input;
    }

    function link(): string
    {
        return $this->ct->Env->current_url;
    }

    function base64(): string
    {
        return $this->ct->Env->encoded_current_url;
    }

    function root(bool $include_host = false, $add_trailing_slash = true): string
    {
        if ($include_host)
            $WebsiteRoot = Uri::root();
        else
            $WebsiteRoot = Uri::root(true);

        if ($add_trailing_slash) {
            if ($WebsiteRoot == '' or $WebsiteRoot[strlen($WebsiteRoot) - 1] != '/') //Root must have a slash character / in the end
                $WebsiteRoot .= '/';
        } else {
            $l = strlen($WebsiteRoot);
            if ($WebsiteRoot != '' and $WebsiteRoot[$l - 1] == '/')
                $WebsiteRoot = substr($WebsiteRoot, 0, $l - 1);//delete trailing slash
        }

        return $WebsiteRoot;
    }

    function getuint($param, $default = 0)
    {
        return $this->jinput->get($param, $default, 'UINT');
    }

    function getfloat($param, $default = 0): float
    {
        return $this->jinput->getFloat($param, $default);
    }

    function getword($param, $default = ''): string
    {
        return $this->jinput->get($param, $default, 'WORD');
    }

    function getalnum($param, $default = ''): string
    {
        return $this->jinput->getCmd($param, $default);
    }

    function getcmd($param, $default = ''): string
    {
        return $this->jinput->getCmd($param, $default);
    }

    function getstringandencode($param, $default = ''): string
    {
        return base64_encode(strip_tags($this->jinput->getString($param, $default)));
    }

    function getstring($param, $default = ''): string
    {
        return $this->jinput->getString($param, $default);
    }

    function getstringanddecode($param, $default = ''): string
    {
        return strip_tags(base64_decode($this->jinput->getString($param, $default)));
    }

    function itemid(): int
    {
        return $this->jinput->getInt('Itemid', 0);
    }

    function getint($param, $default = 0): int
    {
        return $this->jinput->getInt($param, $default);
    }

    function set($option, $param = ''): void
    {
        $this->jinput->set($option, $param);
    }

    function server($param)
    {
        return $_SERVER[$param];
    }

    function format($format, $link_type = 'anchor', $image = '', $imagesize = '', $layoutname = '', $csv_column_separator = ','): string
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return '';
        //$csv_column_separator parameter is only for csv output format

        $link = '';
        /*
                if ($menu_item_alias != '') {
                    $menu_item = JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
                    if ($menu_item != 0) {
                        $menu_item_id = (int)$menu_item['id'];
                        $link = $menu_item['link'];
                        $link .= '&Itemid=' . $menu_item_id;//.'&returnto='.$returnto;
                    }
                } else {*/
        $link = JoomlaBasicMisc::deleteURLQueryOption($this->ct->Env->current_url, 'frmt');
        $link = JoomlaBasicMisc::deleteURLQueryOption($link, 'layout');
        //}

        $link = Route::_($link);

        //check if format supported
        $allowed_formats = ['csv', 'json', 'xml', 'xlsx', 'pdf', 'image'];
        if ($format == '' or !in_array($format, $allowed_formats))
            $format = 'csv';

        $link .= (!str_contains($link, '?') ? '?' : '&') . 'frmt=' . $format . '&clean=1';

        if ($layoutname != '')
            $link .= '&layout=' . $layoutname;

        if ($format == 'csv' and $csv_column_separator != ',')
            $link .= '&sep=' . $csv_column_separator;

        if ($link_type == 'anchor' or $link_type == '') {
            $allowed_sizes = ['16', '32', '48'];
            if ($imagesize == '' or !in_array($imagesize, $allowed_sizes))
                $imagesize = 32;

            if ($format == 'image')
                $format_image = 'jpg';
            else
                $format_image = $format;

            $alt = 'Download ' . strtoupper($format) . ' file';

            if ($image == '') {
                if ($this->ct->Env->toolbaricons != '' and $format == 'csv') {
                    $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-file-csv" data-icon="' . $this->ct->Env->toolbaricons . ' fa-file-csv" title="' . $alt . '"></i>';
                } else {
                    $image = '/components/com_customtables/libraries/customtables/media/images/fileformats/' . $imagesize . 'px/' . $format_image . '.png';
                    $img = '<img src="' . $image . '" alt="' . $alt . '" title="' . $alt . '" width="' . $imagesize . '" height="' . $imagesize . '">';
                }
            } else
                $img = '<img src="' . $image . '" alt="' . $alt . '" title="' . $alt . '" width="' . $imagesize . '" height="' . $imagesize . '">';

            return '<a href="' . $link . '" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank">' . $img . '</a>';

        } elseif ($link_type == '_value' or $link_type == 'linkonly') {
            //link only
            return $link;
        }
        return '';
    }
}

class Twig_Document_Tags
{
    var CT $ct;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
    }

    function setmetakeywords($metakeywords): void
    {
        $this->ct->document->setMetaData('keywords', $metakeywords);
    }

    function setmetadescription($metadescription): void
    {
        $this->ct->document->setMetaData('description', $metadescription);
    }

    function setpagetitle($pageTitle): void
    {
        $this->ct->document->setTitle(JoomlaBasicMisc::JTextExtended($pageTitle));
    }

    function setheadtag($tag): void
    {
        $this->ct->document->addCustomTag($tag);
    }

    function layout($layoutname): string
    {
        if (!isset($this->ct->Table)) {
            $this->ct->app->enqueueMessage('{{ document.layout }} - Table not loaded.', 'error');
            return '';
        }

        $layouts = new Layouts($this->ct);
        $layout = $layouts->getLayout($layoutname);

        if (is_null($layouts->tableid)) {
            $this->ct->app->enqueueMessage('{{ document.layout("' . $layoutname . '") }} - Layout "' . $layoutname . ' not found.', 'error');
            return '';
        }

        if ($layouts->tableid != $this->ct->Table->tableid) {
            $this->ct->app->enqueueMessage('{{ document.layout("' . $layoutname . '") }} - Layout Table ID and Current Table ID do not match.', 'error');
            return '';
        }

        $twig = new TwigProcessor($this->ct, $layout);

        $number = 1;
        $html_result = '';

        if ($layouts->layouttype == 6) {
            if (!is_null($this->ct->Records)) {

                foreach ($this->ct->Records as $row) {
                    $row['_number'] = $number;

                    $html_result_layout = $twig->process($row);

                    if ($this->ct->Env->legacysupport) {
                        $LayoutProc = new LayoutProcessor($this->ct);
                        $LayoutProc->layout = $html_result_layout;
                        $html_result_layout = $LayoutProc->fillLayout($row);
                    }

                    $html_result .= $html_result_layout;

                    $number++;
                }
            }
        } else {
            ///if (!is_null($this->ct->Table->record))
            $html_result = $twig->process($this->ct->Table->record);

            if ($this->ct->Env->legacysupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $html_result;
                $html_result = $LayoutProc->fillLayout($this->ct->Table->record);
            }
        }

        return $html_result;
    }

    function sitename(): string
    {
        return $this->ct->app->get('sitename');
    }

    function languagepostfix(): string
    {
        return $this->ct->Languages->Postfix;
    }
}
