<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'types.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'imagemethods.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_gallery.php');

$document = JFactory::getDocument();
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/base64.js"></script>');

class ESInputBox
{
	var $es;
	var $LanguageList;
	var $langpostfix;
	var $establename;
	var $estableid;
	var $width=0;
	var $requiredlabel='';

	function renderFieldBox(&$Model,$prefix,&$esfield,&$row,$class_,$attributes='',$option_list)
	{
		$place_holder=$esfield['fieldtitle'.$this->langpostfix];
		$class=$class_.' inputbox'.($esfield['isrequired'] ? ' required' : '');

		$realFieldName=$esfield['realfieldname'];

		$result='';

		$value='';
		
		if($row==null)
			$row=array();

		if(count($row)==0)
		{
			$value=JFactory::getApplication()->input->getString($realFieldName);
			if($value=='')
				$value=$this->getWhereParameter($realFieldName);

			if($value=='')
			{
				$value=$esfield['defaultvalue'];

				//Process default value, not processing PHP tag
				if($value!='')
				{
					tagProcessor_General::process($Model,$value,$row,'',1);
					tagProcessor_Item::process(false,$Model,$row,$value,'',array(),'',0);
					tagProcessor_If::process($Model,$value,$row,'',0);
					tagProcessor_Page::process($Model,$value);
					tagProcessor_Value::processValues($Model,$row,$value,'[]');

					if($value!='')
					{
						LayoutProcessor::applyContentPlugins($htmlresult);

						if($esfield['type']=='alias')
						{
							$listing_id=isset($row['listing_id']) ? $row['listing_id'] : 0;
							$value=CTValue::prepare_alias_type_value($listing_id,$value,$Model->realtablename,$esfield['realfieldname'],$Model->tablerow['realidfieldname']);
						}
			        }

				}
				
			}
			
		}
		else
		{
			if($esfield['type']!='multilangstring' and $esfield['type']!='multilangtext' and $esfield['type']!='multilangarticle')
				$value=$row[$realFieldName];
		}

		$isAdmin=$this->isAdmin();

		$allowEditAuthior=false;
		if($isAdmin and $esfield['type']=='userid' and count($row)!=0)
			$allowEditAuthior=true;
			
			$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);

			switch($esfield['type'])
			{
						case 'radio':

							$result.='<table style="border:none;"><tr>';
							$i=0;
							foreach($typeparams as $radiovalue)
							{
								$v=trim($radiovalue);
								$result.='<td valign="middle"><input type="radio"
									name="'.$prefix.$esfield['fieldname'].'"
									id="'.$prefix.$esfield['fieldname'].'_'.$i.'"
									value="'.$v.'" '
								.($value==$v ? ' checked="checked" ' : '')
								.' /></td>';
								$result.='<td valign="middle"><label for="'.$prefix.$esfield['fieldname'].'_'.$i.'">'.$v.'</label></td>';
								$i++;
							}
							$result.='</tr></table>';

							break;
						case 'int':
							
							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','ALNUM');
							
							if($value=='')
								$value=(int)$esfield['defaultvalue'];
							else
								$value=(int)$value;

							
							$result.='<input '
								.'type="text" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'label="'.$esfield['fieldname'].'" '
								.'class="'.$class.'"'
								.' '.$attributes
								.' value="'.$value.'" />';

							break;



						case 'float':
							
							if(count($row)==0)
								$value=JFactory::getApplication()->input->getCmd('es_'.$esfield['fieldname'],'');
							
							if($value=='')
								$value=(float)$esfield['defaultvalue'];
							else
								$value=(float)$value;
							
							$result.='<input '
								.'type="text" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'class="'.$class.'" '
								.' '.$attributes.' ';

							$decimals=intval($typeparams[0]);
							if($decimals<0)
								$decimals=0;

							if(isset($values[2]) and $values[2]=='smart')
								$result.='onkeypress="ESsmart_float(this,event,'.$decimals.')" ';

							$result.='value="'.$value.'" />';

							break;

						case 'phponchange':

							$result.=$value;
							break;

						case 'phponadd':

							$result.=$value;
							break;

						case 'phponview':

							$result.=$value;
							break;

						case 'string':
							$result.='<input type="text" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'label="'.$esfield['fieldname'].'" '
								.'class="'.$class.'" '
								.'value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"').' '.$attributes.' />';

							break;

						case 'alias':
							$result.='<input type="text" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'label="'.$esfield['fieldname'].'" '
								.'class="'.$class.'" '
								.' '.$attributes
								.'value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"').' '.$attributes.' />';

							break;

						case 'phponadd':
							$result.='<input type="hidden" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'value="'.$value.'" />';

							break;

						case 'phponchange':
							$result.='<input type="hidden" '
								.'name="'.$prefix.$esfield['fieldname'].'" '
								.'id="'.$prefix.$esfield['fieldname'].'" '
								.'value="'.$value.'" />';

							break;

						case 'multilangstring':

							$result.=$this->getMultilangString($Model,$esfield,$prefix,$row,$attributes,$class,$option_list);
							
							break;

						case 'text':

								$fname=$prefix.$esfield['fieldname'];

								if(in_array('rich',$typeparams))
								{
									$w=500;
									$h=200;
									$c=0;
									$l=0;

										$editor = JFactory::getEditor();

										$result.='<div>'.$editor->display($fname,$value, $w, $h, $c, $l).'</div>';
								}
								else
								{
										$result.='<textarea name="'.$fname.'" '
											.'id="'.$fname.'" '
											.'class="'.$class.'"'
											.' '.$attributes
											.'>'.$value.'</textarea>';
								}


								if(in_array('spellcheck',$typeparams))
								{
									$document = JFactory::getDocument();
									$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/thirdparty/jsc/include.js"></script>');
									$document->addCustomTag('<script type="text/javascript">$Spelling.SpellCheckAsYouType("'.$fname.'");</script>');
									$document->addCustomTag('<script type="text/javascript">$Spelling.DefaultDictionary = "English";</script>');
								}
							break;

						case 'multilangtext':

							require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'multilangtext.php');
							$result.=render_multilangtext($row,$this->LanguageList,$esfield,'',$this->width,$prefix,$class);
							break;

						case 'checkbox':
							
								$format="";
								if(isset($option_list[2]) and $option_list[2]=='yesno')
									$format="yesno";
								
								if($format=="yesno")
								{
									$result.='<fieldset id="'.$prefix.$esfield['fieldname'].'" class="'.$class.' btn-group radio btn-group-yesno" '
									.'style="border:none !important;background:none !important;">';
								
									$id=$prefix.$esfield['fieldname'];
								
									$result.='<div style="position: absolute;visibility:hidden !important; display:none !important;"><input type="radio"'
										.' id="'.$id.'0"'
										.' name="'.$id.'" '
										.' value="1"'
										.' '.$attributes
										.((int)$value==1 ? ' checked="checked" ' : '')
										.' ></div>'
										.'<label class="btn'.((int)$value==1 ? ' active btn-success' : '').'" for="'.$id.'0" id="'.$id.'0_label" >'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES').'</label>';
										
									$result.='<div style="position: absolute;visibility:hidden !important; display:none !important;"><input type="radio"'
										.' id="'.$id.'1"'
										.' name="'.$id.'" '
										.' '.$attributes
										.' value="0"'
										.((int)$value==0 ? ' checked="checked" ' : '')
										.' ></div>'
										.'<label class="btn'.((int)$value==0 ? ' active btn-danger' : '').'" for="'.$id.'1" id="'.$id.'1_label">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO').'</label>';
										 
									$result.='</fieldset>';
								}
								else
								{
									$onchange=$prefix.$esfield['fieldname'].'_off.value=(this.checked === true ? 1 : 0);';// this is to save unchecked value as well.
									
									if(strpos($attributes,'onchange="')!==false)
										$attributes=str_replace('onchange="','onchange="'.$onchange,$attributes);// onchange event already exists add one before
									
									$result.='<input type="checkbox"'
										.' id="'.$prefix.$esfield['fieldname'].'" '
										.' name="'.$prefix.$esfield['fieldname'].'" '
										.' '.$attributes
										.($value ? ' checked="checked" ' : '')
										.' class="'.$class.'">';
										
									$result.='<input type="hidden"'
										.' id="'.$prefix.$esfield['fieldname'].'_off" '
										.' name="'.$prefix.$esfield['fieldname'].'_off" '
										.($value ? ' value="1" ' : 'value="0"')
										.' >';
								}

								break;

						case 'image':

								$image_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_image.php';
								require_once($image_type_file);

								$result.=CT_FieldTypeTag_image::renderImageFieldBox($prefix,$esfield,$row,$realFieldName,$class,$attributes);

							break;

						case 'file':

								$file_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
								require_once($file_type_file);
								$result.=CT_FieldTypeTag_file::renderFileFieldBox($prefix,$esfield,$row,$realFieldName,$class);

							break;

						case 'userid':

							$result.=$this->getUserBox($prefix,$esfield,$class,$value,false,$attributes);
							break;

						case 'user':

							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','STRING');

							$result.=$this->getUserBox($prefix,$esfield,$class,$value,true,$attributes);
							break;

						case 'usergroup':

							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','STRING');

							$result.=$this->getUserGroupBox($prefix,$esfield,$class,$value);
							break;

						case 'usergroups':

							$result.=JHTML::_('ESUserGroups.render',
											  $prefix.$esfield['fieldname'],
											  $value,
											  $esfield['typeparams']
											  );

						break;

						case 'language':

							if(count($row)!=0 and (int)$row['listing_id']!=0)
							{
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','STRING');
							}
							else
							{
								//If it's a new record then default language is the current one
								$langObj=JFactory::getLanguage();
								$value=$langObj->getTag();
							}

							$attributes=array('name'=>$prefix.$esfield['fieldname'],'id'=>$prefix.$esfield['fieldname'], 'label'=>$esfield['fieldtitle'.$this->langpostfix],'readonly'=>false);
							$result.= CTTypes::getField('language', $attributes,$value)->input;

							break;

						case 'color':

							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','ALNUM');

							if($value=='')
								$value=$esfield['defaultvalue'];



							if($value=='')
								$value='';

							$att=array('name'=>$prefix.$esfield['fieldname'],'id'=>$prefix.$esfield['fieldname'], 'label'=>$esfield['fieldtitle'.$this->langpostfix]);
							
							if($option_list[0]=='transparent')
							{
								$att['format']='rgba';
								$att['keywords']='transparent,initial,inherit';
								
								//convert value to rgba: rgba(255, 0, 255, 0.1)
							
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
								
								$value='rgba('.implode(',',$colors).')';
							}

							$attributes_=ESInputBox::prepareAttributes($att,$attributes);
							
							
							$inputbox=CTTypes::getField('color', $attributes_,$value)->input;
							
							//Add onChange attribute if not added
							$onChangeAttribute='';
							foreach ($attributes_ as $key => $value)
							{
								if ('onChange' == $key)
								{
									$onChangeAttribute='onChange="'.$value.'"';
									break;
								}
							}
							if($onChangeAttribute!='' and strpos($inputbox,'onChange')===false)
								$inputbox=str_replace('<input ','<input '.$onChangeAttribute,$inputbox);
							
							
							$result.=$inputbox;

							break;

						case 'filelink':

							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','STRING');
								
							if($value=='')
								$value=$esfield['defaultvalue'];


							$result='';
							//$attributes_=ESInputBox::prepareAttributes(array(),$attributes);//check it

							$result.=JHTML::_('ESFileLink.render',$prefix.$esfield['fieldname'], $value, '', $attributes, $esfield['typeparams']);

							break;

						case 'customtables':

							if(!isset($typeparams[1]))
							{
								$result.='selector not specified';
								break;
							}

							$optionname=$typeparams[0];

							$parentid=$this->es->getOptionIdFull($optionname);

							//$typeparams[0] is structure parent
							//$typeparams[1] is selector type (multi or single)
							//$typeparams[2] is data length
							//$typeparams[3] is requirementdepth

								if($typeparams[1]=='multi')
								{

									$fValue=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],null,'STRING');
									if(!isset($fValue))
									{
										$fValue='';
										if($esfield['defaultvalue']!='')
										{
											$fValue=','.$typeparams[0].'.'.$esfield['defaultvalue'].'.,';
										}

									}

									if(isset($row[$esfield['realfieldname']]))
										$fValue=$row[$esfield['realfieldname']];

										$result.=JHTML::_('MultiSelector.render',
													  $prefix,
													  $parentid,$optionname,
													  $this->langpostfix,
													  $this->establename,
													  $esfield['fieldname'],
													  $fValue,
													'',
													$place_holder);

								}
								elseif($typeparams[1]=='single')
								{
										$v=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],null,'STRING');
										if(!isset($v))
										{
											$v='';
											if($esfield['defaultvalue']!='')
											{
												$v=','.$typeparams[0].'.'.$esfield['defaultvalue'].'.,';
											}

										}

										if(isset($row[$esfield['realfieldname']]))
											$v=$row[$esfield['realfieldname']];

										$result.='<div style="float:left;">';
										$result.=JHTML::_('ESComboTree.render',
														  $prefix,
														  $this->establename,
														  $esfield['fieldname'],
														  $optionname,
														  $this->langpostfix,
														  $v,
														  '',
														  '',
														  '',
														  '',
														  $esfield['isrequired'],
														  (isset($typeparams[3]) ? (int)$typeparams[3] : 1),
															$place_holder
														  );

										$result.='</div>';
								}
								else
										$result.='selector not specified';

						break;

						case 'sqljoin':

							//$place_holder=$esfield['fieldtitle'.$this->langpostfix];
							
							if(isset($option_list[2]) and $option_list[2]!='')
								$typeparams[2]=$option_list[2];//Overwrites field type filter parameter.

							$result.=JHTML::_('ESSQLJoin.render',
											  $typeparams,
											  $value,
											  false,
											  $this->langpostfix,
											  $prefix.$esfield['fieldname'],
											  $place_holder,
											  $class,
											  $attributes);
							break;

						case 'records':
							//records : table, [fieldname || layout:layoutname], [selector: multi || single], filter, |datalength|
							
							if(count($typeparams)<1)
								$result.='table not specified';

							if(count($typeparams)<2)
								$result.='field or layout not specified';

							if(count($typeparams)<3)
								$result.='selector not specified';

							$esr_table=$typeparams[0];
							if(isset($typeparams[1]))
								$esr_field=$typeparams[1];
							else
								$esr_field='';

							if(isset($typeparams[2]))
								$esr_selector=$typeparams[2];
							else
								$esr_selector='';

							if(count($typeparams)>3)
								$esr_filter=$typeparams[3];
							else
								$esr_filter='';

							if(isset($typeparams[4]))
								$dynamic_filter=$typeparams[4];
							else
								$dynamic_filter='';

							if(isset($typeparams[5]))
								$sortbyfield=$typeparams[5];
							else
								$sortbyfield='';
								
							$result.=JHTML::_('ESRecords.render',
											  $typeparams,
											  $prefix.$esfield['fieldname'],
											  $value,
											  $esr_table,
											  $esr_field,
											  $esr_selector,
											  $esr_filter,
											  '',
											  $class.' ct_improved_selectbox',
											  $attributes,
											  $dynamic_filter,
											  $sortbyfield,
												$this->langpostfix
											  );

						break;

						case 'googlemapcoordinates':


							$result.=JHTML::_('GoogleMapCoordinates.render',$prefix.$esfield['fieldname'], $value  );

						break;

						case 'email';
								$result.='<input '
									.'type="text" '
									.'name="'.$prefix.$esfield['fieldname'].'" '
									.'id="'.$prefix.$esfield['fieldname'].'" '
									.'class="'.$class.'" '
									.'value="'.$value.'" maxlength="255"'
									.' '.$attributes.' '
									.' />';

						break;

						case 'url';
								$filters=array();
								$filters[]='url';
								$params=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);
								if(isset($params[1]) and $params[1]=='true')
									$filters[]='https';
								
								if(isset($params[2]) and $params[2]!='')
									$filters[]='domain:'.$params[2];
								
								$result.='<input '
									.'type="text" '
									.'name="'.$prefix.$esfield['fieldname'].'" '
									.'id="'.$prefix.$esfield['fieldname'].'" '
									.'class="'.$class.'" '
									.'value="'.$value.'" maxlength="1024"'
									.'data-sanitizers="trim"'
									.'data-filters="'.implode(',',$filters).'"'
									.'data-label="'.$esfield['fieldtitle'.$this->langpostfix].'"'
									.' '.$attributes.' '
									.' />';

						break;

						case 'date';
								if($value=="0000-00-00")
									$value='';

								$attributes_=ESInputBox::prepareAttributes(array('class'=>$class),$attributes);

								$result.=JHTML::calendar($value, $prefix.$esfield['fieldname'], $prefix.$esfield['fieldname'],
														'%Y-%m-%d',$attributes_);

						break;

								$result.=JHTML::_('ESUserGroup.render',$prefix.$esfield['fieldname'], $value, '', $attributes, '',$where);

						case 'time';
							if(count($row)==0)
								$value=JFactory::getApplication()->input->get('es_'.$esfield['fieldname'],'','CMD');
								
							if($value=='')
								$value=$esfield['defaultvalue'];
							else
								$value=(int)$value;

								$result.=JHTML::_('CTTime.render',$prefix.$esfield['fieldname'], $value, $class, $attributes, $typeparams,$option_list);

						break;
								

						case 'article':


							$result.=JHTML::_('ESArticle.render',
											  $prefix.$esfield['fieldname'],
											  $value,

											  $class,
											  $esfield['typeparams']
											  );

						break;

						case 'imagegallery':

							if(isset($row['listing_id']))
								$result.=$this->getImageGallery($esfield['fieldname'],$esfield['typeparams'],$row['listing_id']);

						break;

						case 'filebox':

							if(isset($row['listing_id']))
								$result.=$this->getFileBox($Model,$esfield['fieldname'],$esfield['typeparams'],$row['listing_id']);

						break;

						case 'multilangarticle':
							$result.='<table>';

							$firstlanguage=true;
							foreach($LanguageList as $lang)
							{
								if($firstlanguage)
								{
									$postfix='';
									$firstlanguage=false;
								}
								else
									$postfix='_'.$lang->sef;

								$fieldname=$esfield['fieldname'].$postfix;

								if(count($row)==0)
									$value=JFactory::getApplication()->input->get('es_'.$fieldname,'','STRING');
								else
									$value=$row[$esfield['realfieldname'].$postfix];

								$result.='<tr>
								<td>'.$lang->caption.'</td><td>:</td>
									<td>';

								$result.=JHTML::_('ESArticle.render',
											  $prefix.$fieldname,
											  $value,

											  $class,
											  $esfield['typeparams']
											  );

								$result.='</td>
								</tr>';

							}
							$result.='</table>';
							break;

						break;

		}
		return $result;
	}
	
	protected static function prepareAttributes($attributes_,$attributes)
	{
								if($attributes!='')
								{
									$atts_=JoomlaBasicMisc::csv_explode(' ',$attributes,'"',false);
									foreach($atts_ as $a)
									{
										$pair=explode('=',$a);

										if(count($pair)==2)
										{
											
											$att=$pair[0];
											if($att=='onchange')
												$att='onChange';
												
												
											$attributes_[$att]=$pair[1];
											
										}
									}
								}
								
		return $attributes_;
		
	}

	function getWhereParameter($field)
	{
		$f=str_replace('es_','',$field);
		$list=$this->getWhereParameters();

		foreach($list as $l)
		{
			$p=explode('=',$l);
			if($p[0]==$f and isset($p[1]))
				return $p[1];
		}
		return '';
	}

	function getWhereParameters()
	{
		$value=JFactory::getApplication()->input->get('where','','BASE64');;
		$b=base64_decode($value);
		$b=str_replace(' or ',' and ',$b);
		$b=str_replace(' OR ',' and ',$b);
		$b=str_replace(' AND ',' and ',$b);
		$list=explode(' and ',$b);
		return $list;
	}

	
	function getMultilangStringItem(&$Model,&$esfield,$prefix,&$row,$attributes,$class,$postfix,$langsef)
	{
							$attributes_='';
							$addDynamicEvent=false;
							$WebsiteRoot='';
							if(strpos($attributes,'onchange="ct_UpdateSingleValue(')!==false)//its like a keyword
							{
								$addDynamicEvent=true;
					         	$WebsiteRoot=JURI::root(true);
								if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
								    $WebsiteRoot.='/';
							}
							else
								$attributes_=$attributes;
								
															if(count($row)==0)
									$value=JFactory::getApplication()->input->get($prefix.$esfield['fieldname'].$postfix,'','STRING');
								else
									$value=$row[$esfield['realfieldname'].$postfix];
								

								if($addDynamicEvent)
									$attributes_=' onchange="ct_UpdateSingleValue(\''.$WebsiteRoot.'\','.$Model->Itemid.',\''.$esfield['fieldname'].$postfix.'\','.$row['listing_id'].',\''.$langsef.'\')"';
									
								$result='<input type="text" name="'.$prefix.$esfield['fieldname'].$postfix.'" id="'.$prefix.$esfield['fieldname'].$postfix.'" id="code" class="'.$class.'"
								value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"').$attributes_.' />';

		return $result;
	}
	
	function getMultilangString(&$Model,&$esfield,$prefix,&$row,$attributes,$class,&$option_list)
	{
		$result='';
		if(isset($option_list[4]))
		{
			$language=$option_list[4];

			$firstlanguage=true;
			foreach($this->LanguageList as $lang)
			{
				if($firstlanguage)
				{
					$postfix='';
					$firstlanguage=false;
				}
				else
					$postfix='_'.$lang->sef;
					
				if($language==$lang->sef)
				{
					//show single edit box
					return $this->getMultilangStringItem($Model,$esfield,$prefix,$row,$attributes,$class,$postfix,$lang->sef);
				}
			}
		}
		
		//show all languages	
							$result.='<div class="form-horizontal">';

							$firstlanguage=true;
							foreach($this->LanguageList as $lang)
							{
								if($firstlanguage)
								{
									$postfix='';
									$firstlanguage=false;
								}
								else
									$postfix='_'.$lang->sef;

								$realfieldname=$esfield['realfieldname'].$postfix;

							
								$result.='<div class="control-group">
								<div class="control-label">'.$lang->caption.'</div>
								<div class="controls">';
								
								$result.=$this->getMultilangStringItem($Model,$esfield,$prefix,$row,$attributes,$class,$postfix,$lang->sef);								
								$result.='</div>
								</div>';

							}
							$result.='</div>';
							
		return $result;
		
	}
	
	function getUserBox($prefix,&$esfield,$class,$value,$require_authorization,$attributes)
	{
		$result='';

		$user = JFactory::getUser();
		if($user->id==0)
			return '';

		$attributes='class="'.$class.'"';

		$pair=JoomlaBasicMisc::csv_explode(',', $esfield['typeparams'], '"', false);
		$usergroup=$pair[0];

		$where='';
		if(isset($pair[3]))
			$where='INSTR(name,"'.$pair[3].'")';

		if($require_authorization)
		{
			$result.=JHTML::_('ESUser.render',$prefix.$esfield['fieldname'], $value, '', $attributes, $usergroup,'',$where);//check this, it should be disabled to edit
		}
		else
		{
			$result.=JHTML::_('ESUser.render',$prefix.$esfield['fieldname'], $value, '', $attributes, $usergroup,'',$where);
		}
		return $result;
	}

	function getUserGroupBox($prefix,&$esfield,$class,$value)
	{
		$result='';

		$user = JFactory::getUser();
		if($user->id==0)
			return '';

		$attributes='class="'.$class.'"';

		$where='';


		$result.=JHTML::_('ESUserGroup.render',$prefix.$esfield['fieldname'], $value, '', $attributes, '',$where);

		return $result;
	}

	function getImageGallery($fieldname,$TypeParams,$listing_id)
	{
		$htmlout='';

		$getGalleryRows=CT_FieldTypeTag_imagegallery::getGalleryRows($this->establename,$fieldname,$listing_id);

		$htmlout.='
		';

		$prefix='';

		if(isset($pair[1]) and (int)$pair[1]<250)
			$img_width=(int)$pair[1];
		else
			$img_width=250;

		if($prefix=='')
			$img_width=100;

		$imagesrclist=array();
		$imagetaglist=array();

		if(CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $prefix,$listing_id,$fieldname,$TypeParams,$imagesrclist,$imagetaglist,$this->estableid))
		{
			$imagesrclist_arr=explode(';',$imagesrclist);

			$htmlout.='<div style="width:100%;overflow:scroll;border:1px dotted grey;background-image: url(\'components/com_customtables/images/bg.png\');">

		<table cellpadding="3"><tbody><tr>';

		foreach($imagesrclist_arr as $img)
		{
			$htmlout.='<td align="center" valign="top">';
			$htmlout.='<a href="'.$img.'" target="_blank"><img src="'.$img.'" width="'.$img_width.'" />';
			$htmlout.='</td>';
		}

		$htmlout.='</tr></tbody></table>

		</div>';

		}
		else
		{
			return 'No Images';
		}

		$htmlout.='
		';



		return $htmlout;



	}//function


	function getFileBox(&$Model,$fieldname,$TypeParams,$listing_id)
	{
		$htmlout='';


		$FileBoxRows=CT_FieldTypeTag_filebox::getFileBoxRows($this->establename,$fieldname,$listing_id);

		if($TypeParams=='')
			$filefolder='images/esfilebox';
		else
			$filefolder=$TypeParams;

		$prefixs=explode(';',$TypeParams);

		$prefixs[]='';

		$htmlout.='
		';

		foreach($prefixs as $p)
		{
			$pair=explode(',',$p);
			$prefix=$pair[0];

			if(isset($pair[1]) and (int)$pair[1]<250)
				$img_width=(int)$pair[1];
			else
				$img_width=250;

			if($prefix=='')
				$img_width=100;

			if(count($FileBoxRows) > 0)
			{
				$vlu = CT_FieldTypeTag_filebox::process($Model,$FileBoxRows, $listing_id,
									$fieldname,$TypeParams,['','icon-filename-link','32','_blank','ol']);

				$htmlout.='<div style="width:100%;overflow:scroll;background-image: url(\'components/com_customtables/images/bg.png\');">'.$vlu.'</div>';
			}
			else
				return 'No Files';

		}

		$htmlout.='
		';

		return $htmlout;



	}//function

	function isAdmin()
	{
		$user = JFactory::getUser();
		$userid = $user->get('id');

		$db = JFactory::getDBO();

		$query='SELECT (SELECT title FROM #__usergroups WHERE id=group_id LIMIT 1) AS title FROM #__user_usergroup_map WHERE user_id='.$userid;
	    $db->setQuery($query);
	    $records=$db->loadAssocList();
		
	    if(count($records)==0)
		    return '';

		$a=['Super Users'];

		foreach($records as $r)
		{
			if(in_array($r['title'],$a))
				return true;
		}

		return false;
	}
}
