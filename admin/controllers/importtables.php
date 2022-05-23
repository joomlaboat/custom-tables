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
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class CustomTablesControllerImportTables extends JControllerForm
{

    function __construct()
    {
        parent::__construct();

    }

    function display($cachable = false, $urlparams = array())
    {
        $input = Factory::getApplication()->input;
        $task = $input->getCmd('task', '');

        if ($task == 'importtables')
            importtables();
        else {
            $input->set('view', 'importtables');
            parent::display();
        }
    }

    function importtables()
    {
        $model = $this->getModel('importtables');

        $link = 'index.php?option=com_customtables&view=importtables';
        $msg = '';
        if ($model->importtables($msg)) {
            $this->setRedirect($link, JText::_('Tables Imported Successfully'));
        } else {
            $this->setRedirect($link, JText::_('Tables was Unabled to Import: ' . $msg), 'error');
        }
    }
}
