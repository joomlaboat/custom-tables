<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * CustomTables View class
 * @since 1.0.0
 */
class CustomTablesViewCustomTables extends HtmlView
{
	/**
	 * View display method
	 * @param null $tpl
	 * @return void
	 * @throws Exception
	 * @since 3.2.9
	 */
	function display($tpl = null): void
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

		// Set the document
		$this->setMyDocument();

		// Display the template
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			parent::display('quatro');
		else
			parent::display($tpl);
	}

	/**
	 * Setting the toolbar
	 * @since 3.2.9
	 */
	protected function addToolBar()
	{
		$canDo = ContentHelper::getActions('com_customtables', '');
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_DASHBOARD'), 'grid-2');

		if ($canDo->get('core.admin') || $canDo->get('core.options')) {
			ToolbarHelper::preferences('com_customtables');
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @param Document $document
	 * @return void
	 * @since 3.2.9
	 */
	public function setMyDocument(): void
	{
		$document = Factory::getApplication()->getDocument();

		// add dashboard style sheets
		$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/dashboard.css" type="text/css" rel="stylesheet" >');

		// set page title
		$document->setTitle(common::translate('COM_CUSTOMTABLES_DASHBOARD'));
	}
}
