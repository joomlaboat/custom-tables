<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

//jimport( 'joomla.application.component.view');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class CustomTablesViewLog extends JViewLegacy
{
	var $limit;
	var $limitstart;
	var $TotalRows;

	function display($tpl = null)
	{
		$user = JFactory::getUser();
		$this->userid=$user->id;

		$this->action=JFactory::getApplication()->input->getString('action', '');
		if($this->action=='')
			$this->action=-1;

		$this->userid=JFactory::getApplication()->input->get('user',0,'INT');

		//Is user super Admin?
		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);

		$this->records=$this->getRecords($this->action,$this->userid);

		$this->actionSelector=$this->ActionFilter($this->action);

		$this->userSelector=$this->getUsers($this->userid);

		parent::display($tpl);

		return;
	}

	function ActionFilter($action)
	{
		$actions=['New','Edit','Publish','Unpublish','Delete','Image Uploaded','Image Deleted','File Uploaded','File Deleted','Refreshed'];
		$result='<select onchange="ActionFilterChanged(this)">';
		$result.='<option value="-1" '.($action==-1 ? 'selected="SELECTED"' : '').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';

		$v=1;
		foreach($actions as $a)
		{
			$result.='<option value="'.$v.'" '.($action==$v ? 'selected="SELECTED"' : '').'>'.$a.'</option>';
			$v++;
		}

		$result.='</select>';
		return $result;
	}

	function getUsers($userid)
	{
		$db = JFactory::getDBO();

		$query='SELECT #__users.id AS id, #__users.name AS name FROM #__customtables_log INNER JOIN #__users ON #__users.id=#__customtables_log.userid GROUP BY #__users.id ORDER BY name';

		$db->setQuery($query);

		$rows=$db->loadAssocList();

		$result='';
		$result.='<select onchange="UserFilterChanged(this)">';
		$result.='<option value="0" '.($userid==0 ? 'selected="SELECTED"' : '').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';

		foreach($rows as $row)
		{
			$result.='<option value="'.$row['id'].'" '.($userid==$row['id'] ? 'selected="SELECTED"' : '').'>'.$row['name'].'</option>';
		}

		$result.='</select>';

		return $result;
	}

	function getRecords($action,$userid)
	{
		$mainframe = JFactory::getApplication('site');
		$this->limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		if($this->limit=0)
			$this->limit=20;

		$this->limitstart = JFactory::getApplication()->input->get('start',0,'INT');
		// In case limit has been changed, adjust it
		$this->limitstart = ($this->limit != 0 ? (floor($this->limitstart / $this->limit) * $this->limit) : 0);

		$db = JFactory::getDBO();

		$selects=array();
		$selects[]='*';
		$selects[]='(SELECT name FROM #__users WHERE id=userid) AS UserName';
		$selects[]='(SELECT tabletitle FROM #__customtables_tables WHERE id=tableid) AS TableName';
		$selects[]='(SELECT fieldname FROM #__customtables_fields WHERE #__customtables_fields.published=1 AND #__customtables_fields.tableid=#__customtables_log.tableid '
		.'ORDER BY ordering LIMIT 1) AS FieldName';

		$where=array();
		if($action!=-1)
			$where[]='action='.$action;

		if($userid!=0)
			$where[]='userid='.$userid;


		$query='SELECT '.implode(',',$selects).' FROM #__customtables_log '.(count($where)>0 ? ' WHERE '.implode(' AND ',$where) : '').' ORDER BY datetime DESC';

		$this->TotalRows=1000;

		$the_limit=$this->limit;
		if($the_limit>500)
			$the_limit=500;

		if($the_limit==0)
			$the_limit=500;

		if($this->TotalRows<$this->limitstart or $this->TotalRows<$the_limit)
			$this->limitstart=0;

		$db->setQuery($query, $this->limitstart, $the_limit);

		$rows=$db->loadAssocList();

		return $rows;
	}
}