<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesImageMethods
{
	const allowedExtensions = ['jpg','png','gif','jpeg','webp'];
	
	function FileExtenssion($src)
	{
		$ext_list = explode('.', strtolower($src));
		$ext = end($ext_list);
		
		if(in_array($ext,CustomTablesImageMethods::allowedExtensions))
			return $ext;
		
		return '';
	}

	function getColorDec($vlu)
	{
		if($vlu=='transparent')
			return -2;

		elseif($vlu=='fill')
			return -1;

		elseif($vlu=='black')
			return 0;

		elseif($vlu=='white')
			return hexdec('ffffff');

		elseif($vlu=='red')
			return hexdec('ff0000');

		elseif($vlu=='green')
			return hexdec('00ff00');

		elseif($vlu=='blue')
			return hexdec('0000ff');

		elseif($vlu=='yellow')
			return hexdec('ffff00');

		elseif(!(strpos($vlu,'#')===false))
			return hexdec(str_replace('#','',$vlu));//As of PHP 7.4.0 supplying any invalid characters is deprecated. 
		else
			return (int)$vlu;
	}

	
	function getCustomImageOptions($imageparams_)
	{
		$TypeParamsArr=JoomlaBasicMisc::csv_explode(',',$imageparams_,'"',false);
		$imageparams=$TypeParamsArr[0];

		$cleanOptions=array();
		//custom images
		$imagesizes=explode(';',$imageparams);

		foreach($imagesizes as $imagesize)
		{
			$imageoptions=explode(',',$imagesize);
			if(count($imageoptions)>1)
			{
				$prefix=strtolower(trim(preg_replace("/[^a-zA-Z0-9]/", "", $imageoptions[0])));

				if(strlen($prefix)>0)
				{
					//name, width, height, color (0 - black, -1 - bg fill, -2 - trasparent)
					if(count($imageoptions)<2)
						$imageoptions[1]='';

					if(count($imageoptions)<3)
						$imageoptions[2]='';

					if(count($imageoptions)<4)
						$imageoptions[3]='';

					$imageoptions[3]=$this->getColorDec($imageoptions[3]);

					if(count($imageoptions)<5)
						$imageoptions[4]='';

					if($imageoptions[4]!='')
					{
						if(!in_array($imageoptions[4],CustomTablesImageMethods::allowedExtensions))
							$imageoptions[4]='';
					}
					else
						$imageoptions[4]='';

					if(count($imageoptions)<6)
						$imageoptions[5]='';

					$cleanOptions[]=array($prefix,(int)$imageoptions[1],(int)$imageoptions[2],(int)$imageoptions[3],$imageoptions[4],$imageoptions[5]);
				}
			}
		}
		return $cleanOptions;
	}


	function DeleteExistingSingleImage($ExistingImage,$ImageFolder,$imageparams, $realtablename, $realfieldname, $realidfield)
	{
		//$realtablename='-options'
		
		$customsizes=$this->getCustomImageOptions($imageparams);
		CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realidfield);

		foreach($customsizes as $customsize)
			CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder,$customsize[0]);
	}

	function DeleteExistingGalleryImage($ImageFolder,$ImageMainPrefix, $estableid, $galleryname, $photoid,$imageparams,$deleteOriginals=false)
	{
		//Delete original thumbnails
		if($deleteOriginals)
		{
			$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'__esthumb_'.$photoid.'.jpg';
			if(file_exists($filename))
				unlink($filename);
		}

		$customsizes=$this->getCustomImageOptions($imageparams);

		foreach(CustomTablesImageMethods::allowedExtensions as $photo_ext)
		{
			//delete orginal full size images
			if($deleteOriginals)
			{
				$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'__original_'.$photoid.'.'.$photo_ext;
				if(file_exists($filename))
					unlink($filename);
			}

			//Delete custom size images
			foreach($customsizes as $customsize)
			{
				$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'_'.$customsize[0].'_'.$photoid.'.'.$photo_ext;
				if(file_exists($filename))
					unlink($filename);
			}
		}
	}


	function DeleteGalleryImages($gallery_table_name, $estableid, $galleryname,$typeparams,$deleteOriginals=false)
	{
		$TypeParamsArr=JoomlaBasicMisc::csv_explode(',',$typeparams,'"',false);
		$image_parameters=$TypeParamsArr[0];

		$imagefolderword='';
		if(isset($image_parameters[1]))
			$imagefolderword=$image_parameters[1];

		$imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.str_replace('/',DIRECTORY_SEPARATOR,$imagefolderword);
		$imagegalleryprefix='g';

		//delete gallery images if exist
		$db = JFactory::getDBO();

		//check if table exists
		$query = 'SHOW TABLES LIKE "'.$gallery_table_name.'"';
		$db->setQuery($query);
		
		$recs=$db->loadObjectList();

		if(count($recs)>0)
		{
			$query = 'SELECT photoid FROM '.$gallery_table_name;
			$db->setQuery($query);
			
			$photorows=$db->loadObjectList();

			foreach($photorows as $photorow)
			{
				$this->DeleteExistingGalleryImage(
					$imagefolder,
					$imagegalleryprefix,
					$estableid,
					$galleryname,
					$photorow->photoid,
					$image_parameters,
					$deleteOriginals
				);
			}//foreach($photorows as $photorow)
		}
	}

	static protected function DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realidfield)
	{
		//This function deletes original images in case image not ocupied by another record.

		//---------- find child ----------
		//check if the image has child or not
		if($realtablename!='-options')
		{
			$db = JFactory::getDBO();

			if($ExistingImage=='')
				$ExistingImage=0;

			if($ExistingImage > 0)
			{
				//If its an original image not duplicate, find one duplicate and convert it to original
				$query = 'SELECT '.$realidfield.' FROM '.$realtablename.' WHERE '.$realfieldname.'=-'.$ExistingImage.' LIMIT 1';
				$db->setQuery($query);
				$photorows=$db->loadAssocList();
				
				if(count($photorows)==1) //do not compare if there is a child
				{
					$photorow=$photorows[0];

					//Null Parent
					$query = 'UPDATE '.$realtablename.' SET '.$realfieldname.'=0 WHERE '.$realfieldname.'='.$ExistingImage;
					$db->setQuery( $query );
					$db->execute();

					//Convert Child to Parent
					$query = 'UPDATE '.$realtablename.' SET '.$realfieldname.'='.$ExistingImage.' WHERE '.$realidfield.'='.(int)$photorow[$realidfield];

					$db->setQuery( $query );
					$db->execute();
					return true;
				}
			}//if
		}

		foreach(CustomTablesImageMethods::allowedExtensions as $photo_ext)
		{
			if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ExistingImage.'.'.$photo_ext))
				unlink($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ExistingImage.'.'.$photo_ext);

			if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ExistingImage.'.'.$photo_ext))
				unlink($ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ExistingImage.'.'.$photo_ext);
		}
	}
	
	
	static protected function DeleteCustomImage($ExistingImage, $ImageFolder, $CustomSize)
	{
		foreach(CustomTablesImageMethods::allowedExtensions as $photo_ext)
		{
			if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.$CustomSize.'_'.$ExistingImage.'.'.$photo_ext))
				unlink($ImageFolder.DIRECTORY_SEPARATOR.$CustomSize.'_'.$ExistingImage.'.'.$photo_ext);
		}
	}

	function DeleteCustomImages($realtablename, $realfieldname, $ImageFolder, $imageparams, $realidfield, $deleteOriginals=false)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT '.$realfieldname.' FROM '.$realtablename.' WHERE '.$realfieldname.'>0';

		$db->setQuery( $query );
		$imagelist=$db->loadAssocList();
		$customsizes=$this->getCustomImageOptions($imageparams);

		foreach($imagelist as $img)
		{
			$ExistingImage=$img[$realfieldname];

			if($deleteOriginals)
				CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $realtablename, $realfieldname, $realidfield);

			foreach($customsizes as $customsize)
				CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder, $customsize[0]);
		}
	}

	function getImageExtention($ImageName_noExt)
	{
		foreach(CustomTablesImageMethods::allowedExtensions as $photo_ext)
		{
			$filename = $ImageName_noExt.'.'.$photo_ext;

			if(file_exists($filename))
				return $photo_ext;
		}
		return '';
	}

	function CreateNewCustomImages($realtablename, $realfieldname, $ImageFolder, $imageparams, $startindex, $step, $realidfield)
	{
		$count=0;
		$db = JFactory::getDBO();

		$query = 'SELECT '.$realfieldname.' FROM '.$realtablename.' WHERE '.$realfieldname.'>0';
		$db->setQuery( $query );
		$imagelist=$db->loadAssocList();

		$pair=JoomlaBasicMisc::csv_explode(',',$imageparams,'"',false);
		$compareexisting=false;
		if(isset($pair[1]))
		{
			//Additional Parameters
			$second_pair=explode(':',$pair[1]);

			//Special Plugin
			if(strpos($second_pair[0],'compareexisting')!==false)
			{
				$compareexisting=true;

				$identity=4;
				if(isset($second_pair[1]))
					$identity=(int)$second_pair[1];
			}

			if(isset($pair[2]))
				$ImageFolder=$pair[2]; //Path
		}

		$customsizes=$this->getCustomImageOptions($imageparams);

		foreach($imagelist as $img)
		{
			if($count>=$startindex)
			{
				$ImageID=$img[$realfieldname];
				$originalImage=$ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID;
				$ImgExtention=$this->getImageExtention($originalImage);

				if($ImgExtention!='')
				{
					$originalImage.='.'.$ImgExtention;
					$DeleteExistingImage=false;
					if($compareexisting)
					{
						//check if the image has child or not
						$query = 'SELECT '.$realidfield.' AS photoid FROM '.$realtablename.' WHERE '.$realfieldname.'=-'.$ImageID;
						$db->setQuery($query);
						$db->execute();

						if($db->getNumRows()==0) //do not compare if there is a child
						{
							$NewImageID=-FindSimilarImage::find($originalImage,$identity,$realtablename,$realfieldname,$ImageFolder);
							if($NewImageID!=0)
							{
								$DeleteExistingImage=true;

								//Update Table
								$query = 'UPDATE '.$realtablename.' SET '.$realfieldname.'='.$NewImageID.' WHERE '.$realfieldname.'='.$ImageID;
								$db->setQuery( $query );
								$db->execute();
							}
						}//if
					}//if

					if($DeleteExistingImage)
					{
						//Delete Image
						CustomTablesImageMethods::DeleteOriginalImage($ImageID, $ImageFolder, $realtablename, $realfieldname, $realidfield);
					}
					else
					{
						$thumbimage=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ImageID.'.jpg';
						if(!file_exists($thumbimage))
							$this->ProportionalResize($originalImage,$thumbimage, 150, 150,1,true, -1, '');
	
						foreach($customsizes as $imagesize)
						{
							$prefix=$imagesize[0];
							$width=(int)$imagesize[1];
							$height=(int)$imagesize[2];
							$color=(int)$imagesize[3];
							$watermark=$imagesize[5];

							//save as extention
							if($imagesize[4]!='')
								$ext=$imagesize[4];
							else
								$ext=$ImgExtention;

							$newfilename=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$ImageID.'.'.$ext;

							if(!file_exists($newfilename))
								$r=$this->ProportionalResize($originalImage, $newfilename, $width, $height,1,true, $color, $watermark);
						}//foreach
					}//if($DeleteExistingImage)
				}//if($ImgExtention!='')
			}
			if($count-$startindex>=$step-1)
				break;

			$count++;
		}//foreach($imagelist as $img)

		return count($imagelist);
	}

	function UploadSingleImage($ExistingImage, $image_file_id, $realfieldname, $ImageFolder, $imageparams_full, $realtablename,$realidfieldname)
	{
		//$realtablename = '-options'
		
		if(is_object('JFactory::getApplication()'))
			$jinput = JFactory::getApplication()->input;
		else
			$jinput = null;
			
		if($image_file_id!='')
		{
			$pair=JoomlaBasicMisc::csv_explode(',',$imageparams_full,'"',false);

			$additional_params='';
			if(isset($pair[1]))
				$additional_params=$pair[1];
				
			if(strpos($image_file_id,DIRECTORY_SEPARATOR)===false)//in case when other applications pass full path to the file
				$uploadedfile = JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$image_file_id;
			else
				$uploadedfile = $image_file_id;
		
			if(is_object('JFactory::getApplication()'))		
				$is_base64encoded = JFactory::getApplication()->input->get('base64encoded','','CMD');
			else
				$is_base64encoded = '';
				
			if($is_base64encoded=="true")
			{
				$src = $uploadedfile;
				$dst= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'decoded_'.basename( $file['name']);
				$this->base64file_decode( $src, $dst );
				$uploadedfile=$dst;
			}

			//Delete Old Logo
			if($ExistingImage!=0)
				$this->DeleteExistingSingleImage($ExistingImage,$ImageFolder,$imageparams_full,$realtablename,$realfieldname,$realidfieldname);

			$new_photo_ext=$this->FileExtenssion($uploadedfile);

			$ImageID=0;

			
			//Get new file name and avoid possible duplicate
			
			$i=0;
			do
			{
				$ImageID=date("YmdHis").($i>0 ? $i : '');
				//there is possible error, check all possible ext
				$thumbnail_image_file=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ImageID.'.jpg';
				$original_image_file=$ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext;
				
				$i++;
			}while(file_exists($thumbnail_image_file));
			$isOk=true;

			//es Thumb
			$r=$this->ProportionalResize($uploadedfile,$thumbnail_image_file, 150, 150,1,true, -1, '');
			
			if($r!=1)
				$isOk=false;
				
			//--------- compare thumbnails	
			
			$duplicateImageID = $this->compareThumbs($additional_params, $realtablename, $realfieldname, $ImageFolder, $uploadedfile, $thumbnail_image_file);
			
			if($duplicateImageID!=0)
				return $duplicateImageID;
			
			//--------- end of compare thumbnails
			

			//custom images
			if($isOk)
			{
				$customsizes=$this->getCustomImageOptions($imageparams_full);

				foreach($customsizes as $imagesize)
				{
					$prefix=$imagesize[0];
					$width=(int)$imagesize[1];
					$height=(int)$imagesize[2];

					$color=(int)$imagesize[3];
					$watermark=$imagesize[5];

					//save as extention
					if($imagesize[4]!='')
						$ext=$imagesize[4];
					else
						$ext=$new_photo_ext;

					$r=$this->ProportionalResize($uploadedfile,$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$ImageID.'.'.$ext, $width, $height,1,true, $color, $watermark);

					if($r!=1)
						$isOk=false;
				}
			}

			if($isOk)
			{
				copy($uploadedfile,$original_image_file);
				unlink($uploadedfile);
				return $ImageID;
			}
			else
			{
				if(file_exists($original_image_file))
					unlink($original_image_file);

				unlink($uploadedfile);
				return -1;
			}
		}
		return 0;
	}
	
	function compareThumbs($additional_params, $realtablename, $realfieldname, $ImageFolder, $uploadedfile, $thumbFileName)
	{
		$pair=explode(':',$additional_params);

		if($realtablename!='-options' and ($pair[0]=='compare' or $pair[0]=='compareexisting'))
		{
			$identity=2;
			if(isset($pair[1]))
				$identity=(int)$pair[1];
					
			$additional_filter='';
				
			if(isset($pair[2]))
				$additional_filter=$pair[2];
					
			//A bit of sanitation
			$additional_filter=str_replace('"','',$additional_filter);
			$additional_filter=str_replace("'",'',$additional_filter);
			$additional_filter=str_replace(";",'',$additional_filter);
			$additional_filter=str_replace("/",'',$additional_filter);
			$additional_filter=str_replace("\\",'',$additional_filter);
				
			$ImageID = -FindSimilarImage::find($uploadedfile,$identity,$realtablename,$realfieldname,$ImageFolder,$additional_filter);
				
			if($ImageID!=0)
			{
				unlink($uploadedfile);
				unlink($thumbFileName);
				return $ImageID;
			}
		}
		return 0;
	}

	function base64file_decode( $inputfile, $outputfile )
	{
		/* read data (binary) */
		$ifp = fopen( $inputfile, "rb" );
		$srcData = fread( $ifp, filesize( $inputfile ) );
		fclose( $ifp );
		/* encode & write data (binary) */
		$ifp = fopen( $outputfile, "wb" );
		fwrite( $ifp, base64_decode( $srcData ) );
		fclose( $ifp );
		/* return output filename */
		return( $outputfile );
	}

	function CheckImage($src,$memorylimit)
	{
		if(!file_exists($src))
			return false;

		$wh=getimagesize($src);

		$ms=$wh[0]*$wh[1]*4;

		if($ms>$memorylimit)
			return false;
		
		return true;
	}

	function ProportionalResize($src, $dst, $dst_width, $dst_height,$LevelMax, $overwrite,$backgroundcolor, $watermark)
	{
		//Returns:
		// 1 if everything is ok
		// -1 if file extension not supported
		// 2 if file already exists
		
		$fileExtension=$this->FileExtenssion($src);
		$fileExtension_dst=$this->FileExtenssion($dst);

		if($fileExtension == '')
		{
			//JFactory::getApplication()->enqueueMessage('File type ('.$fileExtension.') not supported.', 'error');
			return -1;
		}

		if($LevelMax>1)
			$LevelMax=1;

		//Check if destination file already exists
		if(file_exists($dst)) //Just in case
		{
			//JFactory::getApplication()->enqueueMessage('File with the same name ('.$dst.') already exists.', 'error');
			return 2;
		}
	
		$size = getImageSize($src);

		$ms=$size[0]*$size[1]*4;

		$width = $size[0];
		$height = $size[1];

		if($dst_height==0)
			$dst_height=floor($dst_width/($width/$height));

		if($dst_width==0)
			$dst_width=floor($dst_height*($width/$height));

		$rgb =$backgroundcolor;
		if($fileExtension == "jpg" OR $fileExtension=='jpeg')
		{
			$from = ImageCreateFromJpeg($src);
			if($rgb==-1)
				$rgb = imagecolorat($from, 0, 0);
		}
		elseif($fileExtension == "gif")
		{
			$from1 = ImageCreateFromGIF($src);
			$from = ImageCreateTrueColor ($width,$height);
			imagecopyresampled ($from,  $from1,  0, 0,  0, 0, $width, $height, $width, $height);
			if($rgb==-1)
				$rgb = imagecolorat($from, 0, 0);
		}
		elseif($fileExtension == 'png')
		{
			$from = imageCreateFromPNG($src);
			if($rgb==-1)
			{
				$rgb = imagecolorat($from, 0, 0);

				//if destination is jpeg and background is transparent then replace it with white.
				if($rgb==hexdec('7FFFFFFF') and $fileExtension_dst=='jpg')
					$rgb=hexdec('ffffff');
			}
		}
		elseif($fileExtension == 'webp')
		{
			$from = imagecreatefromwebp($src);
			if($rgb==-1)
			{
				$rgb = imagecolorat($from, 0, 0);

				//if destination is jpeg and background is transparent then replace it with white.
				if($rgb==hexdec('7FFFFFFF') and $fileExtension_dst=='jpg')
					$rgb=hexdec('ffffff');
			}
		}

		$new = ImageCreateTrueColor ($dst_width,$dst_height);

		if($rgb!=-2)
		{
			//Transparent
			imagefilledrectangle ($new, 0, 0, $dst_width, $dst_height,$rgb);
		}
		else
		{
			imageSaveAlpha($new, true);
			ImageAlphaBlending($new, false);

			$transparentColor = imagecolorallocatealpha($new, 255, 0, 0, 127);
			imagefilledrectangle ($new, 0, 0, $dst_width, $dst_height,$transparentColor);
		}

		//Width
		$dst_w=$dst_width; //Dist Width
		$dst_h=round($height*($dst_w/$width));

		if($dst_h>$dst_height)
		{
			$dst_h=$dst_height;
			$dst_w=round($width*($dst_h/$height));

			//Do crop if pr
			$a=$dst_width/$dst_w;
			$x=1+($a-1)*$LevelMax;

			if($LevelMax!=0)
			{
				$dst_w=$dst_width/$x; //Dist Width
				$dst_h=round($height*($dst_w/$width));
			}
		}

		//Setting coordinates
		$dst_x=round($dst_width/2-$dst_w/2);
		$dst_y=round($dst_height/2-$dst_h/2);

		imagecopyresampled ($new,  $from,  $dst_x, $dst_y,  0, 0 , $dst_w, $dst_h,  $width, $height);

		if($watermark!='')
		{
			$watermark_Extension=$this->FileExtenssion($watermark);
			if($watermark_Extension=='png')
			{
				$watermark_file=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$watermark);

				if(file_exists($watermark_file))
				{
					$watermark_from = imageCreateFromPNG($watermark_file);
					$watermark_size = getImageSize($watermark_file);
					if($dst_w>=$watermark_size[0] and $dst_h>=$watermark_size[1])
					{
						$wX=($dst_w-$watermark_size[0])/2;
						$wY=($dst_h-$watermark_size[1])/2;

						imagecopyresampled ($new,  $watermark_from,  $wX, $wY,  0, 0 , $watermark_size[0], $watermark_size[1],  $watermark_size[0], $watermark_size[1]);

					}//if($width>=$watermark_size[0] and $height>=$watermark_size[1])
				}//if(file_exists($watermark))
			}//if($watermark_Extension=='png')
		}//if($watermark!='')
		//----------- end watermark

		if($fileExtension_dst == "jpg" OR $fileExtension_dst == 'jpeg')
			imagejpeg($new, $dst, 90);
		elseif($fileExtension_dst == "gif")
			imagegif($new, $dst);
		elseif($fileExtension_dst == 'png')
			imagepng($new, $dst);
		elseif($fileExtension_dst == 'webp')
			imagewebp($new, $dst, 90);
			
		return 1;
	}

	public static function getImageFolder($imageparams)
	{
		$ImageFolder='images'.DIRECTORY_SEPARATOR.'ct_images';
		$pair=JoomlaBasicMisc::csv_explode(',',$imageparams,'"',false);

		if(isset($pair[2]))
		{
			$ImageFolder=$pair[2];
			if($ImageFolder[0]!='/')
				$ImageFolder='/'.$ImageFolder;

			if(strlen($ImageFolder)>8)
			{
				$p1=substr($ImageFolder,0,7);
				$p2=substr($ImageFolder,0,8);
				
				if($p1!='images/' and $p2!='/images/')
					$ImageFolder='images'.$ImageFolder;
				
				if($p2=='/images/')
					$ImageFolder=substr($ImageFolder,1);
			}
			else
				$ImageFolder='images'.$ImageFolder;
		}
		
		if(strlen($ImageFolder)==0)
			$ImageFolder='images'.DIRECTORY_SEPARATOR.'ct_images';

		$ImageFolderPath=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder);

		if (!file_exists($ImageFolderPath))
			mkdir($ImageFolderPath, 0755, true);

		return $ImageFolder;
	}
}

