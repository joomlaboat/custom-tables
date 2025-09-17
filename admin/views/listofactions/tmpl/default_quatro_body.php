<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;

?>
<?php foreach ($this->items as $i => $item): ?>

	<tr class="row<?php echo $i % 2; ?>">

		<td>
			<?php echo $item->USER_NAME; ?>
		</td>

		<td>
			<?php
			if ($item->datetime !== null and $item->datetime != '0000-00-00 00:00:00')
				echo common::formatDate($item->datetime);
			?>
		</td>

		<td>
			<?php echo $item->TABLE_TITLE; ?>
		</td>

		<td>
			<?php echo $item->ACTION_LABEL; ?>
		</td>

		<td>
			<?php echo $item->listingid; ?>
		</td>

		<td>
			<?php echo $item->MENU_TITLE; ?>
		</td>
	</tr>
<?php endforeach; ?>
