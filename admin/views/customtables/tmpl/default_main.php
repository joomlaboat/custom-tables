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
<?php if (isset($this->icons['main']) && is_array($this->icons['main'])) : ?>
	<?php foreach ($this->icons['main'] as $icon): ?>
		<div class="dashboard-wraper">
			<div class="dashboard-content">
				<a class="icon" href="<?php echo $icon->url; ?>">
					<img alt="<?php echo $icon->alt; ?>"
						 src="<?php echo common::UriRoot(true); ?>/components/com_customtables/libraries/customtables/media/images/controlpanel/icons/<?php echo $icon->image; ?>">
					<span class="dashboard-title"><?php echo common::translate($icon->name); ?></span>
				</a>
			</div>
		</div>
	<?php endforeach; ?>
	<div class="clearfix"></div>
<?php else: ?>
	<div class="alert alert-error"><h4
				class="alert-heading"><?php echo common::translate('Permission denied, or not correctly set'); ?></h4>
		<div class="alert-message"><?php echo common::translate('Please notify your System Administrator if result is unexpected.'); ?></div>
	</div>
<?php endif; ?>
