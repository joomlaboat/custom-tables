<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>
<div id="j-main-container">


    <div class="span9">
        <?php echo JHtml::_('bootstrap.startAccordion', 'dashboard_left', array('active' => 'main')); ?>
        <?php echo JHtml::_('bootstrap.addSlide', 'dashboard_left', JText::_('COM_CUSTOMTABLES_DASH'), 'main'); ?>
        <?php echo $this->loadTemplate('main'); ?>
        <?php echo JHtml::_('bootstrap.endSlide'); ?>


        <?php echo JHtml::_('bootstrap.addSlide', 'dashboard_left', JText::_('COM_CUSTOMTABLES_HOW_IT_WORKS'), 'help'); ?>
        <?php echo $this->loadTemplate('help'); ?>
        <?php echo JHtml::_('bootstrap.endSlide'); ?>

        <?php echo JHtml::_('bootstrap.endAccordion'); ?>
    </div>


    <div class="span3">
        <?php echo JHtml::_('bootstrap.startAccordion', 'dashboard_right', array('active' => 'vdm')); ?>
        <?php echo JHtml::_('bootstrap.addSlide', 'dashboard_right', 'JoomlaBoat.com', 'vdm'); ?>
        <?php echo $this->loadTemplate('vdm'); ?>
        <?php echo JHtml::_('bootstrap.endSlide'); ?>
        <?php echo JHtml::_('bootstrap.endAccordion'); ?>
    </div>

</div>
