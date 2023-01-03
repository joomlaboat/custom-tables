<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\Fields;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
$document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams_common.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams.js"></script>');

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
    . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'layouteditor' . DIRECTORY_SEPARATOR . 'layouteditor.php');

$onPageLoads = array();
$typeBoxId = "jform_layouttype";

foreach ($this->allTables as $table) {
    $fields = Fields::getFields($table[0], true);
    $list = array();
    foreach ($fields as $field)
        $list[] = [$field->id, $field->fieldname];

    echo '<div id="fieldsData' . $table[0] . '" style="display:none;">' . json_encode($list) . '</div>
';
}
?>
<script>
    <?php echo 'all_tables=' . json_encode($this->allTables) . ';'; ?>
</script>

<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

    <?php echo JHtml::_('bootstrap.startTabSet', 'layoutsTab', array('active' => $this->active_tab)); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'general', Text::_('COM_CUSTOMTABLES_LAYOUTS_GENERAL', true)); ?>

    <div class="row-fluid form-horizontal-desktop">
        <div class="span12">

            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('layoutname'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('layoutname'); ?></div>
            </div>

            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('layouttype'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('layouttype'); ?></div>
            </div>

            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('tableid'); ?></div>
            </div>
        </div>
    </div>

    <?php echo JHtml::_('bootstrap.endTab');

    echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutcode-tab', Text::_('COM_CUSTOMTABLES_LAYOUTS_HTML', true));
    echo $this->renderTextArea($this->item->layoutcode, 'layoutcode', $typeBoxId, $onPageLoads);
    echo JHtml::_('bootstrap.endTab');

    echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutmobile-tab', Text::_('COM_CUSTOMTABLES_LAYOUTS_HTML_MOBILE', true));
    if ($this->ct->Env->advancedtagprocessor)
        echo $this->renderTextArea($this->item->layoutmobile, 'layoutmobile', $typeBoxId, $onPageLoads);
    else
        echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
    echo JHtml::_('bootstrap.endTab');

    echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutcss-tab', Text::_('COM_CUSTOMTABLES_LAYOUTS_CSS', true));
    if ($this->ct->Env->advancedtagprocessor)
        echo $this->renderTextArea($this->item->layoutcss, 'layoutcss', $typeBoxId, $onPageLoads);
    else
        echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
    echo JHtml::_('bootstrap.endTab');

    echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutjs-tab', Text::_('COM_CUSTOMTABLES_LAYOUTS_JS', true));
    if ($this->ct->Env->advancedtagprocessor)
        echo $this->renderTextArea($this->item->layoutjs, 'layoutjs', $typeBoxId, $onPageLoads);
    else
        echo Text::_('COM_CUSTOMTABLES_AVAILABLE');

    echo JHtml::_('bootstrap.endTab');
    echo JHtml::_('bootstrap.endTabSet');
    echo JHtml::_('form.token');
    echo render_onPageLoads($onPageLoads, 3);

    $this->getMenuItems();
    ?>
    <input type="hidden" name="task" value="layouts.edit"/>

    <div id="allLayoutRaw" style="display:none;"><?php echo json_encode($this->getLayouts()); ?></div>
    <div id="dependencies_content" style="display:none;">
        <h3><?php echo Text::_('COM_CUSTOMTABLES_LAYOUTS_WHAT_IS_USING_IT', true); ?></h3>
        <div id="layouteditor_tagsContent0" class="dynamic_values_list dynamic_values">
            <?php
            require('dependencies.php');
            echo renderDependencies($this->item); // this will be shown upon the click in the toolbar
            ?>
        </div>
    </div>
</form>