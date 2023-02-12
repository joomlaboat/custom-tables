<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use CT_FieldTypeTag_FileBox;
use JoomlaBasicMisc;
use Joomla\CMS\Uri\Uri;

class RecordToolbar
{
    var CT $ct;
    var Table $Table;
    var bool $isEditable;
    var bool $isPublishable;
    var bool $isDeletable;
    var ?string $listing_id;
    var string $rid;//Record ID
    var array $row;
    var string $iconPath;

    function __construct(CT $ct, $isEditable, $isPublishable, $isDeletable)
    {
        $this->ct = $ct;
        $this->Table = $ct->Table;
        $this->isEditable = $isEditable;
        $this->isPublishable = $isPublishable;
        $this->isDeletable = $isDeletable;
        $this->iconPath = Uri::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/';
    }

    public function render(array $row, $mode)
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
                        return $this->renderFileBoxIcons();
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
            return '<input type="checkbox" onClick="ctUpdateCheckboxCounter(' . $this->Table->tableid . ')" name="esCheckbox' . $this->Table->tableid . '" id="esCheckbox' . $this->rid . '" value="' . $this->listing_id . '" />';

        return '';
    }

    protected function renderEditIcon($isModal = false): string
    {
        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-pen" data-icon="' . $this->ct->Env->toolbaricons . ' fa-pen" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . $this->iconPath . 'edit.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

        $editLink = $this->ct->Env->WebsiteRoot . 'index.php?option=com_customtables&amp;view=edititem'
            . '&amp;listing_id=' . $this->listing_id;

        if ($this->ct->Env->jinput->get('tmpl', '', 'CMD') != '')
            $editLink .= '&tmpl=' . $this->ct->Env->jinput->get('tmpl', '', 'CMD');

        if ($this->ct->Params->ItemId > 0)
            $editLink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

        if (!is_null($this->ct->Params->ModuleId))
            $editLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

        if ($isModal) {
            $tmp_current_url = base64_encode($this->ct->Env->current_url);//To have the returnto link that may include listing_id param.
            $editLink .= '&amp;returnto=' . $tmp_current_url;
            $link = 'javascript:ctEditModal(\'' . $editLink . '\')';
        } else {
            $returnto = base64_encode($this->ct->Env->current_url);
            $link = $editLink . '&amp;returnto=' . $returnto;
        }
        $a = '<a href="' . $link . '">' . $img . '</a>';

        return '<div id="esEditIcon' . $this->rid . '" class="toolbarIcons">' . $a . '</div>';
    }

    protected function renderImageGalleryIcon(): string
    {
        $imageGalleries = [];
        foreach ($this->Table->imagegalleries as $gallery) {
            $imageManagerLink = 'index.php?option=com_customtables&amp;view=editphotos'
                . '&amp;establename=' . $this->Table->tablename
                . '&amp;galleryname=' . $gallery[0]
                . '&amp;listing_id=' . $this->listing_id
                . '&amp;returnto=' . $this->ct->Env->encoded_current_url;

            if ($this->ct->Env->jinput->get('tmpl', '', 'CMD') != '')
                $imageManagerLink .= '&tmpl=' . $this->ct->Env->jinput->get('tmpl', '', 'CMD');

            if ($this->ct->Params->ItemId > 0)
                $imageManagerLink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

            if (!is_null($this->ct->Params->ModuleId))
                $imageManagerLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

            $alt = $gallery[1];

            if ($this->ct->Env->toolbaricons != '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-image" data-icon="' . $this->ct->Env->toolbaricons . ' fa-image" title="' . $alt . '"></i>';
            else
                $img = '<img src="' . $this->iconPath . 'photomanager.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

            $imageGalleries[] = '<div id="esImageGalleryIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $this->ct->Env->WebsiteRoot . $imageManagerLink . '">' . $img . '</a></div>';

        }
        return implode('', $imageGalleries);
    }

    protected function renderFileBoxIcons(): string
    {
        $fileBoxes = [];

        foreach ($this->Table->fileboxes as $fileBox)
            $fileBoxes[] = CT_FieldTypeTag_FileBox::renderFileBoxIcon($this->ct, $this->listing_id, $fileBox[0], $fileBox[1]);

        return implode('', $fileBoxes);
    }

    protected function renderCopyIcon(): string
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

    protected function firstFieldValueLabel(): ?string
    {
        if (is_null($this->Table->fields))
            return null;

        $min_ordering = 99999999;
        $fieldTitleValue = '';

        foreach ($this->Table->fields as $mFld) {
            $ordering = (int)$mFld['ordering'];
            if ($mFld['type'] != 'dummy' and $ordering < $min_ordering) {
                $min_ordering = $ordering;
                $fieldTitleValue = $this->getFieldCleanValue4RDI($mFld);
            }
        }
        return substr($fieldTitleValue, -100);
    }

    protected function getFieldCleanValue4RDI($mFld): string
    {
        $titleField = $mFld['realfieldname'];
        if (str_contains($mFld['type'], 'multi'))
            $titleField .= $this->ct->Languages->Postfix;

        $fieldTitleValue = $this->row[$titleField];
        $deleteLabel = strip_tags($fieldTitleValue);

        $deleteLabel = trim(preg_replace("/[^a-zA-Z\d ,.]/", "", $deleteLabel));
        return preg_replace('/\s{3,}/', ' ', $deleteLabel);
    }

    protected function renderResetPasswordIcon(): string
    {
        $realUserId = $this->row[$this->Table->useridrealfieldname] ?? 0;

        if ($realUserId == 0) {
            $rid = 'ctCreateUserIcon' . $this->rid;
            $alt = 'Create User Account';

            if ($this->ct->Env->toolbaricons != '')
                $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-user-plus" data-icon="' . $this->ct->Env->toolbaricons . ' fa-user-plus" title="' . $alt . '"></i>';
            else
                $img = '<img src="' . $this->iconPath . 'key-add.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

            $resetLabel = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USERWILLBECREATED') . ' ' . $this->firstFieldValueLabel();
            $action = 'ctCreateUser("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . (int)$this->ct->Params->ModuleId . ')';
        } else {
            $userRow = CTUser::GetUserRow($realUserId);
            if ($userRow !== null) {

                $user_full_name = ucwords(strtolower($userRow['name']));

                $rid = 'ctResetPasswordIcon' . $this->rid;
                $alt = 'Username: ' . $userRow['username'];

                if ($this->ct->Env->toolbaricons != '')
                    $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-user" data-icon="' . $this->ct->Env->toolbaricons . ' fa-user" title="' . $alt . '"></i>';
                else
                    $img = '<img src="' . $this->iconPath . 'key.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

                $resetLabel = 'Would you like to reset ' . $user_full_name . ' (' . $userRow['username'] . ') password?';
                $action = 'ctResetPassword("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . (int)$this->ct->Params->ModuleId . ')';
            } else
                return 'User account deleted, open and save the record.';
        }

        return '<div id="' . $rid . '" class="toolbarIcons"><a href=\'javascript:' . $action . ' \'>' . $img . '</a></div>';
    }

    protected function renderDeleteIcon(): string
    {
        $deleteLabel = $this->firstFieldValueLabel();

        $alt = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE');

        if ($this->ct->Env->toolbaricons != '')
            $img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbaricons . ' fa-trash" data-icon="' . $this->ct->Env->toolbaricons . ' fa-trash" title="' . $alt . '"></i>';
        else
            $img = '<img src="' . $this->iconPath . 'delete.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

        $msg = 'Do you want to delete (' . $deleteLabel . ')?';
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