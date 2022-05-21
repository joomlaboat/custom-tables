<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

class CT_FieldTypeTag_imagegallery
{
    public static function getGalleryRows($establename, $galleryName, $listing_id)
    {
        $db = Factory::getDBO();
        $photoTableName = '#__customtables_gallery_' . $establename . '_' . $galleryName;

        $query = 'SELECT photoid, photo_ext FROM ' . $photoTableName . ' WHERE listingid=' . (int)$listing_id . ' ORDER BY ordering, photoid';
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public static function getImageGallerySRC($photoRows, $imagePrefix, $object_id, $galleryName, array $params, &$imageSRCList, &$imageTagList, $tableId): bool
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


        if (!isset($photoRows))
            return false;

        $conf = Factory::getConfig();
        $sitename = $conf->get('config.sitename');

        //the returned list should be separated by ;
        $imageSRCListArray = array();
        $imageTagListArray = array();

        $imgMethods = new CustomTablesImageMethods;

        foreach ($photoRows as $photoRow) {
            $photoRowPhotoId = $photoRow->photoid;
            if (str_contains($photoRowPhotoId, '-')) {
                //$isShortcut=true;
                $photoRowPhotoId = str_replace('-', '', $photoRowPhotoId);
            }

            if ($imagePrefix == '') {
                $imageFile = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';
                $imageFileWeb = $imageFolderWeb . '/' . $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';

                if ($imageFile != '') {
                    $imageTagListArray[] = '<img src="' . $imageFileWeb . '" width="150" height="150" alt="' . $sitename . '" title="' . $sitename . '" />';
                    $imageSRCListArray[] = $imageGalleryPrefix . $tableId . '_' . $galleryName . '__esthumb_' . $photoRowPhotoId . '.jpg';
                }
            } elseif ($imagePrefix == '_original') {
                $imageName = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
                $imageFileExtension = $imgMethods->getImageExtention($imageName);

                $imageFile = $imageName . '.' . $imageFileExtension;

                $imageFileWeb = $imageFolderWeb . '/' . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.jpg';
                if ($imageFile != '') {
                    $imageTagListArray[] = '<img src="' . $imageFileWeb . '" alt="' . $sitename . '" title="' . $sitename . '" />';
                    $imageSRCListArray[] = $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension;
                }
            } else {
                $imageSizes = $imgMethods->getCustomImageOptions($params[0]);
                $foundImageSize = false;

                foreach ($imageSizes as $img) {
                    if ($img[0] == $imagePrefix) {
                        $imageName = $imageFolderServer . DIRECTORY_SEPARATOR . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId;
                        $imageFileExtension = $imgMethods->getImageExtention($imageName);

                        if ($imageFileExtension != '') {
                            $imageNameWeb = $imageFolderWeb . '/' . $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension;

                            $imageTagListArray[] = '<img src="' . $imageNameWeb . '" ' . ($img[1] > 0 ? 'width="' . $img[1] . '"' : '') . ' ' . ($img[2] > 0 ? 'height="' . $img[2] . '"' : '') . ' alt="' . $sitename . '" title="' . $sitename . '" />';
                            $imageSRCListArray[] = $imageGalleryPrefix . $tableId . '_' . $galleryName . '_' . $imagePrefix . '_' . $photoRowPhotoId . '.' . $imageFileExtension;
                            $foundImageSize = true;
                            break;
                        }
                    }
                }

                if (!$foundImageSize) {

                    $imageTagListArray[] = 'filenotfound';
                    $imageSRCListArray[] = 'filenotfound';
                }
            }
        }

        $imageSRCList = implode(';', $imageSRCListArray);
        $imageTagList = implode('', $imageTagListArray);
        return true;
    }
}
