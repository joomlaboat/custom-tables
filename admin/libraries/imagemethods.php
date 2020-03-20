<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesImageMethods
{

function FileExtenssion($src){
	$fileExtension='';
	$name = explode(".", strtolower($src));
	$currentExtensions = $name[count($name)-1];
	$allowedExtensions = 'jpg jpeg gif png';
	$extensions = explode(" ", $allowedExtensions);
	for($i=0; count($extensions)>$i; $i=$i+1){
		if($extensions[$i]==$currentExtensions)
		{
			$extensionOK=1;
			$fileExtension=$extensions[$i];

			return $fileExtension;
			break;
		}
	}

	return $fileExtension;
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
		return hexdec('#ffffff');

	elseif($vlu=='red')
		return hexdec('#ff0000');

	elseif($vlu=='green')
		return hexdec('#00ff00');

	elseif($vlu=='blue')
		return hexdec('#0000ff');

	elseif($vlu=='yellow')
		return hexdec('#ffff00');

	elseif(!(strpos($vlu,'#')===false))
	{
		return hexdec($vlu);
	}

	else
	{
		return (int)$vlu;
	}
}
function getCustomImageOptions($imageparams_)
{
	$TypeParamsArr=JoomlaBasicMisc::csv_explode(',',$imageparams_,'"',false);
	$imageparams=$TypeParamsArr[0];
	//if(strpos($imageparams_,'|')!==false)
	//{
		//$pair=explode('|',$imageparams_);
		//$imageparams=$pair[0];
	//}
	//else
		//$imageparams=$imageparams_;

	$cleanOptions=array();
	//custom images
	$imagesizes=explode(';',$imageparams);
	$allowedExtensions = array('jpg','jpeg', 'gif', 'png');


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
					if(!in_array($imageoptions[4],$allowedExtensions))
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

function DeleteExistingSingleImage($ExistingImage,$ImageFolder,$imageparams, $establename='-options', $esfieldname)
{
	$customsizes=$this->getCustomImageOptions($imageparams);
	CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $establename, $esfieldname);

	foreach($customsizes as $customsize)
	{
		CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder,$customsize[0]);
	}
}
function DeleteExistingGalleryImage($ImageFolder,$ImageMainPrefix, $estableid, $galleryname, $photoid,$imageparams,$deleteOriginals=false)
{

	if($deleteOriginals)
	{
		$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'__esthumb_'.$photoid.'.jpg';
		if(file_exists($filename))
			unlink($filename);
	}

	$customsizes=$this->getCustomImageOptions($imageparams);

	$available_ext=array('jpg','png','gif','jpeg');
	foreach($available_ext as $photo_ext)
	{
		if($deleteOriginals)
		{
			$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'__original_'.$photoid.'.'.$photo_ext;
			if(file_exists($filename))
				unlink($filename);
		}


		foreach($customsizes as $customsize)
		{


			$filename=$ImageFolder.DIRECTORY_SEPARATOR.$ImageMainPrefix.$estableid.'_'.$galleryname.'_'.$customsize[0].'_'.$photoid.'.'.$photo_ext;
			if(file_exists($filename))
				unlink($filename);
		}
	}
}

function DeleteGalleryImages($establename, $estableid, $galleryname,$typeparams,$deleteOriginals=false)
{
		$TypeParamsArr=JoomlaBasicMisc::csv_explode(',',$typeparams,'"',false);
		$image_parameters=$TypeParamsArr[0];

		//$TypeParamsArr=explode('|',$typeparams);

		//$image_parameters=$TypeParamsArr[0];

		//$pair=explode(',',$TypeParamsArr[1]);

		//if(!isset($pair[1]))
			//return true;
		$imagefolderword='';
		if(isset($image_parameters[1]))
			$imagefolderword=$image_parameters[1];

		//$imagefolderword=$pair[1];
		$imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.str_replace('/',DIRECTORY_SEPARATOR,$imagefolderword);

		//$imagefolder=$TypeParamsArr[0];
		//if(!isset($TypeParamsArr[1]))
		//	return false;

		//if($imagefolder=='')
		//$imagefolder='esimages';

		//$imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$imagefolder;

		$imagegalleryprefix='g';

		//delete gallery images if exist
		$db = JFactory::getDBO();


		$phototablename='#__customtables_gallery_'.$establename.'_'.$galleryname;

		//check if table exists
		//$query = 'SHOW TABLES LIKE "'.str_replace('#__','',$phototablename);
		$query = 'SHOW TABLES LIKE "'.$phototablename.'"';
		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());
		$recs=$db->loadObjectList();


		if(count($recs)>0)
		{


			$query = 'SELECT photoid FROM '.$phototablename;
			$db->setQuery($query);
			if (!$db->query())    die( $db->stderr());
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








static protected function DeleteOriginalImage($ExistingImage, $ImageFolder, $establename, $esfieldname)
{
	//if($ExistingImage==-1)



	//-----	return true;



	//---------- find child ----------
	//check if the image has child or not
	if($establename!='-options')
	{
		$db = JFactory::getDBO();

		if($ExistingImage=='')
			$ExistingImage=0;

		$query = 'SELECT id FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'=-'.$ExistingImage.' LIMIT 1';
		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());

		if($db->getNumRows()==1) //do not compare if there is a child
		{
			$photorows=$db->loadObjectList();
			$photorow=$photorows[0];

			//Null Parent
			$query = 'UPDATE #__customtables_table_'.$establename.' SET es_'.$esfieldname.'=0 WHERE es_'.$esfieldname.'='.$ExistingImage;
			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());

			//Convert Child to Parent
			$query = 'UPDATE #__customtables_table_'.$establename.' SET es_'.$esfieldname.'='.$ExistingImage.' WHERE id='.$photorow->id;
			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());

			return true;
		}//if
	}
	//--------------------------------


	$available_ext=array('jpg','png','gif','jpeg');

	foreach($available_ext as $photo_ext)
	{
		if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ExistingImage.'.'.$photo_ext))
			unlink($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ExistingImage.'.'.$photo_ext);

		if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ExistingImage.'.'.$photo_ext))
			unlink($ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ExistingImage.'.'.$photo_ext);
	}


	if($establename!='-options')
	{
		//Update Table
		$query = 'UPDATE #__customtables_table_'.$establename.' SET es_'.$esfieldname.'=0 WHERE es_'.$esfieldname.'="'.$ExistingImage.'"';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
	}

}
static protected function DeleteCustomImage($ExistingImage, $ImageFolder,$CustomSize)
{
	$available_ext=array('jpg','png','gif','jpeg');

	foreach($available_ext as $photo_ext)
	{
		if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.$CustomSize.'_'.$ExistingImage.'.'.$photo_ext))
			unlink($ImageFolder.DIRECTORY_SEPARATOR.$CustomSize.'_'.$ExistingImage.'.'.$photo_ext);
	}

}

function DeleteCustomImages($establename,$esfieldname, $ImageFolder,$imageparams,$deleteOriginals=false)
{
	$db = JFactory::getDBO();
	$query = 'SELECT es_'.$esfieldname.' FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';

	$db->setQuery( $query );
	if (!$db->query())    die( $db->stderr());

	$imagelist=$db->loadAssocList();

	$customsizes=$this->getCustomImageOptions($imageparams);



	foreach($imagelist as $img)
	{
		$ExistingImage=$img['es_'.$esfieldname];

		if($deleteOriginals)
			CustomTablesImageMethods::DeleteOriginalImage($ExistingImage, $ImageFolder, $establename, $esfieldname);

		foreach($customsizes as $customsize)
		{
			CustomTablesImageMethods::DeleteCustomImage($ExistingImage, $ImageFolder,$customsize[0]);
		}
	}



}

function getImageExtention($ImageName_noExt)
{
	$available_ext=array('jpg','png','gif','jpeg');
	foreach($available_ext as $photo_ext)
	{
		if(file_exists($ImageName_noExt.'.'.$photo_ext))
			return $photo_ext;
	}
	return '';
}

function CreateNewCustomImages($establename,$esfieldname, $ImageFolder,$imageparams,$startindex,$step)
{

	$count=0;
	$db = JFactory::getDBO();

	$query = ' SELECT es_'.$esfieldname.' FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'>0';
	$db->setQuery( $query );
	if (!$db->query())    die( $db->stderr());
	$imagelist=$db->loadAssocList();

	$pair=JoomlaBasicMisc::csv_explode(',',$imageparams,'"',false);
	//$image_parameters=$TypeParamsArr[0];

	//$pair=explode('|',$imageparams);
	$compareexisting=false;
	if(isset($pair[1]))
	{
		//Additional Parameters
		$second_pair=explode(':',$pair[1]);

		//Special Plugin
		if(strpos($second_pair[0],'compareexisting')!==false)
		{
			//$compare_pair=explode(':',$second_pair[0]);
			//if(isset($compare_pair[1]))
			//{
				$compareexisting=true;
				require_once('findsimilarimage.php');

				$identity=4;
				if(isset($second_pair[1]))
					$identity=(int)$second_pair[1];
			//}
		}


		if(isset($pair[2]))
		{
			//Path
			$ImageFolder=$pair[2];
		}

	}

	$customsizes=$this->getCustomImageOptions($imageparams);

	foreach($imagelist as $img)
	{


		if($count>=$startindex)
		{

			$ImageID=$img['es_'.$esfieldname];
			$originalImage=$ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID;
			$ImgExtention=$this->getImageExtention($originalImage);


			if($ImgExtention!='')
			{

				$originalImage.='.'.$ImgExtention;
				$DeleteExistingImage=false;
				if($compareexisting)
				{
					//check if the image has child or not
					$query = 'SELECT id AS photoid FROM #__customtables_table_'.$establename.' WHERE es_'.$esfieldname.'=-'.$ImageID;
					$db->setQuery($query);
					if (!$db->query())    die( $db->stderr());

					if($db->getNumRows()==0) //do not compare if there is a child
					{
						$NewImageID=-FindSimilarImage::find($originalImage,$identity,'#__customtables_table_'.$establename,'es_'.$esfieldname,$ImageFolder);
						if($NewImageID!=0)
						{
							$DeleteExistingImage=true;

							//Update Table
							$query = 'UPDATE #__customtables_table_'.$establename.' SET es_'.$esfieldname.'='.$NewImageID.' WHERE es_'.$esfieldname.'='.$ImageID;
							$db->setQuery( $query );
							if (!$db->query())    die( $db->stderr());
						}
					}//if
				}//if

				if($DeleteExistingImage)
				{
					//Delete Image
					CustomTablesImageMethods::DeleteOriginalImage($ImageID, $ImageFolder,$establename,$esfieldname);
				}
				else
				{
					$thumbimage=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ImageID.'.jpg';
					if(!file_exists($thumbimage))
					{
						$this->ProportionalResize($originalImage,$thumbimage, 150, 150,1,true, -1, '');
					}

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
						{
							$r=$this->ProportionalResize($originalImage, $newfilename, $width, $height,1,true, $color, $watermark);
						}
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

function CreateNewGalleryImages($establename,$estableid, $galleryname, $ImageFolder,$imageparams,$startindex,$step)
{
	$count=0;
	$db = JFactory::getDBO();
	$imagegalleryprefix='g';

	$phototablename='#__customtables_gallery_'.$establename.'_'.$galleryname;

	$query = ' SELECT photoid, photo_ext FROM '.$phototablename;
	$db->setQuery( $query );
	if (!$db->query())    die( $db->stderr());
	$imagelist=$db->loadAssocList();

	$customsizes=$this->getCustomImageOptions($imageparams);

	foreach($imagelist as $img)
	{
		if($count>=$startindex)
		{
		$ImageID=$img['photoid'];

		$originalImage=$ImageFolder.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'__original_'.$ImageID.'.'.$img['photo_ext'];


		$thumbImage=$ImageFolder.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$ImageID.'.jpg';
		if(!file_exists($thumbImage))
		{
			$r=$this->ProportionalResize($originalImage,$thumbImage, 150, 150,1,true, -1, '');
		}

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
				{
					$ext=$img['photo_ext'];
				}

				$newimage=$ImageFolder.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$ImageID.'.'.$ext;
				if(!file_exists($newimage))
				{
					$r=$this->ProportionalResize($originalImage, $newimage, $width, $height,1,true, $color, $watermark);
				}

			}//foreach($customsizes as $imagesize)


		}//if($count>$startindex)

		if($count-$startindex>=$step-1)
				break;
		$count++;
	}
	return count($imagelist);
}

function UploadSingleImage($ExistingImage, $image_file_id, $esfieldname,$ImageFolder,$imageparams_full,$establename='-options')
{
		$jinput = JFactory::getApplication()->input;

		if($image_file_id!='')
		{

			$pair=JoomlaBasicMisc::csv_explode(',',$imageparams_full,'"',false);

			$additional_params='';
			if(isset($pair[1]))
				$additional_params=$pair[1];

			$uploadedfile= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$image_file_id;

			$is_base64encoded=JFactory::getApplication()->input->get('base64encoded','','CMD');
			if($is_base64encoded=="true")
			{
				$src = $uploadedfile;
				$dst= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'decoded_'.basename( $file['name']);
				$this->base64file_decode( $src, $dst );
				$uploadedfile=$dst;
			}

			//Delete Old Logo
			if($ExistingImage!=0)
				$this->DeleteExistingSingleImage($ExistingImage,$ImageFolder,$imageparams_full,$establename,$esfieldname);

			$new_photo_ext=$this->FileExtenssion($uploadedfile);

			$ImageID=0;

			$pair=explode(':',$additional_params);
			if($establename!='-options' and ($pair[0]=='compare' or $pair[0]=='compareexisting'))
			{
				$identity=4;
				if(isset($pair[1]))
					$identity=(int)$pair[1];

				require_once('findsimilarimage.php');
				$ImageID=-FindSimilarImage::find($uploadedfile,$identity,$establename,$esfieldname,$ImageFolder);

				if($ImageID!=0)
				{
					unlink($uploadedfile);
					return $ImageID;
				}
			}



			//Get New Logo id
			do
			{
				//there is possible error, check all possible ext
				$ImageID=date("YmdHIs");
				$image_file=$ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext;
			}while(file_exists($image_file));
			$isOk=true;

			//es Thumb

			$r=$this->ProportionalResize($uploadedfile,$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ImageID.'.jpg', 150, 150,1,true, -1, '');

			if($r!=1)
				$isOk=false;

			//custom images

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


			if($isOk)
			{
				copy($uploadedfile,$image_file);
				unlink($uploadedfile);
				return $ImageID;
			}
			else
			{
				if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext))
					unlink($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext);


				unlink($uploadedfile);
				return -1;
			}


		}
		return 0;
	}


function UploadSingleImage_old($ExistingImage, $comesfieldname, $esfieldname,$ImageFolder,$imageparams_full,$establename='-options')
{
		$jinput = JFactory::getApplication()->input;
		$file=$jinput->files->get($comesfieldname);

		$filename=$file['name'];

		if($filename!='')
		{

			$pair=JoomlaBasicMisc::csv_explode(',',$imageparams_full,'"',false);
			//$pair=explode('|',$imageparams_full);
			//$imageparams=$pair[0];
			$additional_params='';
			if(isset($pair[1]))
				$additional_params=$pair[1];

			$pair=explode(':',$additional_params);
			if($pair[0]=='delete')
				return 0;

			$uploadedfile= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.basename( $file['name']);

			if(!move_uploaded_file($file['tmp_name'], $uploadedfile))
				return -1;

			$is_base64encoded=JFactory::getApplication()->input->get('base64encoded','','CMD');
			if($is_base64encoded=="true" or $is_base64encoded=="true") // to support old version with misspeled filter
			{
				$src = $uploadedfile;
				$dst= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'decoded_'.basename( $file['name']);
				$this->base64file_decode( $src, $dst );
				$uploadedfile=$dst;
			}

			//Delete Old Logo
			if($ExistingImage!=0)
				$this->DeleteExistingSingleImage($ExistingImage,$ImageFolder,$imageparams_full,$establename,$esfieldname);

			$new_photo_ext=$this->FileExtenssion($uploadedfile);

			$ImageID=0;

			$pair=explode(':',$additional_params);
			if($establename!='-options' and ($pair[0]=='compare' or $pair[0]=='compareexisting'))
			{
				$identity=4;
				if(isset($pair[1]))
					$identity=(int)$pair[1];

				require_once('findsimilarimage.php');
				$ImageID=-FindSimilarImage::find($uploadedfile,$identity,$establename,$esfieldname,$ImageFolder);

				if($ImageID!=0)
				{
					unlink($uploadedfile);
					return $ImageID;
				}
			}



			//Get New Logo id
			do
			{
				//there is possible error, check all possible ext
				$ImageID=date("YmdHIs");
				$image_file=$ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext;
			}while(file_exists($image_file));
			$isOk=true;

			//es Thumb

			$r=$this->ProportionalResize($uploadedfile,$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$ImageID.'.jpg', 150, 150,1,true, -1, '');

			if($r!=1)
				$isOk=false;

			//custom images

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


			if($isOk)
			{
				copy($uploadedfile,$image_file);
				unlink($uploadedfile);
				return $ImageID;
			}
			else
			{
				if(file_exists($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext))
					unlink($ImageFolder.DIRECTORY_SEPARATOR.'_original_'.$ImageID.'.'.$new_photo_ext);


				unlink($uploadedfile);
				return -1;
			}


		}
		return 0;
	}




function base64file_decode( $inputfile, $outputfile ) {
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

function ProportionalResize($src, $dst, $dst_width, $dst_height,$LevelMax, $overwrite,$backgroundcolor, $watermark){

	$fileExtension=$this->FileExtenssion($src);
	$fileExtension_dst=$this->FileExtenssion($dst);


	if(!$fileExtension!='')return -1;

	if($LevelMax>1){$LevelMax=1;}


	//Check if destination already complited
	 //and $overwrite
	if(file_exists($dst))
	{
	/*$dwh=getimagesize($dst);
	if($dst_width!=0 and  $dst_height!=0)
	{

		if($dwh[0]==$dst_width and $dwh[1]==$dst_height)
			return 2;

	}elseif($dst_height==0)
	{
		if($dwh[0]==$dst_width)
			return 2;

	}elseif($dst_width==0)
	{
		if($dwh[1]==$dst_height)
			return 2;

	}*/
		return 2;
	}


	$size = getImageSize($src);


	$ms=$size[0]*$size [1]*4;
	//if($ms>19000000) --- check this
	//	return -1;




	$width = $size[0];
	$height = $size[1];

	if($dst_height==0)
		$dst_height=floor($dst_width/($width/$height));

	if($dst_width==0)
		$dst_width=floor($dst_height*($width/$height));




	$rgb =$backgroundcolor;
	if($fileExtension == "jpg" OR $fileExtension=='jpeg'){
		$from = ImageCreateFromJpeg($src);
		if($rgb==-1)
			$rgb = imagecolorat($from, 0, 0);
	}elseif ($fileExtension == "gif"){
		$from1 = ImageCreateFromGIF($src);
		$from = ImageCreateTrueColor ($width,$height);
		imagecopyresampled ($from,  $from1,  0, 0,  0, 0, $width, $height, $width, $height);
		if($rgb==-1)
			$rgb = imagecolorat($from, 0, 0);
	}elseif ($fileExtension == 'png'){


			$from = imageCreateFromPNG($src);
			if($rgb==-1)
			{
				$rgb = imagecolorat($from, 0, 0);

				//if destination is jpeg and background is transparent then replace it with white.
				if($rgb==hexdec('#7FFFFFFF') and $fileExtension_dst=='jpg')
					$rgb=hexdec('#ffffff');
			}


	}//if($fileExtension == "jpg" OR $fileExtension=='jpeg'){






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
		{	$dst_w=$dst_width/$x; //Dist Width
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




	if($fileExtension_dst == "jpg" OR $fileExtension_dst == 'jpeg'){
		imagejpeg($new, $dst, 70);
	}elseif ($fileExtension_dst == "gif"){
		imagegif($new, $dst);
	}elseif ($fileExtension_dst == 'png'){
		imagepng($new, $dst);
	}




	return 1;


}



public static function getImageFolder($imageparams)
	{
		$ImageFolder='/images'.DIRECTORY_SEPARATOR.'esimages';
		$pair=JoomlaBasicMisc::csv_explode(',',$imageparams,'"',false);

		if(isset($pair[2]))
		{
			$ImageFolder=$pair[2];
			if($ImageFolder[0]!='/')
				$ImageFolder='/'.$ImageFolder;

			if(strlen($ImageFolder)>8)
			{
				$p=substr($ImageFolder,0,8);

				if($p!='/images/')
					$ImageFolder='/images'.$ImageFolder;

			}
			else
				$ImageFolder='/images'.$ImageFolder;
		}

		if(strlen($ImageFolder)==0)
			$ImageFolder='/images/';

		if($ImageFolder[0]!='/')
			$ImageFolder='/'.$ImageFolder;

		$ImageFolderPath=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder);

		if (!file_exists($ImageFolderPath))
		{
			mkdir($ImageFolderPath, 0755, true);
		}

		return $ImageFolder;
	}



}




?>
