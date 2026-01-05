<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Exception;

class RecordToolbar
{
	var CT $ct;
	var Table $Table;
	var bool $isAddable;
	var bool $isEditable;
	var bool $isPublishable;
	var bool $isDeletable;
	var ?string $listing_id;
	var string $rid;//Record ID
	var array $row;
	var string $iconPath;

	function __construct(CT $ct, $isAddable, $isEditable, $isPublishable, $isDeletable)
	{
		$this->ct = $ct;
		$this->Table = $ct->Table;
		$this->isAddable = $isAddable;
		$this->isEditable = $isEditable;
		$this->isPublishable = $isPublishable;
		$this->isDeletable = $isDeletable;
		$this->iconPath = CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/';
	}

	/**
	 * @throws Exception
	 * @since 3.2.7
	 */
	public function render(array $row, string $mode, bool $reload = false): string
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
					$rid = 'ctRefreshIcon' . $this->rid;
					$icon = Icons::iconRefresh($this->ct->Env->toolbarIcons);
					$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
					$href = 'javascript:ctRefreshRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',' . $moduleIDString . ');';

					return '<div id="' . $rid . '" class="toolbarIcons"><a href="' . $href . '">' . $icon . '</a></div>';

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

				case 'resetpassword':
					return $this->renderResetPasswordIcon();
			}
		}

		if ($this->isAddable and $mode == 'copy')
			return $this->renderCopyIcon();
		elseif ($this->isDeletable and $mode == 'delete')
			return $this->renderDeleteIcon($reload);
		elseif ($this->isPublishable and $mode == 'publish')
			return $this->renderPublishIcon();
		elseif ($mode == 'checkbox')
			return '<input type="checkbox" onClick="ctUpdateCheckboxCounter(' . $this->Table->tableid . ')" name="esCheckbox' . $this->Table->tableid . '" id="esCheckbox' . $this->rid . '" value="' . $this->listing_id . '" />';

		return '';
	}

	protected function renderEditIcon($isModal = false): string
	{
		if (defined('_JEXEC')) {
			$editLink = $this->ct->Env->WebsiteRoot . 'index.php?option=com_customtables&amp;view=edititem'
				. '&amp;listing_id=' . $this->listing_id;

			if (common::inputGetCmd('tmpl'))
				$editLink .= '&tmpl=' . common::inputGetCmd('tmpl', '');

			if ($this->ct->Params->ItemId > 0)
				$editLink .= '&amp;Itemid=' . $this->ct->Params->ItemId;

			if (!empty($this->ct->Params->ModuleId))
				$editLink .= '&amp;ModuleId=' . $this->ct->Params->ModuleId;

			if ($isModal) {
				$tmp_current_url = common::makeReturnToURL($this->ct->Env->current_url);//To have the returnto link that may include listing_id param.


				$editLink .= '&amp;returnto=' . $tmp_current_url;
				$link = 'javascript:ctEditModal(\'' . $editLink . '\',null)';
			} else {
				$returnToEncoded = base64_encode(common::curPageURL());
				$link = $editLink . '&amp;returnto=' . $returnToEncoded;
			}
		} elseif (defined('WPINC')) {
			$link = common::curPageURL();
			$link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);
			$link = CTMiscHelper::deleteURLQueryOption($link, 'listing_id');
			$link .= (str_contains($link, '?') ? '&amp;' : '?') . 'view' . $this->ct->Table->tableid . '=edititem';
			$link .= '&amp;listing_id=' . $this->listing_id;

			if (!empty($this->ct->Env->encoded_current_url))
				$link .= '&amp;returnto=' . $this->ct->Env->encoded_current_url;

		} else {
			$link = '';
		}

		$icon = Icons::iconEdit($this->ct->Env->toolbarIcons);
		$a = '<a href="' . $link . '">' . $icon . '</a>';

		return '<div id="ctEditIcon' . $this->rid . '" class="toolbarIcons">' . $a . '</div>';
	}

	protected function renderImageGalleryIcon(): string
	{
		$imageGalleries = [];
		if (defined('_JEXEC')) {

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

				$icon = Icons::iconPhotoManager($this->ct->Env->toolbarIcons, $gallery[1]);
				$imageGalleries[] = '<div id="esImageGalleryIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $this->ct->Env->WebsiteRoot . $imageManagerLink . '">' . $icon . '</a></div>';
			}
		} elseif (defined('WPINC')) {

		}
		return implode('', $imageGalleries);
	}

	protected function renderFileBoxIcons(): string
	{
		$fileBoxes = [];
		if (defined('_JEXEC')) {

			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
				. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

			foreach ($this->Table->fileboxes as $fileBox)
				$fileBoxes[] = InputBox_filebox::renderFileBoxIcon($this->ct, $this->listing_id, $fileBox[0], $fileBox[1]);

		} elseif (defined('WPINC')) {

		}
		return implode('', $fileBoxes);
	}

	/**
	 * @throws Exception
	 * @since 3.2.7
	 */
	protected function renderResetPasswordIcon(): string
	{
		if (defined('_JEXEC')) {
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
				$icon = Icons::iconCreateUser($this->ct->Env->toolbarIcons);
				$resetLabel = common::translate('COM_CUSTOMTABLES_USERWILLBECREATED') . ' ' . $this->firstFieldValueLabel();
				$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
				$action = 'ctCreateUser("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . $moduleIDString . ')';
			} else {
				$user_full_name = ucwords(strtolower($userRow['name']));

				$rid = 'ctResetPasswordIcon' . $this->rid;
				$title = common::translate('COM_CUSTOMTABLES_FIELDS_USER ') . ': ' . $userRow['username'];
				$icon = Icons::iconResetPassword($this->ct->Env->toolbarIcons, $title);
				$resetLabel = 'Would you like to reset ' . $user_full_name . ' (' . $userRow['username'] . ') password?';
				$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
				$action = 'ctResetPassword("' . $resetLabel . '", ' . $this->listing_id . ', "' . $rid . '",' . $moduleIDString . ')';
			}
			return '<div id="' . $rid . '" class="toolbarIcons"><a href=\'javascript:' . $action . ' \'>' . $icon . '</a></div>';
		} elseif (defined('WPINC')) {
			return '';
		}
		return '';
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
					$min_ordering = $ordering;
					$min_ordering_field = $mFld;
				}
			}
		}
		if ($min_ordering_field !== null) {

			//$fieldRow = $this->ct->Table->getFieldByName()

			$valueProcessor = new Value($this->ct);
			$fieldTitleValue = $valueProcessor->renderValue($min_ordering_field, $this->ct->Table->record, [], true);

			//$fieldTitleValue = $this->getFieldCleanValue4RDI($min_ordering_field);
			return $fieldTitleValue;//substr($fieldTitleValue, -100);
		}
		return null;
	}

	protected function renderCopyIcon(): string
	{
		if (defined('_JEXEC')) {
			$icon = Icons::iconCopy($this->ct->Env->toolbarIcons);
			$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;
			$href = 'javascript:ctCopyRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $this->rid . '\',' . $moduleIDString . ');';

			return '<div id="ctCopyIcon' . $this->rid . '" class="toolbarIcons"><a href="' . $href . '">' . $icon . '</a></div>';
		} elseif (defined('WPINC')) {
			return '';
		}
		return '';
	}

	protected function renderDeleteIcon(bool $reload = false): string
	{
		$deleteLabel = $this->firstFieldValueLabel();
		$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;

		$href = 'javascript:ctDeleteRecord(' . $this->Table->tableid . ', \'' . $this->listing_id . '\', ' . $moduleIDString . ',' . ($reload ? ' true' : ' false') . ');';

		$messageDiv = '<div id="ctDeleteMessage' . $this->rid . '" style="display:none;">Do you want to delete ' . $deleteLabel . '?</div>';
		$a = '<a href="' . $href . '">' . Icons::iconDelete($this->ct->Env->toolbarIcons) . '</a>';
		$result = '<div id="ctDeleteIcon' . $this->rid . '" class="toolbarIcons">' . $messageDiv . $a . '</div>';;

		return $result;
	}

	protected function renderPublishIcon(): string
	{
		if ($this->isPublishable) {
			$rid = 'ctPublishIcon' . $this->rid;

			$moduleIDString = $this->ct->Params->ModuleId === null ? 'null' : $this->ct->Params->ModuleId;

			if ($this->row['listing_published']) {
				$link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',0,' . $moduleIDString . ');';
				$icon = Icons::iconPublished($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_UNPUBLISH'));
			} else {
				$link = 'javascript:ctPublishRecord(' . $this->Table->tableid . ',\'' . $this->listing_id . '\', \'' . $rid . '\',1,' . $moduleIDString . ');';
				$icon = Icons::iconUnpublished($this->ct->Env->toolbarIcons, common::translate('COM_CUSTOMTABLES_PUBLISH'));
			}
			return '<div id="' . $rid . '" class="toolbarIcons"><a href="' . $link . '">' . $icon . '</a></div>';
		} else {
			if (!$this->row['listing_published'])
				return common::translate('COM_CUSTOMTABLES_PUBLISHED');
		}
		return '';
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
}