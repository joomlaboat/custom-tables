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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class CT_FieldTypeTag_image
{
	static public function getImageSRCLayoutView(array $option_list, ?string $rowValue, array $params, string &$imageSrc, string &$imageTag): bool
	{
		if ($rowValue !== null and $rowValue !== '' and is_numeric($rowValue) and intval($rowValue) < 0)
			$rowValue = -intval($rowValue);

		$conf = Factory::getConfig();
		$sitename = $conf->get('config.sitename');

		$option = $option_list[0] ?? '';
		$ImageFolder_ = CustomTablesImageMethods::getImageFolder($params);
		$ImageFolderWeb = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder_);
		$ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);
		$imageSrc = '';
		$imageTag = '';

		if ($option == '' or $option == '_esthumb' or $option == '_thumb') {
			$prefix = '_esthumb';

			$imageFileExtension = 'jpg';
			$imageFileWeb = Uri::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
			$imageFile = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
			if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile)) {
				$imageTag = '<img src="' . $imageFileWeb . '" style="width:150px;height:150px;" alt="' . $sitename . '" title="' . $sitename . '" />';
				$imageSrc = $imageFileWeb;
				return true;
			}
			return false;
		} elseif ($option == '_original') {
			$prefix = '_original';
			$imageName = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue;

			$imgMethods = new CustomTablesImageMethods;

			$imageFileExtension = $imgMethods->getImageExtension(JPATH_SITE . DIRECTORY_SEPARATOR . $imageName);

			if ($imageFileExtension != '') {
				$imageFileWeb = Uri::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
				$imageTag = '<img src="' . $imageFileWeb . '" alt="' . $sitename . '" title="' . $sitename . '" />';

				$imageSrc = $imageFileWeb;
				return true;
			}
			return false;
		}

		$prefix = $option;
		$imgMethods = new CustomTablesImageMethods;
		$imageName = $ImageFolder . DIRECTORY_SEPARATOR . $prefix . '_' . $rowValue;

		$imageFileExtension = $imgMethods->getImageExtension(JPATH_SITE . DIRECTORY_SEPARATOR . $imageName);
		//--- WARNING - ERROR -- REAL EXT NEEDED - IT COMES FROM OPTIONS
		$imageFile = Uri::root() . $ImageFolderWeb . '/' . $prefix . '_' . $rowValue . '.' . $imageFileExtension;
		$imageSizes = $imgMethods->getCustomImageOptions($params[0]);

		foreach ($imageSizes as $img) {
			if ($img[0] == $option) {
				if ($imageFile != '') {
					$styles = [];
					if ($img[1] > 0)
						$styles[] = 'width:' . $img[1] . 'px;';

					if ($img[2] > 0)
						$styles[] = 'height:' . $img[2] . 'px;';

					$imageTag = '<img src="' . $imageFile . '" alt="' . $sitename . '" title="' . $sitename . '"'
						. (count($styles) > 0 ? ' style="' . implode(";", $styles) . '"' : '') . ' />';

					$imageSrc = $imageFile;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	static public function get_image_type_value(Field $field, string $realidfieldname, ?string $listing_id): ?string
	{
		$imageMethods = new CustomTablesImageMethods;
		$ImageFolder = CustomTablesImageMethods::getImageFolder($field->params);
		$fileId = common::inputPostString($field->comesfieldname);

		if ($listing_id == null or $listing_id == '' or (is_numeric($listing_id) and intval($listing_id) < 0)) {
			$value = $imageMethods->UploadSingleImage('', $fileId, $field->realfieldname, JPATH_SITE . DIRECTORY_SEPARATOR
				. $ImageFolder, $field->params, $field->ct->Table->realtablename, $field->ct->Table->realidfieldname);
		} else {
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition($realidfieldname, $listing_id);

			$ExistingImageRows = database::loadObjectList($field->ct->Table->realtablename, [$field->realfieldname], $whereClause, null, null, 1);
			if (count($ExistingImageRows) == 0)
				$ExistingImage = null;
			else
				$ExistingImage = $ExistingImageRows[$field->realfieldname];

			$value = $imageMethods->UploadSingleImage($ExistingImage, $fileId, $field->realfieldname,
				JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder, $field->params, $field->ct->Table->realtablename, $field->ct->Table->realidfieldname);
		}

		if ($value == "-1" or $value == "2") {
			// -1 if file extension not supported
			// 2 if file already exists
			common::enqueueMessage('Could not upload image file.');
			$value = null;
		}
		return $value;
	}

	public static function getImageSRC(?array $row, string $realFieldName, string $ImageFolder, string &$imageFile, bool &$isShortcut): string
	{
		$isShortcut = false;
		if (isset($row[$realFieldName]) and $row[$realFieldName] !== false and $row[$realFieldName] !== '' and $row[$realFieldName] !== '0') {
			$img = $row[$realFieldName];

			if (is_numeric($img) and intval($img) < 0) {
				$isShortcut = true;
				$img = intval($img);
			}

			$imageFile_ = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $img;
			$imageSrc_ = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder) . '/_esthumb_' . $img;
		} else {
			$imageFile_ = '';
			$imageSrc_ = '';
		}

		if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.jpg')) {
			$imageFile = $imageFile_ . '.jpg';
			$imageSrc = $imageSrc_ . '.jpg';
		} elseif (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.png')) {
			$imageFile = $imageFile_ . '.png';
			$imageSrc = $imageSrc_ . '.png';
		} elseif (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile_ . '.webp')) {
			$imageFile = $imageFile_ . '.webp';
			$imageSrc = $imageSrc_ . '.webp';
		} else {
			$imageFile = '';
			$imageSrc = '';
		}

		return Uri::root() . $imageSrc;
	}


	protected static function renderUploaderLimitations(): string
	{
		$max_file_size = CTMiscHelper::file_upload_max_size();

		return '
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
				' . common::translate("COM_CUSTOMTABLES_MIN_SIZE") . ': 10px x 10px<br/>
				' . common::translate("COM_CUSTOMTABLES_MAX_SIZE") . ': 1000px x 1000px<br/>
				' . common::translate("COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE") . ': ' . CTMiscHelper::formatSizeUnits($max_file_size) . '<br/>
				' . common::translate("COM_CUSTOMTABLES_FORMAT") . ': JPEG, GIF, PNG, WEBP
				</div>';
	}

	//Drupal has this implemented fairly elegantly:
	//https://stackoverflow.com/questions/1.6.1.1/php-get-actual-maximum-upload-size

	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size
}
