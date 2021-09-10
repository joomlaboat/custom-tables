<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'toolbar.php');

jimport('joomla.application.component.view');

class CustomTablesViewList extends JView
{
	var $_name = 'list';

	function display($tpl=null)
	{
		$jinput = JFactory::getApplication()->input;

		$mainframe = JFactory::getApplication();

		$this->_layout = 'default';

		$LangMisc	= new ESLanguages;
		$this->LanguageList=$LangMisc->getLanguageList();
		
		$this->limitstart = JFactory::getApplication()->input->getInt('limitstart','0');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->lists		= $this->_getViewLists();
		$this->user		= JFactory::getUser();

		// Ensure ampersands and double quotes are encoded in item titles
		foreach($this->items as $i => $item) {
			$treename = $item->treename;
			$treename = JFilterOutput::ampReplace($treename);
			$treename = str_replace('"', '&quot;', $treename);
			$items[$i]->treename = $treename;
		}

		//Ordering allowed ?
		$this->ordering = ($lists['order'] == 'm.ordering');

		JHTML::_('behavior.tooltip');

		parent::display($tpl);
	}

	function &_getViewLists()
	{
		$mainframe = JFactory::getApplication();
		$db		= JFactory::getDBO();

		$context			= 'com_customtables.list.';

		$filter_order		= $mainframe->getUserStateFromRequest($context."filter_order",		'filter_order',		'm.ordering',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest($context."filter_order_Dir",	'filter_order_Dir',	'ASC',			'word' );

		$filter_rootparent	= $mainframe->getUserStateFromRequest($context."filter_rootparent",'filter_rootparent','','int' );

		$levellimit		= $mainframe->getUserStateFromRequest($context."levellimit",		'levellimit',		10,				'int' );
		$search			= $mainframe->getUserStateFromRequest($context."search",			'search',			'',				'string' );
		$search			= JString::strtolower( $search );

		// level limit filter
		$lists['levellist']	= JHTML::_('select.integerlist',    1, 20, 1, 'levellimit', 'size="1" onchange="document.adminForm.submit();"', $levellimit );



		// Category List
		$javascript = 'onchange="document.adminForm.submit();"';


		$ListModel = $this->getModel();
		$available_rootparents=$ListModel->getAllRootParents();
		$lists['rootparent']=JHTML::_('select.genericlist', $available_rootparents, 'filter_rootparent', $javascript ,'id','optionname', $filter_rootparent);

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;



		$lists['order']		= $filter_order;

		// search filter
		$lists['search']= $search;

		return $lists;
	}
}
