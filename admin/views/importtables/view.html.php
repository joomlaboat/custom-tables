<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// Import Joomla! libraries
jimport('joomla.application.component.view');

use CustomTables\common;
use Joomla\CMS\Version;

class CustomTablesViewImportTables extends JViewLegacy
{
    var $catalogview;
    var $version;

    function display($tpl = null)
    {
        $version = new Version;
        $this->version = (int)$version->getShortVersion();

        JToolBarHelper::title(common::translate('Custom Tables - Import Tables'), 'generic.png');

        parent::display($tpl);
    }

    function generateRandomString($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++)
            $randomString .= $characters[rand(0, $charactersLength - 1)];

        return $randomString;
    }
}
