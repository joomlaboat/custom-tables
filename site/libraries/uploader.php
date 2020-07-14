<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');

class ESFileUploader
{
	public static function getFileNameByID($fileid)
	{
		$dir=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;
		$files = scandir($dir);

		$lookfor='_'.$fileid.'_';
		foreach($files as $file)
		{
			if(strpos($file,$lookfor)!==false)
				return $dir.$file;
		}
		return '';
	}

	public static function getfile_SafeMIME($fileid)
	{
		$jinput=JFactory::getApplication()->input;

		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
		
		if(file_exists($phptagprocessor))
			$proversion=true;
		else
			$proversion=false;

		if($proversion)
		{
			//This will let PRO version users to upload zip files, please note that it will check if the file is zip or not (mime type).
			//If not then regular Joomla input method will be used

			if(!isset($_FILES[$fileid]))
			{
				require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'
				 .DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'importcsv.php');
						
				return  json_encode(['error'=>'Failed to open file.']);
			}

			$file=$_FILES[$fileid];
			if($file==null)
				return  json_encode(['error'=>'File is empty.']);
			
			$mime=mime_content_type ($file["tmp_name"]);//read mime typw

			if($mime!='application/zip')//if not zip file
			{
				$file=$jinput->files->get($fileid); //not zip -  regular Joomla input method will be used
				
				if(!is_array($file) or count($file)==0) //regular joomla imput method blocked custom table structure json file, because it may contain javascript
				{
					$file=$_FILES[$fileid];//get file instance using php method - not safe, but we will validate it later
					
					$handle = fopen($file["tmp_name"], "rb");
					if (FALSE === $handle)
						return  json_encode(['error'=>'Failed to open file.']);
					
					$magicnumber='<customtablestableexport>';//to prove that this is Custom Tables Structure JSON file.
					$l=strlen($magicnumber);
					$file_content=fread($handle, $l);
					fclose($handle);

					if($mime=='text/plain' and $file_content==$magicnumber)
					{
						//All good
						//This is Custom Tables structure import file
					}
					else
						return  json_encode(['error'=>'Illigal mime type ('.$mime.') or content.']);
				}
			}
		}
		else
			$file=$jinput->files->get($fileid);

		return $file;
	}

	public static function checkZIPfile_X($filenamepath,$fileextension)
	{
		//Checks the file zip archive is actually a docx or xlsx or pptx
		//https://www.filesignatures.net/index.php?page=all&currentpage=6&order=EXT
		//504B0304 - zip

		/*
		$magicnumbers=array(
			'docx' => ["504B030414000600"],
			'xlsx' => [0x504B0304,0x504B030414000600],
			'pptx' => [0x504B0304,0x504B030414000600]
		);
		*/

		$magicnumbers=array(hex2bin("504B030414000600"),hex2bin("504B030414000800"));

		$l=strlen($magicnumbers[0]);

		$handle = fopen($filenamepath, "rb");
		if (FALSE === $handle) {
		    exit("Failed to open file.");
		}

		$content=fread($handle, $l);
		fclose($handle);

		$c=substr($content,0,$l);
		if($c==$magicnumbers[0] or $c==$magicnumbers[1])
		{
			if($fileextension=='docx')
				return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			elseif($fileextension=='xlsx')
				return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			elseif($fileextension=='pptx')
				return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
		}

		return 'application/zip';
	}

	public static function uploadFile($fileid,$filetypes_str="")
	{
		$filetypes_str=ESFileUploader::getAcceptedFileTypes(',,'.$filetypes_str);

		$accepted_types=ESFileUploader::getAcceptableMimeTypes($filetypes_str);

		ESFileUploader::deleteOldFiles();

		$output_dir=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;
		$t=time();

		$jinput=JFactory::getApplication()->input;
		$file=ESFileUploader::getfile_SafeMIME($fileid,$filetypes_str);

		$accepted_types=ESFileUploader::getAcceptableMimeTypes($filetypes_str);

		if(isset($file['name']))
		{
			$ret = array();
			$parts=explode('.',$file['name']);
			$fileextension=end($parts);

			//	This is for custom errors;

			$error =$file["error"];

			//You need to handle  both cases
			//If Any browser does not support serializing of multiple files using FormData()
			if(!is_array($file['name'])) //single file
			{
				$mime=mime_content_type ($file["tmp_name"]);

				if($mime=='application/zip' and $fileextension!='zip')
				{
					//could be docx, xlsx, pptx
					$mime=ESFileUploader::checkZIPfile_X($file["tmp_name"],$fileextension);
				}

				if(in_array($mime,$accepted_types))
				{

					$fileName = ESFileUploader::normalizeString($file['name']);
					$newFileName=$output_dir.'ct_'.$t.'_'.$fileid.'_'.$fileName;

					if($jinput->getCmd('task')=='importcsv')
					{
						require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'
									 .DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'importcsv.php');
						
						
						move_uploaded_file($file["tmp_name"],$newFileName);
						$msg=importCSVfile($newFileName, $jinput->getInt('tableid',0));
						if($msg!='' and $msg!='success')
							$ret = ['error'=>$msg];
						else
							$ret = ['status'=>'success','filename'=>'ct_'.$t.'_'.$fileid.'_'.$fileName];

					}
					else
					{
						move_uploaded_file($file["tmp_name"],$newFileName);
						$ret = ['status'=>'success','filename'=>'ct_'.$t.'_'.$fileid.'_'.$fileName];
					}
				}
				else
				{
					unlink($file["tmp_name"]);
					$msg='File type ('.$mime.') not permitted.';
					if($filetypes_str!='')
						$msg.=' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_TYPES' ).' '.$filetypes_str;

					$ret = ['error'=>$msg];
				}
			}
			return json_encode($ret);
		}
		else
			return  json_encode(['error'=>JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_IS_EMPTY' )]);
	}

	protected static function deleteOldFiles()
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp';

		$oldfiles = scandir($path);

		foreach($oldfiles as $oldfile)
		{
			if($oldfile!='.' and $oldfile!='..')
			{
				$filename=$path.DIRECTORY_SEPARATOR.$oldfile;

				if(strpos($oldfile,'.htm')===false and file_exists($filename))
				{
					$parts=explode('_',$oldfile);
				    if($parts[0]=='ct' and count($parts)>=4)
					{
						$t=(int)$parts[1];
						
						$now=time();
						$o=$now-$t;
						if($o>3600)//delete files uploaded more than an hour ago.
							unlink($filename);
					}
				}
			}
		}
	}

	protected static function getAcceptableMimeTypes($filetypes_str="")
	{
		if($filetypes_str=='')
		{
			$app = JFactory::getApplication();
			$jinput=$app->input;
			$fieldname=$jinput->getCmd('fieldname','');

			$tablerow=ESFileUploader::getTableRawByItemid();
			$estableid=$tablerow['id'];

			$esfield=ESFields::getFieldAsocByName($fieldname, $estableid);

			if($esfield['type']=='image')
				return array('image/gif', 'image/png', 'image/jpeg','image/svg+xml');

			$fieldparams=$esfield['typeparams'];
			$parts=JoomlaBasicMisc::csv_explode(',',$fieldparams,'"',false);

			if(!isset($parts[2]))
				return array();

			$filetypes_str=$parts[2];
		}
		$filetypes=explode(' ',$filetypes_str);

		$accepted_filetypes=array();

		foreach($filetypes as $filetype)
		{
			$mime=ESFileUploader::get_mime_type('1.'.$filetype);
			$accepted_filetypes[]=$mime;

			if($filetype=='docx')
				$accepted_filetypes[]='application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			elseif($filetype=='xlsx')
				$accepted_filetypes[]='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			elseif($filetype=='pptx')
				$accepted_filetypes[]='application/vnd.openxmlformats-officedocument.presentationml.presentation';

		}

		return $accepted_filetypes;
	}


	public static function get_mime_type($filename)
	{
    $idx = explode( '.', $filename );
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode-1]);

    $mimet = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed', //not allowed
        'exe' => 'application/x-msdownload', //not allowed
        'msi' => 'application/x-msdownload', //not allowed
        'cab' => 'application/vnd.ms-cab-compressed', //not allowed

        // audio
        'mp3' => 'audio/mpeg',
		'flac' => 'audio/flac',
		'aac' => 'audio/aac',
		'wav' => 'audio/wav',
		'ogg' => 'audio/ogg',

		// video
		'mp4' => 'video/mp4',
		'm4a' => 'video/mp4',
		'm4p' => 'video/mp4',
		'm4b' => 'video/mp4',
		'm4r' => 'video/mp4',
		'm4v' => 'video/mp4',
		'flv' => 'video/x-flv',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
		'3gp' => 'video/3gpp',
		'avi' => 'video/x-msvideo',
		'mpg' => 'video/mpeg',
		'wmv' => 'video/x-ms-wmv',
		'swf' => 'application/x-shockwave-flash',
		
        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    if (isset( $mimet[$idx] )) {
     return $mimet[$idx];
    } else {
     return 'application/octet-stream';
    }
 }

	protected static function getTableRawByItemid()
	{
		$app = JFactory::getApplication();
		$jinput=$app->input;
		$Itemid=$jinput->getInt('Itemid',0);

		$menuItem = $app->getMenu()->getItem($Itemid);
		// Get params for menuItem
		$params = $menuItem->params;

		$esTable=new ESTables;
		$establename=$params->get( 'establename' );
		if($establename=='')
			return 0;

		$tablerow = $esTable->getTableRowByNameAssoc($establename);

		return $tablerow;

	}


	public static function normalizeString ($str = '')
	{
		//String sanitizer for filename
		//https://stackoverflow.com/a/1.2.636
	    $str = strip_tags($str);
	    $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
	    $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
	    $str = strtolower($str);
	    $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
	    $str = htmlentities($str, ENT_QUOTES, "utf-8");
	    $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
	    $str = str_replace(' ', '-', $str);
	    $str = rawurlencode($str);
	    $str = str_replace('%', '-', $str);
	    return $str;
	}

	public static function getAcceptedFileTypes($typeparams)
    {
        $pair=explode(',',$typeparams);

        $allowedExtensions='doc docx pdf txt xls xlsx psd ppt pptx odg odp ods odt'
		.' xcf ai txt avi csv accdb htm html'
		.' jpg bmp ico jpeg png gif svg ai'//Images
		.' zip'//Archive
		.' aac flac mp3 wav ogg'//Audio
		.' mp4 m4a m4p m4b m4r m4v wma flv mpg 3gp wmv mov';//Video

		$allowedExts=explode(' ',$allowedExtensions);
		$file_formats=array();
		if(isset($pair[2]) and $pair[2]!='')
		{
			$file_formats_=explode(' ',$pair[2]);
			foreach($file_formats_ as $f)
			{
				if(in_array($f,$allowedExts))
				   $file_formats[]=$f;
			}

		}
		else
			$file_formats=$allowedExts;

        return implode(' ',$file_formats);
    }
}
