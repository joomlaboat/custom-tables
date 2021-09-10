<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

jimport( 'joomla.application.component.view');
class CustomTablesViewListEdit extends JView
{
    function display($tpl = null)
    {
		$mainframe = JFactory::getApplication();

		$this->optionRecord = $this->get('Data');
		
		$this->isNew= ($this->optionRecord->id < 1);

		$this->ListEditModel = $this->getModel();
	
		$filter_rootparent = $mainframe->getUserStateFromRequest( "com_customtables.filter_rootparent",'filter_rootparent','','int' );

	
		$LangMisc	= new ESLanguages;
		$this->LanguageList=$LangMisc->getLanguageList();
			
        parent::display($tpl);
    }
}
