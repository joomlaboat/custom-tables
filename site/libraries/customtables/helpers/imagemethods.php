<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Filtering;
use CustomTables\FindSimilarImage;
use CustomTables\MySQLWhereClause;

class CustomTablesImageMethods
{
	const allowedExtensions = ['jpg', 'png', 'gif', 'jpeg', 'webp'];

	/**
	 * @throws Exception
	 * @since 3.4.5
	 */
	public static function getImageFolder(array $params, string $fieldType = 'image'): array
	{
		if ($fieldType == 'filelink')
			$index = 0;
		elseif ($fieldType == 'file' or $fieldType == 'filebox')
			$index = 1;
		elseif ($fieldType == 'image' or $fieldType == 'imagegallery' or $fieldType == 'signature')
			$index = 2;
		else
			throw new Exception('Invalid field type');

		$ImageFolderPath = null;
		$ImageFolder = null;

		if (defined('_JEXEC')) {

			if (isset($params[$index]) and !empty($params[$index])) {
				$ImageFolder = $params[$index];

				if ($ImageFolder[0] != '/')
					$ImageFolder = 'images/' . $ImageFolder;

				if (strlen($ImageFolder) > 8) {
					$p1 = substr($ImageFolder, 0, 7);
					$p2 = substr($ImageFolder, 0, 8);

					if ($p1 != 'images/' and $p2 != '/images/')
						$ImageFolder = 'images' . $ImageFolder;

					if ($p2 == '/images/')
						$ImageFolder = substr($ImageFolder, 1);
				} else
					$ImageFolder = 'images' . $ImageFolder;
			} else
				$ImageFolder = 'images';

			$ImageFolderPath = CUSTOMTABLES_ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder);

		} elseif (defined('WPINC')) {

			if (isset($params[$index]) and !empty($params[$index])) {
				$ImageFolder = $params[$index];

				if ($ImageFolder[0] != '/')
					$ImageFolder = 'wp-content/uploads/' . $ImageFolder;

			} else
				$ImageFolder = 'wp-content/uploads';

			$ImageFolderPath = CUSTOMTABLES_ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder);
		}

		if (!file_exists($ImageFolderPath))
			mkdir($ImageFolderPath, 0755, true);

		if (strlen($ImageFolderPath) > 0 and $ImageFolderPath[strlen($ImageFolderPath) - 1] == DIRECTORY_SEPARATOR)
			$ImageFolderPath = substr($ImageFolderPath, 0, strlen($ImageFolderPath) - 1);

		if (strlen($ImageFolder) > 0 and $ImageFolder[strlen($ImageFolder) - 1] == '/')
			$ImageFolder = substr($ImageFolder, 0, strlen($ImageFolder) - 1);

		return ['path' => $ImageFolderPath, 'web' => common::UriRoot(false, true) . $ImageFolder];
	}

	public static function CheckImage($src): bool
	{
		if (!file_exists($src))
			return false;

		$wh = getimagesize($src);
		$ms = $wh[0] * $wh[1] * 4;

		if ($ms == 0)
			return false;

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function DeleteGalleryImages($gallery_name, $tableId, $fieldName, $params, $deleteOriginals = false): void
	{
		$image_parameters = $params[0];

		$imageFolderWord = '';
		if (isset($image_parameters[1]))
			$imageFolderWord = $image_parameters[1];

		$imageFolder = CUSTOMTABLES_IMAGES_PATH . str_replace('/', DIRECTORY_SEPARATOR, $imageFolderWord);
		$imageGalleryPrefix = 'g';

		//delete gallery images if exist
		//check is table exists
		$recs = database::getTableStatus($gallery_name, 'gallery');

		if (count($recs) > 0) {

			$whereClause = new MySQLWhereClause();
			$photoRows = database::loadObjectList('#__customtables_gallery_' . $gallery_name, ['photoid'], $whereClause);

			foreach ($photoRows as $photoRow) {
				$this->DeleteExistingGalleryImage(
					$imageFolder,
					$imageGalleryPrefix,
					$tableId,
					$fieldName,
					$photoRow->photoid,
					$image_parameters ?? '',
					$deleteOriginals
				);
			}
		}
	}

	function DeleteExistingGalleryImage(string $AbsoluteImageFolder, $ImageMainPrefix, $estableid, $galleryName, $photoId, string $imageParams, $deleteOriginals = false): void
	{
		//Delete original thumbnails
		if ($deleteOriginals) {
			$filename = $AbsoluteImageFolder . DIRECTORY_SEPARATOR . $ImageMainPrefix . $estableid . '_' . $galleryName
				. '__esthumb_' . $photoId . '.jpg';
			if (file_exists($filename))
				unlink($filename);
		}

		$customSizes = $this->getCustomImageOptions($imageParams);

		foreach (CustomTablesImageMethods::allowedExtensions as $photo_ext) {
			//delete orginal full size images
			if ($deleteOriginals) {
				$filename = $AbsoluteImageFolder . DIRECTORY_SEPARATOR . $ImageMainPrefix . $estableid . '_' . $galleryName . '__original_' . $photoId . '.' . $photo_ext;
				if (file_exists($filename))
					unlink($filename);
			}

			//Delete custom size images
			foreach ($customSizes as $customSize) {
				$filename = $AbsoluteImageFolder . DIRECTORY_SEPARATOR . $ImageMainPrefix . $estableid . '_' . $galleryName . '_' . $customSize[0] . '_' . $photoId . '.' . $photo_ext;
				if (file_exists($filename))
					unlink($filename);
			}
		}
	}

	function getCustomImageOptions(string $imageParams): array
	{
		$cleanOptions = array();
		//custom images
		$imageSizes = explode(';', $imageParams);

		foreach ($imageSizes as $imagesize) {
			$imageOptions = explode(',', $imagesize);
			if (count($imageOptions) > 1) {
				$prefix = strtolower(trim(preg_replace("/[^a-zA-Z\d]/", "", $imageOptions[0])));

				if (strlen($prefix) > 0) {
					//name, width, height, color (0 - black, -1 - bg fill, -2 - trasparent)
					if (count($imageOptions) < 2)
						$imageOptions[1] = '';

					if (count($imageOptions) < 3)
						$imageOptions[2] = '';

					if (count($imageOptions) < 4)
						$imageOptions[3] = '';

					$imageOptions[3] = $this->getColorDec($imageOptions[3]);

					if (count($imageOptions) < 5)
						$imageOptions[4] = '';

					if ($imageOptions[4] != '') {
						if (!in_array($imageOptions[4], CustomTablesImageMethods::allowedExtensions))
							$imageOptions[4] = '';
					} else
						$imageOptions[4] = '';

					if (count($imageOptions) < 6)
						$imageOptions[5] = '';

					$cleanOptions[] = array($prefix, (int)$imageOptions[1], (int)$imageOptions[2], $imageOptions[3], $imageOptions[4], $imageOptions[5]);
				}
			}
		}
		return $cleanOptions;
	}

	function getColorDec($vlu): int
	{
		$vlu = strtolower(trim($vlu));

		if ($vlu == 'transparent')
			return -2;

		elseif ($vlu == 'fill')
			return -1;

		elseif ($vlu == 'black')
			return 0;

		elseif ($vlu == 'white')
			return hexdec('ffffff');

		elseif ($vlu == 'red')
			return hexdec('ff0000');

		elseif ($vlu == 'green')
			return hexdec('00ff00');

		elseif ($vlu == 'blue')
			return hexdec('0000ff');

		elseif ($vlu == 'yellow')
			return hexdec('ffff00');

		elseif (str_contains($vlu, '#')) {

			$vlu = preg_replace("/[^\dA-Fa-f]/", '', $vlu); // Gets a proper hex string
			return hexdec($vlu);//As of PHP 7.4.0 supplying any invalid characters is deprecated.
		} else
			return (int)$vlu;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function DeleteCustomImages($realtablename, $realfieldname, $ImageFolder, $imageparams, $realidfield, $deleteOriginals = false, string $fileNameType = ''): void
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($realfieldname, 0, '>');

		$imageList = database::loadAssocList($realtablename, [$realfieldname], $whereClause);
		$customSizes = $this->getCustomImageOptions($imageparams);

		foreach ($imageList as $img) {
			$ExistingImage = $img[$realfieldname];

			if ($deleteOriginals)
				CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realidfield, $fileNameType);

			foreach ($customSizes as $customSize)
				CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder, $customSize[0]);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static protected function DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realIdField, string $fileNameType = ''): bool
	{
		//This function deletes original images in case image not occupied by another record.

		//---------- find child ----------
		//check if the image has child or not
		if ($realtablename != '-options') {
			if ($ExistingImage !== null and is_numeric($ExistingImage) and intval($ExistingImage) != 0) {
				//If it's an original image not duplicate, find one duplicate and convert it to original
				$whereClause = new MySQLWhereClause();
				$whereClause->addCondition($realfieldname, (-$ExistingImage));

				$photoRows = database::loadAssocList($realtablename, [$realIdField], $whereClause, null, null, 1);

				if (count($photoRows) == 1) //do not compare if there is a child
				{
					$photoRow = $photoRows[0];

					//Null Parent
					$data = [
						$realfieldname => 0
					];
					$whereClauseUpdate = new MySQLWhereClause();
					$whereClauseUpdate->addCondition($realfieldname, $ExistingImage);
					database::update($realtablename, $data, $whereClauseUpdate);

					//Convert Child to Parent
					$data = [
						$realfieldname => $ExistingImage
					];
					$whereClauseUpdate = new MySQLWhereClause();
					$whereClauseUpdate->addCondition($realIdField, (int)$photoRow[$realIdField]);
					database::update($realtablename, $data, $whereClauseUpdate);
					return true;
				}
			}
		}

		foreach (CustomTablesImageMethods::allowedExtensions as $photo_ext) {

			if ($fileNameType == '')
				$original_fileName = $ImageFolder . DIRECTORY_SEPARATOR . '_original_' . $ExistingImage . '.' . $photo_ext;
			else
				$original_fileName = $ImageFolder . DIRECTORY_SEPARATOR . $ExistingImage . '.' . $photo_ext;

			if (file_exists($original_fileName))
				unlink($original_fileName);

			if (file_exists($ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $ExistingImage . '.' . $photo_ext))
				unlink($ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $ExistingImage . '.' . $photo_ext);
		}

		return true;
	}

	static protected function DeleteCustomImage($ExistingImage, $ImageFolder, $CustomSize): void
	{
		foreach (CustomTablesImageMethods::allowedExtensions as $photo_ext) {
			if (file_exists($ImageFolder . DIRECTORY_SEPARATOR . $CustomSize . '_' . $ExistingImage . '.' . $photo_ext))
				unlink($ImageFolder . DIRECTORY_SEPARATOR . $CustomSize . '_' . $ExistingImage . '.' . $photo_ext);
		}
	}

	function getImageExtension($ImageName_noExt): string
	{
		foreach (CustomTablesImageMethods::allowedExtensions as $photo_ext) {
			$filename = $ImageName_noExt . '.' . $photo_ext;

			if (file_exists($filename))
				return $photo_ext;
		}
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function UploadSingleImage(?string $ExistingImage, string $pathToImageFile, string $fileName, string $realFieldName, string $ImageFolder, array $params, string $realtablename, string $realidfieldname): ?string
	{
		$fileNameType = $params[3] ?? '';

		if ($pathToImageFile == '')
			return null;

		$additional_params = '';
		if (isset($params[1]))
			$additional_params = $params[1];

		if (!str_contains($pathToImageFile, DIRECTORY_SEPARATOR))//in case when other applications pass full path to the file
			$uploadedFile = CUSTOMTABLES_TEMP_PATH . $pathToImageFile;
		else
			$uploadedFile = $pathToImageFile;

		$new_photo_ext = $this->FileExtension($fileName);

		if (defined('_JEXEC')) {
			if (is_object('Factory::getApplication()')) {
				$is_base64encoded = common::inputGetCmd('base64encoded', '');
				if ($is_base64encoded == "true") {
					$src = $uploadedFile;
					$dst = CUSTOMTABLES_TEMP_PATH . 'decoded_' . basename($pathToImageFile);//TODO: Check this functionality
					common::base64file_decode($src, $dst);
				}
			}
		}

		//Delete
		if ($ExistingImage !== null) {
			$fileNameType = $params[3] ?? '';
			$this->DeleteExistingSingleImage($ExistingImage, $ImageFolder, $params[0] ?? '',
				$realtablename, $realFieldName, $realidfieldname, $fileNameType);
		}

		//Get new file name and avoid possible duplicate
		$i = 0;

		do {
			if ($fileNameType == '') {
				$ImageID = common::currentDate("YmdHis") . ($i > 0 ? $i : '');
				$ImageID .= ($i > 0 ? $i : '');

				//there is possible error, check all possible ext
				$thumbnail_image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $ImageID . '.jpg';
				$original_image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_original_' . $ImageID . '.' . $new_photo_ext;
			} else {
				$ImageID = $fileName;

				if ($fileNameType == 'transliterated') {

					if (function_exists("transliterator_transliterate"))
						$ImageID = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", $ImageID);

					$ImageID = trim(str_replace(' ', '_', $ImageID));
					$ImageID = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $ImageID);
				}

				$parts = explode('.', $ImageID);
				if (count($parts) < 1)
					return null;

				unset($parts[count($parts) - 1]);
				$parts[count($parts) - 1] .= ($i > 0 ? '_' . $i : '');
				$ImageID = implode('.', $parts);

				//there is possible error, check all possible ext
				$thumbnail_image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $ImageID . '.jpg';
				$original_image_file = $ImageFolder . DIRECTORY_SEPARATOR . $ImageID . '.' . $new_photo_ext;
			}

			$i++;
		} while (file_exists($original_image_file));

		$isOk = true;
		$r = $this->ProportionalResize($uploadedFile, $thumbnail_image_file, 150, 150, 1, -1, '', $new_photo_ext);

		if ($r != 1)
			$isOk = false;

		//--------- compare thumbnails
		$duplicateImageID = $this->compareThumbs($additional_params, $realtablename, $realFieldName, $ImageFolder, $uploadedFile, $thumbnail_image_file);

		if ($duplicateImageID != 0)
			return $duplicateImageID;
		//--------- end of compare thumbnails

		//custom images
		if ($isOk) {
			$customSizes = $this->getCustomImageOptions($params[0] ?? '');

			foreach ($customSizes as $imageSize) {
				$prefix = $imageSize[0];
				$width = (int)$imageSize[1];
				$height = (int)$imageSize[2];
				$color = (int)$imageSize[3];
				$watermark = $imageSize[5];

				//save as extension
				if ($imageSize[4] != '')
					$ext = $imageSize[4];
				else
					$ext = $new_photo_ext;

				$r = $this->ProportionalResize($uploadedFile, $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $ImageID . '.' . $ext, $width, $height, 1, $color, $watermark);
				if ($r != 1)
					$isOk = false;
			}
		}

		if ($isOk) {
			copy($uploadedFile, $original_image_file);
			unlink($uploadedFile);
			return $ImageID;
		} else {
			if (file_exists($original_image_file))
				unlink($original_image_file);

			if (file_exists($uploadedFile))
				unlink($uploadedFile);

			if ($fileNameType == '') {
				return '-1';
			} else {
				return '';
			}
		}
	}

	function FileExtension($src): string
	{
		$ext_list = explode('.', strtolower($src));
		$ext = end($ext_list);

		if (in_array($ext, CustomTablesImageMethods::allowedExtensions))
			return $ext;

		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function DeleteExistingSingleImage(string $ExistingImage, $ImageFolder, string $imageParams, $realtablename, $realfieldname, $realIdField, string $fileNameType = ''): void
	{
		$customSizes = $this->getCustomImageOptions($imageParams);
		CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realIdField, $fileNameType);

		foreach ($customSizes as $customSize)
			CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder, $customSize[0]);
	}

	function ProportionalResize(string $src, string $dst, int $dst_width, int $dst_height, int $LevelMax, int $backgroundColor, string $watermark, string $fileExtension = null): int
	{
		//Returns:
		// 1 if everything is ok
		// -1 if file extension not supported
		// 2 if file already exists

		if (!file_exists($src))
			return -1;

		if ($fileExtension === null)
			$fileExtension = $this->FileExtension($src);

		$fileExtension_dst = $this->FileExtension($dst);

		if ($fileExtension == '') {
			//File type not supported
			return -1;
		}

		if ($LevelMax > 1)
			$LevelMax = 1;

		//Check if destination file already exists
		if (file_exists($dst)) //Just in case
		{
			//Distillation file with the same name already exists
			return 2;
		}

		$size = getImageSize($src);
		//$ms = $size[0] * $size[1] * 4;

		$width = $size[0];
		$height = $size[1];

		if ($dst_height == 0)
			$dst_height = floor($dst_width / ($width / $height));

		if ($dst_width == 0)
			$dst_width = floor($dst_height * ($width / $height));

		$from = null;

		$rgb = $backgroundColor;
		if ($fileExtension == "jpg" or $fileExtension == 'jpeg') {

			try {
				$from = @imagecreatefromjpeg($src);
			} catch (Exception $e) {
				return -1;
			}

			if (!$from) {
				return -1;
			}

			if ($rgb == -1) {
				try {
					$rgb = imagecolorat($from, 0, 0);
				} catch (Exception $e) {
					return -1;
				}
			}

		} elseif ($fileExtension == "gif") {
			$from1 = ImageCreateFromGIF($src);
			$from = ImageCreateTrueColor($width, $height);
			imagecopyresampled($from, $from1, 0, 0, 0, 0, $width, $height, $width, $height);
			if ($rgb == -1)
				$rgb = imagecolorat($from, 0, 0);
		} elseif ($fileExtension == 'png') {
			$from = imageCreateFromPNG($src);

			if ($rgb == -1) {
				$rgb = imagecolorat($from, 0, 0);

				//if destination is jpeg and background is transparent then replace it with white.
				if ($rgb == hexdec('7FFFFFFF') and $fileExtension_dst == 'jpg')
					$rgb = hexdec('ffffff');
			}
		} elseif ($fileExtension == 'webp') {
			$from = imagecreatefromwebp($src);
			if ($rgb == -1) {
				$rgb = imagecolorat($from, 0, 0);

				//if destination is jpeg and background is transparent then replace it with white.
				if ($rgb == hexdec('7FFFFFFF') and $fileExtension_dst == 'jpg')
					$rgb = hexdec('ffffff');
			}
		}

		$new = ImageCreateTrueColor($dst_width, $dst_height);

		if ($rgb != -2) {
			//Transparent
			imagefilledrectangle($new, 0, 0, $dst_width, $dst_height, $rgb);
		} else {
			imageSaveAlpha($new, true);
			ImageAlphaBlending($new, false);

			$transparentColor = imagecolorallocatealpha($new, 255, 0, 0, 127);
			imagefilledrectangle($new, 0, 0, $dst_width, $dst_height, $transparentColor);
		}

		//Width
		$dst_w = $dst_width; //Dist Width
		$dst_h = round($height * ($dst_w / $width));

		if ($dst_h > $dst_height) {
			$dst_h = $dst_height;
			$dst_w = round($width * ($dst_h / $height));

			//Do crop if pr
			$a = $dst_width / $dst_w;
			$x = 1 + ($a - 1) * $LevelMax;

			if ($LevelMax != 0) {
				$dst_w = $dst_width / $x; //Dist Width
				$dst_h = round($height * ($dst_w / $width));
			}
		}

		//Setting coordinates
		$dst_x = round($dst_width / 2 - $dst_w / 2);
		$dst_y = round($dst_height / 2 - $dst_h / 2);

		imagecopyresampled($new, $from, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $width, $height);

		if ($watermark != '') {
			$watermark_Extension = $this->FileExtension($watermark);
			if ($watermark_Extension == 'png') {
				$watermark_file = CUSTOMTABLES_ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, $watermark);

				if (file_exists($watermark_file)) {
					$watermark_from = imageCreateFromPNG($watermark_file);
					$watermark_size = getImageSize($watermark_file);
					if ($dst_w >= $watermark_size[0] and $dst_h >= $watermark_size[1]) {
						$wX = ($dst_w - $watermark_size[0]) / 2;
						$wY = ($dst_h - $watermark_size[1]) / 2;

						imagecopyresampled($new, $watermark_from, $wX, $wY, 0, 0, $watermark_size[0], $watermark_size[1], $watermark_size[0], $watermark_size[1]);

					}//if($width>=$watermark_size[0] and $height>=$watermark_size[1])
				}//if(file_exists($watermark))
			}//if($watermark_Extension=='png')
		}//if($watermark!='')
		//----------- end watermark

		if ($fileExtension_dst == "jpg" or $fileExtension_dst == 'jpeg')
			imagejpeg($new, $dst, 90);
		elseif ($fileExtension_dst == "gif")
			imagegif($new, $dst);
		elseif ($fileExtension_dst == 'png')
			imagepng($new, $dst);
		elseif ($fileExtension_dst == 'webp')
			imagewebp($new, $dst, 90);

		return 1;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function compareThumbs($additional_params, $realtablename, $realfieldname, $ImageFolder, $uploadedfile, $thumbFileName): int
	{
		$pair = explode(':', $additional_params);

		if ($realtablename != '-options' and ($pair[0] == 'compare' or $pair[0] == 'compareexisting')) {
			$level_identity = 2;
			if (isset($pair[1]))
				$level_identity = (int)$pair[1];

			$whereClause = new MySQLWhereClause();

			if (isset($pair[2])) {

				$tablename = str_replace('#__customtables_table_', '', $realtablename);

				$newCt = new CT([], true);
				$newCt->getTable($tablename);
				if ($newCt->Table === null)
					return 0;

				$f = new Filtering($newCt);
				$f->addWhereExpression($pair[2]);
				$whereClause = $f->whereClause;
			}

			$ImageID = -FindSimilarImage::find($uploadedfile, $level_identity, $realtablename, $realfieldname, $ImageFolder, $whereClause);

			if ($ImageID !== null) {
				unlink($uploadedfile);
				unlink($thumbFileName);
				return $ImageID;
			}
		}
		return 0;
	}
}

