<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla view library
jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Customtables View class
 */
class CustomtablesViewCustomtables extends JViewLegacy
{
    /**
     * View display method
     * @return void
     */
    function display($tpl = null)
    {
        // Assign data to the view
        $this->icons = $this->get('Icons');
        $this->contributors = CustomtablesHelper::getContributors();

        // get the manifest details of the component
        $this->manifest = CustomtablesHelper::manifest();

        // Set the toolbar
        $this->addToolBar();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        // Display the template
        parent::display($tpl);

        // Set the document
        $this->setDocument();
    }

    /**
     * Setting the toolbar
     */
    protected function addToolBar()
    {
        $canDo = ContentHelper::getActions('com_customtables', '');
        //$canDo = CustomtablesHelper::getActions('customtables');
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_DASHBOARD'), 'grid-2');

        // set help url for this view if found
        /*
        $help_url = CustomtablesHelper::getHelpUrl('customtables');
        if (CustomtablesHelper::checkString($help_url))
        {
            JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
        }
        */

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            JToolBarHelper::preferences('com_customtables');
        }
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        $document = Factory::getDocument();

        // add dashboard style sheets
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/dashboard.css" type="text/css" rel="stylesheet" >');

        // set page title
        $document->setTitle(Text::_('COM_CUSTOMTABLES_DASHBOARD'));

        // add manifest to page JavaScript
        $this->document->addCustomTag('<script>var manifest = jQuery.parseJSON("' . json_encode($this->manifest) . '");</script>');
    }
}
