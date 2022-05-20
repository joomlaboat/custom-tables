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
	public static function getGalleryRows($establename,$galleryname, $listing_id)
	{
		$db = Factory::getDBO();
		$phototablename='#__customtables_gallery_'.$establename.'_'.$galleryname;

		$query = 'SELECT photoid, photo_ext FROM '.$phototablename.' WHERE listingid='.(int)$listing_id.' ORDER BY ordering, photoid';
		$db->setQuery($query);
        $photorows=$db->loadObjectList();

		return $photorows;
	}
    
    public static function getImageGallerySRC($photorows, $image_prefix,$object_id,$galleryname,array $params,&$imagesrclist,&$imagetaglist,$estableid)
	{
        $imagegalleryprefix='g';
        
		$imagefolder=CustomTablesImageMethods::getImageFolder($params);

		if($imagefolder=='')
		{
			$imagefolder='ct_images';
			$imagefolderserver=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$imagefolder);
			$imagefolderweb='images/'.$imagefolder;
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
					$imagefolderweb=$imagefolder;
				}
			}
			else
			{
				$imagefolderserver=JPATH_SITE.DIRECTORY_SEPARATOR;
				$imagefolderweb='';
			}


		}


		if(!isset($photorows))
			return false;

		$conf = Factory::getConfig();
		$sitename = $conf->get('config.sitename');

		//the returnedl list should be separated by ;
		$imagesrclistarray=array();
		$imagetaglistarray=array();

		$imgMethods= new CustomTablesImageMethods;

		foreach($photorows as $photorow)
		{
			$photorow_photoid=$photorow->photoid;
			if(str_contains($photorow_photoid, '-'))
			{
				//$isShortcut=true;
				$photorow_photoid=str_replace('-','',$photorow_photoid);
			}

			if($image_prefix=='')
			{
				$imagefile=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';
				$imagefileweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';

				if($imagefile!='')
				{
					$imagetaglistarray[]='<img src="'.$imagefileweb.'" width="150" height="150" alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'__esthumb_'.$photorow_photoid.'.jpg';
				}
			}
			elseif($image_prefix=='_original')
			{
				$imgname=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid;
				$imagefile_ext=$imgMethods->getImageExtention($imgname);

				$imagefile=$imgname.'.'.$imagefile_ext;

				$imagefileweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid.'.jpg';
				if($imagefile!='')
				{
					$imagetaglistarray[]='<img src="'.$imagefileweb.'" alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;
				}
			}
			else
			{
				$imagesizes=$imgMethods->getCustomImageOptions($params[0]);
				$foundimgsize=false;

				foreach($imagesizes as $img)
				{
					if($img[0]==$image_prefix)
					{
						$imgname=$imagefolderserver.DIRECTORY_SEPARATOR.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid;
						$imagefile_ext=$imgMethods->getImageExtention($imgname);

						if($imagefile_ext!='')
						{
							$imgnameweb=$imagefolderweb.'/'.$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;

							$imagetaglistarray[]='<img src="'.$imgnameweb.'" '.($img[1]>0 ? 'width="'.$img[1].'"' : '').' '.($img[2]>0 ? 'height="'.$img[2].'"' : '').' alt="'.$sitename.'" title="'.$sitename.'" />';
							$imagesrclistarray[]=$imagegalleryprefix.$estableid.'_'.$galleryname.'_'.$image_prefix.'_'.$photorow_photoid.'.'.$imagefile_ext;
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
			}//if($image_prefix=='')
		}//foreach($photorows as $photorow)

		$imagesrclist=implode(';',$imagesrclistarray);
		$imagetaglist=implode('',$imagetaglistarray);
		return true;
	}
}
