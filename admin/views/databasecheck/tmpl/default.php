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

// load tooltip behavior
//JHtml::_('behavior.tooltip');
//sJHtml::_('behavior.multiselect');
//JHtml::_('dropdown.init');
//JHtml::_('formbehavior.chosen', 'select');

use CustomTables\IntegrityChecks;

$tables = $this->prepareTables();

$document = JFactory::getDocument();

//$document->addCustomTag('<script src="'.JURI::root(true).'/media/vendor/jquery/js/jquery.min.js"></script>');
//https://github.com/DmitryBaranovskiy/raphael/releases
$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/raphael.min.js"></script>');

$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/diagram.js"></script>');

?>
<form action="<?php echo JRoute::_('index.php?option=com_customtables&view=databasecheck'); ?>" method="post" name="adminForm" id="adminForm">
<style type="text/css">  
        #canvas_container {  
            width: 100%;  
			min-height: <?php echo (count($tables)>50 ? '4000' : '2000'); ?>px;  
            border: 1px solid #aaa;  
        }  
</style>  

<div id="j-sidebar-container" class="span2">
<?php echo $this->sidebar; ?>

</div>
<div id="j-main-container" class="ct_doc">

	<?php echo JHtml::_('bootstrap.startTabSet', 'schemaTab', array('active' => 'diagram'));

	echo JHtml::_('bootstrap.addTab', 'schemaTab', 'diagram', JText::_('COM_CUSTOMTABLES_TABLES_DIAGRAM', true));
	echo '<div id="canvas_container"></div>';
	
	echo JHtml::_('bootstrap.endTab'); 
	
	echo JHtml::_('bootstrap.addTab', 'schemaTab', 'checks', JText::_('COM_CUSTOMTABLES_TABLES_CHECKS', true));
	
	$result = IntegrityChecks::check($this->ct);
	
	if(count($result)>0)
		echo '<ol><li>'.implode('</li><li>',$result).'</li></ol>';
	else
		echo '<p>Database table structure is up to date.</p>';
	
	echo JHtml::_('bootstrap.endTab');
	
	echo JHtml::_('bootstrap.endTabSet');

		
	echo '<script>
	
	TableCategoryID = '.(int)$this->state->get('filter.tablecategory').';
	AllTables = '.json_encode($tables).';
	
	</script>';
	
	?></div>
	
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>