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

class CT_FieldTypeTag_image
{

    static public function getImageSRClayoutview($option_list,$rowValue,$TypeParams,&$imagesrc,&$imagetag)//,$onlylink=false)
	{
		if(strpos($rowValue,'-')!==false)
			$rowValue=str_replace('-','',$rowValue);

		$conf = JFactory::getConfig();
		$sitename = $conf->get('config.sitename');

		$option=$option_list[0];

		//$onlylink=false;
		//if(isset($option_list[1]) and $option_list[1]=='link')
			//$onlylink=true;//this is wrong approach

		$ImageFolder_=CustomTablesImageMethods::getImageFolder($TypeParams);
		
	
		$ImageFolderWeb=str_replace(DIRECTORY_SEPARATOR,'/',$ImageFolder_);
		$ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder_);

		$imagesrc='';
		$imagetag='';
		
		if($option=='' or $option=='_esthumb' or $option=='_thumb')
		{

			$prefix='_esthumb';


			$imagefile_ext='jpg';
			$imagefileweb=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			$imagefile=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile))
			{
				$imagetag='<img src="'.$imagefileweb.'" width="150" height="150" alt="'.$sitename.'" title="'.$sitename.'" />';
				$imagesrc=$imagefileweb;
				return true;
			}
			return false;
		}
		elseif($option=='_original')
		{

			$prefix='_original';
			$imagefile_ext='jpg';
			$imgname=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

			$imgMethods= new CustomTablesImageMethods;

			$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$imgname);

			if($imagefile_ext!='')
			{
				$imagefileweb=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
				$imagetag='<img src="'.$imagefileweb.'" alt="'.$sitename.'" title="'.$sitename.'" />';

				$imagesrc=$imagefileweb;//$prefix.'_'.$rowValue.'.'.$imagefile_ext;
				return true;
			}
			return false;
		}


		$prefix=$option;

		$imgMethods= new CustomTablesImageMethods;


		$imgname=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

		$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$imgname);
		//--- WARNING - ERROR -- REAL EXT NEEDED - IT COMES FROM OPTIONS
		$imagefile=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
		$imagesizes=$imgMethods->getCustomImageOptions($TypeParams);
        
		foreach($imagesizes as $img)
		{
			if($img[0]==$option)
			{
				if($imagefile!='')
				{
					$imagetag='<img src="'.$imagefile.'" '.($img[1]>0 ? 'width="'.$img[1].'"' : '').' '.($img[2]>0 ? 'height="'.$img[2].'"' : '').' alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrc=$imagefile;

					return true;
				}
			}
		}
		return false;
	}


    static public function get_image_type_value($id,&$es,&$savequery,$typeparams,$prefix,$esfieldname,$establename)
    {
        $value_found=false;

        $comesfieldname=$prefix.$esfieldname;
    				$value=0;
					$imagemethods=new CustomTablesImageMethods;
                    $mysqltablename='#__customtables_table_'.$establename;

					$ImageFolder=CustomTablesImageMethods::getImageFolder($typeparams);

                    $jinput=JFactory::getApplication()->input;
                    $fileid = $jinput->post->get($comesfieldname, '','STRING' );


					if($id==0)
					{
							$value=$imagemethods->UploadSingleImage(0, $fileid,$esfieldname,JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder,$typeparams,$establename);
					}
					else
					{
                        $to_delete = $jinput->post->get($comesfieldname.'_delete', '','CMD' );

						
                        $ExistingImage=$es->isRecordExist($id,'id', 'es_'.$esfieldname, $mysqltablename);


							if($to_delete=='true')
							{
								if($ExistingImage>0)
									$imagemethods->DeleteExistingSingleImage($ExistingImage,JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder,$typeparams,$establename,$esfieldname);

                                $value_found=true;
								$savequery[]='es_'.$esfieldname.'='.$value;
							}
							else
							{

								$value=$imagemethods->UploadSingleImage($ExistingImage,$fileid, $esfieldname,JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder,$typeparams,$establename);
							}

					}

                    if($value!=0)
                    {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$value;
                    }
        return $value_found;
    }

    public static function renderImageFieldBox($prefix,&$esfield,&$row,$realFieldName,$class,$optinal_parameter)
	{
        $document = JFactory::getDocument();

        $document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
        $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/uploader.js"></script>');

		$ImageFolder=CustomTablesImageMethods::getImageFolder($esfield['typeparams']);

        $imagefile='';
        $isShortcut=false;
		$imagesrc=CT_FieldTypeTag_image::getImageSRC($row,$realFieldName,$ImageFolder,$imagefile,$isShortcut);

    	$result='<div class="esUploadFileBox" style="vertical-align:top;">';


		if($imagefile!='')
			$result.=CT_FieldTypeTag_image::renderImageAndDeleteOption($prefix,$imagesrc,$esfield,$isShortcut);
    

        $result.=CT_FieldTypeTag_image::renderUploader($esfield);

   		$result.='</div>';
       	return $result;

	}

    protected static function renderImageAndDeleteOption($prefix,$imagesrc,&$esfield,$isShortcut)
    {
        $style='margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;';
        $result='
                <div style="" id="ct_uploadedfile_box_'.$esfield['fieldname'].'">';

		$result.='<img src="'.$imagesrc.'" width="150" /><br/>';

		if(!$esfield['isrequired'])
			$result.='<input type="checkbox" name="'.$prefix.$esfield['fieldname'].'_delete" id="'.$prefix.$esfield['fieldname'].'_delete" value="true">'
				.' Delete '.($isShortcut ? 'Shortcut' : 'Image');

		$result.='
        </div>';

        return $result;
    }

    protected static function renderUploaderLimitations()
    {
		$max_file_size=JoomlaBasicMisc::file_upload_max_size();
		
            $result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
				'.JoomlaBasicMisc::JTextExtended( "MIN SIZE" ).': 10px x 10px<br/>
				'.JoomlaBasicMisc::JTextExtended( "MAX SIZE" ).': 1000px x 1000px<br/>
				'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE" ).': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'<br/>
				'.JoomlaBasicMisc::JTextExtended( "FORMAT" ).': JPEG, GIF, PNG
				</div>';

            return $result;
    }

    protected static function renderUploader(&$esfield)
    {
        $fieldid=(int)$esfield['id'];
        $esfieldname=$esfield['fieldname'];

        $max_file_size=JoomlaBasicMisc::file_upload_max_size();

        $prefix='comes_';

        $fileid=JoomlaBasicMisc::generateRandomString();

        $jinput=JFactory::getApplication()->input;

		$Itemid=$jinput->getInt('Itemid',0);

        $style='margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;';

                $id='ct_ubloadfile_box_'.$esfield['fieldname'];
                $result='
                <div style="'.$style.'"'.($esfield['isrequired'] ? ' class="inputbox required"' : '').' id="'.$id.'">
                	<div id="ct_fileuploader_'.$esfieldname.'"></div>
                    <div id="ct_eventsmessage_'.$esfieldname.'"></div>
                	<script>
                        UploadFileCount=1;
                        AutoSubmitForm=false;
                        esUploaderFormID="eseditForm";
                        ct_eventsmessage_element="ct_eventsmessage";
                        tempFileName="'.$fileid.'";
                        fieldValueInputBox="'.$prefix.$esfieldname.'";
                    	var urlstr="/index.php?option=com_customtables&view=fileuploader&tmpl=component&'.$esfieldname.'_fileid='.$fileid.'&Itemid='.$Itemid.'&fieldname='.$esfieldname.'";
                    	ct_getUploader('.$fieldid.',urlstr,'.$max_file_size.',"jpg jpeg png gif svg","eseditForm",false,"ct_fileuploader_'.$esfieldname.'","ct_eventsmessage_'.$esfieldname.'","'.$fileid.'","'.$prefix.$esfieldname.'","ct_ubloadedfile_box_'.$esfieldname.'");

                    </script>
                    <input type="hidden" name="'.$prefix.$esfieldname.'" id="'.$prefix.$esfieldname.'" value=""'.($esfield['isrequired'] ? ' class="required"' : '').' />
			'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                </div>
                ';

        return $result;

    }


    public static function getImageSRC($row,$realFieldName,$ImageFolder,&$imagefile,&$isShortcut)
    {
        //-------------------------------------- IMAGE ----------------------------------
				$isShortcut=false;
				if(isset($row[$realFieldName]))
				{
					$img=$row[$realFieldName];
					if(strpos($img,'-')!==false)
					{
						$isShortcut=true;
						$img=str_replace('-','',$img);
					}

					$imagefile_=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$img;
					$imagesrc_=str_replace(DIRECTORY_SEPARATOR,'/',$ImageFolder).'/_esthumb_'.$img;
				}
				else
				{
					$imagefile_='';
					$imagesrc_='';
				}

				if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile_.'.jpg'))
				{
					$imagefile=$imagefile_.'.jpg';
					$imagesrc=$imagesrc_.'.jpg';

				}
				elseif(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile_.'.png'))
				{
					$imagefile=$imagefile_.'.png';
					$imagesrc=$imagesrc_.'.png';
				}
				else
				{
					$imagefile='';
					$imagesrc='';
				}

        return JURI::root(false).$imagesrc;
    }

    //Drupal has this implemented fairly elegantly:
    //https://stackoverflow.com/questions/1.6.1.1/php-get-actual-maximum-upload-size

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size


}
