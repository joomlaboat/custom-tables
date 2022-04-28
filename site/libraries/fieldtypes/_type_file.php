<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;
use CustomTables\Field;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'uploader.php');

class CT_FieldTypeTag_file
{
	public static function process($filename,&$field, $option_list, $recid, $filename_only=false)
    {
        if($filename=='')
            return '';

        $FileFolder=CT_FieldTypeTag_file::getFileFolder($field->params);

        $filepath=$FileFolder.'/'.$filename;
		
        if(!isset($option_list[2]))
            $iconsize='32';
        else
            $iconsize=$option_list[2];
            
        if($iconsize!="16" and $iconsize!="32" and $iconsize!="48")
            $iconsize='32';
		
		$parts=explode('.',$filename);
        $fileextension=end($parts);
		$icon='/components/com_customtables/libraries/customtables/media/images/fileformats/'.$iconsize.'px/'.$fileextension.'.png';
		$icanFilePath=JPATH_SITE.$icon;
		if (!file_exists($icanFilePath))
			$icon='';
            
        $how_to_process=$option_list[0];

        if($how_to_process!='')
            $filepath=CT_FieldTypeTag_file::get_private_file_path($filename,$how_to_process,$filepath,$recid,$field->id,$field->ct->Table->tableid,$filename_only);
		
       
		$target='';
		if(isset($option_list[3]))
		{
			if($option_list[3]=='_blank')
				$target=' target="_blank"';
			if($option_list[3]=='savefile')
			{
				if(strpos($filepath,'?')===false)
					$filepath.='?';
				else
					$filepath.='&';
				
				$filepath.='savefile=1'; //Will add HTTP Header: @header("Content-Disposition: attachment; filename=\"".$filename."\"");
			}
		}
        
        $output_format='';
        if(isset($option_list[1]))
            $output_format=$option_list[1];
        
    	switch($output_format)
        {
            case '':
                //Link Only
                return $filepath;
                break;
            
			case 'link':
                //Link Only
                return $filepath;
                break;
            
            case 'icon-filename-link':
                //Clickable Icon and File Name
                return '<a href="'.$filepath.'"'.$target.'>'.($icon!='' ? '<img src="'.$icon.'" alt="'.$filename.'" title="'.$filename.'" />' : '').'<span>'.$filename.'</span></a>';
                break;
            
            case 'icon-link':
                //Clickable Icon
                return '<a href="'.$filepath.'"'.$target.'>'.($icon!='' ? '<img src="'.$icon.'" alt="'.$filename.'" title="'.$filename.'" />' : $filename).'</a>';//show file name if icon not available
                
                break;
            
            case 'filename-link':
                //Clickable File Name
                return '<a href="'.$filepath.'"'.$target.'>'.$filename.'</a>';
                break;
            
            case 'link-anchor':
                //Clickable Link
                return '<a href="'.$filepath.'"'.$target.'>'.$filepath.'</a>';
                break;
            
            case 'icon':
                //Icon
                return ($icon!='' ? '<img src="'.$icon.'" alt="'.$filename.'" title="'.$filename.'" />' : '');//show nothing is icon not available
                break;
            
            case 'link-to-icon':
                //Link to Icon
                
                return $icon;//show nothing if icon not available
                
                break;
            
            case 'filename':
                return $filename;
                //File Name
                break;
            
            case 'extension':
                return $fileextension;
                //Extension
                break;
            
            default:
                return $filepath;
            
                break;
        }
    }

    static protected function get_security_letter($how_to_process)
    {
        switch($how_to_process)
        {
            case 'timelimited':
                return 'd';
            break;

            case 'timelimited_longterm':
                return 'e';
            break;

            case 'hostlimited':
                return 'f';
            break;

            case 'hostlimited_longterm':
                return 'g';
            break;

            case 'private':
                return 'h';
            break;

            case 'private_longterm':
                return 'i';
            break;

            default:
                return '';
            break;
        }

        return '';
    }

    static protected function get_private_file_path($rowValue,$how_to_process,$filepath,$recid,$fieldid,$tableid,$filename_only=false)
    {
            $security=CT_FieldTypeTag_file::get_security_letter($how_to_process);

            //make the key
            $key=CT_FieldTypeTag_file::makeTheKey($filepath,$security,$recid,$fieldid,$tableid);

            $jinput=JFactory::getApplication()->input;
            $Itemid=$jinput->getInt('Itemid',0);

            $currenturl=JoomlaBasicMisc::curPageURL();
            $currenturl=JoomlaBasicMisc::deleteURLQueryOption($currenturl,'returnto');

            //prepare new file name that includes the key
            $fna=explode('.',$rowValue);
            $filetype=$fna[count($fna)-1];
            array_splice($fna, count($fna)-1);
            $filename=implode('.',$fna);
            $filepath=$filename.'_'.$key.'.'.$filetype;//'/index.php?option=com_customtables&view=files&file='.$rowValue.'&Itemid='.$Itemid.'&key='.$key;

            if(!$filename_only)
            {
                if(strpos($currenturl,'?')!==false)
                {
                    $filepath=$currenturl.'&file='.$filepath;
                }
                else
                {
                    if($currenturl[strlen($currenturl)-1]!='/')
                        $filepath=$currenturl.'/'.$filepath;
					else
						$filepath=$currenturl.$filepath;
                }
            }

            return $filepath;
    }

    static public function get_file_type_value(&$field, $listing_id)
    {
		$db = JFactory::getDBO();
		
        $jinput=JFactory::getApplication()->input;
        
        $FileFolder=CT_FieldTypeTag_file::getFileFolder($field->params);

        $fileid = $jinput->post->get($field->comesfieldname, '','STRING' );

		$value = null;
		$value_found = false;
		
		$filepath=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$FileFolder);

		if($listing_id==0)
        {
			$value = CT_FieldTypeTag_file::UploadSingleFile('',$fileid, $field,JPATH_SITE.$FileFolder);
			if($value)
				$value_found = true;
        }
		else
		{
			$to_delete = $jinput->post->get($field->comesfieldname.'_delete', '','CMD' );

			$ExistingFile=$field->ct->Table->getRecordFieldValue($listing_id,$field->realfieldname);

            if($to_delete=='true')
			{
				if($ExistingFile!='' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile,$FileFolder,$field))
				{
					$filename_full=$filepath.DIRECTORY_SEPARATOR.$ExistingFile;
					if(file_exists($filename_full))
						unlink($filename_full);
				}
            
				$value_found = true;
			}

			$value = CT_FieldTypeTag_file::UploadSingleFile($ExistingFile,$fileid, $field,JPATH_SITE.$FileFolder);
			if($value)
				$value_found = true;
		}

		if($value_found)
			return $value;

        return null;
    }

    protected static function UploadSingleFile($ExistingFile, $file_id, &$field, $FileFolder)//,$realtablename='-options')
    {
		if(isset($field->params[2]))
			$fileextensions = $field->params[2];
		else
			$fileextensions = '';

		if($file_id!='')
		{
            $accepted_file_types=explode(' ',ESFileUploader::getAcceptedFileTypes($fileextensions));

         	$accepted_filetypes=array();

            foreach($accepted_file_types as $filetype)
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

			$uploadedfile= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$file_id;

			$is_base64encoded=JFactory::getApplication()->input->get('base64encoded','','CMD');
			if($is_base64encoded=="true")
			{
				$src = $uploadedfile;
				$dst= JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'decoded_'.basename( $file['name']);
				CustomTablesFileMethods::base64file_decode( $src, $dst );
				$uploadedfile=$dst;
			}
			
    		if($ExistingFile!='' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile,$FileFolder,$field))
    		{
				//Delete Old File
    			$filename_full=$FileFolder.DIRECTORY_SEPARATOR.$ExistingFile;

                if(file_exists($filename_full))
                  	unlink($filename_full);
			}

			if(!file_exists($uploadedfile))
				return false;
			
            $mime=mime_content_type ($uploadedfile);

            $parts=explode('.',$uploadedfile);
			$fileextension=end($parts);
            if($mime=='application/zip' and $fileextension!='zip')
			{
					//could be docx, xlsx, pptx
				$mime=ESFileUploader::checkZIPfile_X($uploadedfile,$fileextension);
			}

			if(in_array($mime,$accepted_filetypes))
			{

                $new_filename=CT_FieldTypeTag_file::getCleanAndAvailableFileName($file_id,$FileFolder);
                $new_filename_path=str_replace('/',DIRECTORY_SEPARATOR,$FileFolder.DIRECTORY_SEPARATOR.$new_filename);

                if(@copy($uploadedfile,$new_filename_path))
				{
					unlink($uploadedfile);
					
					//Copied
					return $new_filename;
				}
				else
				{
					unlink($uploadedfile);
					
					//Cannot copy
					return false;
				}
			}
			else
			{
				unlink($uploadedfile);
				return false;
			}
		}
		return false;
	}
	
	static protected function checkIfTheFileBelongsToAnotherRecord($filename,$FileFolder,&$field)
	{
		$db = JFactory::getDBO();
		$query='SELECT * FROM '.$field->ct->Table->realtablename.' WHERE '.$field->realfieldname.'='.$db->quote($filename).' LIMIT 2';
		
		$db->setQuery( $query );
		$db->execute();
		
		return $db->getNumRows()>1;
	}

    static protected function getCleanAndAvailableFileName($filename,$FileFolder)
    {

        $parts=explode('_',$filename);
        if(count($parts)<4)
            return '';

        $parts[0]='';
        $parts[1]='';
        $parts[2]='';

        $new_filename=trim(implode(' ',$parts));

        //Clean Up file name
		$filename_raw=strtolower($new_filename);
		$filename_raw=str_replace(' ','_',$filename_raw);
		$filename_raw=str_replace('-','_',$filename_raw);
		$filename=preg_replace("/[^a-z0-9._]/", "", $filename_raw);

        $i=0;
		$filename_new=$filename;
		do
		{

			if(file_exists($FileFolder.DIRECTORY_SEPARATOR.$filename_new))
			{
				//increase index
				$i++;
				$filename_new=str_replace('.','-'.$i.'.',$filename);
			}
			else
				break;

		}while(1==1);

        return $filename_new;
    }

    public static function renderFileFieldBox(&$ct, &$fieldrow,&$row,$class)
	{
		$field = new Field($ct,$fieldrow);
		
        if(count($row)>0 and $row['listing_id'] != 0)
            $file=$row[$field->realfieldname];
        else
            $file='';

    	$result='<div class="esUploadFileBox" style="vertical-align:top;">';

		$result.=CT_FieldTypeTag_file::renderFileAndDeleteOption($file,$field);
        $result.=CT_FieldTypeTag_file::renderUploader($field);

   		$result.='</div>';
       	return $result;
    }

    protected static function renderFileAndDeleteOption($file,&$field)
    {
        if($file=='')
            return '';

        $FileFolder=CT_FieldTypeTag_file::getFileFolder($field->params);

        $link=$FileFolder.'/'.$file;

        $parts=explode('.',$file);
        $fileextension=end($parts);

        $imagesrc=JURI::root(true).'/components/com_customtables/libraries/customtables/media/images/fileformats/48px/'.$fileextension.'.png';

        $prefix='comes_';
        $result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_'.$field->fieldname.'">';

						    $result.='<a href="'.$link.'" target="_blank" alt="'.$file.'" title="'.$file.'"><img src="'.$imagesrc.'" width="48" /></a><br/>';

							if(!$field->isrequired)
								$result.='<input type="checkbox" name="'.$field->prefix.$field->fieldname.'_delete" id="'.$field->prefix.$field->fieldname.'_delete" value="true">'
								.' Delete File';

		$result.='
                </div>';

        return $result;
    }

    protected static function renderUploader(&$field)
    {
		if(isset($field->params[2]))
			$fileextensions = $field->params[2];
		else
			$fileextensions = '';
		
        $accepted_file_types=ESFileUploader::getAcceptedFileTypes($fileextensions);
		
		$custom_max_size = (int)$field->params[0];
		if($custom_max_size != 0 and $custom_max_size < 10000)
			$custom_max_size = $custom_max_size  * 1000000; //to change 20 to 20MB
		
        $max_file_size=JoomlaBasicMisc::file_upload_max_size($custom_max_size);

        $fileid=JoomlaBasicMisc::generateRandomString();

		$result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
                
                	<div id="ct_fileuploader_'.$field->fieldname.'"></div>
                    <div id="ct_eventsmessage_'.$field->fieldname.'"></div>
                	<script>
                        UploadFileCount=1;

                    	var urlstr="'.JURI::root(true).'/index.php?option=com_customtables&view=fileuploader&tmpl=component&'.$field->fieldname
							.'_fileid='.$fileid.'&Itemid='.$field->ct->Env->Itemid.'&fieldname='.$field->fieldname.'";
                    	
						ct_getUploader('.$field->id.',urlstr,'.$max_file_size.',"'.$accepted_file_types.'","eseditForm",false,"ct_fileuploader_'.$field->fieldname.'","ct_eventsmessage_'
							.$field->fieldname.'","'.$fileid.'","'.$field->prefix.$field->fieldname.'","ct_ubloadedfile_box_'.$field->fieldname.'");

                    </script>
                    <input type="hidden" name="'.$field->prefix.$field->fieldname.'" id="'.$field->prefix.$field->fieldname.'" value="" />
                    '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_FILE_TYPES').': '.$accepted_file_types.'<br/>
					'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                </div>
                ';

        return $result;

    }
	
	public static function getFileFolder(&$params)
    {
        $folder='';

		if(isset($params[1]))
			$folder=$params[1];
        
		if($folder=='')
			$folder='/images';	//default folder
		elseif($folder[0]=='/')
		{
			//absolute path
			
			//delete trailing slash if found
			$p=substr($folder,strlen($folder)-1,1);
			if($p=='/')
				$folder=substr($folder,0,strlen($folder)-1);
		}
		else
		{
			$folder='/'.$folder;
			if(strlen($folder)>8)//add /images to relative path
			{
				$p=substr($folder,0,8);
				if($p!='/images/')
					$folder='/images'.$folder;
			}
			else
			{
				$folder='/images'.$folder;
			}

			//delete trailing slash if found
			$p=substr($folder,strlen($folder)-1,1);
			if($p=='/')
				$folder=substr($folder,0,strlen($folder)-1);
		}
		
		$folderPath=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$folder); //relative path
		
		//Create folder if not exists
			if (!file_exists($folderPath))
				mkdir($folderPath, 0755, true);
		
		return $folder;
    }
    
    public static function wrong()
    {
    	JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
		return false;
    }

    public static function process_file_link($filename)
    {

        $jinput=JFactory::getApplication()->input;
        $parts=explode('.',$filename);

        if(count($parts)==1)
            CT_FieldTypeTag_file::wrong();

        $filetype=$parts[count($parts)-1];

        array_splice($parts, count($parts)-1);
        $filename_without_ext=implode('.',$parts);

        $parts2=explode('_',$filename_without_ext);
        $key=$parts2[count($parts2)-1];

        $key_parts=explode('c',$key);

        if(count($key_parts)==1)
            CT_FieldTypeTag_file::wrong();

        $jinput->set('key', $key);

        $key_params=$key_parts[count($key_parts)-1];

        //TODO: improve it. Get $security from layout, somehow
        //security letters tells what method used
        $security='d';//Time Limited (8-24 minutes)

        if(strpos($key_params,'e')!==false)            $security='e';//Time Limited (1.5 - 4 hours)
        elseif(strpos($key_params,'f')!==false)        $security='f';//Time/Host Limited (8-24 minutes)
        elseif(strpos($key_params,'g')!==false)        $security='g';//Time/Host Limited (1.5 - 4 hours)
        elseif(strpos($key_params,'h')!==false)        $security='h';//Time/Host/User Limited (8-24 minutes)
        elseif(strpos($key_params,'i')!==false)        $security='i';//Time/Host/User Limited (1.5 - 4 hours)
		
		$jinput->set('security', $security);

        $key_params_a=explode($security,$key_params);
        if(count($key_params_a)!=2)
            CT_FieldTypeTag_file::wrong();

        $listing_id=$key_params_a[0];
		$jinput->set('listing_id', $listing_id);
		
		if(isset($key_params_a[1]))
		{
			$fieldid=$key_params_a[1];
			$jinput->set('fieldid', $fieldid);
		}
		
		if(isset($key_params_a[2]))
		{
			$tableid=$key_params_a[2];
			$jinput->set('tableid', $tableid);
		}
    }

    public static function makeTheKey($filepath,$security,$rec_id,$fieldid,$tableid)
    {
        $user = JFactory::getUser();
        $username=$user->get('username');
        $currentuserid=(int)$user->get('id');
        $t=time();
        //prepare augmented timer
        $secs=1000;
        if($security=='e' or $security=='g' or $security=='i')
            $secs=10000;

        $tplus=floor(($t+$secs)/$secs)*$secs;
        $ip=$_SERVER['REMOTE_ADDR'];

        //get secs key char
        $sep=$security;//($secs==1000 ? 'a' : 'b');
        $m2='c'.$rec_id.$sep.$fieldid.$sep.$tableid;

        //calculate MD5
        if($security=='d' or $security=='e')
            $m=md5($filepath.$tplus);
        elseif($security=='f' or $security=='g')
            $m=md5($filepath.$tplus.$ip);
        elseif($security=='h' or $security=='i')
            $m=md5($filepath.$tplus.$ip.$username.$currentuserid);

        //replace rear part of the hash
        $m3=substr($m,0,strlen($m)-strlen($m2));
        $m4=$m3.$m2;

        return $m4;
    }
}
