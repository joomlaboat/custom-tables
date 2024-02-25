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
if (!defined('_JEXEC')) die('Restricted access');

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

?>
<div id="j-main-container">


    <div class="span9">
		<?php echo HTMLHelper::_('bootstrap.startAccordion', 'dashboard_left', array('active' => 'main')); ?>
		<?php echo HTMLHelper::_('bootstrap.addSlide', 'dashboard_left', common::translate('COM_CUSTOMTABLES_DASH'), 'main'); ?>
		<?php echo $this->loadTemplate('main'); ?>
		<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>


		<?php echo HTMLHelper::_('bootstrap.addSlide', 'dashboard_left', common::translate('COM_CUSTOMTABLES_HOW_IT_WORKS'), 'help'); ?>
		<?php echo $this->loadTemplate('help'); ?>
		<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>

		<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
    </div>


    <div class="span3">
		<?php echo HTMLHelper::_('bootstrap.startAccordion', 'dashboard_right', array('active' => 'vdm')); ?>
		<?php echo HTMLHelper::_('bootstrap.addSlide', 'dashboard_right', 'JoomlaBoat.com', 'vdm'); ?>
		<?php echo $this->loadTemplate('vdm'); ?>
		<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
		<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
    </div>

</div>
