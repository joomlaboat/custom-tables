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
	
	echo '<ol>';
	
	foreach($this->tables as $table)
	{
		echo '<p><span style="font-size:1.3em;">'.$table['tabletitle'].'</span><br/><span style="color:gray;">'.$table['realtablename'].'</span></p>';
	
		checkTableFields($table['id'],$table['tablename'],$table['tabletitle'],$table['customtablename']);	
		
		echo '<hr/>';
	}
	
	echo '</ol>';
	
	?>
</div>
   
    
