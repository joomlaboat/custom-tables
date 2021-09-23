<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
  * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

class updateImages
{
    public static function process()
	{
		$input	= JFactory::getApplication()->input;
		$old_typeparams=base64_decode($input->get('old_typeparams','','BASE64'));
		if($old_typeparams=='')
			return array('error'=>'old_typeparams not set');
			
		$new_typeparams=base64_decode($input->get('new_typeparams','','BASE64'));
		if($new_typeparams=='')
			return array('error'=>'new_typeparams not set');
		
		$fieldid=(int)$input->getInt('fieldid',0);
		if($fieldid==0)
			return array('error'=>'fieldid not set');
		
		
		$field_row=Fields::getFieldRow($fieldid);
		$tableid=$field_row->tableid;
		$table_row=ESTables::getTableRowByID($tableid);
		
		$stepsize=(int)$input->getInt('stepsize',10);
		
		$startindex=(int)$input->getInt('startindex',0);
		$count=0;
		if($startindex==0)
		{
			$count=updateImages::countImages($table_row->tablename,$field_row->fieldname);
			if($stepsize>$count)
				$stepsize=$count;
		}
		
		$old_ImageFolder_=CustomTablesImageMethods::getImageFolder($old_typeparams);
		$old_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$old_ImageFolder_);
		
		$new_ImageFolder_=CustomTablesImageMethods::getImageFolder($new_typeparams);
		$new_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$new_ImageFolder_);
		
		$status=updateImages::processImages($table_row->realtablename,$field_row->realfieldname, $old_typeparams, $new_typeparams, $old_ImageFolder,$new_ImageFolder, $startindex, $stepsize);

		return array('count'=>$count,'success'=>(int)($status==null),'startindex'=>$startindex,'stepsize'=>$stepsize,'error'=>$status);
		
	}
	
	protected static function countImages($establename,$esfieldname)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT count(id) AS c FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';
	
		$db->setQuery( $query );
		
		$recs=$db->loadAssocList();
		
		return (int)$recs[0]['c'];
	}
	
	protected static function processImages($realtablename,$realfieldname,$old_typeparams, $new_typeparams, $old_ImageFolder, $new_ImageFolder, $startindex, $stepsize, $deleteOriginals=false)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT '.$realfieldname.' FROM '.$realtablename.' WHERE '.$realfieldname.'>0';
		$db->setQuery($query, $startindex, $stepsize);

		$imagelist=$db->loadAssocList();
		
		$imgMethods= new CustomTablesImageMethods;
		$old_imagesizes=$imgMethods->getCustomImageOptions($old_typeparams);
		$new_imagesizes=$imgMethods->getCustomImageOptions($new_typeparams);
		
		foreach($imagelist as $img)
		{
			$status=updateImages::processImage($imgMethods,$old_imagesizes,$new_imagesizes,$img[$realfieldname],$old_ImageFolder, $new_ImageFolder);
			//if $status is null then all good, status is a text string with error message if any
			if($status!=null)
				return $status;
		}
		
		return null;
	}
	
	protected static function processImage_Thumbnail(&$imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder)
	{
		//Check thumbnail
		$prefix='_esthumb';
		$imagefile_ext='jpg';
		$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
		$new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
		
		if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
		{
			if($old_ImageFolder!=$new_ImageFolder)
			{
				if(!@rename(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile,JPATH_SITE.DIRECTORY_SEPARATOR.$new_imagefile))
					return 'cannot move file to '.$new_imagefile;
			}
		}
		else
			return 'file not found';
	
		return null;
	}
	
	protected static function processImage_Original(&$imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder,&$original_image_file)
	{
		//Check original image file
		$prefix='_original';
		$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

		$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile);//file extension is unknow - let's find out

		if($imagefile_ext!='')
		{
			$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			$new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
			{
				if($old_ImageFolder!=$new_ImageFolder)
				{
					if(!@rename(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile,JPATH_SITE.DIRECTORY_SEPARATOR.$new_imagefile))
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
	
	protected static function processImage_CustomSize_MoveFile(&$imgMethods,$old_imagesize,$rowValue,$old_ImageFolder, $new_ImageFolder, $prefix,$imagefile_ext="",$original_image_file)
	{
		$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;
		
		if($imagefile_ext=='')
			$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file);//file extension is unknow - let's find out based on original file
	
		if($imagefile_ext!='')
		{
			$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			$new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			
			if(!file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
			{
				//Custom size file not found, create it
				$width=(int)$old_imagesize[1];
				$height=(int)$old_imagesize[2];

				$color=(int)$old_imagesize[3];
				$watermark=$old_imagesize[5];

				$r=$imgMethods->ProportionalResize(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file,JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile, $width, $height,1,true, $color, $watermark);

				if($r!=1)
					return 'cannot create file: '.$old_imagefile;
			}

			if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
			{
				if($old_ImageFolder!=$new_ImageFolder)
				{
					//Move exiting custom size file to new folder
					if(!@rename(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile,JPATH_SITE.DIRECTORY_SEPARATOR.$new_imagefile))
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
	
	protected static function processImage_CustomSize_createFile(&$imgMethods,$new_imagesize,$rowValue,$new_ImageFolder, $prefix,$imagefile_ext="",$original_image_file)
	{
		$new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;
	
		if($imagefile_ext=='')
			$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file);//file extension is unknow - let's find out based on original file

		if($imagefile_ext!='')
		{
			$new_imagefile=$new_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			
			if(!file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$new_imagefile))
			{
				//Custom size file not found, create it
				$width=(int)$new_imagesize[1];
				$height=(int)$new_imagesize[2];

				$color=(int)$new_imagesize[3];
				$watermark=$new_imagesize[5];

				$r=$imgMethods->ProportionalResize(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file,JPATH_SITE.DIRECTORY_SEPARATOR.$new_imagefile, $width, $height,1,true, $color, $watermark);

				if($r!=1)
					return 'cannot create file: '.$new_imagefile;
			}
		}
		
		return null;
	}
	
	protected static function processImage_CustomSize_deleteFile(&$imgMethods,$rowValue,$old_ImageFolder, $prefix, $imagefile_ext="",$original_image_file)
	{
		$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;
		
		if($imagefile_ext=='')
			$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file);//file extension is unknow - let's find out based on original file
		
		if($imagefile_ext!='')
		{
			$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			
			if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
			{
				if (!@unlink(JPATH_SITE.DIRECTORY_SEPARATOR.$old_imagefile))
					return 'cannot delete old file: '.$old_imagefile;
			}
		}
		
		return null;
	}
	
	protected static function processImage_CustomSizes(&$imgMethods,$old_imagesizes,$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file)
	{
		//Move files if neccesary
		foreach($old_imagesizes as $img)
		{
			$status=updateImages::processImage_CustomSize_MoveFile($imgMethods,$img,$rowValue,$old_ImageFolder, $new_ImageFolder, $prefix=$img[0],$imagefile_ext=$img[4],$original_image_file);
			if($status!=null)
				return $status;
		}
		
		return null;
	}
	
	protected static function processImage(&$imgMethods,&$old_imagesizes,&$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder)
	{
		$original_image_file='';
		
		$status=updateImages::processImage_Original($imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file);
	
		if($status!=null)
			return null;//Skip if original file not found
		
		$status=updateImages::processImage_Thumbnail($imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder);
		if($status!=null)
		{
			//Create Thumbnail file
			$r=$imgMethods->ProportionalResize(JPATH_SITE.DIRECTORY_SEPARATOR.$original_image_file,JPATH_SITE.DIRECTORY_SEPARATOR.$new_ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$rowValue.'.jpg', 150, 150,1,true, -1, '');

			if($r!=1)
				$isOk=false;
		}
		
		//Move custom size files to new folder, or create if custom size file in original folder is missing
		$status=updateImages::processImage_CustomSizes($imgMethods,$old_imagesizes,$new_imagesizes,$rowValue,$old_ImageFolder, $new_ImageFolder,$original_image_file);
		
		//Delete custom size file if no longer in use or size or propery changed
		$image_sizes_to_delete=updateImages::findChangedOrDeletedCustomSizes($old_imagesizes,$new_imagesizes);

		//Delete old files
		foreach($image_sizes_to_delete as $img)
		{
			$status=updateImages::processImage_CustomSize_deleteFile($imgMethods,$rowValue,$new_ImageFolder, $prefix=$img[0],$imagefile_ext=$img[4],$original_image_file);
			if($status!=null)
				return $status;
		}
		
		//Create custom size file that doesnt exist
		foreach($new_imagesizes as $img)
		{
			$status=updateImages::processImage_CustomSize_createFile($imgMethods,$img,$rowValue,$new_ImageFolder, $prefix=$img[0],$imagefile_ext=$img[4],$original_image_file);
			if($status!=null)
				return $status;
		}
		
		return null;
	
	}
	
	protected static function findChangedOrDeletedCustomSizes(&$old_imagesizes,&$new_imagesizes)
	{
		$image_sizes_to_delete=array();
		
		foreach($old_imagesizes as $old_img)
		{
			$changed=false;
			
			foreach($new_imagesizes as $new_img)
			{
				if($old_img[0]==$new_img[0])//check if the size name is match
				{
					//Compare parameters
					for($i=1;$i<6;$i++)
					{
						if($old_img[$i]!=$new_img[$i])
						{
							$changed=true;
							$image_sizes_to_delete[]=$old_img;
							break;
						}
					}
				
					if($changed)
						break;
				}
			}

		}
		
		return $image_sizes_to_delete;
	}
}
