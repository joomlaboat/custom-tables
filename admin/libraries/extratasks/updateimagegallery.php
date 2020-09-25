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

$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
require_once($path.'misc.php');
require_once($path.'fields.php');
require_once($path.'imagemethods.php');

class updateImageGallery
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
		
		
		$field_row=ESFields::getFieldRow($fieldid);
		$tableid=$field_row->tableid;
		$table_row=ESTables::getTableRowByID($tableid);
		
		$stepsize=(int)$input->getInt('stepsize',10);
		
		$startindex=(int)$input->getInt('startindex',0);
		$count=0;
		if($startindex==0)
		{
			$count=updateImages::countImages($table_row->tablename,$field_row->fieldname);
		}
		
		$old_ImageFolder_=CustomTablesImageMethods::getImageFolder($old_typeparams);
		$old_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$old_ImageFolder_);
		
		$new_ImageFolder_=CustomTablesImageMethods::getImageFolder($new_typeparams);
		$new_ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$new_ImageFolder_);
		
		$status=updateImages::processImages($table_row->tablename,$field_row->fieldname, $old_typeparams, $new_typeparams, $old_ImageFolder,$new_ImageFolder, $startindex, $stepsize);
		die;
		return array('count'=>$count,'success'=>(int)($status==null),'startindex'=>$startindex,'stepsize'=>$stepsize,'error'=>$status);
		
	}
	
	protected static function countImages($establename,$esfieldname)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT count(id) AS c FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';
	
		$db->setQuery( $query );
		//if (!$db->query())    die( $db->stderr());
		
		$recs=$db->loadAssocList();
		
		return (int)$recs[0]['c'];
	}
	
	protected static function processImages($establename,$esfieldname,$old_typeparams, $new_typeparams, $old_ImageFolder, $new_ImageFolder, $startindex, $stepsize, $deleteOriginals=false)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT es_'.$esfieldname.' FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';
		$db->setQuery($query);
		//$db->setQuery($query, $startindex, $stepsize);

		$imagelist=$db->loadAssocList();
		
		$imgMethods= new CustomTablesImageMethods;
		$old_imagesizes=$imgMethods->getCustomImageOptions($old_typeparams);
		$new_imagesizes=$imgMethods->getCustomImageOptions($new_typeparams);
		
		
		//echo '$old_ImageFolder='.$old_ImageFolder.'<br/>';
		//echo '$new_ImageFolder='.$new_ImageFolder.'<br/>';

		foreach($imagelist as $img)
		{
			$status=updateImages::processImage($imgMethods,$old_imagesizes,$new_imagesizes,$img['es_'.$esfieldname],$old_ImageFolder, $new_ImageFolder);
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
		
		//echo '$old_ImageFolder='.$old_ImageFolder.'<br/>';
			//echo '$new_ImageFolder='.$new_ImageFolder.'<br/>';
			//echo '$old_imagefile='.$old_imagefile.'<br/>';
			//echo '$new_imagefile='.$new_imagefile.'<br/>';
		
		if(file_exists(JPATH_SITE.$old_imagefile))
		{
			//echo 'Found<br/>';
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
	
	protected static function processImage_CustomSize_MoveFile(&$imgMethods,$old_imagesize,$rowValue,$old_ImageFolder, $new_ImageFolder, $prefix,$imagefile_ext="",$original_image_file)
	{
		$old_imagefile=$old_ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;
		
		//echo '1$old_imagefile='.$old_imagefile.'<br/>';
		//echo '$imagefile_ext='.$imagefile_ext.'<br/>';
		
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
				{
					//echo 'cannot create file: '.$old_imagefile;
					return 'cannot create file: '.$old_imagefile;
				}
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
		
		//echo '$original_image_file='.$original_image_file.'<br/>';
		
		$status=updateImages::processImage_Thumbnail($imgMethods,$rowValue,$old_ImageFolder, $new_ImageFolder);
		if($status!=null)
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
	
	function doResizeImageGallery($imageparams, $ex_typeparams,$galleryname)
	{
		$imagemethods=new ExtraSearchImageMethods;
		
		if(strlen($ex_typeparams)>0)
		{
			$imagemethods->DeleteGalleryImages($this->establename,$this->estableid,$galleryname,$ex_typeparams, false);
		}
		
		$TypeParamsArr=explode('|',$imageparams);
		$imagefolder=$TypeParamsArr[0];
		if(!isset($TypeParamsArr[1]))
			return false;
		
		//Schadule Resize Process
		JRequest::setVar('view', 'tablefieldedit');
		JRequest::setVar( 'task', 'galleryresizeprocess');
		JRequest::setVar( 'galleryname',$galleryname);
		JRequest::setVar( 'imageparams',str_replace('#','$',$imageparams));
		
		//$imagemethods->CreateNewGalleryImages($this->establename,$this->estableid, $galleryname, $imagefolder,$imageparams);
		
		
	}
	
	function doResizeImageGalleryProcess()
	{
		
		$this->estableid=	JRequest::getInt( 'tableid',0);
		$this->establename= $this->ESTable->getTableName($this->estableid);
		
		$galleryname = JRequest::getCmd( 'galleryname');
		$imageparams = JRequest::getVar( 'imageparams');
		$startindex = JRequest::getInt( 'startindex',0);
		$total = JRequest::getInt( 'total',0);
		
		
		
		$imagemethods=new ExtraSearchImageMethods;
		
		$TypeParamsArr=explode('|',$imageparams);
		
		/*
		if(!isset($TypeParamsArr[1]))
		{
			//this is about comparing images 
			
			return false;
		}
		*/
		
		if(!isset($TypeParamsArr[1]))
			return true;
		
		$pair=explode(',',$TypeParamsArr[1]);
		
		if(!isset($pair[1]))
			return true;
		
		$imagefolderword=$pair[1];
		//$imagefolder=JPATH_SITE.DS.'images'.str_replace('/',DS,$imagefolderword);
		$imagefolder=JPATH_SITE.DS.str_replace('/',DS,$imagefolderword);
		
		echo '<h1>Resize Image Gallery Process</h1>';

		
		$step=$this->imageprocstep;
		$total=$imagemethods->CreateNewGalleryImages($this->establename,$this->estableid, $galleryname, $imagefolder,str_replace('$','#',$imageparams),$startindex,$step);
		
		$startindex+=$this->imageprocstep;
		if($startindex>$total)
			$startindex=$total;

		echo '<h2>Progress: '.$startindex.' of '.$total.'</h2>';

		
		
		if($startindex<$total)
		{
			$link 	= 'index.php?option=com_extrasearch'
					.'&view=tablefieldedit'
					.'&task=tablefieldedit.galleryresizeprocess'
					.'&tableid='.$this->estableid
					.'&galleryname='.$galleryname
					.'&imageparams='.$imageparams
					.'&total='.$total
					.'&startindex='.$startindex
					;

		
		echo '
		<script language="javascript">
		
		function imageresizeerstart() {
			window.location = "'.$link.'";
		}
		window.onload = imageresizeerstart;
		</script> 
		';
		
		
		
		}
		
		
		
		
		JRequest::setVar( 'total',$total);
		if($startindex>=$total)
			return -1; //finished
		
		return $startindex;
	}
	

}
