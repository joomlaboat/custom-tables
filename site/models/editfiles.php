	<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

class CustomTablesModelEditFiles extends JModelLegacy
{
	var $ct;
	var $filemethods;
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
		$this->ct = new CT;
		
		parent::__construct();

		$this->allowedExtensions='doc docx pdf txt xls xlsx psd ppt pptx webp png mp3 jpg jpeg csv accdb';

		$app = JFactory::getApplication();
		$params = $app->getParams();
		
		$this->ct->Env->menu_params = $params;

		$this->maxfilesize=JoomlaBasicMisc::file_upload_max_size();

		$this->filemethods=new CustomTablesFileMethods;

		$this->ct->getTable($params->get( 'establename' ), null);
				
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected (63).', 'error');
			return;
		}

		$this->listing_id=$this->ct->Env->jinput->getInt('listing_id', 0);
		if(!$this->ct->Env->jinput->getCmd('fileboxname'))
			return false;


		$this->fileboxname=$this->ct->Env->jinput->getCmd('fileboxname');

		if(!$this->getFileBox())
			return false;

		$this->getObject();

		$this->fileboxtablename='#__customtables_filebox_'.$this->ct->Table->tablename.'_'.$this->fileboxname;

		parent::__construct();
	}

	function getFileList()
	{
		// get database handle
		$db = JFactory::getDBO();
		$query = 'SELECT fileid, file_ext FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' ORDER BY fileid';
		$db->setQuery($query);
		$rows=$db->loadObjectList();

		return $rows;
	}

	function getFileBox()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT fieldtitle'.$this->ct->Languages->Postfix.' AS title,typeparams FROM #__customtables_fields WHERE published=1 AND tableid='
			.(int)$this->ct->Table->tableid.' AND fieldname="'.$this->fileboxname.'" AND type="filebox" LIMIT 1';

		$db->setQuery($query);

		$rows=$db->loadObjectList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$pair=explode(',',$row->typeparams);
		$this->fileboxfolderweb='images/'.$pair[1];

		$this->fileboxfolder=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$this->fileboxfolderweb);
		//Create folder if not exists
		if (!file_exists($this->fileboxfolder))
			mkdir($this->fileboxfolder, 0755, true);

		$this->FileBoxTitle=$row->title;

		return true;
	}

	function getObject()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__customtables_table_'.$this->ct->Table->tablename.' WHERE id='.(int)$this->listing_id.' LIMIT 1';

		$db->setQuery($query);

		$rows = $db->loadAssocList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->Listing_Title='';

		foreach($this->ct->Table->fields as $mFld)
		{
			$titlefield=$mFld['realfieldname'];
			if(!(strpos($mFld['type'],'multi')===false))
				$titlefield.=$this->ct->Languages->Postfix;

			if($row[$titlefield]!='')
			{
				$this->Listing_Title=$row[$titlefield];
				break;
			}
		}
	}

	function delete()
	{
		$db = JFactory::getDBO();

		$fileids=$this->ct->Env->jinput->getString('fileids','');
		$file_arr=explode('*',$fileids);

		foreach($file_arr as $fileid)
		{
			if($fileid!='')
			{
				$file_ext=CustomTablesFileMethods::getFileExtByID($this->ct->Table->tablename, $this->ct->Table->tableid, $this->fileboxname,$fileid);

				CustomTablesFileMethods::DeleteExistingFileBoxFile($this->fileboxfolder, $this->ct->Table->tableid, $this->fileboxname, $fileid, $file_ext);

				$query = 'DELETE FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' AND fileid='.$fileid;
				$db->setQuery($query);
				$db->execute();
			}
		}

		$this->ct->Table->saveLog($this->listing_id,9);

		return true;
	}


	function add()
	{
		$file=$this->ct->Env->jinput->files->get('uploadedfile'); //not zip -  regular Joomla input method will be used

		$uploadedfile= "tmp/".basename( $file['name']);

		if(!move_uploaded_file($file['tmp_name'], $uploadedfile))
			return false;


		if($this->ct->Env->jinput->getCmd( 'base64ecnoded', '')=="true")
		{
			$src = $uploadedfile;
			$dst = "tmp/decoded_".basename( $file['name']);
			CustomTablesFileMethods::base64file_decode( $src, $dst );
			$uploadedfile=$dst;
		}

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
		$newfilename=$this->fileboxfolder.DIRECTORY_SEPARATOR.$this->ct->Table->tableid.'_'.$this->fileboxname.'_'.$fileid.".".$file_ext;

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

		$this->ct->Table->saveLog($this->listing_id,8);
		return true;
	}


	function addFileRecord($file_ext)
	{
		$db = JFactory::getDBO();

		$query = 'INSERT '.$this->fileboxtablename.' SET '
			.'file_ext="'.$file_ext.'", '
			.'listingid='.$this->listing_id;

		$db->setQuery( $query );
		$db->execute();	

		$query =' SELECT fileid FROM '.$this->fileboxtablename.' WHERE listingid='.$this->listing_id.' ORDER BY fileid DESC LIMIT 1';
		$db->setQuery( $query );

		$espropertytype= $db->loadObjectList();
		if(count($espropertytype)==1)
		{
			return $espropertytype[0]->fileid;
		}

		return -1;
	}
}
