<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

echo '<div id="j-sidebar-container" class="span2">';

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR
	.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'_checktable.php');

echo $this->sidebar; ?>
</div>
	
	<div id="j-main-container" class="ct_doc">
    
    
	<?php 
	$result='';
	
	
	foreach($this->tables as $table)
	{
		$link=JURI::root().'administrator/index.php?option=com_customtables&view=databasecheck&tableid='.$table['id'];
		$content = checkTableFields($table['id'],$table['tablename'],$table['tabletitle'],$table['customtablename'],$link);	
		
		$zeroId=$this->getZeroRecordID($table['realtablename'],$table['realidfieldname']);
		
		if($content !='' or $zeroId>0)
		{
			$result.='<li><p><span style="font-size:1.3em;">'.$table['tabletitle'].'</span><br/><span style="color:gray;">'.$table['realtablename'].'</span></p>';
			
			$result.=$content;
	
			if($zeroId>0)
				$result.='<p style="font-size:1.3em;color:red;">Records with ID = 0 found. Please fix it manually.</p>';
		
			$result.='<hr/></li>';
		}
	}
	
	if($result!='')
	{
		echo '<ol>'.$result.'</ol>';
	}
	else
	{
		echo '<p>Database table structure is up to date.</p>';
	}
	
	?>
</div>
   
    
