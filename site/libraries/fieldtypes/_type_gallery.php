<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
  * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_imagegallery{
    

	public static function getGalleryRows($establename,$galleryname, $listing_id)
	{

		$db = JFactory::getDBO();
		$phototablename='#__customtables_gallery_'.$establename.'_'.$galleryname;

		$query = 'SELECT photoid, photo_ext FROM '.$phototablename.' WHERE listingid='.(int)$listing_id.' ORDER BY ordering, photoid';
		$db->setQuery($query);
		if (!$db->query())    die('getGalleryRows: '. $db->stderr());
        $photorows=$db->loadObjectList();

		return $photorows;
	}
    
    public static function getImageGallerySRC($photorows, $prefix,$id,$galleryname,$TypeParams,&$imagesrclist,&$imagetaglist,$estableid)
	{
        $imagegalleryprefix='g';
        
		$TypeParamsArr=JoomlaBasicMisc::csv_explode(',',$TypeParams,'"',false);

		$imagefolder=CustomTablesImageMethods::getImageFolder($TypeParams);

		if($imagefolder=='')
		{
			$imagefolder='esimages';
			$imagefolderserver=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$imagefolder);
			$imagefolderweb='/images/'.$imagefolder;
		}
		else
		{
			$f=str_replace('/',DIRECTORY_SEPARATOR,$imagefolder);
			if(strlen($f)>0)
			{
				if($f[0]==DIRECTORY_SEPARATOR)
				{
					$imagefolderserver=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$imagefolder);
					$imagefolderweb=$imagefolder;
				}
				else
				{
					$imagefolderserver=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$imagefolder);
					$imagefolderweb='/'.$imagefolder;
				}
			}
			else
			{
				$imagefolderserver=JPATH_SITE.DIRECTORY_SEPARATOR;
				$imagefolderweb='/';
			}


		}


		if(!isset($photorows))
			return false;



		$conf = JFactory::getConfig();
		$sitename = $conf->get('config.sitename');

		//the returnedl list should be separated by ;
		$imagesrclistarray=array();
		$imagetaglistarray=array();

		$imgMethods= new CustomTablesImageMethods;

		foreach($photorows as $photorow)
		{

			$photorow_photoid=$photorow->photoid;
			if(strpos($photorow_photoid,'-')!==false)
			{
				//$isShortcut=true;
				$photorow_photoid=str_replace('-','',$photorow_photoid);
			}


			if($prefix=='')
			{
				$imagefile_ext='jpg';


				$imagefile=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';
				$imagefileweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';

				if($imagefile!='')
				{
					$imagetaglistarray[]='<img src="'.$imagefileweb.'" width="150" height="150" alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';
				}
			}
			elseif($prefix=='_original')
			{
				$imagefile_ext='jpg';
				$imgname=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid;
				$imagefile_ext=$imgMethods->getImageExtention($imgname);

				$imagefile=$imgname.'.'.$imagefile_ext;

				$imagefileweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid.'.jpg';
				if($imagefile!='')
				{
					$imagetaglistarray[]='<img src="'.$imagefileweb.'" alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;
				}
			}
			else
			{


				$imagesizes=$imgMethods->getCustomImageOptions($TypeParams);


				$foundimgsize=false;

				foreach($imagesizes as $img)
				{

					if($img[0]==$prefix)
					{

						$imgname=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid;


						$imagefile_ext=$imgMethods->getImageExtention($imgname);



						if($imagefile_ext!='')
						{
							$imgnameweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;

							$imagetaglistarray[]='<img src="'.$imgnameweb.'" '.($img[1]>0 ? 'width="'.$img[1].'"' : '').' '.($img[2]>0 ? 'height="'.$img[2].'"' : '').' alt="'.$sitename.'" title="'.$sitename.'" />';
							$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;
							$foundimgsize=true;
							break;
						}
					}//if($img[0]==$option)
				}//foreach($imagesizes as $img)

				if(!$foundimgsize)
				{

					$imagetaglistarray[]='filenotfound';
					$imagesrclistarray[]='filenotfound';
				}
			}//if($prefix=='')
		}//foreach($photorows as $photorow)


		$imagesrclist=implode(';',$imagesrclistarray);
		$imagetaglist=implode('',$imagetaglistarray);

		return true;
	}
    
}

?>
