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
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

?>
<img alt="<?php echo common::translate('COM_CUSTOMTABLES'); ?>"
     src="<?php echo common::UriRoot(true); ?>/components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg"
     style="text-align:center;">
<ul class="list-striped">
    <li><b><?php echo common::translate('COM_CUSTOMTABLES_VERSION'); ?>:</b> <?php echo $this->manifest->version; ?>
        &nbsp;&nbsp;<span class="update-notice"></span></li>
    <li><b><?php echo common::translate('COM_CUSTOMTABLES_DATE'); ?>:</b> <?php echo $this->manifest->creationDate; ?>
    </li>
    <li><b><?php echo common::translate('COM_CUSTOMTABLES_AUTHOR'); ?>:</b> <a
                href="mailto:<?php echo $this->manifest->authorEmail; ?>"><?php echo $this->manifest->author; ?></a>
    </li>
    <li><b><?php echo common::translate('COM_CUSTOMTABLES_WEBSITE'); ?>:</b> <a
                href="<?php echo $this->manifest->authorUrl; ?>"
                target="_blank"><?php echo $this->manifest->authorUrl; ?></a>
    </li>
    <li><b><?php echo common::translate('COM_CUSTOMTABLES_LICENSE'); ?>:</b> <?php echo $this->manifest->license; ?>
    </li>
    <li><b><?php echo $this->manifest->copyright; ?></b></li>
</ul>
<div class="clearfix"></div>
<?php if (CustomtablesHelper::checkArray($this->contributors)): ?>
    <?php if (count($this->contributors) > 1): ?>
        <h3><?php echo common::translate('COM_CUSTOMTABLES_CONTRIBUTORS'); ?></h3>
    <?php else: ?>
        <h3><?php echo common::translate('COM_CUSTOMTABLES_CONTRIBUTOR'); ?></h3>
    <?php endif; ?>
    <ul class="list-striped">
        <?php foreach ($this->contributors as $contributor): ?>
            <li><b><?php echo $contributor['title']; ?>:</b> <?php echo $contributor['name']; ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="clearfix"></div>
<?php endif; ?>
