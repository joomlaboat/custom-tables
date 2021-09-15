<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CustomTablesFileMethods
{
	static public function FileExtenssion($src,$allowedExtensions='doc docx pdf txt xls xlsx psd ppt pptx png jpg jpeg gif webp mp3')
	{
		$name = explode(".", strtolower($src));
		if(count($name)<2)
			return ''; //not allowed
	
		$file_ext = $name[count($name)-1];
		$extensions = explode(" ", $allowedExtensions);
	
		if(!in_array($file_ext,$extensions))
			return ''; //not allowed

		return $file_ext;
	}

	static public function DeleteExistingFileBoxFile($filefolder, $estableid, $fileboxname, $fileid, $ext)
	{
		$filename=$filefolder.DIRECTORY_SEPARATOR.$estableid.'_'.$fileboxname.'_'.$fileid.'.'.$ext;
	
		if(file_exists($filename))
			unlink($filename);
	}

	static public function getFileExtByID($establename, $estableid, $fileboxname,$id)
	{
		$db = JFactory::getDBO();
	
		$fileboxtablename='#__customtables_filebox_'.$establename.'_'.$fileboxname;
		$query = 'SELECT file_ext FROM '.$fileboxtablename.' WHERE fileid='.(int)$id.' LIMIT 1';
		$db->setQuery($query);
		$filerows=$db->loadObjectList();
		if(count($filerows)!=1)
			return '';
		
		$rec=$filerows[0];
		return $rec->file_ext;
	}

	static public function DeleteFileBoxFiles($filebox_table_name, $estableid, $fileboxname, $typeparams)
	{
		$filefolder=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$typeparams);
		
		$db = JFactory::getDBO();

		$query = 'SELECT fileid FROM '.$filebox_table_name;
		$db->setQuery($query);

		$filerows=$db->loadObjectList();
										
		foreach($filerows as $filerow)
		{
			CustomTablesFileMethods::DeleteExistingFileBoxFile(
				$filefolder,
				$estableid,
				$fileboxname,
				$filerow->fileid,
				$filerow->file_ext
			);
		}
	}




static public function base64file_decode( $inputfile, $outputfile ) { 
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
	






}
