<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use CustomTablesImageMethods;
use Exception;

class Save_imagegallery
{
	var CT $ct;
	public Field $field;
	var ?array $row_new;
	var array $imageFolderArray;
	var string $photoTableName;
	var CustomTablesImageMethods $imageMethods;
	var string $imageMainPrefix;

	/**
	 * @throws Exception
	 *
	 * @since 3.4.5
	 */
	function __construct(CT &$ct, Field $field)
	{
		$this->ct = &$ct;
		$this->field = $field;
		$this->imageFolderArray = CustomTablesImageMethods::getImageFolder($this->field->params);//self::getImageGalleryFolder($this->field->params);

		$this->photoTableName = database::getDBPrefix() . 'customtables_gallery_' . $this->ct->Table->tablename . '_' . $field->fieldname;
		$this->imageMethods = new CustomTablesImageMethods;
		$this->imageMainPrefix = 'g';
	}

	/**
	 * @throws Exception
	 * @since 3.4.6
	 */
	function saveFieldSet(?string $listing_id): ?array
	{
		$existingFilesString = common::inputPostString($this->ct->Table->fieldInputPrefix . $this->field->fieldname . '_uploaded', null, 'create-edit-record');
		$existingFiles = explode(',', $existingFilesString ?? '');

		foreach ($existingFiles as $file) {
			$photoId = intval($file);
			if ($photoId < 0) {
				$this->imageMethods->DeleteExistingGalleryImage($this->imageFolderArray['path'], $this->imageMainPrefix, $this->ct->Table->tableid, $this->field->fieldname,
					(-$photoId), (($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? '' : ''), true);

				database::deleteRecord($this->photoTableName, 'photoid', (-$photoId));
			}
		}

		$files = common::inputFiles($this->field->comesfieldname, 'create-edit-record');

		foreach ($files as $file)
			$this->uploadImageFile($file['name'], $file['tmp_name'], $listing_id);

		return ['value' => null]; //Nothing to save because records are saved to separate table.
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.4.5
	 */
	function uploadImageFile(string $fileName, string $fileTempName, ?string $listing_id): bool
	{
		$uploadedFile = CUSTOMTABLES_TEMP_PATH . basename($fileName);

		if (!move_uploaded_file($fileTempName, $uploadedFile))
			return false;

		if (common::inputGetCmd('base64encoded', '') == "true") {
			$src = $uploadedFile;
			$dst = "tmp/decoded_" . basename($fileName);
			common::base64file_decode($src, $dst);
			$uploadedFile = $dst;
		}

		//Check file
		if (!CustomTablesImageMethods::CheckImage($uploadedFile)) {
			unlink($uploadedFile);
			throw new Exception(common::translate('COM_CUSTOMTABLES_ERROR_BROKEN_IMAGE'));
		}

		//Save to DB
		$photo_ext = $this->imageMethods->FileExtension($uploadedFile);
		$filenameParts = explode('/', $uploadedFile);
		$filename = end($filenameParts);
		$title = str_replace('.' . $photo_ext, '', $filename);
		$title = str_replace('.' . strtoupper($photo_ext), '', $title);

		$photoId = $this->addPhotoRecord($photo_ext, $title, $listing_id);

		$isOk = true;

		//es Thumb
		$newFileName = $this->imageFolderArray['path'] . DIRECTORY_SEPARATOR . $this->imageMainPrefix . $this->ct->Table->tableid . '_' . $this->field->fieldname . '__esthumb_' . $photoId . ".jpg";
		$r = $this->imageMethods->ProportionalResize($uploadedFile, $newFileName, 150, 150, 1, -1, '');

		if ($r != 1)
			$isOk = false;

		$customSizes = $this->imageMethods->getCustomImageOptions(($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? '' : '');

		foreach ($customSizes as $imageSize) {
			$prefix = $imageSize[0];
			$width = (int)$imageSize[1];
			$height = (int)$imageSize[2];
			$color = (int)$imageSize[3];

			//save as an extension
			if ($imageSize[4] != '')
				$ext = $imageSize[4];
			else
				$ext = $photo_ext;

			$newFileName = $this->imageFolderArray['path'] . DIRECTORY_SEPARATOR . $this->imageMainPrefix . $this->ct->Table->tableid . '_' . $this->field->fieldname . '_' . $prefix . '_' . $photoId . "." . $ext;
			$r = $this->imageMethods->ProportionalResize($uploadedFile, $newFileName, $width, $height, 1, $color, '');

			if ($r != 1)
				$isOk = false;
		}

		if ($isOk) {
			$originalName = $this->imageMainPrefix . $this->ct->Table->tableid . '_' . $this->field->fieldname . '__original_' . $photoId . "." . $photo_ext;

			if (!copy($uploadedFile, $this->imageFolderArray['path'] . DIRECTORY_SEPARATOR . $originalName)) {
				unlink($uploadedFile);
				return false;
			}
		} else {
			unlink($uploadedFile);
			return false;
		}

		unlink($uploadedFile);
		return true;
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.4.5
	 */
	function addPhotoRecord(string $photo_ext, string $title, string $listing_id): int
	{
		$data = [
			'ordering' => 100,
			'photo_ext' => $photo_ext,
			'listingid' => $listing_id,
			'title' => $title
		];

		try {
			database::insert($this->photoTableName, $data);
		} catch (Exception $e) {
			die('Caught exception: ' . $e->getMessage() . "\n");
		}

		$this->AutoReorderPhotos($listing_id);
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $listing_id);
		$rows = database::loadObjectList($this->photoTableName, ['photoid'], $whereClause, 'photoid', 'DESC', 1);

		if (count($rows) == 1)
			return $rows[0]->photoid;

		return -1;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function AutoReorderPhotos(string $listing_id): bool
	{
		$images = $this->getPhotoList($listing_id);
		asort($images);
		$i = 0;
		foreach ($images as $image) {
			$safeTitle = common::inputPostString('esphototitle' . $image->photoid, null, 'create-edit-record');
			if ($safeTitle !== null)
				$safeTitle = str_replace('"', "", $safeTitle);

			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('listingid', $listing_id);
			$whereClauseUpdate->addCondition('photoid', $image->photoid);

			$data = ['ordering' => $i];

			if ($safeTitle != null)
				$data = ['title' . $this->ct->Languages->Postfix => $safeTitle];

			database::update($this->photoTableName, $data, $whereClauseUpdate);
			$i++;
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getPhotoList($listing_id)
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $listing_id);

		return database::loadObjectList($this->photoTableName, ['ordering', 'photoid', 'title' . $this->ct->Languages->Postfix . ' AS title', 'photo_ext'],
			$whereClause, 'ordering, photoid');
	}
}