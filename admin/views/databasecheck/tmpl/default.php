<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/*
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');
*/

use CustomTables\IntegrityChecks;

?>

<?php if($this->version < 4): ?>
	<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
	</div>
<?php endif; ?>

<div id="j-main-container" class="ct_doc">

	<?php 
	$result = IntegrityChecks::check();
	
	if(count($result)>0)
		echo '<ol><li>'.implode('</li><li>',$result).'</li></ol>';
	else
		echo '<p>Database table structure is up to date.</p>';
	
	?>
</div>
