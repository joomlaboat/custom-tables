<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/


// no direct access
use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\HTML\HTMLHelper;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

if ($this->ct->Env->frmt == 'html') {
    if (is_null($this->ct->Params->listing_id)) //there is no need to have a header if we are loading a single record.
    {
        if ($this->ct->Params->showPageHeading) {

            if ($this->ct->Params->pageTitle) {
                $title = common::translate($this->ct->Params->pageTitle);
                echo '<div class="page-header' . ($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">' . $title . '</h2></div>';
            }
        }
    }
}
/*
if ($this->ct->Env->frmt == 'json') {
    if (ob_get_contents()) ob_end_clean();

    $filename = $this->ct->Params->pageTitle;
    if (is_null($filename))
        $filename = 'ct';

    $filename = JoomlaBasicMisc::makeNewFileName($filename, 'json');

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: application/json; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");

    $pageLayoutContent = $this->catalog->render();

    die($pageLayoutContent);
}
*/
echo $this->catalog->render();

if ($this->ct->Env->frmt == 'html') {
    if (isset($this->ct->LayoutVariables['ordering_field_type_found']) and $this->ct->LayoutVariables['ordering_field_type_found']) {

        $orderby_pair = explode(' ', $this->ct->Ordering->orderby);
        $edit_userGroup = (int)$this->ct->Params->editUserGroups;
        $isEditable = CTUser::checkIfRecordBelongsToUser($this->ct, $edit_userGroup);

        if ($isEditable) {

            $saveOrderingUrl = 'index.php?option=com_customtables&view=catalog&task=ordering&tableid=' . $this->ct->Table->tableid . '&tmpl=component&clean=1';
            if ($this->ct->Env->version < 4) {
                JHtml::_('sortablelist.sortable', 'ctTable_' . $this->ct->Table->tableid, 'ctTableForm_' . $this->ct->Table->tableid, 'asc', $saveOrderingUrl);
            } else {
                HTMLHelper::_('draggablelist.draggable');
            }
        }
    }

    if (is_null($this->ct->Params->listing_id)) //there is no need to have a header if we are loading a single record.
    {
        echo '<!-- Modal content -->
<div id="ctModal" class="ctModal">
    <div id="ctModal_box" class="ctModal_content">
        <span id="ctModal_close" class="ctModal_close">&times;</span>
        <div id="ctModal_content"></div>
    </div>
</div>
<!-- end of the modal -->';
    } else {
        die;
    }
}