<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Exception;

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
		$this->iconPath = CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/';
	}

	public function render(array $row, $mode): string
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
					$alt = common::translate('COM_CUSTOMTABLES_REFRESH');

					if ($this->ct->Env->toolbarIcons != '')
						$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-sync" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-sync" title="' . $alt . '"></i>';
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
		if (defined('WPINC'))
			return 'CustomTables: Edit Icons not supported in WP yet.';

		$alt = common::translate('COM_CUSTOMTABLES_EDIT');

		if ($this->ct->Env->toolbarIcons != '')
			$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-pen" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-pen" title="' . $alt . '"></i>';
		else
			$img = '<img src="' . $this->iconPath . 'edit.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

		$editLink = $this->ct->Env->WebsiteRoot . 'index.php?option=com_customtables&amp;view=edititem'
			. '&amp;listing_id=' . $this->listing_id;

		if (common::inputGetCmd('tmpl'))
			$editLink .= '&tmpl=' . common::inputGetCmd('tmpl', '');

		if ($this->ct->Params->ItemId > 0)
			$editLink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

		if (!is_null($this->ct->Params->ModuleId))
			$editLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

		if ($isModal) {
			$tmp_current_url = common::makeReturnToURL($this->ct->Env->current_url);//To have the returnto link that may include listing_id param.
			$editLink .= '&amp;returnto=' . $tmp_current_url;
			$link = 'javascript:ctEditModal(\'' . $editLink . '\',null)';
		} else {
			$returnToEncoded = common::getReturnToURL(false);

			if (!empty($returnToEncoded))
				$link = $editLink . '&amp;returnto=' . $returnToEncoded;
			else
				$link = $editLink;
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

			if (common::inputGetCmd('tmpl'))
				$imageManagerLink .= '&tmpl=' . common::inputGetCmd('tmpl', '');

			if ($this->ct->Params->ItemId > 0)
				$imageManagerLink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

			if (!is_null($this->ct->Params->ModuleId))
				$imageManagerLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

			$alt = $gallery[1];

			if ($this->ct->Env->toolbarIcons != '')
				$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-image" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-image" title="' . $alt . '"></i>';
			else
				$img = '<img src="' . $this->iconPath . 'photomanager.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

			$imageGalleries[] = '<div id="esImageGalleryIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $this->ct->Env->WebsiteRoot . $imageManagerLink . '">' . $img . '</a></div>';

		}
		return implode('', $imageGalleries);
	}

	protected function renderFileBoxIcons(): string
	{
		$fileBoxes = [];

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
			. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

		foreach ($this->Table->fileboxes as $fileBox)
			$fileBoxes[] = InputBox_filebox::renderFileBoxIcon($this->ct, $this->listing_id, $fileBox[0], $fileBox[1]);

		return implode('', $fileBoxes);
	}

	protected function renderCopyIcon(): string
	{
		$Label = 'Would you like to copy (' . $this->firstFieldValueLabel() . ')?';

		$alt = common::translate('COM_CUSTOMTABLES_COPY');

		if ($this->ct->Env->toolbarIcons != '')
			$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-copy" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-copy" title="' . $alt . '"></i>';
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
		$min_ordering_field = null;

		foreach ($this->Table->fields as $mFld) {
			$ordering = (int)$mFld['ordering'];
			if ($mFld['type'] != 'dummy' and $ordering < $min_ordering) {
				if ($mFld['type'] != 'virtual' and !Fields::isVirtualField($mFld)) {
					if ($ordering < $min_ordering) {
						$min_ordering = $ordering;
						$min_ordering_field = $mFld;
					}
				}
			}
		}
		if ($min_ordering_field !== null) {
			$fieldTitleValue = $this->getFieldCleanValue4RDI($min_ordering_field);
			return substr($fieldTitleValue, -100);
		}
		return null;
	}

	protected function getFieldCleanValue4RDI($mFld): string
	{
		$titleField = $mFld['realfieldname'];
		if (str_contains($mFld['type'], 'multi'))
			$titleField .= $this->ct->Languages->Postfix;

		$fieldTitleValue = $this->row[$titleField];
		$deleteLabel = common::ctStripTags($fieldTitleValue ?? '');

		$deleteLabel = trim(preg_replace("/[^a-zA-Z\d ,.]/", "", $deleteLabel));
		return preg_replace('/\s{3,}/', ' ', $deleteLabel);
	}

	protected function renderResetPasswordIcon(): string
	{
		$realUserId = $this->row[$this->Table->useridrealfieldname] ?? 0;

		if ($realUserId == 0)
			$userRow = null;
		else {
			$userRow = CTUser::GetUserRow($realUserId);

			if ($userRow === null) {
				//User account deleted, null record value.
				$data = [$this->Table->useridrealfieldname => null];
				try {
					$whereClauseUpdate = new MySQLWhereClause();
					$whereClauseUpdate->addCondition($this->Table->realidfieldname, $this->row[$this->Table->realidfieldname]);

					database::update($this->Table->realtablename, $data, $whereClauseUpdate);
				} catch (Exception $e) {
					return $e->getMessage();
				}
			}
		}

		if ($userRow === null) {
			$rid = 'ctCreateUserIcon' . $this->rid;
			$alt = 'Create User Account';

			if ($this->ct->Env->toolbarIcons != '')
				$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-user-plus" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-user-plus" title="' . $alt . '"></i>';
			else
				$img = '<img src="' . $this->iconPath . 'key-add.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

			$resetLabel = common::translate('COM_CUSTOMTABLES_USERWILLBECREATED') . ' ' . $this->firstFieldValueLabel();
			$action = 'ctCreateUser("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . ($this->ct->Params->ModuleId ?? 0) . ')';
		} else {
			$user_full_name = ucwords(strtolower($userRow['name']));

			$rid = 'ctResetPasswordIcon' . $this->rid;
			$alt = 'Username: ' . $userRow['username'];

			if ($this->ct->Env->toolbarIcons != '')
				$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-user" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-user" title="' . $alt . '"></i>';
			else
				$img = '<img src="' . $this->iconPath . 'key.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

			$resetLabel = 'Would you like to reset ' . $user_full_name . ' (' . $userRow['username'] . ') password?';
			$action = 'ctResetPassword("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . $this->ct->Params->ModuleId . ')';
		}
		return '<div id="' . $rid . '" class="toolbarIcons"><a href=\'javascript:' . $action . ' \'>' . $img . '</a></div>';
	}

	protected function renderDeleteIcon(): string
	{
		$deleteLabel = $this->firstFieldValueLabel();

		$alt = common::translate('COM_CUSTOMTABLES_DELETE');

		if ($this->ct->Env->toolbarIcons != '')
			$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-trash" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-trash" title="' . $alt . '"></i>';
		else
			$img = '<img src="' . $this->iconPath . 'delete.png" border="0" alt="' . $alt . '" title="' . $alt . '">';

		$msg = 'Do you want to delete (' . $deleteLabel . ')?';
		$href = 'javascript:ctDeleteRecord(\'' . $msg . '\', ' . $this->Table->tableid . ', \'' . $this->listing_id . '\', \'esDeleteIcon' . $this->rid . '\', ' . $this->ct->Params->ModuleId . ');';
		return '<div id="esDeleteIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $href . '">' . $img . '</a></div>';
	}

	protected function renderPublishIcon(): string
	{
		if ($this->isPublishable) {
			$rid = 'esPublishIcon' . $this->rid;

			if ($this->row['listing_published']) {
				$link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',0,' . $this->ct->Params->ModuleId . ');';
				$alt = common::translate('COM_CUSTOMTABLES_UNPUBLISH');

				if ($this->ct->Env->toolbarIcons != '')
					$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-check-circle" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-check-circle" title="' . $alt . '"></i>';
				else
					$img = '<img src="' . $this->iconPath . 'publish.png" border="0" alt="' . $alt . '" title="' . $alt . '">';
			} else {
				$link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',1,' . $this->ct->Params->ModuleId . ');';
				$alt = common::translate('COM_CUSTOMTABLES_PUBLISH');

				if ($this->ct->Env->toolbarIcons != '')
					$img = '<i class="ba-btn-transition ' . $this->ct->Env->toolbarIcons . ' fa-ban" data-icon="' . $this->ct->Env->toolbarIcons . ' fa-ban" title="' . $alt . '"></i>';
				else
					$img = '<img src="' . $this->iconPath . 'unpublish.png" border="0" alt="' . $alt . '" title="' . $alt . '">';
			}
			return '<div id="' . $rid . '" class="toolbarIcons"><a href="' . $link . '">' . $img . '</a></div>';
		} else {
			if (!$this->row['listing_published'])
				return common::translate('COM_CUSTOMTABLES_PUBLISHED');
		}
		return '';
	}
}