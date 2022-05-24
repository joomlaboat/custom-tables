<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

JHTML::addIncludePath(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers');
$isNew = $this->item->id == 0;
?>

<form id="adminForm" action="index.php" method="post" class="form-inline" enctype="multipart/form-data">

    <legend><?php echo Text::_('Custom Tables - Option Details'); ?></legend>

    <div class="form-horizontal">
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('optionname'); ?></div>
            <div class="controls"><?php echo($isNew ? $this->form->getInput('optionname') : $this->item->optionname); ?></div>
        </div>

        <?php
        $row_lang = (array)$this->item;
        $morethanonelang = false;
        foreach ($this->languages as $lang) {
            $id = 'title';
            if ($morethanonelang)
                $id .= '_' . $lang->sef;
            else
                $morethanonelang = true; //More than one language installed

            ?>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('title') . ' (' . $lang->caption . ')'; ?></div>
                <div class="controls"><input type="text" name="jform[<?php echo $id; ?>]" id="jform_<?php echo $id; ?>"
                                             class="inputbox" size="40" value="<?php echo $row_lang[$id]; ?>"/></div>
            </div>

            <?php
        }

        ?>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('parentid'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('parentid'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('isselectable'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('isselectable'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('optionalcode'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('optionalcode'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('link'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('link'); ?></div>
        </div>

    </div>


    <input type="hidden" name="option" value="com_customtables"/>
    <?php echo JHtml::_('form.token'); ?>
    <?php

    ?>

    <input type="hidden" name="task" value="options.edit"/>
    <input type="hidden" name="id" value="<?php echo $this->item->id; ?>"/>


</form>

