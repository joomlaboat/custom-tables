<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use CustomTables\CTUser;
use \Joomla\CMS\Factory;
use \JoomlaBasicMisc;
use \Joomla\CMS\Uri\Uri;

class RecordToolbar
{
    var CT $ct;
    var $Table;
    var $isEditable;
    var $isPublishable;
    var $isDeletable;
    var $jinput;
    var $listing_id;
    var $rid;
    var $row;
    var $iconPath;

    function __construct(CT &$ct, $isEditable, $isPublishable, $isDeletable)
    {
        $this->ct = $ct;
        $this->Table = $ct->Table;
        $this->isEditable = $isEditable;
        $this->isPublishable = $isPublishable;
        $this->isDeletable = $isDeletable;
        $this->jinput = Factory::getApplication()->input;
        $this->iconPath = Uri::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/';
    }

    public function render($row, $mode)
    {
        $this->listing_id = $row[$this->Table->realidfieldname];
        $this->rid = $this->Table->tableid . 'x' . $this->listing_id;
        $this->row = $row;

        if ($this->isEditable) {
            switch ($mode) {
                case 'edit':
                    return $this->renderEditIcon();

                case 'editmodal':
                    return $this->renderEditIcon(true);

                case 'refresh':
                    $rid = 'esRefreshIcon' . $this->rid;
                    $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_REFRESH');

                    if ($this->ct->Env->toolbaricons != '')
                        $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-sync" data-icon="' . $this->ct->Env->toolbaricons . ' fa-sync" title="' . $alt . '"></i>';
                    else
                        $img = '<img src="' . $this->iconPath . 'refresh.png" border="0" alt="' . $alt . '" title="' . $alt . '">';


                    $href = 'javascript:ctRefreshRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',' . (int)$this->ct->Params->ModuleId . ');';

                    return '<div id="' . $rid . '" class="toolbarIcons"><a href="' . $href . '">' . $img . '</a></div>';

                case 'gallery':
                    if (is_array($this->Table->imagegalleries) and count($this->Table->imagegalleries) > 0)
                        return $this->renderImageGalleryIcon();
                    else
                        return '';

                case 'filebox':
                    if (is_array($this->Table->fileboxes) and count($this->Table->fileboxes) > 0)
                        return $this->renderFileBoxIcon();
                    else
                        return '';

                case 'copy':
                    return $this->renderCopyIcon();

                case 'resetpassword':
                    return $this->renderResetPasswordIcon();
            }
        }

        if ($this->isDeletable and $mode == 'delete')
            return $this->renderDeleteIcon();
        elseif ($mode == 'publish')
            return $this->renderPublishIcon();
        elseif ($mode == 'checkbox')
            return '<input type="checkbox" name="esCheckbox' . $this->Table->tableid . '" id="esCheckbox' . $this->rid . '" value="' . $this->listing_id . '" />';

        return '';
    }

    protected function renderEditIcon($isModal = false)
    {
        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-pen" data-icon="' . $this->ct->Env->toolbaricons . ' fa-pen" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . $this->iconPath . 'edit.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

        $editlink = $this->ct->Env->WebsiteRoot . 'index.php?option=com_customtables&amp;view=edititem'
            . '&amp;listing_id=' . $this->listing_id;

        if ($this->jinput->get('tmpl', '', 'CMD') != '')
            $editlink .= '&tmpl=' . $this->jinput->get('tmpl', '', 'CMD');

        if ($this->ct->Params->ItemId > 0)
            $editlink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

        if (!is_null($this->ct->Params->ModuleId))
            $editlink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

        if ($isModal) {
            $tmp_current_url = base64_encode($this->ct->Env->current_url);//To have  the returnto link that may include listing_id param.
            $editlink .= '&amp;returnto=' . $tmp_current_url;

            $link = 'javascript:ctEditModal(\'' . $editlink . '\')';
            $a = '<a href="' . $link . '">' . $img . '</a>';
        } else {
            $returnto = base64_encode($this->ct->Env->current_url);
            $link = $editlink . '&amp;returnto=' . $returnto;

            $a = '<a href="' . $link . '">' . $img . '</a>';
        }

        return '<div id="esEditIcon' . $this->rid . '" class="toolbarIcons">' . $a . '</div>';
    }

    protected function renderImageGalleryIcon()
    {
        $imagegalleries = [];
        foreach ($this->Table->imagegalleries as $gallery) {
            $imagemanagerlink = 'index.php?option=com_customtables&amp;view=editphotos'
                . '&amp;establename=' . $this->Table->tablename
                . '&amp;galleryname=' . $gallery[0]
                . '&amp;listing_id=' . $this->listing_id
                . '&amp;returnto=' . $this->ct->Env->encoded_current_url;

            if ($this->jinput->get('tmpl', '', 'CMD') != '')
                $imagemanagerlink .= '&tmpl=' . $this->jinput->get('tmpl', '', 'CMD');

            if ($this->ct->Params->ItemId > 0)
                $imagemanagerlink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

            if (!is_null($this->ct->Params->ModuleId))
                $imagemanagerlink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

            $alt = $gallery[1];

            if ($this->ct->Env->toolbaricons != '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-image" data-icon="' . $this->ct->Env->toolbaricons . ' fa-image" title="' . $alt . '"></i>';
            else
                $img = '<img src="' . $this->iconPath . 'photomanager.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

            $imagegalleries[] = '<div id="esImageGalleryIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $this->ct->Env->WebsiteRoot . $imagemanagerlink . '">' . $img . '</a></div>';

        }
        return implode('', $imagegalleries);
    }

    protected function renderFileBoxIcon()
    {
        $fileboxes = [];

        foreach ($this->Table->fileboxes as $filebox) {
            $filemanagerlink = 'index.php?option=com_customtables&amp;view=editfiles'
                . '&amp;establename=' . $this->Table->tablename
                . '&amp;fileboxname=' . $filebox[0]
                . '&amp;listing_id=' . $this->listing_id
                . '&amp;returnto=' . $this->ct->Env->encoded_current_url;

            if ($this->jinput->get('tmpl', '', 'CMD') != '')
                $filemanagerlink .= '&tmpl=' . $this->jinput->get('tmpl', '', 'CMD');

            if ($this->ct->Params->ItemId > 0)
                $filemanagerlink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

            if (!is_null($this->ct->Params->ModuleId))
                $filemanagerlink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

            //$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_MANAGER').' ('.$filebox[1].')';
            $alt = $filebox[1];

            if ($this->ct->Env->toolbaricons != '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-folder" data-icon="' . $this->ct->Env->toolbaricons . ' fa-folder" title="' . $alt . '"></i>';
            else
                $img = '<img src="' . $this->iconPath . 'filemanager.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

            $fileboxes[] = '<div id="esFileBoxIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $this->ct->Env->WebsiteRoot . $filemanagerlink . '">' . $img . '</a></div>';
        }

        return implode('', $fileboxes);
    }

    protected function renderCopyIcon()
    {
        $Label = 'Would you like to copy (' . $this->firstFieldValueLabel() . ')?';

        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_COPY');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-copy" data-icon="' . $this->ct->Env->toolbaricons . ' fa-copy" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . $this->iconPath . 'copy.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

        $href = 'javascript:ctCopyObject("' . $Label . '", ' . $this->listing_id . ', "ctCopyIcon' . $this->rid . '",' . (int)$this->ct->Params->ModuleId . ')';
        return '<div id="ctCopyIcon' . $this->rid . '" class="toolbarIcons"><a href=\'' . $href . '\'>' . $img . '</a></div>';
    }

    protected function firstFieldValueLabel()
    {
        if (is_null($this->Table->fields))
            return null;

        $min_ordering = 99999999;

        $fieldtitlevalue = '';

        foreach ($this->Table->fields as $mFld) {
            $ordering = (int)$mFld['ordering'];
            if ($mFld['type'] != 'dummy' and $ordering < $min_ordering) {
                $min_ordering = $ordering;
                $fieldtitlevalue = $this->getFieldCleanValue4RDI($mFld);
            }
        }

        return substr($fieldtitlevalue, -100);
    }

    protected function getFieldCleanValue4RDI(&$mFld)
    {
        $titlefield = $mFld['realfieldname'];
        if (strpos($mFld['type'], 'multi') !== false)
            $titlefield .= $this->ct->Languages->Postfix;

        $fieldtitlevalue = $this->row[$titlefield];
        $deleteLabel = strip_tags($fieldtitlevalue);

        $deleteLabel = trim(preg_replace("/[^a-zA-Z0-9 ,.]/", "", $deleteLabel));
        $deleteLabel = preg_replace('/\s{3,}/', ' ', $deleteLabel);

        return $deleteLabel;
    }

    protected function renderResetPasswordIcon()
    {
        if (isset($this->row[$this->Table->useridrealfieldname]))
            $realuserid = $this->row[$this->Table->useridrealfieldname];
        else
            $realuserid = 0;

        if ($realuserid == 0) {
            $rid = 'ctCreateUserIcon' . $this->rid;
            $alt = 'Create User Account';

            if ($this->ct->Env->toolbaricons != '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-user-plus" data-icon="' . $this->ct->Env->toolbaricons . ' fa-user-plus" title="' . $alt . '"></i>';
            else
                $img = '<img src="' . $this->iconPath . 'key-add.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

            $resetLabel = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USERWILLBECREATED') . ' ' . $this->firstFieldValueLabel();
            $action = 'ctCreateUser("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . (int)$this->ct->Params->ModuleId . ')';
        } else {
            $userrow = CTUser::GetUserRow($realuserid);
            if ($userrow !== null) {

                $user_full_name = ucwords(strtolower($userrow['name']));

                $rid = 'ctResetPasswordIcon' . $this->rid;
                $alt = 'Username: ' . $userrow['username'];

                if ($this->ct->Env->toolbaricons != '')
                    $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-user" data-icon="' . $this->ct->Env->toolbaricons . ' fa-user" title="' . $alt . '"></i>';
                else
                    $img = '<img src="' . $this->iconPath . 'key.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

                $resetLabel = 'Would you like to reset ' . $user_full_name . ' (' . $userrow['username'] . ') password?';
                $action = 'ctResetPassword("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . (int)$this->ct->Params->ModuleId . ')';
            } else
                return 'User account deleted, open and save the record.';
        }

        return '<div id="' . $rid . '" class="toolbarIcons"><a href=\'javascript:' . $action . ' \'>' . $img . '</a></div>';
    }

    protected function renderDeleteIcon()
    {
        $deleteLabel = $this->firstFieldValueLabel();

        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-trash" data-icon="' . $this->ct->Env->toolbaricons . ' fa-trash" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . $this->iconPath . 'delete.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

        $msg = 'Do you want to delete (' . $deleteLabel . ')?';

        //ctDeleteRecord(msg, tableid, recordid, toolbarboxid, custom_link)
        $href = 'javascript:ctDeleteRecord(\'' . $msg . '\', ' . $this->Table->tableid . ', \'' . $this->listing_id . '\', \'esDeleteIcon' . $this->rid . '\', ' . (int)$this->ct->Params->ModuleId . ');';
        return '<div id="esDeleteIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $href . '">' . $img . '</a></div>';
    }

    protected function renderPublishIcon()
    {
        if ($this->isPublishable) {
            $rid = 'esPublishIcon' . $this->rid;

            if ($this->row['listing_published']) {
                $link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',0,' . (int)$this->ct->Params->ModuleId . ');';
                $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNPUBLISH');

                if ($this->ct->Env->toolbaricons != '')
                    $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-check-circle" data-icon="' . $this->ct->Env->toolbaricons . ' fa-check-circle" title="' . $alt . '"></i>';
                else
                    $img = '<img src="' . $this->iconPath . 'publish.png" border="0" alt="' . $alt . '" title="' . $alt . '">';
            } else {
                $link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',1,' . (int)$this->ct->Params->ModuleId . ');';
                $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISH');

                if ($this->ct->Env->toolbaricons != '')
                    $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-ban" data-icon="' . $this->ct->Env->toolbaricons . ' fa-ban" title="' . $alt . '"></i>';
                else
                    $img = '<img src="' . $this->iconPath . 'unpublish.png" border="0" alt="' . $alt . '" title="' . $alt . '">';
            }
            return '<div id="' . $rid . '" class="toolbarIcons"><a href="' . $link . '">' . $img . '</a></div>';
        } else {
            if (!$this->row['listing_published'])
                return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED');
        }
        return '';
    }
}