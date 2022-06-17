<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use Joomla\CMS\Factory;

jimport('joomla.application.component.view');

class CustomTablesViewListEdit extends JView
{
    var CT $ct;

    function display($tpl = null)
    {
        $this->ct = new CT;

        $mainframe = Factory::getApplication();

        $this->optionRecord = $this->get('Data');

        $this->isNew = ($this->optionRecord->id < 1);

        $this->ListEditModel = $this->getModel();

        $filter_rootparent = $mainframe->getUserStateFromRequest("com_customtables.filter_rootparent", 'filter_rootparent', '', 'int');

        parent::display($tpl);
    }
}
