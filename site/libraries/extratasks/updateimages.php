<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\MySQLWhereClause;

class updateImages
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function process(int $tableId): array
	{
		$stepSize = common::inputGetInt('stepsize', 10);
		$startIndex = common::inputGetInt('startindex', 0);

		$old_typeparams = base64_decode(common::inputGetBase64('old_typeparams', ''));
		if ($old_typeparams == '')
			return array('error' => 'old_typeparams not set');

		$old_params = CTMiscHelper::csv_explode(',', $old_typeparams);

		$new_typeparams = base64_decode(common::inputGetBase64('new_typeparams', ''));
		if ($new_typeparams == '')
			return array('error' => 'new_typeparams not set');

		$new_params = CTMiscHelper::csv_explode(',', $new_typeparams);

		$fieldid = common::inputGetInt('fieldid', 0);
		if ($fieldid == 0)
			return array('error' => 'fieldid not set');

		$ct = new CT;
		$ct->getTable($tableId);
		$fieldRow = $ct->Table->getFieldById($fieldid);
		if ($fieldRow === null) {
			return array('error' => 'field id set but field not found');
		} else {
			$count = 0;
			if ($startIndex == 0) {
				$count = self::countImages($ct->Table->realtablename, $fieldRow['realfieldname']);
				if ($stepSize > $count)
					$stepSize = $count;
			}

			$status = self::processImages($ct, $fieldRow, $old_params, $new_params);
			return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startIndex, 'stepsize' => $stepSize, 'error' => $status);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function countImages(string $realtablename, string $realfieldname): int
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($realfieldname, null, 'NOT NULL');

		$rows = database::loadAssocList($realtablename, ['COUNT_ROWS'], $whereClause);
		return (int)$rows[0]['record_count'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processImages(CT &$ct, array $fieldRow, array $old_params, array $new_params): ?string
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($fieldRow['realfieldname'], null, 'NOT NULL');

		$rows = database::loadAssocList($ct->Table->realtablename, [$fieldRow['realfieldname']], $whereClause);
		$old_ImageFolder = '';
		$imgMethods = new CustomTablesImageMethods;

		foreach ($rows as $img) {

			if ((is_numeric($img) and intval($img) > 0) or !is_numeric($img)) {
				$field_row_old = $fieldRow;
				$field_row_old['params'] = $old_params;

				$field_old = new Field($ct, $field_row_old, $img);
				$field_old->params = $old_params;

				$old_ImageFolderArray = CustomTablesImageMethods::getImageFolder($field_old->params);
				//$old_ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $old_ImageFolder_);

				$old_imageSizes = $imgMethods->getCustomImageOptions($field_old->params[0]);

				$field_row_new = $fieldRow;

				$field_new = new Field($ct, $field_row_new, $img);
				$field_new->params = $new_params;

				$new_ImageFolderArray = CustomTablesImageMethods::getImageFolder($field_new->params);
				//$new_ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $new_ImageFolder_);

				$new_imageSizes = $imgMethods->getCustomImageOptions($field_new->params[0]);

				$status = self::processImage($imgMethods, $old_imageSizes, $new_imageSizes, $img[$fieldRow['realfieldname']], $old_ImageFolderArray['path'], $new_ImageFolderArray['path']);
				//if $status is null then all good, status is a text string with error message if any
				if ($status !== null)
					return $status;
			}
		}
		CTMiscHelper::deleteFolderIfEmpty($old_ImageFolder);
		return null;
	}

	protected static function processImage($imgMethods, $old_imageSizes, $new_imageSizes, string $rowValue, string $old_ImageFolder, string $new_ImageFolder): ?string
	{
		$original_image_file = '';

		$status = self::processImage_Original($imgMethods, $rowValue, $old_ImageFolder, $new_ImageFolder, $original_image_file);

		if ($status !== null)
			return null;//Skip if original file not found

		$status = self::processImage_Thumbnail($rowValue, $old_ImageFolder, $new_ImageFolder);
		if ($status !== null) {
			//Create Thumbnail file
			$r = $imgMethods->ProportionalResize($original_image_file, $new_ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $rowValue . '.jpg', 150, 150, 1, -1, '');

			if ($r != 1)
				return null;//Skip could not create thumbnail
		}

		//Move custom size files to new folder, or create if custom size file in original folder is missing
		$status = self::processImage_CustomSizes($imgMethods, $old_imageSizes, $rowValue, $old_ImageFolder, $new_ImageFolder, $original_image_file);
		if ($status !== null)
			return $status;

		//Delete custom size file if no longer in use or size or property changed
		$image_sizes_to_delete = self::findChangedOrDeletedCustomSizes($old_imageSizes, $new_imageSizes);

		//Delete old files
		foreach ($image_sizes_to_delete as $img) {
			$status = self::processImage_CustomSize_deleteFile($imgMethods, $rowValue, $new_ImageFolder, $img[0], $img[4], $original_image_file);
			if ($status !== null)
				return $status;
		}

		//Create custom size file that doesn't exist
		foreach ($new_imageSizes as $img) {
			$status = self::processImage_CustomSize_createFile($imgMethods, $img, $rowValue, $new_ImageFolder, $img[0], $img[4], $original_image_file);
			if ($status !== null)
				return $status;
		}
		return null;
	}

	protected static function processImage_Original($imgMethods, string $rowValue, string $old_ImageFolder, string $new_ImageFolder, &$original_image_file): ?string
	{
		//Check original image file
		$prefix = '_original';
		$old_imageFile = $old_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue;

		$imageFile_ext = $imgMethods->getImageExtention($old_imageFile);//file extension is unknown - let's find out

		if ($imageFile_ext != '') {
			$old_imageFile = $old_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;
			$new_imageFile = $new_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;
			if (file_exists($old_imageFile)) {
				if ($old_ImageFolder != $new_ImageFolder) {
					if (!@rename($old_imageFile, $new_imageFile))
						return 'cannot move file to ' . $new_imageFile;
					else
						$original_image_file = $new_imageFile;
				} else
					$original_image_file = $old_imageFile;
			} else
				return 'file not found';
		} else
			return 'file not found';

		return null;
	}

	protected static function processImage_Thumbnail(string $rowValue, string $old_ImageFolder, string $new_ImageFolder): ?string
	{
		//Check thumbnail
		$prefix = '_esthumb';
		$imageFile_ext = 'jpg';
		$old_imageFile = $old_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;
		$new_imageFile = $new_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;

		if (file_exists($old_imageFile)) {
			if ($old_ImageFolder != $new_ImageFolder) {
				if (!@rename($old_imageFile, $new_imageFile))
					return 'cannot move file to ' . $new_imageFile;
			}
		} else
			return 'file not found';

		return null;
	}

	protected static function processImage_CustomSizes($imgMethods, $imageSizes, $rowValue, $old_ImageFolder, $new_ImageFolder, $original_image_file): ?string
	{
		//Move files if necessary
		foreach ($imageSizes as $img) {
			$status = self::processImage_CustomSize_MoveFile($imgMethods, $img, $rowValue, $old_ImageFolder, $new_ImageFolder, $img[0], $img[4], $original_image_file);
			if ($status !== null)
				return $status;
		}
		return null;
	}

	protected static function processImage_CustomSize_MoveFile($imgMethods, $old_imageSize, $rowValue, $old_ImageFolder, $new_ImageFolder, $prefix, string $imageFile_ext, string $original_image_file): ?string
	{
		if ($imageFile_ext == '')
			$imageFile_ext = $imgMethods->getImageExtention($original_image_file);//file extension is unknown - let's find out based on original file

		if ($imageFile_ext != '') {
			$old_imageFile = $old_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;
			$new_imageFile = $new_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;

			if (!file_exists($old_imageFile)) {
				//Custom size file not found, create it
				$width = (int)$old_imageSize[1];
				$height = (int)$old_imageSize[2];
				$color = (int)$old_imageSize[3];
				$watermark = $old_imageSize[5];

				$r = $imgMethods->ProportionalResize($original_image_file, $old_imageFile, $width, $height, 1, $color, $watermark);

				if ($r != 1)
					return 'cannot create file: ' . $old_imageFile;
			}

			if (file_exists($old_imageFile)) {
				if ($old_ImageFolder != $new_ImageFolder) {
					//Move exiting custom size file to new folder
					if (!@rename($old_imageFile, $new_imageFile))
						return 'cannot move file to ' . $new_imageFile;
				}
			} else {
				return 'cannot create and move file: ' . $old_imageFile;
			}
		}

		return null;
	}

	protected static function findChangedOrDeletedCustomSizes($old_imageSizes, $new_imageSizes): array
	{
		$image_sizes_to_delete = array();

		foreach ($old_imageSizes as $old_img) {
			$changed = false;

			foreach ($new_imageSizes as $new_img) {
				if ($old_img[0] == $new_img[0])//check if the size name is match
				{
					//Compare parameters
					for ($i = 1; $i < 6; $i++) {
						if ($old_img[$i] != $new_img[$i]) {
							$changed = true;
							$image_sizes_to_delete[] = $old_img;
							break;
						}
					}
					if ($changed)
						break;
				}
			}
		}
		return $image_sizes_to_delete;
	}

	protected static function processImage_CustomSize_deleteFile($imgMethods, $rowValue, $old_ImageFolder, $prefix, string $imageFile_ext, string $original_image_file): ?string
	{
		if ($imageFile_ext == '')
			$imageFile_ext = $imgMethods->getImageExtention($original_image_file);//file extension is unknown - let's find out based on original file

		if ($imageFile_ext != '') {
			$old_imageFile = $old_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;

			if (file_exists($old_imageFile)) {
				if (!@unlink($old_imageFile))
					return 'cannot delete old file: ' . $old_imageFile;
			}
		}
		return null;
	}

	protected static function processImage_CustomSize_createFile($imgMethods, $new_imageSize, $rowValue, $new_ImageFolder, $prefix, string $imageFile_ext, string $original_image_file): ?string
	{
		if ($imageFile_ext == '')
			$imageFile_ext = $imgMethods->getImageExtention($original_image_file);//file extension is unknown - let's find out based on original file

		if ($imageFile_ext != '') {
			$new_imageFile = $new_ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFile_ext;

			if (!file_exists(CUSTOMTABLES_ABSPATH . $new_imageFile)) {
				//Custom size file not found, create it
				$width = (int)$new_imageSize[1];
				$height = (int)$new_imageSize[2];
				$color = (int)$new_imageSize[3];
				$watermark = $new_imageSize[5];

				$r = $imgMethods->ProportionalResize(CUSTOMTABLES_ABSPATH . $original_image_file, CUSTOMTABLES_ABSPATH . $new_imageFile,
					$width, $height, 1, $color, $watermark);

				if ($r != 1)
					return 'cannot create file: ' . $new_imageFile;
			}
		}
		return null;
	}
}
