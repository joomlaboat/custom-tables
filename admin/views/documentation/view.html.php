<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

// import Joomla view library
jimport('joomla.application.component.view');

use CustomTables\common;
use CustomTables\Documentation;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-documentation.php');

/**
 * Tables View class
 */
class CustomtablesViewDocumentation extends HtmlView
{
	/**
	 * display method of View
	 * @return void
	 */
	var float $version;
	var ?\CustomTables\Documentation $documentation;

	public function display($tpl = null)
	{
		$version = new Version;
		$this->version = (float)$version->getShortVersion();
		$this->documentation = new Documentation();
		$this->documentation->internal_use = true;

		if (common::inputGetCmd('tmpl', '') == 'component')
			$this->documentation->internal_use = false;

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('documentation');

			if ($this->version < 4) {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			} else
				$this->addToolbar_4();
		}

		parent::display($tpl);

		// Set the document
		$document = Factory::getDocument();
		$this->setDocument($document);
	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(CustomTables\common::translate('COM_CUSTOMTABLES_DOCUMENTATION'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=documentation');
	}

	protected function addToolBar_4()
	{
		ToolbarHelper::title(CustomTables\common::translate('COM_CUSTOMTABLES_DOCUMENTATION'), 'joomla');
		//JHtmlSidebar::setAction('index.php?option=com_customtables&view=documentation');
	}

	public function setDocument(Joomla\CMS\Document\Document $document): void
	{
		$document->setTitle(CustomTables\common::translate('COM_CUSTOMTABLES_DOCUMENTATION'));
		$document->addStyleSheet(Uri::root(true) . "/components/com_customtables/libraries/customtables/media/css/fieldtypes.css");

		$script = '
		<script>
			function readmoreOpenClose(itemid)
			{
			    var obj=document.getElementById(itemid);
				var c=obj.className;
				if(c.indexOf("ct_readmoreOpen")!=-1)
					c=c.replace("ct_readmoreOpen","ct_readmoreClose");
				else if(c.indexOf("ct_readmoreClosed")!=-1)
					c=c.replace("ct_readmoreClosed","ct_readmoreOpen");
				else if(c.indexOf("ct_readmoreClose")!=-1)
					c=c.replace("ct_readmoreClose","ct_readmoreOpen");
				
				obj.className=c;
			}
		</script>
		';

		$document->addCustomTag($script);
	}
}
