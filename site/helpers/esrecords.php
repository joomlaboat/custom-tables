<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\LinkJoinFilters;

use \Joomla\CMS\Uri\Uri;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESRecords
{
    static public function render($typeparams,$control_name, $value, $establename, $thefield, $selector, $filter,$style='',
		$cssclass='', $attribute='', $dynamic_filter='',$sortbyfield='',$langpostfix='',$place_holder='')
    {
		$htmlresult='';
		$fieldarray=explode(';',$thefield);
		$field=$fieldarray[0];
		$selectorpair=explode(':',$selector);
		$config=array();

        if(isset($typeparams[6]) and $typeparams[6]=='true')
			$allowunpublished=true;
		else
			$allowunpublished=false;

				//With filter
				$paramsArray=array();
				$paramsArray['limit']=10000;
				$paramsArray['establename']=$establename;
				$paramsArray['filter']=str_replace('****quote****','"',$filter);

                if($allowunpublished)//0 - published only; 1 - hidden only; 2 - Any
					$paramsArray['showpublished']=2;
                else
					$paramsArray['showpublished']=0;

				$paramsArray['groupby']='';

                if($sortbyfield!='')
					$paramsArray['forcesortby']=$sortbyfield;
				elseif(strpos($field,':')===false) //cannot sort by layout only by field name
					$paramsArray['forcesortby']=$field;
					

				$model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);

				$_params= new JRegistry;
				$_params->loadArray($paramsArray);
                                
   				$model->load($_params, true);
				
				$model->getSearchResult();
			
                                
				//Without filter
                                if($selectorpair[0]=='single' or $selectorpair[0]=='multibox')
                                {
                                        $model_nofilter = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);

                                        $paramsArray_nofilter=array();
                                        $paramsArray_nofilter['limit']=0;
                                        $paramsArray_nofilter['establename']=$establename;
                                        $paramsArray_nofilter['filter']=''; //!IMPORTANT - NO FILTER

                                        if($allowunpublished)
                                                $paramsArray_nofilter['showpublished']=2;//0 - published only; 1 - hidden only; 2 - Any
                                        else
                                                $paramsArray_nofilter['showpublished']=0;//0 - published only; 1 - hidden only; 2 - Any

                                        $paramsArray_nofilter['groupby']='';
										
										if($sortbyfield!='')
											$paramsArray_nofilter['forcesortby']=$sortbyfield;
										elseif(strpos($field,':')===false) //cannot sort by layout only by field name
											$paramsArray_nofilter['forcesortby']=$field;
                                        
                                        $_params_nofilter= new JRegistry;
                                        $_params_nofilter->loadArray($paramsArray_nofilter);

                                        $model_nofilter->load($_params_nofilter, true);

                                        $model_nofilter->getSearchResult();
                                }

				$valuearray=explode(',',$value);

				if(strpos($field,':')===false)
				{
						//without layout
						
						$real_field_row=Fields::getFieldRowByName($field, '',$establename);
						
						switch($selectorpair[0])
						{

								case 'single' :
								
										$control_name_postfix = '';

										$htmlresult.=JHTMLESRecords::getSingle($model, $model_nofilter,$valuearray,$field,$selectorpair,$control_name,
                                            $control_name_postfix, $style,$cssclass,$attribute,$value,$establename,$dynamic_filter,$langpostfix,$place_holder);
											
										break;

								case 'multi' :

										if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
												$real_field=$real_field_row->realfieldname.$langpostfix;
										else
												$real_field=$real_field_row->realfieldname;


										$htmlresult.='<SELECT name="'.$control_name.'[]" '
											.'id="'.$control_name.'" MULTIPLE ';
													
										if(count($selectorpair)>1)
											$htmlresult.='size="'.$selectorpair[1].'" ';
											
										$htmlresult.=($style!='' ? 'style="'.$style.'" ' : '')
													.($cssclass!='' ? 'class="'.$cssclass.'" ' : '')
													.'data-label="'.$place_holder.'" '
													.($attribute!='' ? ' '.$attribute.' ' : '').'>';
										
										foreach($model->ct->Records as $row)
										{
											if($row['listing_published']==0)
                                                $style='style="color:red"';
                                            else
                                                $style='';

												$htmlresult.='<option value="'.$row[$model->ct->Table->realidfieldname].'" '
														.((in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
														.' '.$style.'>';

												$htmlresult.=$row[$real_field].'</option>';
										}

										$htmlresult.='</SELECT>';
										break;

								case 'radio' :

										$htmlresult.='<table style="border:none;" id="sqljoin_table_'.$control_name.'">';
										$i=0;
										foreach($model->ct->Records as $row)
										{
												$htmlresult.='<tr><td valign="middle">'
														.'<input type="radio" '
														.'name="'.$control_name.'" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row[$model->ct->Table->realidfieldname].'" '
														.((in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
														.($cssclass!='' ? 'class="'.$cssclass.'"' : '')
														.' /></td>';

												$htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row[$real_field_row->realfieldname].'</label>'
														.'</td></tr>';
												$i++;
										}
										$htmlresult.='</table>';
										break;

								case 'checkbox' :
										
										if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
												$real_field=$real_field_row->realfieldname.$langpostfix;
										else
												$real_field=$real_field_row->realfieldname;

										$htmlresult.='<table style="border:none;">';
										$i=0;
										foreach($model->ct->Records as $row)
										{
												$htmlresult.='<tr><td valign="middle">'
														.'<input type="checkbox" '
														.'name="'.$control_name.'[]" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row[$model->ct->Table->realidfieldname].'" '
														.((in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0 ) ? ' checked="checked" ' : '')
														.($cssclass!='' ? 'class="'.$cssclass.'"' : '')
														.' /></td>';

                                                $htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row[$real_field].'</label>'
														.'</td></tr>';


												$i++;
										}
										$htmlresult.='</table>';
										break;

								case 'multibox' :
		
										$htmlresult.=JHTMLESRecords::getMultibox($model, $model_nofilter,$valuearray,$field,$selectorpair,
											$control_name,$style,$cssclass,$attribute,$establename,$dynamic_filter,$langpostfix,$place_holder);

										break;

								default:
										return '<p>Incorrect selector</p>';
								break;
						}
				}
				else
				{
						//with layout
						$pair=JoomlaBasicMisc::csv_explode(':',$field,'"',false);
						if($pair[0]!='layout' and $pair[0]!='tablelesslayout')
								return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';
						
						$ct = new CT;
						
						$Layouts = new Layouts($ct);
						$layoutcode = $Layouts->getLayout($pair[1]);
						
						if($layoutcode=='')
							return '<p>layout "'.$pair[1].'" not found or is empty.</p>';

						$model->ct->LayoutProc->layout=$layoutcode;

						$htmlresult.='<table style="border:none;" id="sqljoin_table_'.$control_name.'">';
						$i=0;
						foreach($model->ct->Records as $row)
						{
								$htmlresult.='<tr><td valign="middle">';

								if($selectorpair[0]=='multi' or $selectorpair[0]=='checkbox')
								{

										$htmlresult.='<input type="checkbox" '
										.'name="'.$control_name.'[]" '
										.'id="'.$control_name.'_'.$i.'" '
										.'value="'.$row[$model->ct->Table->realidfieldname].'" '
										.((in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
										.' />';
								}
								elseif($selectorpair[0]=='single' or $selectorpair[0]=='radio')
								{
										$htmlresult.='<input type="radio" '
										.'name="'.$control_name.'" '
										.'id="'.$control_name.'_'.$i.'" '
										.'value="'.$row[$model->ct->Table->realidfieldname].'" '
										.((in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
										.' />';
								}
								else
										return '<p>Incorrect selector</p>';

								$htmlresult.='</td>';

								$htmlresult.='<td valign="middle">';

								//process layout
								$htmlresult.='<label for="'.$control_name.'_'.$i.'">';
								$htmlresult.=$model->ct->LayoutProc->fillLayout($row);
								$htmlresult.='</label>';


								$htmlresult.='</td></tr>';
								$i++;
						}
						$htmlresult.='</table>';





				}

				return $htmlresult;
        }

	static protected function getSingle(&$model, &$model_nofilter,&$valuearray,
		$field,$selectorpair,$control_name, $control_name_postfix, $style,$cssclass,$attribute,string $value,
		$establename,$dynamic_filter='',$langpostfix='',$place_holder='')
	{

		$htmlresult='';

		if($dynamic_filter!='')
		{
			$htmlresultjs='';
			$elements=array();
			$elementsID=array();
			$elementsFilter=array();
            $elementsPublished=array();

			$filtervalue='';
			foreach($model_nofilter->ct->Records as $row)
			{
				if($row[$model_nofilter->ct->Table->realidfieldname]==$value)
				{
					$filtervalue=$row[$model_nofilter->ct->Env->field_prefix.$dynamic_filter];
					break;
				}
			}
			$htmlresult.=LinkJoinFilters::getFilterBox($establename,$dynamic_filter,$control_name,$filtervalue,$control_name_postfix);

		}

		$htmlresult_options = '';

		if(strpos($control_name,'_selector')===false)
			$htmlresult_options.='<option value="">- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).' '.$place_holder.'</option>';
	
		if($value=='' or $value==',' or $value==',,')
			$valuefound=true;
		else
			$valuefound=false;

		foreach($model->ct->Records as $row)
		{
			if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0 )
			{
				$htmlresult_options.='<option value="'.$row[$model->ct->Table->realidfieldname].'" SELECTED '.($row['listing_published']==0 ? ' disabled="disabled"' : '').'>';
				$valuefound=true;
			}
			else
				$htmlresult_options.='<option value="'.$row[$model->ct->Table->realidfieldname].'" '.($row['listing_published']==0 ? ' disabled="disabled"' : '').'>';

			$v=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
			$htmlresult_options.=$v;

			if($dynamic_filter!='')
			{
				$elements[]=$v;
				$elementsID[]=$row[$model->ct->Table->realidfieldname];
				$elementsFilter[]=$row[$model->ct->Env->field_prefix.$dynamic_filter];
				$elementsPublished[]=(int)$row['listing_published'];
			}
			$htmlresult_options.='</option>';
		}

		if($value!='' and $value!=',' and $value!=',,' and !$valuefound)
		{
			//_nofilter - add all elements, don't remember why, probably if value is not in the list after the fileter
			//workaround in case the value not found

			foreach($model_nofilter->ct->Records as $row)
			{
				if(in_array($row[$model_nofilter->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0 )
				{
					$htmlresult_options.='<option value="'.$row[$model_nofilter->ct->Table->realidfieldname].'" SELECTED '.($row['listing_published']==0 ? ' disabled="disabled"' : '').'>';
					$valuefound=true;
				}
				else
					$htmlresult_options.='<option value="'.$row[$model_nofilter->ct->Table->realidfieldname].'" '.($row['listing_published']==0 ? ' disabled="disabled"' : '').'>';

				$v=JoomlaBasicMisc::processValue($field,$model_nofilter->ct,$row,$langpostfix);
				$htmlresult_options.=$v;

				if($dynamic_filter!='')
				{
					$elements[]=$v;
					$elementsID[]=$row[$model_nofilter->ct->Table->realidfieldname];
					$elementsFilter[]=$row[$model_nofilter->ct->Env->field_prefix.$dynamic_filter];
					$elementsPublished[]=(int)$row['listing_published'];
				}
				$htmlresult_options.='</option>';
			}
		}

		$htmlresult.='<SELECT name="'.$control_name.'" id="'.$control_name.$control_name_postfix.'" '
			.($style!='' ? 'style="'.$style.'" ' : '')
			.($cssclass!='' ? 'class="'.$cssclass.'" ' : '')
			.$attribute.($attribute!='' ? ' ' : '')
			.'data-label="'.$place_holder.'" '
		.'>';
				
		$htmlresult .= $htmlresult_options;

		$htmlresult.='</SELECT>';

		if($dynamic_filter!='')
		{
			$htmlresultjs.='
			<div id="'.$control_name.$control_name_postfix.'_elements" style="display:none;">'.json_encode($elements).'</div>
			<div id="'.$control_name.$control_name_postfix.'_elementsID" style="display:none;">'.implode(',',$elementsID).'</div>
			<div id="'.$control_name.$control_name_postfix.'_elementsFilter" style="display:none;">'.implode(';',$elementsFilter).'</div>
			<div id="'.$control_name.$control_name_postfix.'_elementsPublished" style="display:none;">'.implode(',',$elementsPublished).'</div>
			';
			$htmlresult=$htmlresultjs.$htmlresult;
		}

		return $htmlresult;
	}

	static protected function getMultibox(&$model, &$model_nofilter,&$valuearray,$field,$selectorpair,
                                              $control_name,$style,$cssclass,$attribute,$establename,$dynamic_filter,$langpostfix='',$place_holder='')
	{
		$real_field_row=Fields::getFieldRowByName($field, '',$establename);

		if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
			$real_field=$real_field_row->realfieldname.$langpostfix;
		else
			$real_field=$real_field_row->realfieldname;

		$deleteimage=URI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/cancel.png';
		
		$ctInputboxRecords_r = [];
		$ctInputboxRecords_v = [];
		$ctInputboxRecords_p = [];
		
		foreach($model->ct->Records as $row)
		{
			if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) and count($valuearray)>0)
			{
				$ctInputboxRecords_r[]=$row[$model->ct->Table->realidfieldname];
				$ctInputboxRecords_v[]=$row[$real_field];
				$ctInputboxRecords_p[]=(int)$row['listing_published'];
			}
		}

		$htmlresult='
		<script>
			//Field value
			ctInputboxRecords_r["'.$control_name.'"] = '.json_encode($ctInputboxRecords_r).';
			ctInputboxRecords_v["'.$control_name.'"] = '.json_encode($ctInputboxRecords_v).';
			ctInputboxRecords_p["'.$control_name.'"] = '.json_encode($ctInputboxRecords_p).';
		</script>
		';

		$value='';
		$single_box='';

		$single_box.=JHTMLESRecords::getSingle($model, $model_nofilter,$valuearray,$field,$selectorpair,
		$control_name, '_selector',$style,$cssclass,$attribute,'',$establename,$dynamic_filter,$langpostfix,$place_holder);
		$icon_path = JURI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/';
		$htmlresult.='<div style="padding-bottom:20px;"><div style="width:90%;" id="'.$control_name.'_box"></div>'
		.'<div style="height:30px;">'
			.'<div id="'.$control_name.'_addButton" style="visibility: visible;"><img src="'.$icon_path.'new.png" alt="Add" title="Add" style="cursor: pointer;" '
			.'onClick="ctInputboxRecords_addItem(\''.$control_name.'\',\'_selector\')" /></div>'
			.'<div id="'.$control_name.'_addBox" style="visibility: hidden;">'
				.'<div style="float:left;">'.$single_box.'</div>'
				.'<img src="'.$icon_path.'plus.png" '
					.'alt="Add" title="Add" '
					.'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;width:16px;height:16px;" '
					.'onClick="ctInputboxRecords_DoAddItem(\''.$control_name.'\',\'_selector\')" />'
				.'<img src="'.$icon_path.'cancel.png" alt="Cancel" title="Cancel" '
					.'style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;width:16px;height:16px;" '
					.'onClick="ctInputboxRecords_cancel(\''.$control_name.'\',\'_selector\')" />'

			.'</div>'
		.'</div>'
			.'<div style="visibility: hidden;"><select name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE ></select></div>'
		.'</div>

		<script>
			ctInputboxRecords_showMultibox("'.$control_name.'","_selector");
		</script>
		';

		return $htmlresult;
	}
}
