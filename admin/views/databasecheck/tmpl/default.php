<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\IntegrityChecks;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$document = Factory::getDocument();

//https://github.com/DmitryBaranovskiy/raphael/releases
$document->addCustomTag('<script src="' . CUSTOMTABLES_PLUGIN_WEBPATH . 'js/raphael.min.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/diagram.js"></script>');

?>
<form action="<?php echo Route::_('index.php?option=com_customtables&view=databasecheck'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<style>
		#canvas_container {
			width: 100%;
			min-height: <?php echo (count($this->diagram->tables)>50 ? '4000' : '2000'); ?>px;
			border: 1px solid #aaa;
		}
	</style>

	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>

	</div>
	<div id="j-main-container" class="ct_doc">

		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'schemaTab', array('active' => 'diagram'));

		echo HTMLHelper::_('bootstrap.addTab', 'schemaTab', 'diagram', common::translate('COM_CUSTOMTABLES_TABLES_DIAGRAM'));
		echo '<div id="canvas_container"></div>';

		echo HTMLHelper::_('bootstrap.endTab');

		echo HTMLHelper::_('bootstrap.addTab', 'schemaTab', 'checks', common::translate('COM_CUSTOMTABLES_TABLES_CHECKS'));

		$result = IntegrityChecks::check($this->ct);

		if (count($result) > 0)
			echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';
		else
			echo '<p>Database table structure is up-to-date.</p>';

		echo HTMLHelper::_('bootstrap.endTab');

		echo HTMLHelper::_('bootstrap.endTabSet');


		echo '<script>
	
	TableCategoryID = ' . (int)$this->state->get('filter.tablecategory') . ';
	AllTables = ' . common::ctJsonEncode($this->diagram->tables) . ';
	
	</script>';

		?></div>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>