<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if ($this->version >= 4) {
    $wa = $this->document->getWebAssetManager();
    $wa->useScript('keepalive')
        ->useScript('form.validate');
} else {
    JHtml::_('behavior.formvalidation');
    JHtml::_('behavior.keepalive');
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
    <div class="form-horizontal">

        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('categoryname'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('categoryname'); ?></div>
                </div>

            </div>
        </div>

        <div>
            <input type="hidden" name="task" value="categories.edit"/>
            <?php echo JHtml::_('form.token'); ?>
        </div>
    </div>
</form>