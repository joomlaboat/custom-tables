<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla view library
jimport('joomla.application.component.view');

use CustomTables\Documentation;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-documentation.php');

/**
 * Tables View class
 */
class CustomtablesViewDocumentation extends JViewLegacy
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
        $jinput = Factory::getApplication()->input;

        if ($jinput->getCmd('tmpl', '') == 'component')
            $this->documentation->internal_use = false;

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('documentation');
            $this->addToolBar();

            if ($this->version < 4)
                $this->sidebar = JHtmlSidebar::render();
        }

        // Set the document
        $this->setDocument();

        parent::display($tpl);
    }

    protected function addToolBar()
    {
        JToolBarHelper::title(CustomTables\common::translate('COM_CUSTOMTABLES_DOCUMENTATION'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=documentation');
    }

    protected function setDocument()
    {
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(CustomTables\common::translate('COM_CUSTOMTABLES_DOCUMENTATION'));
        $this->document->addStyleSheet(JURI::root(true) . "/components/com_customtables/libraries/customtables/media/css/fieldtypes.css");

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

        $this->document->addCustomTag($script);
    }
}
