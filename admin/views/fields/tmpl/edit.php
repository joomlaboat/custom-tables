<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage views/fields/tmpl/edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Fields;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$document = Factory::getDocument();

$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_common.js"></script>');
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams.js"></script>');

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if ($this->version >= 4) {
    $wa = $this->document->getWebAssetManager();
    $wa->useScript('keepalive')->useScript('form.validate');
} else {
    HTMLHelper::_('behavior.formvalidation');
    HTMLHelper::_('behavior.keepalive');
}

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'extratasks' . DIRECTORY_SEPARATOR . 'extratasks.php');

if (in_array(common::inputGetCmd('extratask', ''), $this->extrataskOptions)) {
    extraTasks::prepareJS();
}

foreach ($this->allTables as $table) {

    $ct = new CT;
    $ct->getTable($table[0]);
    $list = array();
    foreach ($ct->Table->fields as $field)
        $list[] = [$field['id'], $field['fieldname']];

    echo '<div id="fieldsData' . $table[0] . '" style="display:none;">' . common::ctJsonEncode($list) . '</div>
';
} ?>
<script>

    var outerDiv = jQuery('body');
    jQuery('<div id="loading"></div>')
        .css("background", "rgba(255, 255, 255, .8) url('<?php echo common::UriRoot(true); ?>/components/com_customtables/libraries/images/controlpanel/images/import.gif') 50% 15% no-repeat")
        .css("top", outerDiv.position().top - jQuery(window).scrollTop())
        .css("left", outerDiv.position().left - jQuery(window).scrollLeft())
        .css("width", outerDiv.width())
        .css("height", outerDiv.height())
        .css("position", "fixed")
        .css("opacity", "0.80")
        .css("-ms-filter", "progid:DXImageTransform.Microsoft.Alpha(Opacity = 80)")
        .css("filter", "alpha(opacity = 80)")
        .css("display", "none")
        .appendTo(outerDiv);
    jQuery('#loading').show();
    // when page is ready remove and show
    jQuery(window).load(function () {
        jQuery('#customtables_loader').fadeIn('fast');
        jQuery('#loading').hide();
    });

    <?php

    if ($this->ct->Env->advancedTagProcessor) {
        echo PHP_EOL . 'proversion=true;' . PHP_EOL;
    }
    echo 'all_tables=' . common::ctJsonEncode($this->allTables) . ';' . PHP_EOL;
    ?>
</script>
<div id="customtables_loader" style="display: none;">

    <form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)($this->item->id) . $this->referral); ?>"
          method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

        <div class="form-horizontal">

            <?php
            echo HTMLHelper::_('bootstrap.startTabSet', 'fieldsTab', array('active' => 'general')); ?>

            <?php echo HTMLHelper::_('bootstrap.addTab', 'fieldsTab', 'general', common::translate('COM_CUSTOMTABLES_FIELDS_GENERAL')); ?>
            <div class="row-fluid form-horizontal-desktop">
                <div class="span12">

                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('tableid', null, $this->tableid); ?></div>
                    </div>

                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('fieldname'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('fieldname'); ?></div>
                    </div>

                    <?php /* if ($this->table_row->customtablename != ''): ?>
                        <hr/>
                        <p><?php echo common::translate('COM_CUSTOMTABLES_FIELDS_THIS_IS_THIRDPARTY_FIELD') . ': "' . $this->table_row->customtablename . '"'; ?></p>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('customfieldname'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('customfieldname'); ?></div>
                        </div>

                    <?php endif; */ ?>

                    <hr/>

                    <?php

                    $moreThanOneLanguage = false;
                    foreach ($this->ct->Languages->LanguageList as $lang) {
                        $id = 'fieldtitle';
                        if ($moreThanOneLanguage) {
                            $id .= '_' . $lang->sef;

                            $cssclass = 'text_area';
                            $att = '';
                        } else {
                            $cssclass = 'text_area required';
                            $att = ' required aria-required="true"';
                        }

                        $item_array = (array)$this->item;
                        $vlu = '';

                        if (isset($item_array[$id]))
                            $vlu = $item_array[$id];

                        if ($moreThanOneLanguage)
                            $field_label = common::translate('COM_CUSTOMTABLES_FIELDS_FIELDTITLE');
                        else
                            $field_label = $this->form->getLabel('fieldtitle');

                        echo '
					<div class="control-group">
						<div class="control-label">' . $field_label . '</div>
						<div class="controls">
							<input type="text" name="jform[' . $id . ']" id="jform_' . $id . '"  value="' . $vlu . '" class="' . $cssclass . '"     placeholder="Field Title"   maxlength="255" ' . $att . '  />
							<b>' . $lang->title . '</b>
						</div>

					</div>
					';

                        $moreThanOneLanguage = true; //More than one language installed
                    }
                    ?>

                    <hr/>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('type'); ?></div>
                    </div>

                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('typeparams'); ?></div>
                        <div class="controls">
                            <div class="typeparams_box" id="typeparams_box"></div>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="control-label"></div>
                        <div class="controls"><?php echo $this->form->getInput('typeparams'); ?></div>
                    </div>

                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('parent'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('parent'); ?></div>
                    </div>
                </div>
            </div>
            <?php echo HTMLHelper::_('bootstrap.endTab'); ?>

            <?php echo HTMLHelper::_('bootstrap.addTab', 'fieldsTab', 'optional', common::translate('COM_CUSTOMTABLES_FIELDS_OPTIONAL')); ?>
            <div class="row-fluid form-horizontal-desktop">
                <div class="span12">
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('isrequired'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('isrequired'); ?></div>
                    </div>

                    <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                        <div class="control-label"><?php echo $this->form->getLabel('defaultvalue'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('defaultvalue'); ?></div>
                    </div>

                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('allowordering'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('allowordering'); ?></div>
                    </div>

                    <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                        <div class="control-label"><?php echo $this->form->getLabel('valuerule'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('valuerule'); ?></div>
                    </div>

                    <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                        <div class="control-label"><?php echo $this->form->getLabel('valuerulecaption'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('valuerulecaption'); ?></div>
                    </div>
                </div>
            </div>
            <?php echo HTMLHelper::_('bootstrap.endTab'); ?>

            <?php

            $moreThanOneLanguage = false;
            foreach ($this->ct->Languages->LanguageList as $lang) {
                $id = 'description';
                if ($moreThanOneLanguage)
                    $id .= '_' . $lang->sef;

                HTMLHelper::_('bootstrap.addTab', 'fieldsTab', $id, common::translate('COM_CUSTOMTABLES_FIELDS_DESCRIPTION') . ' <b>' . $lang->title . '</b>');
                echo '
			<div id="' . $id . '" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">';

                $editor = Factory::getEditor();

                $item_array = (array)$this->item;
                $vlu = '';

                if (isset($item_array[$id]))
                    $vlu = $item_array[$id];

                echo '<textarea rows="10" cols="20" name="jform[' . $id . ']" id="jform_' . $id . '" style="width:100%;height:100%;"
				class="text_area" placeholder="Field Description" >' . $vlu . '</textarea>';

                echo '
					</div>
				</div>
			</div>';
                $moreThanOneLanguage = true; //More than one language installed
            }

            echo HTMLHelper::_('bootstrap.endTabSet'); ?>

            <div>
                <input type="hidden" name="task" value="fields.edit"/>
                <input type="hidden" name="tableid" value="<?php echo $this->tableid; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>

            <script>
                updateTypeParams("jform_type", "jform_typeparams", "typeparams_box", "Joomla");
                <?php if(!$this->ct->Env->advancedTagProcessor): ?>
                disableProField("jform_defaultvalue");
                disableProField("jform_valuerule");
                disableProField("jform_valuerulecaption");
                <?php endif; ?>
            </script>

        </div>

        <div id="ct_fieldtypeeditor_box"
             style="display: none;"><?php echo implode(',', common::folderList(CUSTOMTABLES_IMAGES_PATH)); ?></div>

    </form>
</div>

<!-- Modal content -->
<div id="ctModal" class="ctModal">
    <div id="ctModal_box" class="ctModal_content">
        <span id="ctModal_close" class="ctModal_close">&times;</span>
        <div id="ctModal_content"></div>
    </div>
</div>
<!-- end of the modal -->