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

?>
<div id="j-main-container" class="row">
	<div class="col-md-9">
		<div class="accordion" id="dashboard_left">
			<!-- Accordion Item 1 -->
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading_main">
					<button class="accordion-button" type="button" data-bs-toggle="collapse"
							data-bs-target="#collapse_main" aria-expanded="true" aria-controls="collapse_main">
						<?php echo common::translate('COM_CUSTOMTABLES_DASH'); ?>
					</button>
				</h2>
				<div id="collapse_main" class="accordion-collapse collapse show" aria-labelledby="heading_main"
					 data-bs-parent="#dashboard_left">
					<div class="accordion-body">
						<?php echo $this->loadTemplate('main'); ?> <!-- Load your 'main' template -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>