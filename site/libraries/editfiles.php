<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filemethods.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');


class CustomTablesModelEditFiles extends JModelLegacy {

	var $es;
	var $filemethods;
	var $LangMisc;
	var $LanguageList;
	var $langpostfix=0;
	var $esTable;
	var $establename;
	var $estableid;
	var $esfields;
	var $listing_id;
	var $Listing_Title;
	var $fileboxname;
	var $FileBoxTitle;
	var $fileboxfolder;
	var $fileboxfolderweb;
	var $maxfilesize;
	var $fileboxtablename;
	var $allowedExtensions;

	function __construct()
    {
		$this->allowedExtensions='gslides doc docx pdf txt xls xlsx psd ppt pptx png mp3 jpg jpeg accdb';

		$params = JComponentHelper::getParams( 'com_customtables' );
		
		$this->maxfilesize=$max_file_size=JoomlaBasicMisc::file_upload_max_size();

		$this->es= new CustomTablesMisc;

		$this->LangMisc	= new ESLanguages;
		$this->esTable=new ESTables;
		$this->filemethods=new CustomTablesFileMethods;


		$this->LanguageList=$this->LangMisc->getLanguageList();
		$this->langpostfix=$this->LangMisc->getLangPostfix();

		if(JFactory::getApplication()->input->get('establename','','CMD'))
			$this->establename=JFactory::getApplication()->input->get('establename','','CMD');
		else
			$this->establename=$params->get( 'establename' );


		$tablerow = $this->esTable->getTableRowByName($this->establename);
		$this->estableid=$tablerow->id;

		$this->listing_id=JFactory::getApplication()->input->getInt('listing_id', 0);
		if(!JFactory::getApplication()->input->getCmd('fileboxname'))
			return false;

		$this->fileboxname=JFactory::getApplication()->input->getCmd('fileboxname');

		if(!$this->getFileBox())
			return false;

		$this->getObject();

		$this->fileboxtablename='#__customtables_filebox_'.$this->establename.'_'.$this->fileboxname;

		parent::__construct();
	}


	function getFileList()
	{
		// get database handle
		$db = JFactory::getDBO();

		$query = 'SELECT fileid, file_ext FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' ORDER BY fileid';

		$db->setQuery($query);
//		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadObjectList();

		return $rows;
	}

	function getFileBox()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT fieldtitle'.$this->langpostfix.' AS title,typeparams FROM #__customtables_fields WHERE published=1 AND fieldname="'.$this->fileboxname.'" AND type="filebox" LIMIT 1';

		$db->setQuery($query);
//		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadObjectList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->fileboxfolder=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$row->typeparams);
		$this->fileboxfolderweb=$row->typeparams;
		$this->FileBoxTitle=$row->title;


		return true;
	}

	function getObject()
	{
		$this->esfields = ESFields::getFields($this->estableid);

		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__customtables_table_'.$this->establename.' WHERE id='.$this->listing_id.' LIMIT 1';

		$db->setQuery($query);
//		if (!$db->query())    die( $db->stderr());

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

	function delete()
	{
		$db = JFactory::getDBO();

		$fileids=JFactory::getApplication()->input->getString( 'fileids');
		$file_arr=explode('*',$fileids);

		foreach($file_arr as $fileid)
		{
			if($fileid!='')
			{
				$file_ext=CustomTablesFileMethods::getFileExtByID($this->establename, $this->estableid, $this->fileboxname,$fileid);

				CustomTablesFileMethods::DeleteExistingFileBoxFile($this->fileboxfolder, $this->estableid, $this->fileboxname, $fileid, $file_ext);

				$query = 'DELETE FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' AND fileid='.$fileid;
				$db->setQuery($query);
//				if (!$db->query())    die( $db->stderr());

			}
		}

		return true;
	}


	function add()
	{
		//$allowed_ext=['doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','pdf','.png','psd','mp3'];
		$jinput=JFactory::getApplication()->input;

		$file = $jinput->input->getVar('uploadedfile', '', 'files', 'array');


		$uploadedfile= "tmp/".basename( $file['name']);

		if(!move_uploaded_file($file['tmp_name'], $uploadedfile))
			return false;


		if(JFactory::getApplication()->input->getCmd( 'base64ecnoded', '')=="true")
		{
			$src = $uploadedfile;
			$dst = "tmp/decoded_".basename( $file['name']);
			CustomTablesFileMethods::base64file_decode( $src, $dst );
			$uploadedfile=$dst;
		}



		$es= new CustomTablesMisc;

		//Save to DB

		$file_ext=CustomTablesFileMethods::FileExtenssion($uploadedfile,$this->allowedExtensions);
		//or $allowed_ext.indexOf($file_ext)==-1
		if($file_ext=='')
		{
			//unknown file extension (type)
			unlink($uploadedfile);

			return false;
		}

		$fileid=$this->addFileRecord($file_ext);



		$isOk=true;

		//es Thumb
		$newfilename=$this->fileboxfolder.DIRECTORY_SEPARATOR.$this->estableid.'_'.$this->fileboxname.'_'.$fileid.".".$file_ext;

		if($isOk)
		{

			if (!copy($uploadedfile, $newfilename))
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

		return true;
	}


	function addFileRecord($file_ext)
	{
		$db = JFactory::getDBO();

		$query = 'INSERT '.$this->fileboxtablename.' SET '

				.'file_ext="'.$file_ext.'", '
				.'listingid='.$this->listing_id;

		$db->setQuery( $query );
//		if (!$db->query())    die( $db->stderr());


		$query =' SELECT fileid FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' ORDER BY fileid DESC LIMIT 1';
		$db->setQuery( $query );
//		if (!$db->query())    die( $db->stderr());

		$espropertytype= $db->loadObjectList();
		if(count($espropertytype)==1)
		{
			return $espropertytype[0]->fileid;
		}

		return -1;
	}
}
