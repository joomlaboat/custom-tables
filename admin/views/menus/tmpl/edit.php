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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();


$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

?>

<form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
    <div class="form-horizontal">

        <?php echo HTMLHelper::_('uitab.startTabSet', 'tablesTab', ['active' => 'table_and_layouts', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'table_and_layouts', common::translate('Table and Layouts')); ?>


        <div class="row-fluid form-horizontal-desktop">

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('establename'); ?></div>
                    <div class="controls"><?php

                        // Assume $categoryId is the ID you want to pass
                        echo $this->form->getInput('establename'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('establename')->description ?></small>
                        </div>

                    </div>


                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('escataloglayout'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('escataloglayout'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('escataloglayout')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('esitemlayout'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('esitemlayout'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('esitemlayout')->description ?></small>
                        </div>


                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('eseditlayout'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('eseditlayout'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('eseditlayout')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('esdetailslayout'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('esdetailslayout'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('esdetailslayout')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'filters', common::translate('Filter & Limit')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('filter'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('filter'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('filter')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('showpublished'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('showpublished'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('showpublished')->description ?></small>
                        </div>


                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('useridfield'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('useridfield'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('useridfield')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('recordstable'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('recordstable'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('recordstable')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('recordsuseridfield'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('recordsuseridfield'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('recordsuseridfield')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('recordsfield'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('recordsfield'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('recordsfield')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('groupby'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('groupby'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('groupby')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('limit'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('limit'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('limit')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'SORTBY', common::translate('Sort By (Order By)')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('sortby'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('sortby'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('sortby')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('forcesortby'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('forcesortby'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('forcesortby')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'ctpermissions', common::translate('COM_CUSTOMTABLES_CATEGORIES_PERMISSION')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('addusergroups'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('addusergroups'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('addusergroups')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('editusergroups'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('editusergroups'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('editusergroups')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('publishusergroups'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('publishusergroups'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('publishusergroups')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('deleteusergroups'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('deleteusergroups'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('deleteusergroups')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('publishstatus'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('publishstatus'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('publishstatus')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>


        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'SaveRecordAction', common::translate('Save Action')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('returnto'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('returnto'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('returnto')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('msgitemissaved'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('msgitemissaved'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('msgitemissaved')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'tablesTab', 'email-notification', common::translate('Email Notification')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('onrecordaddsendemaillayout'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('onrecordaddsendemaillayout'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('onrecordaddsendemaillayout')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('onrecordaddsendemail'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('onrecordaddsendemail'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('onrecordaddsendemail')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('onrecordaddsendemailto'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('onrecordaddsendemailto'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('onrecordaddsendemailto')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('onrecordsavesendemailto'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('onrecordsavesendemailto'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('onrecordsavesendemailto')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('sendemailcondition'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('sendemailcondition'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('sendemailcondition')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="span12">
                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('emailsentstatusfield'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('emailsentstatusfield'); ?>

                        <div id="jform_params_establename-desc" class="">
                            <small class="form-text"><?php echo $this->form->getField('emailsentstatusfield')->description ?></small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php

    $categoryId = common::inputGetInt('categoryid', 0);
    echo '<input type="hidden" name="task" value="' . $categoryId . '" />';

    ?>

    <input type="hidden" name="task" value="menus.edit"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>