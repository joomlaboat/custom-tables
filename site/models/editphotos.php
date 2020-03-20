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

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'imagemethods.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'logs.php');

class CustomTablesModelEditPhotos extends JModelLegacy {

	var $es;

	var $imagemethods;
	var $LangMisc;

	var $LanguageList;
	var $langpostfix=0;

	var $esTable;
	var $establename;
	var $estableid;
	var $esfields;

	var $listing_id;
	var $Listing_Title;


	var $galleryname;


	var $GalleryTitle;
	var $GalleryParams;

	var $imagefolderword;
	var $imagefolder;
	var $imagefolderweb;
	var $imagemainprefix;
	var $maxfilesize;

	var $phototablename;
	/*function load()
	{

	}*/

	function __construct()
	{
		$params = JComponentHelper::getParams( 'com_customtables' );

		$this->maxfilesize=10000000;

		$this->imagefolderword='esimages';
		$this->imagefolderweb='images/esimages';
		$this->imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';

		$this->imagemainprefix='g';

		$this->es= new CustomTablesMisc;

		$this->LangMisc	= new ESLanguages;
		$this->esTable=new ESTables;
		$this->imagemethods=new CustomTablesImageMethods;


		$this->LanguageList=$this->LangMisc->getLanguageList();
		$this->langpostfix=$this->LangMisc->getLangPostfix();


		if(JFactory::getApplication()->input->get('establename','','CMD'))
			$this->establename=JFactory::getApplication()->input->get('establename','','CMD');
		else
			$this->establename=$params->get( 'establename' );

		$tablerow = $this->esTable->getTableRowByName($this->establename);
		$this->estableid=$tablerow->id;

		$this->listing_id=JFactory::getApplication()->input->getInt('listing_id', 0);
		if(!JFactory::getApplication()->input->getCmd('galleryname'))
			return false;

		$this->galleryname=JFactory::getApplication()->input->getCmd('galleryname');

		if(!$this->getGallery())
			return false;

		$this->getObject();

		$this->phototablename='#__customtables_gallery_'.$this->establename.'_'.$this->galleryname;

		parent::__construct();
	}


	function getPhotoList()
	{


		// get database handle
		$db = JFactory::getDBO();

		$query = 'SELECT ordering, photoid,  title'.$this->langpostfix.' AS title, photo_ext FROM '.$this->phototablename.' WHERE listingid='.$this->listing_id.' ORDER BY ordering, photoid';

		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadObjectList();

		return $rows;
	}

	function getGallery()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT id, fieldtitle'.$this->langpostfix.' AS title,typeparams FROM #__customtables_fields WHERE published=1 AND tableid='.$this->estableid.' AND  fieldname="'.$this->galleryname.'" AND type="imagegallery" LIMIT 1';

		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadObjectList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->GalleryTitle=$row->title;

		$this->imagefolderword=CustomTablesImageMethods::getImageFolder($row->typeparams);

		$this->imagefolderweb=$this->imagefolderword;
		$this->imagefolder=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$this->imagefolderword);

		$this->GalleryParams=$row->typeparams;

		return true;
	}

	function getObject()
	{
		$this->esfields = ESFields::getFields($this->estableid);

		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__customtables_table_'.$this->establename.' WHERE id='.$this->listing_id.' LIMIT 1';

		$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());

		$rows = $db->loadAssocList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->Listing_Title='';


		foreach($this->esfields as $mFld)
		{
			$titlefield=$mFld['fieldname'];
				if(!(strpos($mFld['type'],'multi')===false))
					$titlefield.=$this->langpostfix;

				if($row['es_'.$titlefield]!='')
				{
					$this->Listing_Title=$row['es_'.$titlefield];
					break;
				}
		}


	}

	function reorder()
	{
		$images=$this->getPhotoList();

		//Set order

		//Apply Main Photo
		//Get New Ordering
		$Mainphoto= JFactory::getApplication()->input->getInt('esphotomain');

		foreach($images as $image)
		{
			$image->ordering=abs(JFactory::getApplication()->input->getInt( 'esphotoorder'.$image->photoid,0));
			if($Mainphoto==$image->photoid)
				$image->ordering=-1;
		}

		//Increase all if main
		$nonegative=true;
		do
		{
			$nonegative=true;
			foreach($images as $image)
			{
				if($image->ordering==-1)
					$nonegative=false;
			}

			if(!$nonegative)
			{
				foreach($images as $image)
					$image->ordering++;
			}



		}while(!$nonegative);

		$db = JFactory::getDBO();

		asort  (  $images );
		$i=0;
		foreach($images as $image)
		{
			$safetitle=JFactory::getApplication()->input->getString('esphototitle'.$image->photoid);
			$safetitle=str_replace('"',"",$safetitle);

			$query = 'UPDATE '.$this->phototablename.' SET ordering='.$i.', title'.$this->langpostfix.'="'.$safetitle.'" WHERE listingid='.$this->listing_id.' AND photoid='.$image->photoid;

			$db->setQuery($query);
			if (!$db->query())    die( $db->stderr());

			$i++;
		}
		return true;
	}
	function AutoReorderPhotos()
	{
		$images=$this->getPhotoList($this->listing_id, $this->langpostfix);

		$db = JFactory::getDBO();

		asort  (  $images );
		$i=0;
		foreach($images as $image)
		{
			$safetitle=JFactory::getApplication()->input->getString('esphototitle'.$image->photoid);
			$safetitle=str_replace('"',"",$safetitle);

			$query = 'UPDATE '.$this->phototablename.' SET ordering='.$i.', title'.$this->langpostfix.'="'.$safetitle.'" WHERE listingid='.$this->listing_id.' AND photoid='.$image->photoid;
			$db->setQuery($query);
		if (!$db->query())    die( $db->stderr());
			$i++;
		}


		return true;
	}

	function delete()
	{

	//	$this->GalleryParams

		$db = JFactory::getDBO();

		$photoids=JFactory::getApplication()->input->getString('photoids','');
		$photo_arr=explode('*',$photoids);

		foreach($photo_arr as $photoid)
		{
			if($photoid!='')
			{
				$this->imagemethods->DeleteExistingGalleryImage($this->imagefolder,$this->imagemainprefix, $this->estableid, $this->galleryname, $photoid,$this->GalleryParams,true);

				$query = 'DELETE FROM '.$this->phototablename.' WHERE listingid='.$this->listing_id.' AND photoid='.$photoid;
				$db->setQuery($query);
				if (!$db->query())    die( $db->stderr());

			}
		}

		ESLogs::save($this->estableid,$this->listing_id,7);
		return true;
	}

	function add()
	{
		$jinputfile = JFactory::getApplication()->input->files;
		$file = $jinputfile->files->get('uploadedfile');

		$uploadedfile= "tmp/".basename( $file['name']);
		if(!move_uploaded_file($file['tmp_name'], $uploadedfile))
			return false;

		if(JFactory::getApplication()->input->getCmd( 'base64ecnoded', '')=="true")
		{
			$src = $uploadedfile;
			$dst = "tmp/decoded_".basename( $file['name']);
			$this->base64file_decode( $src, $dst );
			$uploadedfile=$dst;
		}



		$es= new CustomTablesMisc;

		//Check file
		
		if(!$this->imagemethods->CheckImage($uploadedfile,30000000))//$this->maxfilesize
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_BROKEN_IMAGE'), 'error');
			unlink($uploadedfile);
			return false;
		}
		//Save to DB

		$photo_ext=$this->imagemethods->FileExtenssion($uploadedfile);
		$photoid=$this->addPhotoRecord($photo_ext);







		$isOk=true;

		//es Thumb
		$newfilename=$this->imagefolder.DIRECTORY_SEPARATOR.$this->imagemainprefix.$this->estableid.'_'.$this->galleryname.'__esthumb_'.$photoid.".jpg";
		$r=$this->imagemethods->ProportionalResize($uploadedfile,$newfilename, 150, 150,1,true, -1, '');

		if($r!=1)
			$isOk=false;



		$customsizes=$this->imagemethods->getCustomImageOptions($this->GalleryParams);


		foreach($customsizes as $imagesize)
		{

			$prefix=$imagesize[0];
			$width=(int)$imagesize[1];
			$height=(int)$imagesize[2];
			$color=(int)$imagesize[3];


			//save as extention
			if($imagesize[4]!='')
				$ext=$imagesize[4];
			else
				$ext=$photo_ext;

			$newfilename=$this->imagefolder.DIRECTORY_SEPARATOR.$this->imagemainprefix.$this->estableid.'_'.$this->galleryname.'_'.$prefix.'_'.$photoid.".".$ext;
			$r=$this->imagemethods->ProportionalResize($uploadedfile,$newfilename, $width, $height,1,true, $color, '');

			if($r!=1)
				$isOk=false;

		}


		if($isOk)
		{
			$originalname=$this->imagemainprefix.$this->estableid.'_'.$this->galleryname.'__original_'.$photoid.".".$photo_ext;

			if (!copy($uploadedfile, $this->imagefolder.DIRECTORY_SEPARATOR.$originalname))
			{
				unlink($uploadedfile);
				return false;
			}
		}
		else
		{
			unlink($uploadedfile);
			return false;
		}



		unlink($uploadedfile);

		ESLogs::save($this->estableid,$this->listing_id,6);

		return true;
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

	function addPhotoRecord($photo_ext)
	{
		$db = JFactory::getDBO();

		$query = 'INSERT '.$this->phototablename.' SET '
				.'ordering=100, '
				.'photo_ext="'.$photo_ext.'", '
				.'listingid='.$this->listing_id;

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		$this->AutoReorderPhotos();


		$query =' SELECT photoid FROM '.$this->phototablename.' WHERE listingid='.$this->listing_id.' ORDER BY photoid DESC LIMIT 1';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		$espropertytype= $db->loadObjectList();
		if(count($espropertytype)==1)
		{
			return $espropertytype[0]->photoid;
		}

		return -1;
	}


	function DoAutoResize($uploadedfile,$folder_resized,$image_width,$image_height,$photoid,$fileext)
	{
		if(!file_exists($uploadedfile))
			return false;


		$newfilename=$folder_resized.$photoid.'.'.$fileext;

		//hexdec ("#323131")
		$r=$this->imagemethods->ProportionalResize($uploadedfile, $newfilename, $image_width, $image_height,1,true, -1, '');
		if($r!=1)
			return false;

		return true;
	}





}
