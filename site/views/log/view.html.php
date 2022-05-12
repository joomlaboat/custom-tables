<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesViewLog extends JViewLegacy
{
	var $limit;
	var $limitstart;
	var $record_count;

	function display($tpl = null)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'details.php');
		
		$user = JFactory::getUser();
		$this->userid=$user->id;

		$this->action=JFactory::getApplication()->input->getString('action', '');
		if($this->action=='')
			$this->action=-1;

		$this->userid=JFactory::getApplication()->input->getInt('user',0);
		
		$this->tableid=JFactory::getApplication()->input->getInt('table',0);

		//Is user super Admin?
		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);

		$this->records=$this->getRecords($this->action,$this->userid,$this->tableid);

		$this->actionSelector=$this->ActionFilter($this->action);

		$this->userSelector=$this->getUsers($this->userid);
		
		$this->tableSelector=$this->gettables($this->tableid);

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
	
	function getTables($tableid)
	{
		$db = JFactory::getDBO();

		$query='SELECT id,tablename FROM #__customtables_tables ORDER BY tablename';

		$db->setQuery($query);

		$rows=$db->loadAssocList();

		$result='';
		$result.='<select onchange="TableFilterChanged(this)">';
		$result.='<option value="0" '.($tableid==0 ? 'selected="SELECTED"' : '').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';

		foreach($rows as $row)
		{
			$result.='<option value="'.$row['id'].'" '.($tableid==$row['id'] ? 'selected="SELECTED"' : '').'>'.$row['tablename'].'</option>';
		}

		$result.='</select>';

		return $result;
	}

	function getRecords($action,$userid,$tableid)
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
		
		if($tableid!=0)
			$where[]='tableid='.$tableid;


		$query='SELECT '.implode(',',$selects).' FROM #__customtables_log '.(count($where)>0 ? ' WHERE '.implode(' AND ',$where) : '').' ORDER BY datetime DESC';

		$this->record_count = 1000;

		$the_limit=$this->limit;
		if($the_limit>500)
			$the_limit=500;

		if($the_limit==0)
			$the_limit=500;

		if($this->record_count < $this->limitstart or $this->record_count < $the_limit)
			$this->limitstart=0;

		$db->setQuery($query, $this->limitstart, $the_limit);

		$rows=$db->loadAssocList();

		return $rows;
	}
	
	function renderLogLine($rec)
	{
		$actions=['New','Edit','Publish','Unpublish','Delete','Image Uploaded','Image Deleted','File Uploaded','File Deleted','Refreshed'];
		$action_images=['new.png','edit.png','publish.png','unpublish.png','delete.png','photomanager.png','photomanager.png','filemanager.png','filemanager.png','refresh.png'];
		$action_image_path='/components/com_customtables/libraries/customtables/media/images/icons/';

		$a=(int)$rec['action']-1;
		$alt=$actions[$a];
		
		$result = '';

		$result.= '<tr>'
			.'<td>';

		if($a==1 or $a==2)
		{
			$link='/index.php?option=com_customtables&view=edititem&listing_id='.$rec['listingid'].'&Itemid='.$rec['Itemid'];
			$result.= '<a href="'.$link.'" target="_blank"><img src="'.$action_image_path.$action_images[$a].'" alt='.$alt.' title='.$alt.' width="16" height="16" /></a>';
		}
		else
			$result.= '<img src="'.$action_image_path.$action_images[$a].'" alt='.$alt.' title='.$alt.' width="16" height="16" />';

		$result.= '</td>'
		.'<td>'.$rec['UserName'].'</td>';

		$link='/index.php?option=com_customtables&view=details&listing_id='.$rec['listingid'].'&Itemid='.$rec['Itemid'];

		$result.= '<td><a href="'.$link.'" target="_blank">'.$rec['datetime'].'</a></td>'

			.'<td>'.$rec['TableName'].'</td>'
			.'<td>'.$this->getRecordValue($rec['listingid'],$rec['Itemid'],$rec['FieldName']).'<br/>(id: '.$rec['listingid'].')</td>'
			.'<td>'.$alt.'</td>'
		.'</tr>';

		return $result;
	}

	function getRecordValue($listing_id,$Itemid,$FieldName)
	{
		if(!isset($FieldName) or $FieldName=='')
			return "Table/Field not found.";

		$app= JFactory::getApplication();
		$jinput = JFactory::getApplication()->input;

		$jinput->set("listing_id", $listing_id);
		$jinput->set('Itemid', $Itemid);		

		$menu = $app->getMenu();
		$menuparams = $menu->getParams($Itemid);

		$model = new CustomTablesModelDetails;
		$model->load($menuparams,$listing_id,true);
		
		if($model->ct->Table->tablename=='')
			return "Table ".$model->ct->Table->tablename."not found.";
		
		$layout = '{{ ' . $FieldName . ' }}'
		$twig = new TwigProcessor($this->ct, $layout);
		
		$row = $model->ct->Table->loadRecord($listing_id);
		
		return $twig->process($row);
	}
}