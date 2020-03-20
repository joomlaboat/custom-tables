<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @version 1.6.1
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filtering.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');

class JHTMLESRecords
{


        static public function render($typeparams,$control_name, $value, $establename, $thefield, $selector, $filter,$style='',
                                      $cssclass='', $attribute='', $dynamic_filter='',$sortbyfield='',$langpostfix='')
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
				$paramsArray['limit']=0;
				$paramsArray['establename']=$establename;
				$paramsArray['filter']=str_replace('****quote****','"',$filter);

                                if($allowunpublished)//0 - published only; 1 - hidden only; 2 - Any
                                        $paramsArray['showpublished']=2;
                                else
                                        $paramsArray['showpublished']=0;

				$paramsArray['showpagination']=0;
				$paramsArray['groupby']='';
				$paramsArray['shownavigation']=0;
                                if($sortbyfield=='')
                                        $paramsArray['sortby']=$field;
                                else
                                        $paramsArray['sortby']=$sortbyfield;

				$model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);

				if($selectorpair[0]=='single')
						$model->es_ordering=$field;
				else
				{
						if(isset($fieldarray[2]))
								$model->es_ordering=$fieldarray[2];
						else
						{
								if(strpos($field,':')===false)
										$model->es_ordering=$field;
								else
										$model->es_ordering='';
						}
				}

				$_params= new JRegistry;
				$_params->loadArray($paramsArray);
                                
   				$model->load($_params, true);
				$model->showpagination=false;

				$SearchResult=$model->getSearchResult();
                                
				//Without filter
                                if($selectorpair[0]=='single' or $selectorpair[0]=='multibox')
                                {
                                        $model_nofilter = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);

                                        $paramsArray_nofilter=array();
                                        $paramsArray_nofilter['limit']=0;
                                        $paramsArray_nofilter['establename']=$establename;
                                        $paramsArray_nofilter['filter']=''; //!IMPORTANT - NO FILTER
                                        //$paramsArray_nofilter['showpublished']=0;//only published
                                        $paramsArray_nofilter['showpagination']=0;

                                        if($allowunpublished)
                                                $paramsArray_nofilter['showpublished']=2;//0 - published only; 1 - hidden only; 2 - Any
                                        else
                                                $paramsArray_nofilter['showpublished']=0;//0 - published only; 1 - hidden only; 2 - Any

                                        $paramsArray_nofilter['groupby']='';
                                        $paramsArray_nofilter['shownavigation']=0;
                                        
                                        if($sortbyfield=='')
                                                $paramsArray_nofilter['sortby']=$field;
                                        else
                                                $paramsArray_nofilter['sortby']=$sortbyfield;
                                        
                                        $model_nofilter->es_ordering=$field;
                                        
                                        $_params_nofilter= new JRegistry;
                                        $_params_nofilter->loadArray($paramsArray_nofilter);

                                        $model_nofilter->showpagination=false;
                                        $model_nofilter->load($_params_nofilter, true);

                                        $SearchResult_nofilter=$model_nofilter->getSearchResult();
                                }
  /*      
				if($selectorpair[0]!='single')
				{
						if(isset($fieldarray[2]))
								$model_nofilter->es_ordering=$fieldarray[2];
						else
						{
								if(strpos($field,':')===false)
										$model_nofilter->es_ordering=$field;
								else
										$model_nofilter->es_ordering='';
						}

				}
*/
				$valuearray=explode(',',$value);

				if(strpos($field,':')===false)
				{
						//without layout
						switch($selectorpair[0])
						{

								case 'single' :

										$htmlresult.=JHTMLESRecords::getSingle($model, $model_nofilter,$SearchResult,$SearchResult_nofilter,$valuearray,$field,$selectorpair,$control_name,
                                                                                                                       $style,$cssclass,$attribute,$value,$establename,$dynamic_filter,$langpostfix);
										break;

								case 'multi' :

										require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
										$real_field_row=ESFields::getFieldRowByName($field, '',$establename);


										if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
												$real_field='es_'.$field.$langpostfix;
										else
												$real_field='es_'.$field;


										if(count($selectorpair)>1)
												$htmlresult.='<SELECT name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE size="'.$selectorpair[1].'"'.($style!='' ? 'style="'.$style.'"' : '').' '.($cssclass!='' ? 'class="'.$cssclass.'"' : '').($attribute!='' ? ' '.$attribute.' ' : '').'>';
										else
												$htmlresult.='<SELECT name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE '.($style!='' ? 'style="'.$style.'"' : '').' '.($cssclass!='' ? 'class="'.$cssclass.'"' : '').($attribute!='' ? ' '.$attribute.' ' : '').'>';

										foreach($SearchResult as $row)
										{

                                                                                        if($row['published']==0)
                                                                                                        $style='style="color:red"';
                                                                                                else
                                                                                                        $style='';

												$htmlresult.='<option value="'.$row['listing_id'].'" '
														.((in_array($row['listing_id'],$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
														.' '.$style.'>';

												$htmlresult.=$row[$real_field].'</option>';
										}

										$htmlresult.='</SELECT>';
										break;

								case 'radio' :

										$htmlresult.='<table style="border:none;" id="sqljoin_table_'.$control_name.'">';
										$i=0;
										foreach($SearchResult as $row)
										{

												$htmlresult.='<tr><td valign="middle">'
														.'<input type="radio" '
														.'name="'.$control_name.'" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row['listing_id'].'" '
														.((in_array($row['listing_id'],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
														.($cssclass!='' ? 'class="'.$cssclass.'"' : '')
														.' /></td>';

												$htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row['es_'.$field].'</label>'
														.'</td></tr>';
												$i++;
										}
										$htmlresult.='</table>';
										break;

								case 'checkbox' :
										$real_field_row=ESFields::getFieldRowByName($field, '',$establename);
                                                                                if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
												$real_field='es_'.$field.$langpostfix;
										else
												$real_field='es_'.$field;

										$htmlresult.='<table style="border:none;">';
										$i=0;
										foreach($SearchResult as $row)
										{
												$htmlresult.='<tr><td valign="middle">'
														.'<input type="checkbox" '
														.'name="'.$control_name.'[]" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row['listing_id'].'" '
														.((in_array($row['listing_id'],$valuearray) and count($valuearray)>0 ) ? ' checked="checked" ' : '')
														.($cssclass!='' ? 'class="'.$cssclass.'"' : '')
														.' /></td>';
														/*
												$htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row['es_'.$field].'</label>'
														.'</td></tr>';
                                                                                                                */
                                                                                                $htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row[$real_field].'</label>'
														.'</td></tr>';


												$i++;
										}
										$htmlresult.='</table>';
										break;

								case 'multibox' :

										$htmlresult.=JHTMLESRecords::getMultibox($model, $model_nofilter,$SearchResult,$SearchResult_nofilter,$valuearray,$field,$selectorpair,
                                                                                                                         $control_name,$style,$cssclass,$attribute,$establename,$dynamic_filter,$langpostfix);

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




						//$pair[1] is layoutname
                                                $layouttype=0;
						$layoutcode=ESLayouts::getLayout($pair[1],$layouttype);
						if($layoutcode=='')
							return '<p>layout "'.$pair[1].'" not found or is empty.</p>';



						$model->LayoutProc->layout=$layoutcode;


						$htmlresult.='<table style="border:none;" id="sqljoin_table_'.$control_name.'">';
						$i=0;
						foreach($SearchResult as $row)
						{



								$htmlresult.='<tr><td valign="middle">';

								if($selectorpair[0]=='multi' or $selectorpair[0]=='checkbox')
								{

										$htmlresult.='<input type="checkbox" '
										.'name="'.$control_name.'[]" '
										.'id="'.$control_name.'_'.$i.'" '
										.'value="'.$row['listing_id'].'" '
										.((in_array($row['listing_id'],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
										.' />';
								}
								elseif($selectorpair[0]=='single' or $selectorpair[0]=='radio')
								{
										$htmlresult.='<input type="radio" '
										.'name="'.$control_name.'" '
										.'id="'.$control_name.'_'.$i.'" '
										.'value="'.$row['listing_id'].'" '
										.((in_array($row['listing_id'],$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
										.' />';
								}
								else
										return '<p>Incorrect selector</p>';

								$htmlresult.='</td>';

								$htmlresult.='<td valign="middle">';

								//process layout
								$htmlresult.='<label for="'.$control_name.'_'.$i.'">';
								$htmlresult.=$model->LayoutProc->fillLayout($row,'','');
								$htmlresult.='</label>';


								$htmlresult.='</td></tr>';
								$i++;
						}
						$htmlresult.='</table>';





				}

				return $htmlresult;


        }

	static protected function getSingle(&$model, &$model_nofiter,&$SearchResult,&$SearchResult_nofilter,&$valuearray,
                                            $field,$selectorpair,$control_name,$style,$cssclass,$attribute,$value='',$establename,$dynamic_filter='',$langpostfix='')
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
			foreach($SearchResult_nofilter as $row)
			{
				if($row['listing_id']==$value)
				{
					$filtervalue=$row['es_'.$dynamic_filter];
					break;
				}
			}
			$htmlresult.=LinkJoinFilters::getFilterBox($establename,$dynamic_filter,$control_name,$filtervalue);

		}

                //if(strpos($cssclass,' ct_improved_selectbox')!==false)
                      //  JHtml::_('formbehavior.chosen', '.ct_improved_selectbox');

//$style.=' min-width:200px !important;';
		$htmlresult.='<SELECT name="'.$control_name.'" id="'.$control_name.'"'
.' '.($style!='' ? 'style="'.$style.'"' : '')
				.' '.($cssclass!='' ? 'class="'.$cssclass.'"' : '');
		$htmlresult.=	' '.$attribute;
		$htmlresult.='>';

		if(strpos($control_name,'_selector')===false)
		{
			$htmlresult.='<option value="">- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';
		}
										if($value=='' or $value==',' or $value==',,')
												$valuefound=true;
										else
												$valuefound=false;



										$htmlresult_='';
										foreach($SearchResult as $row)
										{
												if(in_array($row['listing_id'],$valuearray) and count($valuearray)>0 )
												{
														$htmlresult_.='<option value="'.$row['listing_id'].'" SELECTED '.($row['published']==0 ? ' disabled="disabled"' : '').'>';
														$valuefound=true;
												}
												else
														$htmlresult_.='<option value="'.$row['listing_id'].'" '.($row['published']==0 ? ' disabled="disabled"' : '').'>';

												$v=JoomlaBasicMisc::processValue($field,$model,$row,$langpostfix);
												$htmlresult_.=$v;

												if($dynamic_filter!='')
												{
														$elements[]='"'.$v.'"';
														$elementsID[]=$row['listing_id'];
														$elementsFilter[]='"'.$row['es_'.$dynamic_filter].'"';
                                                                                                                $elementsPublished[]=(int)$row['published'];
												}

												$htmlresult_.='</option>';
										}

										if($value!='' and $value!=',' and $value!=',,' and !$valuefound)
										{
												//_nofilter

												$htmlresult_nofilter='';
												foreach($SearchResult_nofilter as $row)
												{
													if(in_array($row['listing_id'],$valuearray) and count($valuearray)>0 )
													{
														$htmlresult_nofilter.='<option value="'.$row['listing_id'].'" SELECTED '.($row['published']==0 ? ' disabled="disabled"' : '').'>';
														$valuefound=true;
													}
													else
														$htmlresult_.='<option value="'.$row['listing_id'].'" '.($row['published']==0 ? ' disabled="disabled"' : '').'>';

													$v=JoomlaBasicMisc::processValue($field,$model_nofilter,$row,$langpostfix);
													$htmlresult_nofilter.=$v;



													if($dynamic_filter!='')
													{
														$elements[]='"'.$v.'"';
														$elementsID[]=$row['listing_id'];
														$elementsFilter[]='"'.$row['es_'.$dynamic_filter].'"';
                                                                                                                $elementsPublished[]=(int)$row['published'];
													}


													$htmlresult_nofilter.='</option>';


												}
												$htmlresult.=$htmlresult_nofilter;
										}

										$htmlresult.=$htmlresult_.'</SELECT>';

										if($dynamic_filter!='')
										{
											$htmlresultjs.='
											<script>
												var '.$control_name.'elements=['.implode(',',$elements).'];
												var '.$control_name.'elementsID=['.implode(',',$elementsID).'];
												var '.$control_name.'elementsFilter=['.implode(',',$elementsFilter).'];
												var '.$control_name.'elementsPublished=['.implode(',',$elementsPublished).'];
											</script>
											';
											$htmlresult=$htmlresultjs.$htmlresult;
										}



		return $htmlresult;
	}

	static protected function getMultibox(&$model, &$model_nofilter,&$SearchResult,&$SearchResult_nofilter,&$valuearray,$field,$selectorpair,
                                              $control_name,$style,$cssclass,$attribute,$establename,$dynamic_filter,$langpostfix='')
	{

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
		$real_field_row=ESFields::getFieldRowByName($field, '',$establename);

		if($real_field_row->type=="multilangstring" or $real_field_row->type=="multilangtext")
				$real_field='es_'.$field.$langpostfix;
		else
				$real_field='es_'.$field;




		$deleteimage='components/com_customtables/images/cancel_small.png';
		$htmlresult='
		<script>
			var '.$control_name.'_r=new Array();
			var '.$control_name.'_v=new Array();
			var '.$control_name.'_p=new Array();
			';
			$i=0;
			foreach($SearchResult as $row)
			{
				if(in_array($row['listing_id'],$valuearray) and count($valuearray)>0)
				{
					$htmlresult.='
					'.$control_name.'_r['.$i.']="'.$row['listing_id'].'";
					'.$control_name.'_v['.$i.']="'.$row[$real_field].'";
                                        '.$control_name.'_p['.$i.']="'.(int)$row['published'].'";
';
					$i++;
				}
			}

			$htmlresult.='
			function '.$control_name.'removeOptions(selectobj)
			{
				for(var i=selectobj.options.length-1;i>=0;i--)
				{
					selectobj.remove(i);
				}
			}
			';

			/*
			function '.$control_name.'removeEmptyParents(selectobj)
			{
				alert("aa")
				var selectobj = document.getElementById("'.$control_name.'_SQLJoinLink");

				for(var i=0;i<selectobj.options.length;i++)
				{
						var r=selectobj.options[i].value;

						for(var x=0;x<'.$control_name.'_r.length;x++)
						{
							if('.$control_name.'_r[x]==r)
								selectobj.remove(i);

						}
				}

			}
			*/

			$htmlresult.='

			function '.$control_name.'addItem(index)
			{
				var o = document.getElementById("'.$control_name.'_selector");
				o.selectedIndex=0;
				';

				if($dynamic_filter!='')
					$htmlresult.='
				var ol = document.getElementById("'.$control_name.'_selectorSQLJoinLink");
				ol.selectedIndex=0;
                                '.$control_name.'_current_value="";
				'.$control_name.'_selectorUpdateSQLJoinLink();
				';

				$htmlresult.='


				var btn = document.getElementById("'.$control_name.'_addButton");
				btn.style.visibility="hidden";

				var box = document.getElementById("'.$control_name.'_addBox");
				box.style.visibility="visible";

			}

			';
			/*
			function '.$control_name.'DeleteExistingItems()
			{
				alert("aa")
				var selectobj = document.getElementById("'.$control_name.'_selector");

				for(var i=0;i<selectobj.options.length;i++)
				{
						var r=selectobj.options[i].value;

						for(var x=0;x<'.$control_name.'_r.length;x++)
						{
							if('.$control_name.'_r[x]==r)
								selectobj.remove(i);

						}
				}


			}
			*/

			$htmlresult.='

			function '.$control_name.'DoAddItem()
			{
				var o = document.getElementById("'.$control_name.'_selector");
				if(o.selectedIndex==-1)
						return;

				var r=o.options[o.selectedIndex].value;
				var t=o.options[o.selectedIndex].text;
                                var p=1;

                                if (typeof arr != "undefined" && (arr instanceof Array))
                                {
                                        for(var i=0;i<'.$control_name.'_selectorelementsPublished.length;i++)
                                        {
                                                if('.$control_name.'_selectorelementsID[i]==r)
                                                        p='.$control_name.'_selectorelementsPublished[i];
                                        }
                                }



				var i='.$control_name.'_r.length;

				for(var x=0;x<'.$control_name.'_r.length;x++)
				{
					if('.$control_name.'_r[x]==r)
					{
						alert("Item already exists");
						return false;
					}
				}

				'.$control_name.'_r[i]=r;
				'.$control_name.'_v[i]=t;
                                '.$control_name.'_p[i]=p;


				//'.$control_name.'cancel();


				o.remove(o.selectedIndex);


				'.$control_name.'showMultibox();

				//'.$control_name.'DeleteExistingItems();
			}

			function '.$control_name.'cancel()
			{


				var btn = document.getElementById("'.$control_name.'_addButton");
				btn.style.visibility="visible";

				var box = document.getElementById("'.$control_name.'_addBox");
				box.style.visibility="hidden";

			}

			function '.$control_name.'deleteItem(index)
			{
				//alert(index);
				'.$control_name.'_r.splice(index,1);
				'.$control_name.'_v.splice(index,1);
                                '.$control_name.'_p.splice(index,1);

				'.$control_name.'showMultibox();
			}

			function '.$control_name.'showMultibox()
			{
				var l = document.getElementById("'.$control_name.'");
				'.$control_name.'removeOptions(l);

                                var opt1 = document.createElement("option");
					opt1.value = 0;
					opt1.innerHTML = "";
					opt1.setAttribute("selected","selected");
                			l.appendChild(opt1);

				var v=\'<table style="width:100%;"><tbody>\';
				for(var i=0;i<'.$control_name.'_r.length;i++)
				{
					v+=\'<tr><td style="border-bottom:1px dotted grey;">\';
                                        if('.$control_name.'_p[i]==0)
                                        {
                  //                              v+=\'<span class="esmultiboxoptiondisabled" style="color:red;">\';
                                                v+='.$control_name.'_v[i];
                    //                            v+=\'</span>\';
                                        }
                                        else
                                        {
                                                v+='.$control_name.'_v[i];
                                        }

                                        v+=\'<td style="border-bottom:1px dotted grey;min-width:16px;"><img src="'.$deleteimage.'" alt="Delete" title="Delete" style="width:16px;height:16px;cursor: pointer;" onClick="'.$control_name.'deleteItem(\'+i+\')" /></td>\';
                                        v+=\'</tr>\';


					var opt = document.createElement("option");
					opt.value = '.$control_name.'_r[i];
					opt.innerHTML = '.$control_name.'_v[i];
                                        opt.style.cssText="color:red;";
					opt.setAttribute("selected","selected");

                                        //if('.$control_name.'_p[i]==0)
                                        //        opt.setAttribute("disabled","disabled");

					l.appendChild(opt);

				}
				v+=\'</tbody></table>\';

				var o = document.getElementById("'.$control_name.'_box");
				o.innerHTML = v;

			}


		</script>
		';

		$value='';
		$single_box='';

		$single_box.=JHTMLESRecords::getSingle($model, $model_nofilter,$SearchResult,$SearchResult_nofilter,$valuearray,$field,$selectorpair,
                                                      $control_name.'_selector',$style,$cssclass,$attribute,'',$establename,$dynamic_filter,$langpostfix);

		$htmlresult.='<div style="padding-bottom:20px;"><div style="width:90%;" id="'.$control_name.'_box"></div>'
		.'<div style="height:30px;">'
			.'<div id="'.$control_name.'_addButton" style="visibility: visible;"><img src="'.JURI::root(true).'/components/com_customtables/images/new.png" alt="Add" title="Add" style="cursor: pointer;" onClick="'.$control_name.'addItem()" /></div>'
			.'<div id="'.$control_name.'_addBox" style="visibility: hidden;">'
				.'<div style="float:left;">'.$single_box.'</div>'
				.'<img src="'.JURI::root(true).'/components/com_customtables/images/plus_13.png" alt="Add" title="Add" style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;width:16px;height:16px;" onClick="'.$control_name.'DoAddItem()" />'
				.'<img src="'.JURI::root(true).'/components/com_customtables/images/cancel_small.png" alt="Cancel" title="Cancel" style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;width:16px;height:16px;" onClick="'.$control_name.'cancel()" />'

			.'</div>'
		.'</div>'
			.'<div style="visibility: hidden;"><select name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE ></select></div>'
		.'</div>

		<script>
			'.$control_name.'showMultibox();
		</script>
		';

		return $htmlresult;

	}
}
