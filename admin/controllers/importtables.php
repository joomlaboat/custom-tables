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
use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.application.component.controller');

class CustomTablesControllerImportTables extends JControllerForm
{

    function __construct()
    {
        parent::__construct();

    }

    function display($cachable = false, $urlparams = array())
    {
        $task = common::inputGetCmd('task', '');

        if ($task == 'importtables')
            $this->importtables();
        else {
            common::inputSet('view', 'importtables');
            parent::display();
        }
    }

    function importtables()
    {
        $model = $this->getModel('importtables');

        $link = 'index.php?option=com_customtables&view=importtables';
        $msg = '';
        if ($model->importTables($msg)) {
            $this->setRedirect($link, Text::_('Tables Imported Successfully'));
        } else {
            $this->setRedirect($link, Text::_('Tables was Unabled to Import: ' . $msg), 'error');
        }
    }
}
