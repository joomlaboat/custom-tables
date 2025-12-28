<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
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

			<!-- Accordion Item 2 -->
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading_help">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
							data-bs-target="#collapse_help" aria-expanded="false" aria-controls="collapse_help">
						<?php echo common::translate('COM_CUSTOMTABLES_HOW_IT_WORKS'); ?>
					</button>
				</h2>
				<div id="collapse_help" class="accordion-collapse collapse" aria-labelledby="heading_help"
					 data-bs-parent="#dashboard_left">
					<div class="accordion-body">
						<?php echo $this->loadTemplate('help'); ?> <!-- Load your 'help' template -->
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="accordion" id="dashboard_right">
			<!-- Accordion Item 1 -->
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading_vdm">
					<button class="accordion-button" type="button" data-bs-toggle="collapse"
							data-bs-target="#collapse_vdm" aria-expanded="true" aria-controls="collapse_vdm">
						About
					</button>
				</h2>
				<div id="collapse_vdm" class="accordion-collapse collapse show" aria-labelledby="heading_vdm"
					 data-bs-parent="#dashboard_right">
					<div class="accordion-body">
						<?php echo $this->loadTemplate('vdm'); ?> <!-- Load your 'vdm' template -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>