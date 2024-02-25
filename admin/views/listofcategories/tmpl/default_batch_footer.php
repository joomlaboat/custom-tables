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
use CustomTables\common;

defined('_JEXEC') or die();

?>
<!-- clear the batch values if cancel -->
<button class="btn" type="button" onclick="" data-dismiss="modal">
	<?php echo common::translate('JCANCEL'); ?>
</button>
<!-- post the batch values if process -->
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('categories.batch');">
	<?php echo common::translate('JGLOBAL_BATCH_PROCESS'); ?>
</button>
