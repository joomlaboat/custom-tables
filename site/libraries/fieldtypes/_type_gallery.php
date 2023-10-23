<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use Joomla\CMS\Factory;

class CT_FieldTypeTag_imagegallery
{
    public static function getGalleryRows($tableName, $galleryName, $listing_id)
    {
        $photoTableName = '#__customtables_gallery_' . $tableName . '_' . $galleryName;
        return database::loadObjectList('SELECT photoid, photo_ext FROM ' . $photoTableName . ' WHERE listingid=' . (int)$listing_id . ' ORDER BY ordering, photoid');
    }

    public static function getImageGalleryTagList(array $imageSRCList)//, array $params
    {
        $conf = Factory::getConfig();
        $sitename = $conf->get('config.sitename');

        $tags = [];
        foreach ($imageSRCList as $imageSRC) {
            $tags[] = '<img src="' . $imageSRC . '" alt="' . $sitename . '" title="' . $sitename . '" />';
        }
        return $tags;
    }

    public static function getImageGallerySRC(array $photoRows, string $imagePrefix, string $galleryName, array $params, int $tableId, bool $addFolderPath = false): array
    {
        $imageGalleryPrefix = 'g';
        $imageFolder = CustomTablesImageMethods::getImageFolder($params);

        if ($imageFolder == '') {
            $imageFolder = 'ct_images';
            $imageFolderServer = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imageFolder);
            $imageFolderWeb = 'images/' . $imageFolder;
        } else {
            $f = str_replace('/', DIRECTORY_SEPARATOR, $imageFolder);
            if (strlen($f) > 0) {
                if ($f[0] == DIRECTORY_SEPARATOR)
                    $imageFolderServer = JPATH_SITE . str_replace('/', DIRECTORY_SEPARATOR, $imageFolder);
                else
                    $imageFolderServer = JPATH_SITE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imageFolder);

                $imageFolderWeb = $imageFolder;
            } else {
                $imageFolderServer = JPATH_SITE . DIRECTORY_SEPARATOR;
                $imageFolderWeb = '';
            }
        }

        //the returned list should be separated by ;
        $imageSRCListArray = array();
        $imgMethods = new CustomTablesImageMethods;

        foreach ($photoRows as $photoRow) {
            $photoRowPhotoId = $photoRow->photoid;
            if (str_contains($photoRowPhotoId, '-'))
                $photoRowPhotoId = str_replace('-', '', $photoRowPhotoId);

            if ($imagePrefix == '') {
                $imageFile = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';

                if ($imageFile != '')
                    $imageSRCListArray[] = ($addFolderPath ? $imageFolderWeb . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';

            } elseif ($imagePrefix == '_original') {
                $imageName = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
                $imageFileExtension = $imgMethods->getImageExtension($imageName);
                $imageFile = $imageName . '.' . $imageFileExtension;

                if ($imageFile != '')
                    $imageSRCListArray[] = ($addFolderPath ? $imageFolderWeb . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension;

            } else {
                $imageSizes = $imgMethods->getCustomImageOptions($params[0]);
                $foundImageSize = false;

                foreach ($imageSizes as $img) {
                    if ($img[0] == $imagePrefix) {
                        $imageName = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
                        $imageFileExtension = $imgMethods->getImageExtension($imageName);

                        if ($imageFileExtension != '') {
                            $imageSRCListArray[] = ($addFolderPath ? $imageFolderWeb . '/' : '') . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension;
                            $foundImageSize = true;
                            break;
                        }
                    }
                }

                if (!$foundImageSize)
                    $imageSRCListArray[] = 'filenotfound';
            }
        }

        return $imageSRCListArray;
    }
}
