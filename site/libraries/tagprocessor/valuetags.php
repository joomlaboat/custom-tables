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

$types_path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR;
require_once($types_path.'_type_ct.php');
require_once($types_path.'_type_file.php');
require_once($types_path.'_type_filebox.php');
require_once($types_path.'_type_gallery.php');
require_once($types_path.'_type_image.php');
require_once($types_path.'_type_log.php');
require_once($types_path.'_type_records.php');
require_once($types_path.'_type_sqljoin.php');

defined('_JEXEC') or die('Restricted access');

class tagProcessor_Value
{
    public static function TextFunctions($content,$parameters)
	{
        if(count($parameters)==0)
            return $content;
        
    				switch($parameters[0])
					{
						case "chars" :

							if(isset($parameters[1]))
								$count=(int)$parameters[1];
							else
								$count=-1;

							if(isset($parameters[2]) and $parameters[2]=='true')
								$cleanbraces=true;
							else
								$cleanbraces=false;

							if(isset($parameters[3]) and $parameters[3]=='true')
								$cleanquotes=true;
							else
								$cleanquotes=false;

							return JoomlaBasicMisc::chars_trimtext($content, $count, $cleanbraces, $cleanquotes);
							break;

						case "words" :

							if(isset($parameters[1]))
								$count=(int)$parameters[1];
							else
								$count=-1;

							if(isset($parameters[2]) and $parameters[2]=='true')
								$cleanbraces=true;
							else
								$cleanbraces=false;

							if(isset($parameters[3]) and $parameters[3]=='true')
								$cleanquotes=true;
							else
								$cleanquotes=false;

							return JoomlaBasicMisc::words_trimtext($content, $count, $cleanbraces, $cleanquotes);
							break;

						case "firstimage" :

							return JoomlaBasicMisc::getFirstImage($content);

							break;


						default:

							return $content;


						break;
					}

		return $content;

	}

    public static function getArticle($articleid,$field)
	{
    	// get database handle
		$db = JFactory::getDBO();
		$query='SELECT '.$field.' FROM #__content WHERE id='.(int)$articleid.' LIMIT 1';
		$db->setQuery($query);

		$rows=$db->loadAssocList();

		if(count($rows)!=1)
			return ""; //return nothing if article not found

		$row=$rows[0];
		return $row[$field];
	}

    public static function getFieldTypeByName($fieldname)
	{
		foreach($Model->esfields as $ESField)
		{
			if($ESField['fieldname']==$fieldname)
				return $ESField['type'];
		}
		return '';
	}
    
    public static function processEditValues(&$Model,&$htmlresult, &$row,&$isGalleryLoaded,&$getGalleryRows,&$isFileBoxLoaded,&$getFileBoxRows,$tag_chars='[]')
	{
		
		$items_to_replace=array();
		$pureValueOptions=array();
		$pureValueList=JoomlaBasicMisc::getListToReplace('_edit',$pureValueOptions,$htmlresult,$tag_chars);
        
        if(count($pureValueList)>0)
        {
                require_once(JPATH_SITE
                    .DIRECTORY_SEPARATOR.'components'
                    .DIRECTORY_SEPARATOR.'com_customtables'
                    .DIRECTORY_SEPARATOR.'libraries'
                    .DIRECTORY_SEPARATOR.'esinputbox.php');
                            
            	$esinputbox = new ESInputBox;
				$esinputbox->Model = $Model;
                
                $esinputbox->establename=$Model->establename;
                $esinputbox->estableid=$Model->estableid;
                $esinputbox->requiredlabel='COM_CUSTOMTABLES_REQUIREDLABEL';
                
               	$WebsiteRoot=JURI::root(true);
                if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
                    $WebsiteRoot.='/';
                    
                $document = JFactory::getDocument();
                $document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/ajax.js"></script>');
                
                require_once(JPATH_SITE
                    .DIRECTORY_SEPARATOR.'components'
                    .DIRECTORY_SEPARATOR.'com_customtables'
                    .DIRECTORY_SEPARATOR.'libraries'
                    .DIRECTORY_SEPARATOR.'tagprocessor'
                    .DIRECTORY_SEPARATOR.'itemtags.php');

                $edit_userGroup=(int)$Model->params->get( 'editusergroups' );
                $isEditable=tagProcessor_Item::checkAccess($Model,$edit_userGroup,$row);
        }
        else
        {
            $isEditable=false;
            $WebsiteRoot='';
        }
        
        
		$p=0;
		foreach($pureValueOptions as $pureValueOption)
		{
			$pureValueOptionArr=explode(':',$pureValueOption);
            
            $class_='';
            $style='';
            if(isset($pureValueOptionArr[1]))
                $class_=$pureValueOptionArr[1];
            else
                $style=' style="width:auto; !important;border:none !important;box-shadow:none;"';

            $i=0;
			foreach($Model->esfields as $ESField)
			{
                $replaceitecode=md5(JoomlaBasicMisc::generateRandomString().(isset($row['listing_id']) ? $row['listing_id'] : '').$ESField['fieldname']);
                
				if($pureValueOptionArr[0]==$ESField['fieldname'])
				{
                            //this is temporary replace string - part of the mechanism to avoid getting values of another fields
							$new_replaceitecode=$replaceitecode.str_pad($ESField['id'], 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);
                            
                            if($isEditable)
                            {
                                $postfix='';
                                $prefix='com_'.$row['listing_id'].'_';//.'_es_';//example: com_153_es_fieldname
                            
                                $value_option_list=array();
                                if(isset($pureValueOptionArr[1]))
                                    $value_option_list=JoomlaBasicMisc::csv_explode(',',$pureValueOptionArr[1],'"',false);    
                            
                                if($ESField['type']=='multilangstring')
                                {
                                    if(isset($value_option_list[4]))
                                    {
                                        //multilang field specific language
                                        $firstlanguage=true;
                                        foreach($Model->ct->Languages->LanguageList as $lang)
                                        {
                                            if($lang->sef==$value_option_list[4])
                                            {
                                                $postfix=$lang->sef;
                                                break;
                                            }
                                        }
                                        $new_replaceitecode.=$postfix;
                                    }
                                }
								
								$onchange='ct_UpdateSingleValue(\''.$WebsiteRoot.'\','.$Model->Itemid.',\''.$ESField['fieldname'].'\','.$row['listing_id'].',\''.$postfix.'\');';
								
                                $attributes='onchange="'.$onchange.'"'.$style;
                            
                                $vlu='<div class="" id="'.$prefix.$ESField['fieldname'].$postfix.'_div">'
                                .$esinputbox->renderFieldBox($Model,$prefix,$ESField,$row,$class_,$attributes,$value_option_list);
                                $vlu.='</div>';
                            }
                            else
							{
								$fieldtype='';
								$fieldname='';
								$rowValue='';
								tagProcessor_Value::doMultiValues($Model,$ESField,$row,$fieldtype,$rowValue,$fieldname);
                                $vlu=$rowValue;
							}

							$items_to_replace[]=array($new_replaceitecode,$vlu);
							$htmlresult=str_replace($pureValueList[$p],$new_replaceitecode,$htmlresult);
                }
                $i++;
            }
            $p++;
        }
        
        return $items_to_replace;
    }

    public static function processPureValues(&$Model,&$htmlresult, &$row,&$isGalleryLoaded,&$getGalleryRows,&$isFileBoxLoaded,&$getFileBoxRows,$tag_chars='[]')
	{
		$id = (isset($row['listing_id']) ? $row['listing_id'] : 0);
		
		$items_to_replace=array();

		$pureValueOptions=array();
		$pureValueList=JoomlaBasicMisc::getListToReplace('_value',$pureValueOptions,$htmlresult,$tag_chars);
		$p=0;
		foreach($pureValueOptions as $pureValueOption)
		{
			$pureValueOptionArr=explode(':',$pureValueOption);
			if(count($pureValueOptionArr)==1)
				$pureValueOptionArr[1]='';

            $i=0;
			foreach($Model->esfields as $ESField)
			{
				$TypeParams = $ESField['typeparams'];
                $replaceitecode=md5(JoomlaBasicMisc::generateRandomString().(isset($row['listing_id']) ? $row['listing_id'] : '').$ESField['fieldname']);
                
				if($pureValueOptionArr[0]==$ESField['fieldname'])
				{
					$fieldtype='';
					$fieldname='';
					$rowValue='';
					tagProcessor_Value::doMultiValues($Model,$ESField,$row,$fieldtype,$rowValue,$fieldname);

					if($fieldtype=='imagegallery')
					{
						if(count($isGalleryLoaded)>0)
						{
							if(!isset($isGalleryLoaded[$fieldname]) or $isGalleryLoaded[$fieldname]==false)
							{
								//load if not loaded
								$isGalleryLoaded[$fieldname]=true;
								$getGalleryRows[$fieldname]=CT_FieldTypeTag_imagegallery::getGalleryRows($Model->establename,$fieldname,$row['listing_id']);
							}
						}
						else
						{
							//load if not loaded
							$isGalleryLoaded[$fieldname]=true;
							$getGalleryRows[$fieldname]=CT_FieldTypeTag_imagegallery::getGalleryRows($Model->establename,$fieldname,$row['listing_id']);
						}

						if(count($getGalleryRows[$fieldname])==0)
								$isEmpty=true;
							else
								$isEmpty=false;
					}
					elseif($fieldtype=='filebox')
					{
						if(count($isFileBoxLoaded)>0)
						{
							if($isFileBoxLoaded[$fieldname]==false)
							{
								//load if not loaded
								$isFileBoxLoaded[$fieldname]=true;
								$getFileBoxRows[$fieldname]=CT_FieldTypeTag_filebox::getFileBoxRows($Model->establename,$fieldname,$row['listing_id']);
							}

						}
						else
						{
							//load if not loaded
							$isFileBoxLoaded[$fieldname]=true;
							$getFileBoxRows[$fieldname]=CT_FieldTypeTag_filebox::getFileBoxRows($Model->establename,$fieldname,$row['listing_id']);
						}

						if(count($getFileBoxRows[$fieldname])==0)
								$isEmpty=true;
							else
								$isEmpty=false;
					}
                    else
						$isEmpty=tagProcessor_Value::isEmpty($rowValue,$fieldtype,$ESField['typeparams']);

					$ifname='[_if:_value:'.$ESField['fieldname'].']';
					$endifname='[_endif:_value:'.$ESField['fieldname'].']';

					if($isEmpty)
					{
							do{
								$textlength=strlen($htmlresult);

								$startif_=strpos($htmlresult,$ifname);
								if($startif_===false)
									break;
								if(!($startif_===false))
								{

									$endif_=strpos($htmlresult,$endifname);
									if(!($endif_===false))
									{
										$p=$endif_+strlen($endifname);
										$htmlresult=substr($htmlresult,0,$startif_).substr($htmlresult,$p);
									}
								}

							}while(1==1);//$textlengthnew!=$textlength);

							$htmlresult=str_replace($pureValueList[$p],'',$htmlresult);
					}
					else
					{
							$htmlresult=str_replace($ifname,'',$htmlresult);
							$htmlresult=str_replace($endifname,'',$htmlresult);

							$vlu='';
							
							if($fieldtype=='image')
							{
                                $imagesrc='';
                                $imagetag='';

                                $new_array=array();
                                
                                if(count($pureValueOptionArr)>1)
                                {
                                    for($i=1;$i<count($pureValueOptionArr);$i++)
                                        $new_array[]=$pureValueOptionArr[$i];
                                }
                                
                                CT_FieldTypeTag_image::getImageSRClayoutview($new_array,$rowValue,$ESField['typeparams'],$imagesrc,$imagetag);

								$vlu=$imagesrc;
							}
							elseif($fieldtype=='imagegallery')
							{
								$imagetaglist='';
								$imagesrclist='';

                                $new_array=array();
                                
                                if(count($pureValueOptionArr)>1)
                                {
                                    for($i=1;$i<count($pureValueOptionArr);$i++)
                                        $new_array[]=$pureValueOptionArr[$i];
                                }
                                
                                if(count($new_array)>0)
                                {
                                    $option=$new_array[0];
                                    CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows[$fieldname],
                                    			$option,$row['listing_id'],$fieldname,$ESField['typeparams'],$imagesrclist,$imagetaglist,$Model->estableid);
                                }

								$vlu=$imagesrclist;
							}
							elseif($fieldtype=='filebox')
							{								
								$vlu = CT_FieldTypeTag_filebox::process($Model,$getFileBoxRows[$fieldname], $id,
									$fieldname,$TypeParams,['','link','32','_blank',';']);
							}
							elseif($fieldtype=='records')
							{
								$a=explode(",",$rowValue);
								$b=array();
								foreach($a as $c)
								{
									if($c!="")
									    $b[]=$c;
								}
								$vlu=implode(',',$b);
							}
                            elseif($fieldtype=='file')
                            {
                                if(isset($pureValueOptionArr[1]) and $pureValueOptionArr[1]!='')
                                {
                                    $processor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
                                    require_once($processor_file);
                                    
                                    $new_array=array();
                                
                                    if(count($pureValueOptionArr)>1)
                                    {
                                        for($i=1;$i<count($pureValueOptionArr);$i++)
                                            $new_array[]=$pureValueOptionArr[$i];
                                    }
                                    
                                    $vlu=CT_FieldTypeTag_file::process($rowValue,$ESField['typeparams'],$new_array,$row['listing_id'],$ESField['id'],$Model->estableid,true);
                                }
                                else
                                    $vlu=$rowValue;
                            }
							else
								$vlu=$rowValue;

							//this is temporary replace string - part of the mechanism to avoid getting values of another fields
							$new_replaceitecode=$replaceitecode.str_pad($ESField['id'], 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);

							$items_to_replace[]=array($new_replaceitecode,$vlu);
							$htmlresult=str_replace($pureValueList[$p],$new_replaceitecode,$htmlresult);
					}
				}
                $i++;
			}//foreach($Model->esfields as $ESField)
			$p++;
		}//foreach($pureValueOptions as $pureValueOption)

		return $items_to_replace;

	}//function

	public static function isEmpty(&$rowValue,$fieldtype,$fieldtypeparams='')
	{
		if($fieldtype=='int' or $fieldtype=='user' or $fieldtype=='userid' or $fieldtype=='usergroup')
		{
			$v=(int)$rowValue;
			if($v==0)
				return true;
			else
				return false;
		}
		elseif($fieldtype=='float')
		{
			$v=(float)$rowValue;
			if($v==0)
				return true;
			else
				return false;
		}
		elseif($fieldtype=='checkbox')
		{
			$v=(int)$rowValue;
			if($v==0)
				return true;
			else
				return false;
		}
		elseif($fieldtype=='records' or $fieldtype=='usergroups')
		{
			if($rowValue=='' or $rowValue==',' or $rowValue==',,')
				return true;
			else
				return false;
		}
		elseif($fieldtype=='date')
		{
			if($rowValue=='' or $rowValue=='0000-00-00')
				return true;
			else
				return false;
		}
        elseif($fieldtype=='time')
		{
			if($rowValue=='' or $rowValue=='0')
				return true;
			else
				return false;
		}
		elseif($fieldtype=='image')
		{
			if($rowValue=='' or $rowValue=='-1' or $rowValue=='0' )
				return true;
			else
			{
				//check if file exists
				$ImageFolder_=CustomTablesImageMethods::getImageFolder($fieldtypeparams);

				$ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder_);

				$prefix='_esthumb';

				$img=$rowValue;
				if(strpos($img,'-')!==false)
				{
					//$isShortcut=true;
					$img=str_replace('-','',$img);
				}

				$imagefile_ext='jpg';
				$imagefile=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$img.'.'.$imagefile_ext;

				if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile))
					return false;
				else
					return true;
			}
		}
		elseif($fieldtype=='customtables')
		{
			if($rowValue=='' or $rowValue==',.')
			{
				$rowValue=='';
				return true;
			}
			else
				return false;
		}
		elseif($fieldtype=='sqljoin')
		{
			if($rowValue==0)
				return true;
			else
				return false;
		}
		else
		{
			if($rowValue=='')
				return true;
			else
				return false;
		}

	}


	public static function doMultiValues(&$Model,&$ESField, &$row,&$fieldtype,&$rowValue,&$fieldname,$specific_lang='')
	{
		$fieldtype=$ESField['type'];
		if(strpos($fieldtype,'multilang')===false)
		{

			if($fieldtype=='dummy')
			{
				$rowValue='';
				$fieldname=$ESField['fieldname'];
			}
            elseif($fieldtype=='phponview')
			{
                $fieldname=$ESField['fieldname'];
                $rowValue=$row[$ESField['realfieldname']];
                
                if(isset($row['_processing_field_values']))
                    return true;
                
                if(isset($row['listing_id']))
                {
                    $params=JoomlaBasicMisc::csv_explode(',',$ESField['typeparams'],'"',false);
				
                    if(isset($params[1]) and $params[1]=='dynamic')
                    {
                    	$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
                        if(file_exists($phptagprocessor))
                        {
                           	require_once($phptagprocessor);
                           	$rowValue=tagProcessor_PHP::processTempValue($Model,$row,$fieldname,$params,true);
                        }
                    }
                }
			}
			else
			{
				$rowValue=$row[$ESField['realfieldname']];
				$fieldname=$ESField['fieldname'];
			}
		}
		else
		{
			if($fieldtype=='multilangstring')
				$fieldtype='string';
			elseif($fieldtype=='multilangtext')
				$fieldtype='text';
                
            $postfix='';
            if($specific_lang!='')
            {
                $i=0;
                foreach($Model->ct->Languages->LanguageList as $l)
                {
                    if($l->sef==$specific_lang)
                    {
                        if($i==0)
                            $postfix='';//first language in the list
                        else
                            $postfix='_'.$specific_lang;
                            
                        break;
                    }
                    $i++;
                }

            }
            else
                $postfix=$Model->ct->Languages->Postfix; //front-end default language
                
    		$fieldname=$ESField['realfieldname'].$postfix;
			$rowValue=$row[$fieldname];
		}
	}

    public static function processValues(&$Model,&$row,&$htmlresult,$tag_chars='[]')
	{
		$fields_used=[];//Fields found in the layout.
		
		$items_to_replace=array();
		$isGalleryLoaded=array();
		$getGalleryRows=array();
		$isFileBoxLoaded=array();
		$getFileBoxRows=array();

		if(isset($row) and count($row)>0 and $row['listing_id'] != 0)
		{
			foreach($Model->esfields as $ESField)
			{
                $replaceitecode=md5(JoomlaBasicMisc::generateRandomString().(isset($row['listing_id']) ? $row['listing_id'] : '').$ESField['fieldname']);
                
				$temp_items_to_replace=tagProcessor_Value::processPureValues($Model,$htmlresult,$row,$isGalleryLoaded,$getGalleryRows,$isFileBoxLoaded,$getFileBoxRows,$tag_chars);
				if(count($temp_items_to_replace)!=0)
					$items_to_replace=array_merge($items_to_replace,$temp_items_to_replace);
                    
                $temp_items_to_replace=tagProcessor_Value::processEditValues($Model,$htmlresult,$row,$isGalleryLoaded,$getGalleryRows,$isFileBoxLoaded,$getFileBoxRows,$tag_chars);
				if(count($temp_items_to_replace)!=0)
					$items_to_replace=array_merge($items_to_replace,$temp_items_to_replace);

				$ValueOptions=array();
				$ValueList=JoomlaBasicMisc::getListToReplace($ESField['fieldname'],$ValueOptions,$htmlresult,$tag_chars);

					$fieldtype='';
					$fieldname='';
					$rowValue='';
					tagProcessor_Value::doMultiValues($Model,$ESField,$row,$fieldtype,$rowValue,$fieldname,'');

					if($fieldtype=='imagegallery')
					{
						if(!isset($isGalleryLoaded[$fieldname]) or $isGalleryLoaded[$fieldname]==false)
						{
							$isGalleryLoaded[$fieldname]=true;
							$r=CT_FieldTypeTag_imagegallery::getGalleryRows($Model->establename,$fieldname,$row['listing_id']);
							$getGalleryRows[$fieldname]=$r;
						}

						if(isset($isGalleryLoaded[$fieldname]) and count($getGalleryRows[$fieldname])==0)
							$isEmpty=true;
						else
							$isEmpty=false;

					}
					elseif($fieldtype=='filebox')
					{

						if(count($isFileBoxLoaded)>0)
						{
							if($isFileBoxLoaded[$fieldname]==false)
							{
								$isFileBoxLoaded[$fieldname]=true;
								$getFileBoxRows[$fieldname]=CT_FieldTypeTag_filebox::getFileBoxRows($Model->establename,$fieldname,$row['listing_id']);
							}
						}
						else
						{
							$isFileBoxLoaded[$fieldname]=true;
							$getFileBoxRows[$fieldname]=CT_FieldTypeTag_filebox::getFileBoxRows($Model->establename,$fieldname,$row['listing_id']);
						}


						if(isset($isFileBoxLoaded[$fieldname]) and count($getFileBoxRows[$fieldname])==0)
							$isEmpty=true;
						else
							$isEmpty=false;

					}
					else
					{
						//isEmpty
						$isEmpty=tagProcessor_Value::isEmpty($rowValue,$fieldtype,$ESField['typeparams']);
					}

					// IF
					tagProcessor_If::IFStatment('[_if:'.$ESField['fieldname'].']','[_endif:'.$ESField['fieldname'].']',$htmlresult,$isEmpty);

					// IF NOT
					tagProcessor_If::IFStatment('[_ifnot:'.$ESField['fieldname'].']','[_endifnot:'.$ESField['fieldname'].']',$htmlresult,!$isEmpty);

					if($isEmpty)
					{
						foreach($ValueList as $ValueListItem)
							$htmlresult=str_replace($ValueListItem,'',$htmlresult);
					}
					else
					{
						$i=0;
						foreach($ValueOptions as $ValueOption)
						{
                            $value_option_list=JoomlaBasicMisc::csv_explode(',',$ValueOption,'"',false);
                            
                            if(count($value_option_list)>=5)
                                tagProcessor_Value::doMultiValues($Model,$ESField,$row,$fieldtype,$rowValue,$fieldname,$value_option_list[4]);

							$vlu=tagProcessor_Value::getValueByType($Model,$rowValue,$fieldname,$fieldtype,$ESField['typeparams'],$value_option_list,$getGalleryRows[$fieldname],$getFileBoxRows[$fieldname],$row['listing_id'],$row,$ESField['id']);

							//this is temporary replace string - part of the mechanism to avoid getting values of another fields
							$new_replaceitecode=$replaceitecode.str_pad($ESField['id'], 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);

							$items_to_replace[]=array($new_replaceitecode,$vlu);
							$htmlresult=str_replace($ValueList[$i],$new_replaceitecode,$htmlresult);

							$i++;
						}
					}
			//process field names

			}//foreach($Model->esfields as $ESField)
		}//isset

		//replace temprary items with values
		foreach($items_to_replace as $item)
			$htmlresult=str_replace($item[0],$item[1],$htmlresult);

	}

    static public function getValueByType(&$Model,$rowValue,$FieldName, $FieldType,$TypeParams,$option_list,&$getGalleryRows,&$getFileBoxRows,$id,&$row=array(),$fieldid)
	{
		switch($FieldType)
		{
				case 'viewcount':
						if((int)$rowValue==0)
							return '';

						return $rowValue;
						break;

				case 'int':
						if((int)$rowValue==0)
							return '';

						if($option_list[0]!='')//thousand separator
							return number_format ( (int) $rowValue , 0 , "." , $option_list[0]);
						else
						{
							if($TypeParams!='')
								return number_format ( (int) $rowValue , 0 , "." , $option_list[0]);
							else
								return $rowValue;
						}
						break;

				case 'float':
						if((float)$rowValue==0)
							return '0';

						$decimals=(int)$option_list[0];
						$decimals_sep='.';
						$thousand_sep=',';

                        if($decimals==0 and $TypeParams!='')
						{
							$pair=JoomlaBasicMisc::csv_explode(',',$TypeParams,'"',false);
                            if((int)$pair[0]!=0)
                                $decimals=$pair[0];
						}
                        
						if(count($option_list)>0 and $option_list[0]!="")
						{
                            if(isset($option_list[1]) and $option_list[1]!='')
								$decimals_sep=$option_list[1];

							if(isset($option_list[2]) and $option_list[2]!='')
								$thousand_sep=$option_list[2];
						}
						
                        if($decimals==0 and count($option_list)==1)
							return (float)$rowValue;

						return number_format ( (float)$rowValue, $decimals,$decimals_sep,$thousand_sep);
						break;

				case 'phponadd':
						return $rowValue;
						break;

				case 'phponchange':
						return $rowValue;
						break;
                    
                case 'phponview':
						return $rowValue;
						break;

				case 'googlemapcoordinates':
						return $rowValue;
						break;

				case 'string':
						return tagProcessor_Value::TextFunctions($rowValue,$option_list);
						break;

                case 'color':

                    $value=$rowValue;
                    if($value=='')
						$value='000000';

                    if($option_list[0]=="rgba")
                    {
                        $colors=array();
                        if(strlen($value)>=6)
						{
							$colors[]=hexdec(substr($value, 0,2));
							$colors[]=hexdec(substr($value, 2,2));
							$colors[]=hexdec(substr($value, 4,2));
						}
								
						if(strlen($value)==8)
						{
							$a=hexdec(substr($value, 6,2));
							$colors[]=round($a/255,2);
						}                        
                        
                        if(strlen($value)==8)
                            return 'rgba('.implode(',',$colors).')';
                        else
                            return 'rgb('.implode(',',$colors).')';
                    }
                    else
    					return "#".$value;
                        
						break;

                case 'alias':
						return $rowValue;
						break;

				case 'radio':
						return $rowValue;
						break;

				case 'text':
						return tagProcessor_Value::TextFunctions($rowValue,$option_list);
						break;

				case 'file':
					return CT_FieldTypeTag_file::process($rowValue,$TypeParams,$option_list,$row['listing_id'],$fieldid,$Model->estableid);
					break;

				case 'image':
					$imagesrc='';
					$imagetag='';

					CT_FieldTypeTag_image::getImageSRClayoutview($option_list,$rowValue,$TypeParams,$imagesrc,$imagetag);

					return $imagetag;
					break;

				case 'article':

					if(isset($option_list[0]) and $option_list[0]!='')
						$article_field=$option_list[0];
                    else
                        $article_field='title';
                        
					$article=tagProcessor_Value::getArticle((int)$rowValue,$article_field);

					if(isset($option_list[1]))
                    {
                        $opts=str_replace(':',',',$option_list[1]);
						$article=tagProcessor_Value::TextFunctions($article,explode(',',$opts));
                    }

					return $article;

						break;

				case 'multilangarticle':
                    
					if(isset($option_list[0]) and $option_list[0]!='')
						$article_field=$option_list[0];
                    else
                        $article_field='title';

					$article=tagProcessor_Value::getArticle((int)$rowValue,$article_field);

					if(isset($option_list[1]))
                    {
                        $opts=str_replace(':',',',$option_list[1]);
						$article=tagProcessor_Value::TextFunctions($article,explode(',',$opts));
                    }

						break;


				case 'imagegallery':

					if($option_list[0]=='_count')
						return count($getGalleryRows);

					$imagesrclist='';
					$imagetaglist='';


					CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows,$option_list[0],$id,$FieldName,$TypeParams,$imagesrclist,$imagetaglist,$Model->estableid);
					return $imagetaglist;

						break;

				case 'filebox':

					if($option_list[0]=='_count')
						return count($getFileBoxRows);

					return CT_FieldTypeTag_filebox::process($Model,$getFileBoxRows, $id,$FieldName,$TypeParams,$option_list,$fieldid,'');

    				break;

				case 'customtables':

					if(count($option_list)>1 and $option_list[0]!="")
					{
						if($option_list[0]=='group')
						{
							$typeparamsarray=explode(',',$TypeParams);
							$rootparent=$typeparamsarray[0];

							$orientation=0;// horizontal
							if(isset($option_list[1]) and $option_list[1]=='vertical')
								$orientation=1;// vertical

							$grouparray=CT_FieldTypeTag_ct::groupCustomTablesParents($Model,$rowValue,$rootparent);


							$vlu='';

							//Build structure
							$vlu.='<table border="0"><tbody>';

							if($orientation==0)
								$vlu.='<tr>';

							foreach($grouparray as $fgroup)
							{
								if($orientation==1)
									$vlu.='<tr>';

								$vlu.='<td valign="top" align="left"><h3>'.$fgroup[0].'</h3><ul>';

								for($i=1; $i<count($fgroup);$i++)
								    $vlu.='<li>'.$fgroup[$i].'</li>';

								$vlu.='<ul></td><td width="20"></td>';

								if($orientation==1)
									$vlu.='</tr>';
							}

							if($orientation==0)
								$vlu.='</tr>';

							$vlu.='</tbody></table>';

							return $vlu;
						}


						if($option_list[0]=='list')
						{
							if($rowValue!='')
							{
								$vlus=explode(',',$rowValue);
								$vlus = array_filter($vlus);

								sort ($vlus);

								$temp_index=0;
								$vlu=Tree::BuildULHtmlList($vlus,$temp_index,$Model->ct->Languages->Postfix);

								return $vlu;
							}
							else
								return '';

						}

					}
					else
					{
						if($rowValue!='')
							return implode(',',Tree::getMultyValueTitles($rowValue,$Model->ct->Languages->Postfix,1, ' - ',$TypeParams));
						else
							return '';

					}
						break;

				case 'records':

						return CT_FieldTypeTag_records::resolveRecordType($Model,$rowValue, $TypeParams, $option_list);
						break;

				case 'sqljoin':
						return CT_FieldTypeTag_sqljoin::resolveSQLJoinType($Model,$rowValue, $TypeParams, $option_list);
						break;

				case 'userid':

					return JHTML::_('ESUserView.render',$rowValue,$option_list[0]);

						break;

				case 'user':

					return JHTML::_('ESUserView.render',$rowValue,$option_list[0]);

				case 'usergroup':

					return tagProcessor_Value::showUserGroup($rowValue);

						break;

				case 'usergroups':

					return tagProcessor_Value::showUserGroups($rowValue);

						break;

				case 'filelink':

					$processor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
					require_once($processor_file);
					
					return CT_FieldTypeTag_file::process($rowValue,','.$TypeParams,$option_list,$row['listing_id'],$fieldid,$Model->estableid); // "," is to be compatible with file field type params. Becuse first parameter is max file size there
					break;

						//return $TypeParams.'/'.$rowValue;
						//break;

				case 'server':
						return $rowValue;
						break;

				case 'md5':
						return $rowValue;
						break;

				case 'log':
						return CT_FieldTypeTag_log::getLogVersionLinks($Model,$rowValue,$row);
						break;

				case 'multilangstring':
						return tagProcessor_Value::TextFunctions($rowValue,$option_list);
						break;

				case 'multilangtext':
						return tagProcessor_Value::TextFunctions($rowValue,$option_list);
						break;

				case 'email':
						return $rowValue;
						break;

				case 'url':
						return $rowValue;
						break;

				case 'checkbox':
						if($rowValue)
							return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES');
						else
							return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');

						break;

				case 'lastviewtime':
						if($rowValue=='' or $rowValue=='0000-00-00' or $rowValue=='0000-00-00 00:00:00')
							return '';

						$phpdate =strtotime( $rowValue);

						if($option_list[0]!='')
						{
							if($option_list[0]=='timestamp')
								return  $phpdate;

							return date($option_list[0], $phpdate);
						}
						else
							return JHTML::date($phpdate );

						break;

				case 'date':
						if($rowValue=='' or $rowValue=='0000-00-00' or $rowValue=='0000-00-00 00:00:00')
							return '';

						$phpdate =strtotime( $rowValue);

						if($option_list[0]!='')
						{
							if($option_list[0]=='timestamp')
								return  $phpdate;

							return date($option_list[0], $phpdate);
						}
						else
							return JHTML::date($phpdate );

						break;
                    
                case 'time':
                    
                    require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'cttime.php');
                    
					$typeparams=explode(',',$TypeParams);
                    $seconds=JHTMLCTTime::ticks2Seconds($rowValue,$typeparams);
                    
                    return JHTMLCTTime::seconds2FormatedTime($seconds,$option_list[0]);

						break;

				case 'creationtime':
						$phpdate = strtotime($rowValue);
						if($rowValue=="0000-00-00 00:00:00")
							return '';
						
						if($option_list[0]!='')
						{
							if($option_list[0]=='timestamp')
								return  $phpdate;

							return date($option_list[0],$phpdate );
						}
						else
						{
							return JHTML::date($phpdate );
						}

						break;

				case 'changetime':
						$phpdate = strtotime( $rowValue);
						if($option_list[0]!='')
						{
							if($option_list[0]=='timestamp')
								return  $phpdate;

							return date($option_list[0],$phpdate );
						}
						else
							return JHTML::date($phpdate );

						break;
				case 'id':
						return $rowValue;
						break;

		}
		return '';
	}

    public static function showUserGroup($id)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT title FROM #__usergroups WHERE id='.(int)$id.' LIMIT 1';

		$db->setQuery($query);

		$options=$db->loadAssocList();
		if(count($options)!=0)
			return $options[0]['title'];


		return '';
	}

	public static function showUserGroups($valuearray_str)
	{

		$db = JFactory::getDBO();

		$where=array();
		$valuearray=explode(',',$valuearray_str);
		foreach($valuearray as $value)
		{
					if($value!='')
					{
						$where[]='id='.(int)$value;
					}
		}

		$query = 'SELECT title FROM #__usergroups WHERE '.implode(' OR ',$where).' ORDER BY title';

		$db->setQuery($query);

		$options=$db->loadAssocList();

		if(count($options)==0)
			return '';

		$groups=array();
		foreach($options as $opt)
			$groups[]=$opt['title'];

		return implode(',',$groups);
	}

    public static function ApplyQueryGetValue($str,$sj_tablename)
	{
		$list=explode('$get_',$str);
		if(count($list)==2)
		{
			$q=$list[1];

			$v=JFactory::getApplication()->input->getString($q);
			$v=str_replace('"','',$v);
			$v=str_replace("'",'',$v);

			if(strpos($v,','))
			{
				$f='#__customtables_table_'.$sj_tablename.'.es_'.str_replace('$get_'.$q,'',$str);
				$values=explode(',',$v);


				$vls=array();
				foreach($values as $v1)
				{
					$vls[]=$f.'"'.$v1.'"';
				}

				$v='('.implode(' or ',$vls).')';
				return $v;
			}

			return '#__customtables_table_'.$sj_tablename.'.es_'.str_replace('$get_'.$q,'"'.$v.'"',$str);
		}
        else
        {
            if(strpos($str,'_id')!==false)
                return '#__customtables_table_'.$sj_tablename.'.'.str_replace('_id','listing_id',$str);
            elseif(strpos($str,'_published')!==false)
                return '#__customtables_table_'.$sj_tablename.'.'.str_replace('_published','published',$str);
        }

		$str=str_replace('=null',' IS NULL',$str);

		return '#__customtables_table_'.$sj_tablename.'.es_'.$str;
	}
}
