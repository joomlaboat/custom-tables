<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Catalog;
use CustomTables\Inputbox;
use CustomTables\Layouts;
use CustomTables\ViewJSON;

class CustomTablesViewCatalog extends JViewLegacy
{
    var CT $ct;
    var string $listing_id;
    var Catalog $catalog;
    var string $catalogTableCode;

    function display($tpl = null)
    {
        $this->ct = new CT(null, false);

        $key = $this->ct->Env->jinput->getCmd('key');
        if ($key != '')
            Inputbox::renderTableJoinSelectorJSON($this->ct, $key);
        else
            $this->renderCatalog($tpl);
    }

    function renderCatalog($tpl): bool
    {
        $this->catalog = new Catalog($this->ct);

        if ($this->ct->Env->frmt == 'csv') {
            if (function_exists('mb_convert_encoding')) {
                require_once('tmpl' . DIRECTORY_SEPARATOR . 'csv.php');
            } else {
                $msg = '"mbstring" PHP extension not installed.<br/>
				You need to install this extension. It depends on of your operating system, here are some examples:<br/><br/>
				sudo apt-get install php-mbstring  # Debian, Ubuntu<br/>
				sudo yum install php-mbstring  # RedHat, Fedora, CentOS<br/><br/>
				Uncomment the following line in php.ini, and restart the Apache server:<br/>
				extension=mbstring<br/><br/>
				Then restart your webs\' server. Example:<br/>service apache2 restart';

                $this->ct->app->appenqueueMessage($msg, 'error');
            }
        } elseif ($this->ct->Env->frmt == 'json') {

            // --------------------- Layouts
            $Layouts = new Layouts($this->ct);
            $Layouts->layouttype = 0;

            $pageLayoutContent = '';

            if ($this->ct->Params->pageLayout != null) {
                $pageLayoutContent = $Layouts->getLayout($this->ct->Params->pageLayout);
                if ($pageLayoutContent == '')
                    $pageLayoutContent = '{catalog:,notable}';
            } else
                $pageLayoutContent = '{catalog:,notable}';

            if ($this->ct->Params->itemLayout != null)
                $itemLayoutContent = $Layouts->getLayout($this->ct->Params->itemLayout);
            else
                $itemLayoutContent = '';

            $pathViews = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
                . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

            require_once($pathViews . 'json.php');

            $jsonOutput = new ViewJSON($ct);
            $jsonOutput->render($pageLayoutContent, $itemLayoutContent, $Layouts->layouttype);

        } else {
            parent::display($tpl);
        }

        //Save view log
        $allowed_fields = $this->SaveViewLog_CheckIfNeeded();
        if (count($allowed_fields) > 0 and $this->ct->Records !== null) {
            foreach ($this->ct->Records as $rec)
                $this->SaveViewLogForRecord($rec, $allowed_fields);
        }

        return true;
    }

    function SaveViewLog_CheckIfNeeded(): array
    {
        $user_groups = $this->ct->Env->user->get('groups');
        $allowed_fields = array();

        foreach ($this->ct->Table->fields as $mFld) {
            if ($mFld['type'] == 'lastviewtime' or $mFld['type'] == 'viewcount' or $mFld['type'] == 'phponview') {
                $pair = explode(',', $mFld['typeparams']);
                $user_group = '';

                if (isset($pair[1])) {
                    if ($pair[1] == 'catalog')
                        $user_group = $pair[0];
                } else
                    $user_group = $pair[0];

                $group_id = JoomlaBasicMisc::getGroupIdByTitle($user_group);

                if ($user_group != '') {
                    if (in_array($group_id, $user_groups))
                        $allowed_fields[] = $mFld['fieldname'];
                }
            }
        }
        return $allowed_fields;
    }

    function SaveViewLogForRecord($rec, $allowedFields)
    {
        $update_fields = array();

        foreach ($this->ct->Table->fields as $mFld) {
            if (in_array($mFld['fieldname'], $allowedFields)) {
                if ($mFld['type'] == 'lastviewtime')
                    $update_fields[] = $mFld['realfieldname'] . '="' . date('Y-m-d H:i:s') . '"';

                if ($mFld['type'] == 'viewcount')
                    $update_fields[] = $mFld['realfieldname'] . '="' . ((int)($rec[$this->ct->Env->field_prefix . $mFld['fieldname']]) + 1) . '"';
            }
        }

        if (count($update_fields) > 0) {

            $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET ' . implode(', ', $update_fields) . ' WHERE id=' . $rec[$this->ct->Table->realidfieldname];
            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
        }
    }
}
