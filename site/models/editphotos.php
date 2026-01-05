<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\MySQLWhereClause;
use CustomTables\Save_imagegallery;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditPhotos extends BaseDatabaseModel
{
	var CT $ct;

	var Save_imagegallery $imageGallery;
	var ?string $listing_id;
	var string $Listing_Title;
	var string $GalleryTitle;

	var ?array $row;
	var Field $field;

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function load(): bool
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();

		$this->ct->getTable($this->ct->Params->tableName);
		if ($this->ct->Table === null) {
			common::enqueueMessage('Table not selected (62).');
			return false;
		}

		$galleryName = common::inputGetCmd('galleryname');
		if ($galleryName === null)
			return false;

		$fieldRow = $this->ct->Table->getFieldByName($galleryName);
		if ($fieldRow === null) {
			common::enqueueMessage('Image Gallery field not found.');
			return false;
		}

		$this->listing_id = common::inputGetInt("listing_id");
		if ($this->listing_id === null)
			return false;

		$this->getObject();

		$this->field = new Field($this->ct, $fieldRow, $this->row);
		$this->GalleryTitle = $this->field->title;

		$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'records' . DIRECTORY_SEPARATOR;
		require_once $path . 'Save_imagegallery.php';
		$this->imageGallery = new Save_imagegallery($this->ct, $this->field);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function getObject(): bool
	{
		if (!empty($this->listing_id)) {

			$this->ct->Params->listing_id = $this->listing_id;

			if ($this->ct->getRecord()) {
				$this->row = $this->ct->Table->record;
			} else
				return false;
		} else {
			return false;
		}

		$this->Listing_Title = '';

		//Get first field value as a Gallery Title
		foreach ($this->ct->Table->fields as $mFld) {
			$titleField = $mFld['realfieldname'];
			if (str_contains($mFld['type'], 'multi'))
				$titleField .= $this->ct->Languages->Postfix;

			if ($this->row[$titleField] != '') {
				$this->Listing_Title = $this->row[$titleField];
				break;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function reorder(): bool
	{
		if (!$this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT))
			throw new Exception(common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));

		$images = $this->imageGallery->getPhotoList($this->listing_id);

		//Set order

		//Apply Main Photo
		//Get New Ordering
		$MainPhoto = common::inputGetInt('esphotomain');

		foreach ($images as $image) {
			$image->ordering = abs(common::inputGetInt('esphotoorder' . $image->photoid, 0));
			if ($MainPhoto == $image->photoid)
				$image->ordering = -1;
		}

		//Increase all if main
		do {
			$noNegative = true;
			foreach ($images as $image) {
				if ($image->ordering == -1)
					$noNegative = false;
			}

			if (!$noNegative) {
				foreach ($images as $image)
					$image->ordering++;
			}

		} while (!$noNegative);

		asort($images);
		$i = 0;
		foreach ($images as $image) {
			$safeTitle = common::inputPostString('esphototitle' . $image->photoid, null, 'create-edit-record');
			$safeTitle = str_replace('"', "", $safeTitle);

			$data = [
				'ordering' => $i,
				'title' . $this->ct->Languages->Postfix => $safeTitle
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('listingid', $this->listing_id);
			$whereClauseUpdate->addCondition('photoid', $image->photoid);
			database::update($this->imageGallery->photoTableName, $data, $whereClauseUpdate);
			$i++;
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function delete(): bool
	{
		if (!$this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT))
			throw new Exception(common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));

		$photoIDs = common::inputPostString('photoids', '', 'create-edit-record');
		$photo_arr = explode('*', $photoIDs);

		foreach ($photo_arr as $photoId) {
			if ($photoId != '') {
				$this->imageGallery->imageMethods->DeleteExistingGalleryImage(
					$this->imageGallery->imageFolderArray['path'],
					$this->imageGallery->imageMainPrefix,
					$this->ct->Table->tableid,
					$this->galleryname,
					$photoId,
					(($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? '' : ''),
					true
				);

				database::deleteRecord($this->imageGallery->photoTableName, 'photoid', $photoId);
			}
		}

		$this->ct->Table->saveLog($this->listing_id, 7);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function add(): bool
	{
		if (!$this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT))
			throw new Exception(common::translate('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));

		$file = common::inputFiles('uploadedfile');
		$this->imageGallery->uploadImageFile($file['name'], $file['tmp_name'], $this->listing_id);
		return true;
	}

	/*
	function DoAutoResize($uploadedFile, $folder_resized, $image_width, $image_height, $photoid, $fileext): bool
	{
		if (!file_exists($uploadedFile))
			return false;

		$newFileName = $folder_resized . $photoid . '.' . $fileext;

		//hexdec ("#323131")
		$r = $this->imagemethods->ProportionalResize($uploadedFile, $newFileName, $image_width, $image_height, 1, -1, '');
		if ($r != 1)
			return false;

		return true;
	}
	*/
}
