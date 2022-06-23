<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\Fields;
use Joomla\CMS\Factory;

class updateFileBox
{
    public static function process()
    {
        $input = Factory::getApplication()->input;
        $old_typeparams = base64_decode($input->get('old_typeparams', '', 'BASE64'));
        if ($old_typeparams == '')
            return array('error' => 'old_typeparams not set');

        $old_params = JoomlaBasicMisc::csv_explode(',', $old_typeparams, '"', false);

        $new_typeparams = base64_decode($input->get('new_typeparams', '', 'BASE64'));
        if ($new_typeparams == '')
            return array('error' => 'new_typeparams not set');

        $new_params = JoomlaBasicMisc::csv_explode(',', $new_typeparams, '"', false);

        $fieldid = (int)$input->getInt('fieldid', 0);
        if ($fieldid == 0)
            return array('error' => 'fieldid not set');


        $field_row = Fields::getFieldRow($fieldid);
        $tableid = $field_row->tableid;
        $table_row = ESTables::getTableRowByID($tableid);

        $stepsize = (int)$input->getInt('stepsize', 10);

        $startindex = (int)$input->getInt('startindex', 0);
        $count = 0;
        if ($startindex == 0) {
            $count = updateImages::countImages($table_row->tablename, $field_row->fieldname);
        }

        /*
        $old_typeparams_array=JoomlaBasicMisc::csv_explode(',',$old_typeparams,'"',false);

        $old_ImageFolder_=CustomTablesImageMethods::getImageFolder($old_typeparams_array);
        $old_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$old_ImageFolder_);

        $new_typeparams_array=JoomlaBasicMisc::csv_explode(',',$new_typeparams,'"',false);

        $new_ImageFolder_=CustomTablesImageMethods::getImageFolder($new_typeparams_array);
        $new_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$new_ImageFolder_);
        */

        //$status=updateImages::processImages($table_row->tablename,$field_row->fieldname, $old_typeparams_array[0], $new_typeparams_array[0],
        //$old_ImageFolder,$new_ImageFolder, $startindex, $stepsize);

        $status = updateImages::processImages($table_row->realtablename, $field_row->realfieldname, $old_params, $new_params, $startindex, $stepsize);

        return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startindex, 'stepsize' => $stepsize, 'error' => $status);


    }
    /*
    protected static function countImages($establename,$esfieldname)
    {
        $db = Factory::getDBO();
        $query = 'SELECT count(id) AS c FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';

        $db->setQuery( $query );

        $recs=$db->loadAssocList();

        return (int)$recs[0]['c'];
    }

    protected static function processImages($realtablename,$realfieldname, string $old_imageparams, string $new_imageparams, $old_ImageFolder, $new_ImageFolder, $startindex, $stepsize, $deleteOriginals=false)
    {
        $db = Factory::getDBO();
        $query = 'SELECT '.$realfieldname.' FROM '.$realtablename.' WHERE '.$realfieldname.'>0';
        $db->setQuery($query);

        $imagelist=$db->loadAssocList();

        $imgMethods= new CustomTablesImageMethods;
        $old_imagesizes=$imgMethods->getCustomImageOptions($old_imageparams);
        $new_imagesizes=$imgMethods->getCustomImageOptions($new_imageparams);

        foreach($imagelist as $img)
        {
            $status=updateImages::processImage($imgMethods,$old_imagesizes,$new_imagesizes,$img[$realfieldname],$old_ImageFolder, $new_ImageFolder);
            //if $status is null then all good, status is a text string with error message if any
            if($status!== null)
                return $status;
        }

        return null;
    }
    */
    /*
    protected static function processImage_Thumbnail(&$imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder)
    {
        //Check thumbnail
        $prefix='_esthumb';
        $imagefile_ext='jpg';
        $old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
        $new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;

        if(file_exists(JPATH_SITE.$old_imagefile))
        {
            if($old_ImageFolder!=$new_ImageFolder)
            {
                if(!@rename(JPATH_SITE.$old_imagefile,JPATH_SITE.$new_imagefile))
                    return 'cannot move file to '.$new_imagefile;
            }

        }
        else
        {
            return 'file not found';
        }

        return null;
    }
    */
    /*
    protected static function processImage_Original(&$imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder,&$original_image_file)
    {
        //Check original image file
        $prefix='_original';
        $old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;
        $imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.$old_imagefile);//file extension is unknow - let's find out

        if($imagefile_ext!='')
        {
            $old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
            $new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
            if(file_exists(JPATH_SITE.$old_imagefile))
            {
                if($old_ImageFolder!=$new_ImageFolder)
                {
                    if(!@rename(JPATH_SITE.$old_imagefile,JPATH_SITE.$new_imagefile))
                        return 'cannot move file to '.$new_imagefile;
                    else
                        $original_image_file=$new_imagefile;
                }
                else
                    $original_image_file=$old_imagefile;
            }
            else
                return 'file not found';
        }
        else
            return 'file not found';

        return null;
    }
    */
    /*
    protected static function processImage_CustomSize_MoveFile(&$imgMethods,$old_imagesize,$rowValue,$old_ImageFolder, $new_ImageFolder, $prefix,$imagefile_ext="",$original_image_file)
    {
        $old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

        if($imagefile_ext=='')
            $imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.$original_image_file);//file extension is unknow - let's find out based on original file


        if($imagefile_ext!='')
        {
            $old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
            $new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;

            if(!file_exists(JPATH_SITE.$old_imagefile))
            {
                //Custom size file not found, create it
                $width=(int)$old_imagesize[1];
                $height=(int)$old_imagesize[2];

                $color=(int)$old_imagesize[3];
                $watermark=$old_imagesize[5];

                $r=$imgMethods->ProportionalResize(JPATH_SITE.$original_image_file,JPATH_SITE.$old_imagefile, $width, $height,1,true, $color, $watermark);

                if($r!=1)
                    return 'cannot create file: '.$old_imagefile;
            }

            if(file_exists(JPATH_SITE.$old_imagefile))
            {
                if($old_ImageFolder!=$new_ImageFolder)
                {
                    //Move exiting custom size file to new folder
                    if(!@rename(JPATH_SITE.$old_imagefile,JPATH_SITE.$new_imagefile))
                        return 'cannot move file to '.$new_imagefile;
                }
            }
            else
            {
                return 'cannot create and move file: '.$old_imagefile;
            }
        }

        return null;
    }
    */
    /*
    protected static function processImage_CustomSizes(&$imgMethods,$old_imagesizes,$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file)
    {
        //Move files if neccesary
        foreach($old_imagesizes as $img)
        {
            $status=updateImages::processImage_CustomSize_MoveFile($imgMethods,$img,$rowValue,$old_ImageFolder, $new_ImageFolder, $prefix=$img[0],$imagefile_ext=$img[4],$original_image_file);
            if($status!== null)
                return $status;
        }

        return null;
    }
    */
    /*
    protected static function processImage(&$imgMethods,&$old_imagesizes,&$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder)
    {
        $original_image_file='';
        $status=updateImages::processImage_Original($imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file);

        if($status!== null)
            return null;//Skip if original file not found

        $status=updateImages::processImage_Thumbnail($imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder);
        if($status!== null)
        {
            //Create Thumbnail file
            $r=$imgMethods->ProportionalResize(JPATH_SITE.$original_image_file,JPATH_SITE.$new_ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$rowValue.'.jpg', 150, 150,1,true, -1, '');

            if($r!=1)
            {
                $isOk=false;
            }
        }

        //Move custom size files to new folder, or create if custom size file in original folder is missing
        $status=updateImages::processImage_CustomSizes($imgMethods,$old_imagesizes,$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file);

        //Delete custom size file if no longer in use or size or propery changed


        //Create custom size file that doesnt exist


        return null;

    }
    */
}
