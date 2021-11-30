<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\IntegrityChecks;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$tables = $this->prepareTables();
	
$document = JFactory::getDocument();

$document->addCustomTag('<script src="'.JURI::root(true).'/media/vendor/jquery/js/jquery.min.js"></script>');
//https://github.com/DmitryBaranovskiy/raphael/releases
$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/raphael.min.js"></script>');

$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/diagram.js"></script>');

?>


        <style type="text/css">  
            #canvas_container {  
                width: 100%;  
				min-height: <?php echo (count($tables)>50 ? '4000' : '2000'); ?>px;  
                border: 1px solid #aaa;  
            }  
        </style>  

<?php echo HTMLHelper::_('uitab.startTabSet', 'schemaTab', ['active' => 'diagram', 'recall' => true, 'breakpoint' => 768]); ?>
	
	<?php echo HTMLHelper::_('uitab.addTab', 'schemaTab', 'diagram', Text::_('COM_CUSTOMTABLES_TABLES_DIAGRAM')); ?>
	
	<div id="canvas_container"></div>
	
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	
	<?php echo HTMLHelper::_('uitab.addTab', 'schemaTab', 'checks', Text::_('COM_CUSTOMTABLES_TABLES_CHECKS')); ?>

	<?php 
	$result = IntegrityChecks::check($this->ct);
	
	if(count($result)>0)
		echo '<ol><li>'.implode('</li><li>',$result).'</li></ol>';
	else
		echo '<p>Database table structure is up to date.</p>';
	
	?>
	
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	
	
	<?php 
	
	echo '<script>
	
	AllTables = '.json_encode($tables).';
	
	</script>';
	
	?>
