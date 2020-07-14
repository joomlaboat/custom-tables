<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'details.php');


$currenturl=JoomlaBasicMisc::curPageURL();
$cleanurl=JoomlaBasicMisc::deleteURLQueryOption($currenturl, 'action');
$cleanurl=JoomlaBasicMisc::deleteURLQueryOption($cleanurl, 'user');

if(strpos($cleanurl,"?")===false)
	$cleanurl.='?';
else
	$cleanurl.='&';

echo '
<script>
function ActionFilterChanged(o)
{
	var action=o.value;
	location.href="'.$cleanurl.'user='.$this->userid.'&action="+action;
}

function UserFilterChanged(o)
{
	location.href="'.$cleanurl.'action='.$this->action.'&user="+o.value;
}
</script>';


echo $this->actionSelector;
echo $this->userSelector;

echo '<div class="datagrid">'
		.'<table>'
			.'<thead>'
				.'<tr>'
					.'<th>A</th>'
					.'<th>User</th>'
					.'<th>Time</th>'
					.'<th>Table</th>'
					.'<th>Record</th>'
					.'<th>Action</th>'
				.'</tr>'
			.'</thead>'
			.'<tbody>';
//Content
foreach($this->records as $rec)
{
	renderLogLine($rec);
}
echo '</tbody></table>'
.'</div>';



function renderLogLine($rec)
{

	$actions=['New','Edit','Publish','Unpublish','Delete','Image Uploaded','Image Deleted','File Uploaded','File Deleted','Refreshed'];
	$action_images=['new.png','edit.png','publish.png','unpublish.png','delete.png','photomanager.png','photomanager.png','filemanager.png','filemanager.png','refresh.png'];
	$action_image_path='/components/com_customtables/images/';

	$a=(int)$rec['action']-1;
	$alt=$actions[$a];


	echo '<tr>'
			.'<td>';

			if($a==1 or $a==2)
			{
				$link='/index.php?option=com_customtables&view=edititem&listing_id='.$rec['listingid'].'&Itemid='.$rec['Itemid'];
				echo '<a href="'.$link.'" target="_blank"><img src="'.$action_image_path.$action_images[$a].'" alt='.$alt.' title='.$alt.' width="16" height="16" /></a>';
			}
			else
				echo '<img src="'.$action_image_path.$action_images[$a].'" alt='.$alt.' title='.$alt.' width="16" height="16" />';

			echo '</td>'
			.'<td>'.$rec['UserName'].'</td>';


			$link='/index.php?option=com_customtables&view=details&listing_id='.$rec['listingid'].'&Itemid='.$rec['Itemid'];

			echo '<td><a href="'.$link.'" target="_blank">'.$rec['datetime'].'</a></td>'

			.'<td>'.$rec['TableName'].'</td>'
			.'<td>'.getRecordValue($rec['listingid'],$rec['Itemid'],$rec['FieldName']).'<br/>(id: '.$rec['listingid'].')</td>'
			.'<td>'.$alt.'</td>'
		.'</tr>';


}

function getRecordValue($listing_id,$Itemid,$FieldName)
{
	if(!isset($FieldName) or $FieldName=='')
		return "Table/Field not found.";

		$app= JFactory::getApplication();
		JFactory::getApplication()->input->set('listing_id', $listing_id);
		JFactory::getApplication()->input->set('Itemid', $Itemid);

		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$menuparams         = $menu->getParams($Itemid);

		$model = new CustomTablesModelDetails;
		$model->load($menuparams,$listing_id,true);

		if($model->establename=='')
			return "Table ".$model->establename."not found.";

		$model->LayoutProc->layout='['.$FieldName.']';

		$tablename=$model->establename;

		$row=getRecord($tablename,$listing_id);

		return $model->LayoutProc->fillLayout($row);

}

function getRecord($tablename,$id)
{
	if($tablename!='')
	{
		$db = JFactory::getDBO();
		$query='SELECT *, id  as  listing_id, published AS listing_published  FROM #__customtables_table_'.$tablename.' WHERE id='.$id.' LIMIT 1';

		$db->setQuery($query);
		if (!$db->query()) die( $db->stderr());

		$records=$db->loadAssocList();

		if(count($records)==0)
			return array();


		return $records[0];

	}
	else
		return array();//"table not found;"
}
