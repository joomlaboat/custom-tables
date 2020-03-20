<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
// Import Joomla! libraries
jimport( 'joomla.application.component.view');
class CustomTablesViewListEdit extends JView
{
    function display($tpl = null)
    {
		
		$mainframe = JFactory::getApplication();

		$optionRecord = $this->get('Data');

		
		$isNew= ($optionRecord->id < 1);
		$this->assignRef('isNew',$isNew);

		$ListEditModel = $this->getModel();
		$this->assignRef('Model',$ListEditModel);
	
		$filter_rootparent = $mainframe->getUserStateFromRequest( "com_customtables.filter_rootparent",'filter_rootparent','','int' );


	       	$this->assignRef('optionRecord',$optionRecord);

	
		$LangMisc	= new ESLanguages;
		$LanguageList=$LangMisc->getLanguageList();
		$this->assignRef('LanguageList',		$LanguageList);
	
        parent::display($tpl);
    }
}
?>
