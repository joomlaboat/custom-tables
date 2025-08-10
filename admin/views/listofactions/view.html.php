<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die;

use CustomTables\common;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use CustomTables\CT;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Customtables View class for the ListOfRecords
 *
 * @since 1.0.0
 */
class CustomtablesViewListOfActions extends HtmlView
{
	/**
	 * Listoffields view display method
	 * @return void
	 *
	 * @since 3.0.0
	 */
	var CT $ct;
	var $ordering_realfieldname;

	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal') {

			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofactions');

			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				$this->addToolbar_4();
			} else {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
		}

		// Display the template
		echo 'Coming soon.';
		/*
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			parent::display('quatro');
		else
			parent::display($tpl);
		*/
	}

	/**
	 * Setting the toolbar
	 * @since 3.0.0
	 */

	protected function addToolbar_4()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFACTIONS'), 'joomla');

	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFACTIONS'), 'joomla');
	}
}
