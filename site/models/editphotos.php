<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;

use CustomTables\Field;
use CustomTables\Fields;
use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'tables');

class CustomTablesModelEditPhotos extends JModelLegacy
{
    var CT $ct;
    var $imagemethods;
    var $listing_id;
    var $Listing_Title;
    var $galleryname;
    var $GalleryTitle;

    var $imagefolderword;
    var $imagefolder;
    var $imagefolderweb;
    var $imagemainprefix;
    var $maxfilesize;
    var $useridfield;
    var $phototablename;
    var $row;
    var Field $field;

    function __construct()
    {
        parent::__construct();
    }

    function load(): bool
    {
        $this->ct = new CT;

        $app = Factory::getApplication();
        $params = $app->getParams();

        $this->maxfilesize = JoomlaBasicMisc::file_upload_max_size();

        $this->imagefolderword = 'esimages';
        $this->imagefolderweb = 'images/esimages';
        $this->imagefolder = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'esimages';

        $this->imagemainprefix = 'g';
        $this->imagemethods = new CustomTablesImageMethods;

        $this->useridfield = $params->get('useridfield');

        $this->ct->getTable($params->get('establename'), $this->useridfield);

        if ($this->ct->Table->tablename === null) {
            Factory::getApplication()->enqueueMessage('Table not selected (62).', 'error');
            return false;
        }

        $this->listing_id = common::inputGetInt("listing_id", 0);
        if (!common::inputGetCmd('galleryname'))
            return false;

        $this->galleryname = common::inputGetCmd('galleryname');

        $this->getObject();

        if (!$this->getGallery())
            return false;

        $db = Factory::getDBO();
        $this->phototablename = $db->getPrefix() . 'customtables_gallery_' . $this->ct->Table->tablename . '_' . $this->galleryname;
        return true;
    }

    function getObject(): bool
    {
        $this->row = $this->ct->Table->loadRecord($this->listing_id);
        if ($this->row === null)
            return false;

        $this->Listing_Title = '';

        foreach ($this->ct->Table->fields as $mFld) {
            $titlefield = $mFld['realfieldname'];
            if (str_contains($mFld['type'], 'multi'))
                $titlefield .= $this->ct->Languages->Postfix;

            if ($this->row[$titlefield] != '') {
                $this->Listing_Title = $this->row[$titlefield];
                break;
            }
        }

        return true;
    }

    function getGallery(): bool
    {
        $fieldrow = Fields::getFieldAssocByName($this->galleryname, $this->ct->Table->tableid);
        if ($fieldrow === null)
            return false;

        $this->field = new Field($this->ct, $fieldrow, $this->row);
        $this->GalleryTitle = $this->field->title;
        $this->imagefolderword = CustomTablesImageMethods::getImageFolder($this->field->params);
        $this->imagefolderweb = $this->imagefolderword;

        $this->imagefolder = JPATH_SITE;
        if ($this->imagefolder[strlen($this->imagefolder) - 1] != '/' and $this->imagefolderword[0] != '/')
            $this->imagefolder .= '/';

        $this->imagefolder .= str_replace('/', DIRECTORY_SEPARATOR, $this->imagefolderword);
        //Create folder if not exists
        if (!file_exists($this->imagefolder)) {
            Factory::getApplication()->enqueueMessage('Path ' . $this->imagefolder . ' not found.', 'error');
            mkdir($this->imagefolder, 0755, true);
        }

        return true;
    }

    function reorder(): bool
    {
        $images = $this->getPhotoList();

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

        $db = Factory::getDBO();

        asort($images);
        $i = 0;
        foreach ($images as $image) {
            $safeTitle = common::inputGetString('esphototitle' . $image->photoid);
            $safeTitle = str_replace('"', "", $safeTitle);

            $query = 'UPDATE ' . $this->phototablename . ' SET ordering=' . $i . ', title' . $this->ct->Languages->Postfix . '="' . $safeTitle . '" WHERE listingid='
                . $this->listing_id . ' AND photoid=' . $image->photoid;

            $db->setQuery($query);
            $db->execute();

            $i++;
        }
        return true;
    }

    function getPhotoList()
    {
        // get database handle
        $db = Factory::getDBO();

        $query = 'SELECT ordering, photoid,  title' . $this->ct->Languages->Postfix . ' AS title, photo_ext FROM ' . $this->phototablename
            . ' WHERE listingid=' . $this->listing_id . ' ORDER BY ordering, photoid';

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    function delete(): bool
    {
        $db = Factory::getDBO();

        $photoids = common::inputGetString('photoids', '');
        $photo_arr = explode('*', $photoids);

        foreach ($photo_arr as $photoid) {
            if ($photoid != '') {
                $this->imagemethods->DeleteExistingGalleryImage($this->imagefolder, $this->imagemainprefix, $this->ct->Table->tableid, $this->galleryname,
                    $photoid, $this->field->params[0], true);

                $query = 'DELETE FROM ' . $this->phototablename . ' WHERE listingid=' . $this->listing_id . ' AND photoid=' . $photoid;
                $db->setQuery($query);
                $db->execute();
            }
        }

        $this->ct->Table->saveLog($this->listing_id, 7);
        return true;
    }

    function add(): bool
    {
        $file = common::inputFiles('uploadedfile');

        $uploadedfile = "tmp/" . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $uploadedfile))
            return false;

        if (common::inputGetCmd('base64ecnoded', '') == "true") {
            $src = $uploadedfile;
            $dst = "tmp/decoded_" . basename($file['name']);
            $this->base64file_decode($src, $dst);
            $uploadedfile = $dst;
        }

        //Check file
        if (!$this->imagemethods->CheckImage($uploadedfile, JoomlaBasicMisc::file_upload_max_size()))//$this->maxfilesize
        {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_BROKEN_IMAGE'), 'error');
            unlink($uploadedfile);
            return false;
        }

        //Save to DB
        $photo_ext = $this->imagemethods->FileExtension($uploadedfile);
        $filenameParts = explode('/', $uploadedfile);
        $filename = end($filenameParts);
        $title = str_replace('.' . $photo_ext, '', $filename);

        $photoId = $this->addPhotoRecord($photo_ext, $title);

        $isOk = true;

        //es Thumb
        $newFileName = $this->imagefolder . DIRECTORY_SEPARATOR . $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '__esthumb_' . $photoId . ".jpg";
        $r = $this->imagemethods->ProportionalResize($uploadedfile, $newFileName, 150, 150, 1, -1, '');

        if ($r != 1)
            $isOk = false;

        $customSizes = $this->imagemethods->getCustomImageOptions($this->field->params[0]);

        foreach ($customSizes as $imagesize) {
            $prefix = $imagesize[0];
            $width = (int)$imagesize[1];
            $height = (int)$imagesize[2];
            $color = (int)$imagesize[3];

            //save as extention
            if ($imagesize[4] != '')
                $ext = $imagesize[4];
            else
                $ext = $photo_ext;

            $newFileName = $this->imagefolder . DIRECTORY_SEPARATOR . $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '_' . $prefix . '_' . $photoId . "." . $ext;
            $r = $this->imagemethods->ProportionalResize($uploadedfile, $newFileName, $width, $height, 1, $color, '');

            if ($r != 1)
                $isOk = false;
        }

        if ($isOk) {
            $originalName = $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '__original_' . $photoId . "." . $photo_ext;

            if (!copy($uploadedfile, $this->imagefolder . DIRECTORY_SEPARATOR . $originalName)) {
                unlink($uploadedfile);
                return false;
            }
        } else {
            unlink($uploadedfile);
            return false;
        }

        unlink($uploadedfile);
        $this->ct->Table->saveLog($this->listing_id, 6);

        return true;
    }

    function base64file_decode($inputfile, $outputfile)
    {
        /* read data (binary) */
        $ifp = fopen($inputfile, "rb");
        $srcData = fread($ifp, filesize($inputfile));
        fclose($ifp);
        /* encode & write data (binary) */
        $ifp = fopen($outputfile, "wb");
        fwrite($ifp, base64_decode($srcData));
        fclose($ifp);
        /* return output filename */
        return ($outputfile);
    }

    protected function addPhotoRecord(string $photo_ext, string $title): int
    {
        $db = Factory::getDBO();

        $query = 'INSERT ' . $this->phototablename . ' SET '
            . 'ordering=100, '
            . 'photo_ext=' . $db->quote($photo_ext) . ', '
            . 'listingid=' . $db->quote($this->listing_id) . ', '
            . 'title=' . $db->quote($title);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            die;
        }

        $this->AutoReorderPhotos();


        $query = ' SELECT photoid FROM ' . $this->phototablename . ' WHERE listingid=' . $db->quote($this->listing_id) . ' ORDER BY photoid DESC LIMIT 1';
        $db->setQuery($query);

        $rows = $db->loadObjectList();
        if (count($rows) == 1)
            return $rows[0]->photoid;

        return -1;
    }

    function AutoReorderPhotos(): bool
    {
        $images = $this->getPhotoList();
        $db = Factory::getDBO();

        asort($images);
        $i = 0;
        foreach ($images as $image) {
            $safeTitle = common::inputGetString('esphototitle' . $image->photoid);
            $safeTitle = str_replace('"', "", $safeTitle);

            if ($safeTitle != '') {
                $query = 'UPDATE ' . $this->phototablename . ' SET ordering=' . $i . ', title' . $this->ct->Languages->Postfix . '=' . $db->quote($safeTitle) . ' WHERE listingid='
                    . $this->listing_id . ' AND photoid=' . $image->photoid;
            } else {
                $query = 'UPDATE ' . $this->phototablename . ' SET ordering=' . $i . ' WHERE listingid='
                    . $this->listing_id . ' AND photoid=' . $image->photoid;
            }

            $db->setQuery($query);
            $db->execute();
            $i++;
        }
        return true;
    }

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
}
