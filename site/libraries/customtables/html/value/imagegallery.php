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

class Value_imagegallery extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(): ?string
	{
		$listing_id = $this->ct->Table->record[$this->ct->Table->realidfieldname] ?? null;
		if ($listing_id === null)
			return null;

		$getGalleryRows = self::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);

		$pair = explode(':', $this->option_list[0] ?? '');
		if ($pair[0] == '_count')
			return count($getGalleryRows);

		$linksOnly = false;

		if (count($pair) == 2 and $pair[0] == 'link') {
			$linksOnly = true;
			$imageSize = $pair[1];
		} else
			$imageSize = $pair[0];

		$separator = $this->option_list[1] ?? ',';

		$imageSRCList = self::getImageGallerySRC($getGalleryRows, $imageSize, $this->field->fieldname, $this->field->params, $this->ct->Table->tableid, true);

		if ($linksOnly)
			return implode($separator, $imageSRCList);


		$imageTagList = self::getImageGalleryTagList($imageSRCList);
		return implode($separator, $imageTagList);
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.2.2
	 */
	public static function getGalleryRows($tableName, $galleryName, $listing_id)
	{
		$photoTableName = '#__customtables_gallery_' . $tableName . '_' . $galleryName;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', (int)$listing_id);
		return database::loadObjectList($photoTableName, ['photoid', 'photo_ext'], $whereClause, 'ordering, photoid');
	}

	/**
	 * @throws Exception
	 * @since 3.4.5
	 */
	public static function getImageGallerySRC(array $photoRows, string $imagePrefix, string $galleryName, array $params, int $tableId, bool $addFolderPath = false): array
	{
		$list = self::getImageGallerySRCArrayWithIDs($photoRows, $imagePrefix, $galleryName, $params, $tableId, $addFolderPath);
		$newList = [];
		foreach ($list as $item)
			$newList[] = $item['src'];

		return $newList;
	}

	/**
	 * @throws Exception
	 * @since 3.4.5
	 */
	public static function getImageGallerySRCArrayWithIDs(array $photoRows, string $imagePrefix, string $galleryName, array $params, int $tableId, bool $addFolderPath = false): array
	{
		$imageGalleryPrefix = 'g';
		$imageFolderArray = CustomTablesImageMethods::getImageFolder($params);

		//the returned list should be separated by ;
		$imageSRCListArray = [];
		$imgMethods = new CustomTablesImageMethods;

		foreach ($photoRows as $photoRow) {
			$photoRowPhotoId = $photoRow->photoid;
			if (str_contains($photoRowPhotoId, '-'))
				$photoRowPhotoId = str_replace('-', '', $photoRowPhotoId);

			if ($imagePrefix == '') {
				$imageFile = $imageFolderArray['path'] . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';

				if ($imageFile != '')
					$imageSRCListArray[] = ['id' => $photoRowPhotoId, 'src' => ($addFolderPath ? $imageFolderArray['web'] . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg'];

			} elseif ($imagePrefix == '_original') {
				$imageName = $imageFolderArray['path'] . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
				$imageFileExtension = $imgMethods->getImageExtension($imageName);
				$imageSRCListArray[] = ['id' => $photoRowPhotoId, 'src' => ($addFolderPath ? $imageFolderArray['web'] . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension];

			} else {
				$imageSizes = $imgMethods->getCustomImageOptions($params[0]);
				$foundImageSize = false;

				foreach ($imageSizes as $img) {
					if ($img[0] == $imagePrefix) {
						$imageName = $imageFolderArray['path'] . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
						$imageFileExtension = $imgMethods->getImageExtension($imageName);

						if ($imageFileExtension != '') {
							$imageSRCListArray[] = ['id' => $photoRowPhotoId, 'src' => ($addFolderPath ? $imageFolderArray['web'] . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension];
							$foundImageSize = true;
							break;
						}
					}
				}

				if (!$foundImageSize)
					$imageSRCListArray[] = ['id' => $photoRowPhotoId, 'src' => null];
			}
		}

		return $imageSRCListArray;
	}

	public static function getImageGalleryTagList(array $imageSRCList): array
	{
		$siteName = common::getSiteName();

		$tags = [];
		foreach ($imageSRCList as $imageSRC) {
			$tags[] = '<img src="' . $imageSRC . '" alt="' . $siteName . '" title="' . $siteName . '" />';
		}
		return $tags;
	}
}