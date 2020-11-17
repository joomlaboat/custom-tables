<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'uploader.php');

class CT_FieldTypeTag_file
{
    public static function process($filename,$TypeParams, $option_list,$recid,$fieldid,$tableid,$filename_only=false)
    {
        if($filename=='')
            return '';

        $FileFolder=CT_FieldTypeTag_file::getFileFolder($TypeParams);

        $filepath=$FileFolder.'/'.$filename;
		
        if(!isset($option_list[2]))
            $iconsize='32';
        else
            $iconsize=$option_list[2];
            
        if($iconsize!="16" and $iconsize!="32" and $iconsize!="48")
            $iconsize='32';
		
		$parts=explode('.',$filename);
        $fileextension=end($parts);
		$icon='/components/com_customtables/images/fileformats/'.$iconsize.'px/'.$fileextension.'.png';
		$icanFilePath=JPATH_SITE.$icon;
		if (!file_exists($icanFilePath))
			$icon='';
            
        $how_to_process=$option_list[0];

        if($how_to_process!='')
            $filepath=CT_FieldTypeTag_file::get_private_file_path($filename,$how_to_process,$filepath,$recid,$fieldid,$tableid,$filename_only);
		
       
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

    static public function get_file_type_value($id,&$es,&$savequery,$typeparams,$prefix,$esfieldname,$establename)
    {
        $value_found=false;
        $jinput=JFactory::getApplication()->input;
        $mysqltablename='#__customtables_table_'.$establename;
        $comesfieldname=$prefix.$esfieldname;
        $FileFolder=CT_FieldTypeTag_file::getFileFolder($typeparams);

        $fileid = $jinput->post->get($comesfieldname, '','STRING' );

		$value='';
		$filepath=JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$FileFolder);

					if($id==0)
                    {
                        $value=CT_FieldTypeTag_file::UploadSingleFile('',$fileid, $esfieldname,JPATH_SITE.$FileFolder,$typeparams,$establename);
                    }
					else
					{
                        $to_delete = $jinput->post->get($comesfieldname.'_delete', '','CMD' );
						$ExistingFile=$es->isRecordExist($id,'id', 'es_'.$esfieldname, $mysqltablename);

                        if($to_delete=='true')
						{
								if($ExistingFile!='' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile,$FileFolder,$establename,$esfieldname))
								{
									$filename_full=$filepath.DIRECTORY_SEPARATOR.$ExistingFile;
									if(file_exists($filename_full))
										unlink($filename_full);

								}
                                $value_found=true;
								$savequery[]='es_'.$esfieldname.'=""';
						}
						else
                            $value=CT_FieldTypeTag_file::UploadSingleFile($ExistingFile,$fileid, $esfieldname,JPATH_SITE.$FileFolder,$typeparams,$establename);
					}

					if($value and $value!='')
                    {
                        $value_found=true;
						$savequery[]='es_'.$esfieldname.'="'.$value.'"';
                    }

        return $value_found;
    }




    protected static function UploadSingleFile($ExistingFile, $file_id, $esfieldname,$FileFolder,$typeparams,$establename='-options')
    {
		$jinput = JFactory::getApplication()->input;

		if($file_id!='')
		{
            $accepted_file_types=explode(' ',ESFileUploader::getAcceptedFileTypes($typeparams));

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

			
    		if($ExistingFile!='' and !CT_FieldTypeTag_file::checkIfTheFileBelongsToAnotherRecord($ExistingFile,$FileFolder,$establename,$esfieldname))
    		{
				//Delete Old File
    			$filename_full=$FileFolder.DIRECTORY_SEPARATOR.$ExistingFile;

                if(file_exists($filename_full))
                  	unlink($filename_full);
			}

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
	
	static protected function checkIfTheFileBelongsToAnotherRecord($filename,$FileFolder,$establename,$esfieldname)
	{
		$mysqltablename='#__customtables_table_'.$establename;
        $comesfieldname='es_'.$esfieldname;
		
		$db = JFactory::getDBO();
		$query='SELECT id FROM '.$mysqltablename.' WHERE '.$comesfieldname.'='.$db->quote($filename).' LIMIT 2';
		
		$db->setQuery( $query );
		$db->execute();
//		if (!$db->query())    die ;
		
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

    public static function renderFileFieldBox($prefix,&$esfield,&$row,$realFieldName,$class)
	{
        $document = JFactory::getDocument();
        $document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/uploader.js"></script>');


        if(count($row)>0)
            $file=$row[$realFieldName];
        else
            $file='';


        //$isShortcut=false;
		//$imagesrc=CT_FieldTypeTag_image::getImageSRC($row,$realFieldName,$ImageFolder,$imagefile,$isShortcut);

    	$result='<div class="esUploadFileBox" style="vertical-align:top;">';


        $result.=CT_FieldTypeTag_file::renderFileAndDeleteOption($file,$esfield);

        $result.=CT_FieldTypeTag_file::renderUploader((int)$esfield['id'],$esfield['fieldname'],$esfield['typeparams']);

   		$result.='</div>';
       	return $result;
    }




    protected static function renderFileAndDeleteOption($file,&$esfield)
    {
        if($file=='')
            return '';

        $FileFolder=CT_FieldTypeTag_file::getFileFolder($esfield['typeparams']);

                $link=$FileFolder.'/'.$file;

                $parts=explode('.',$file);
                $fileextension=end($parts);

                $imagesrc='/components/com_customtables/images/fileformats/48px/'.$fileextension.'.png';

                $prefix='comes_';
                $result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;" id="ct_uploadedfile_box_'.$esfield['fieldname'].'">';

						    $result.='<a href="'.$link.'" target="_blank" alt="'.$file.'" title="'.$file.'"><img src="'.$imagesrc.'" width="48" /></a><br/>';

							if(!$esfield['isrequired'])
								$result.='<input type="checkbox" name="'.$prefix.$esfield['fieldname'].'_delete" id="'.$prefix.$esfield['fieldname'].'_delete" value="true">'
								.' Delete File';

				$result.='
                </div>';

            return $result;
    }

    protected static function renderUploader($fieldid,$esfieldname,$typeparams)
    {
        $accepted_file_types=ESFileUploader::getAcceptedFileTypes($typeparams);
        $max_file_size=JoomlaBasicMisc::file_upload_max_size();
        $prefix='comes_';

        $fileid=JoomlaBasicMisc::generateRandomString();

        $jinput=JFactory::getApplication()->input;

		$Itemid=$jinput->getInt('Itemid',0);

                $result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
                
                	<div id="ct_fileuploader_'.$esfieldname.'"></div>
                    <div id="ct_eventsmessage_'.$esfieldname.'"></div>
                	<script>
                        UploadFileCount=1;

                    	var urlstr="/index.php?option=com_customtables&view=fileuploader&tmpl=component&'.$esfieldname.'_fileid='.$fileid.'&Itemid='.$Itemid.'&fieldname='.$esfieldname.'";
                    	ct_getUploader('.$fieldid.',urlstr,'.$max_file_size.',"'.$accepted_file_types.'","eseditForm",false,"ct_fileuploader_'.$esfieldname.'","ct_eventsmessage_'.$esfieldname.'","'.$fileid.'","'.$prefix.$esfieldname.'","ct_ubloadedfile_box_'.$esfieldname.'");
                    </script>
                    <input type="hidden" name="'.$prefix.$esfieldname.'" id="'.$prefix.$esfieldname.'" value="" />
                    '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_FILE_TYPES').': '.$accepted_file_types.'<br/>
					'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                </div>
                ';

        return $result;

    }
	
	public static function getFileFolder($typeparams)
    {

        $folder='';
        $pair=explode(',',$typeparams);

		if(isset($pair[1]))
			$folder=$pair[1];

        
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

			//delete trailing slash if found
			$p=substr($folder,strlen($folder)-1,1);
			if($p=='/')
				$folder=substr($folder,0,strlen($folder)-1);

			
			$folderPath=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$folder); //relative path
			//Create folder if not exists
			if (!file_exists($folderPath))
				mkdir($folderPath, 0755, true);
		}

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

        $key_params_a=explode($security,$key_params);
        if(count($key_params_a)!=2)
            CT_FieldTypeTag_file::wrong();

        $listing_id=$key_params_a[0];
        $fieldid=$key_params_a[1];
        //$tableid=$key_params_a[2];

        //set extracted parameters
        $jinput->set('listing_id', $listing_id);
        $jinput->set('fieldid', $fieldid);
        $jinput->set('security', $security);
        //$jinput->set('tableid', $tableid);
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
