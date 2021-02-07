<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class ESFiltering
{

	var $es;
	var $esfields;
	var $estable;
	var $langpostfix;

	function getWhereExpression($param,&$PathValue)
	{
		$db = JFactory::getDBO();
		$numerical_fields=['int','float','checkbox','viewcount','userid','user','id','sqljoin','article','multilangarticle'];

				$this->es= new CustomTablesMisc;
				$where='';
				
				$items=$this->ExplodeSmartParams($param);
				
				foreach($items as $item)
				{
						if($item[0]=='or' or $item[0]=='and')
						{
							$opr='';

							if(!(strpos($item[1],'<=')===false))
								$opr='<=';
							elseif(!(strpos($item[1],'>=')===false))
								$opr='>=';
							elseif(strpos($item[1],'!==')!==false)
								$opr='!==';
							elseif(!(strpos($item[1],'!=')===false))
								$opr='!=';
							elseif(strpos($item[1],'==')!==false)
								$opr='==';
							elseif(strpos($item[1],'=')!==false)
								$opr='=';
							elseif(!(strpos($item[1],'<')===false))
								$opr='<';
							elseif(!(strpos($item[1],'>')===false))
								$opr='>';

						if($opr!='')
						{

								$whr=JoomlaBasicMisc::csv_explode($opr,$item[1],'"',false);

								if(count($whr)==2)
								{
									$value1=trim($whr[0]);
									$value2=trim($whr[1]);

										if($value1=='_id')
										{
											$fieldrow=array(
																'fieldname' => 'id',
																'type' => '_id',
																'typeparams' => ''
																);
										}
										elseif($value1=='_published')
										{
											$fieldrow=array(
																'fieldname' => 'published',
																'type' => '_published',
																'typeparams' => ''
																);
										}
										else
										{
											$fieldrow=$this->getFieldRowByName($value1,$value2);
											/*
											if($value2=='')
											{
												if(isset($fieldrow['type']) and in_array($fieldrow['type'],$numerical_fields))
													$value2='0';
											}
					*/
										}
								}


								if(count($whr)==2 and $value1!='')// allow empty value2 to check if value set or not
								{
										$whr[0]=$value1;
										$whr[1]=$value2;
										
										$c='';
										if(isset($fieldrow['type']))
										{

										switch($fieldrow['type']	)
										{
												case '_id':

														if($opr=='==')
															$opr='=';

														$vList=explode(',',$value2);
														$cArr=array();
														foreach($vList as $vL)
														{
																$cArr[]='id'.$opr.(int)$vL;

																$PathValue[]='ID '.$opr.' '.(int)$vL;
														}
														if(count($cArr)==1)
																$c=$cArr[0];
														else
																$c='('.implode(' OR ', $cArr).')';

														break;

												case '_published':

														if($opr=='==')
															$opr='=';

														$c='published'.$opr.(int)$value2;

														$PathValue[]='Published '.$opr.' '.(int)$value2;
														$c=$cArr[0];

														break;

												case 'user':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_User($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'userid':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_User($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'usergroup':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_UserGroup($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'int':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_Number($whr, $PathValue, $fieldrow,$opr);
														break;
												
												case 'image':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_Number($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'viewcount':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_Number($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'checkbox':

														if($opr=='==')
															$opr='=';

														$vList=explode(',',$whr[1]);
														$cArr=array();
														foreach($vList as $vL)
														{

																if($vL=='true' or $vL=='1')
																{
																		$cArr[]='es_'.$whr[0].'=1';
																		$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix];
																}
																else
																{
																		$cArr[]='es_'.$whr[0].'=0';

																		$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT').' '.$fieldrow['fieldtitle'.$this->langpostfix];
																}
														}
														if(count($cArr)==1)
																$c=$cArr[0];
														else
																$c='('.implode(' OR ', $cArr).')';

														break;

												case 'range':
														if($opr=='==')
															$opr='=';

														$c=$this->getRangeWhere($fieldrow,$PathValue,$whr[1]);
														break;

												case 'float':
														if($opr=='==')
															$opr='=';

														$c=$this->Search_Number($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'phponadd':

														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'phponchange':

														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'string':

														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'alias':

														$c=$this->Search_Alias($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'md5':

														$c=$this->Search_Alias($whr, $PathValue, $fieldrow,$opr);

														break;

												case 'email':

														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;
												case 'url':

														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'date':

														$c=$this->Search_Date($whr,$PathValue,$opr,$this->estable);
														break;

												case 'creationtime':

														$c=$this->Search_Date($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'changetime':

														$c=$this->Search_Date($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'lastviewtime':

														$c=$this->Search_Date($whr, $PathValue, $fieldrow,$opr);
														break;



												case 'multilangstring':

														$v=str_replace('"','',trim($whr[1]));
														$c='instr(es_'.$whr[0].$this->langpostfix.',"'.$v.'")';

														break;

												case 'customtables':

														if($opr=='==')
															$opr='=';

														$vList=explode(',',$whr[1]);


														$cArr=array();
														foreach($vList as $vL)
														{
																//--------

																$v=trim($vL);
															if($v!='')
															{
															

																//to fix the line
																if($v[0]!=',')
																	$v=','.$v;

																if($v[strlen($v)-1]!='.')
																	$v.='.';


																if($opr=='=')
																{
																	$cArr[]='instr(es_'.$whr[0].',"'.$v.'")';

																	$vTitle=$this->es->getMultyValueTitles($v,$this->langpostfix,1, ' - ');
																	$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].': '.implode(',',$vTitle);
																}

																elseif($opr=='!=')
																{
																	$cArr[]='!instr(es_'.$whr[0].',"'.$v.'")';

																	$vTitle=$this->es->getMultyValueTitles($v,$this->langpostfix,1, ' - ');
																	$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].': '.implode(',',$vTitle);
																}
															}
														}
														
														if(count($cArr)==1)
																$c=$cArr[0];
														else
																$c='('.implode(' OR ', $cArr).')';

														break;

												case 'records':

			

													$vList=explode(',',$this->getString_vL($whr[1]));

														$cArr=array();
														foreach($vList as $vL)
														{
															// Filter Title
															$typeparamsarray=JoomlaBasicMisc::csv_explode(',',$fieldrow['typeparams'],'"',false);

															$filtertitle='';
															if(count($typeparamsarray)<1)
																$filtertitle.='table not specified';

															if(count($typeparamsarray)<2)
																$filtertitle.='field or layout not specified';

															if(count($typeparamsarray)<3)
																$filtertitle.='selector not specified';

															$esr_table=$typeparamsarray[0];
															$esr_table_full=$this->estable;
															$esr_field=$typeparamsarray[1];
															$esr_selector=$typeparamsarray[2];

															if(count($typeparamsarray)>3)
																$esr_filter=$typeparamsarray[3];
															else
																$esr_filter='';


																$filtertitle.=JHTML::_('ESRecordsView.render',
																					   $vL,
																					   $esr_table,
																					   $esr_field,
																					   $esr_selector,
																					   $esr_filter);

															$opt_title='';

															if($esr_selector=='multi' or $esr_selector=='checkbox' or $esr_selector=='multibox')
															{
																if($opr=='!=')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_CONTAINS');
																elseif($opr=='=')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS');
																elseif($opr=='==')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IS');
																elseif($opr=='!==')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ISNOT');
																else
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOW_OPERATION');
															}
															elseif($esr_selector=='radio' or $esr_selector=='single')
																$opt_title=':';


															$vLnew=$this->getInt_vL($vL);

															if($vLnew=='')
															{
																//$cArr[]='es_'.$whr[0].'=""';
																//$cArr[]='es_'.$whr[0].'=",,"';
															}
															else
															{

										
																if($opr=='!=')
																	$cArr[]='!instr('.$esr_table_full.'.es_'.$whr[0].',",'.$vLnew.',")';
																elseif($opr=='!==')
																	$cArr[]=$esr_table_full.'.es_'.$whr[0].'!='.$db->quote(','.$vLnew.',');//not exact value
																elseif($opr=='=')
																	$cArr[]='instr('.$esr_table_full.'.es_'.$whr[0].',",'.$vLnew.',")';
																elseif($opr=='==')
																	$cArr[]=$esr_table_full.'.es_'.$whr[0].'='.$db->quote(','.$vLnew.',');//exact value
																else
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOW_OPERATION' );
																	

																if($opr=='!=' or $opr=='=')
																{
																	$PathValue[]=$fieldrow['fieldtitle'
																			.$this->langpostfix]
																			.' '
																			.$opt_title
																			.' '
																			.$filtertitle;
																}
															}
														}
														if(count($cArr)==1)
																$c=$cArr[0];
														elseif(count($cArr)>1)
																$c='('.implode(' OR ', $cArr).')';
																
																

														break;
												case 'sqljoin':

														if($opr=='==')
															$opr='=';

														$vList=explode(',',$this->getString_vL($whr[1]));
														$cArr=array();

														foreach($vList as $vL)
														{

															// Filter Title
															$typeparamsarray=explode(',',$fieldrow['typeparams']);
															$filtertitle='';
															if(count($typeparamsarray)<1)
																$filtertitle.='table not specified';

															if(count($typeparamsarray)<2)
																$filtertitle.='field or layout not specified';

															$esr_table=$typeparamsarray[0];
															$esr_table_full=$this->estable;
															$esr_field=$typeparamsarray[1];


															if(isset($typeparamsarray[2]))
																$esr_filter=$typeparamsarray[2];
															else
																$esr_filter='';


															$vLnew=$this->getInt_vL($vL);


															$filtertitle.=JHTML::_('ESSQLJoinView.render',
																					   $vL,
																					   $esr_table,
																					   $esr_field,
																					   $esr_filter,
																					   $this->langpostfix);

															$opt_title='';

															if($vLnew!='')
															{

																if($opr=='!=')
																{
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT');

																	$cArr[]=$esr_table_full.'.es_'.$whr[0].'!='.(int)$vLnew;
																	$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix]
																			.' '
																			.$opt_title
																			.' '
																			.$filtertitle;
																}
																elseif($opr=='=')
																{
																	$opt_title=':';

																	$ivLnew=(int)$vLnew;
																	if($ivLnew==0)
																		$cArr[]='('.$esr_table_full.'.es_'.$whr[0].'='.(int)$vLnew.' OR '.$esr_table_full.'.es_'.$whr[0].' IS NULL)';
																	elseif($ivLnew==-1)
																		$cArr[]='('.$esr_table_full.'.es_'.$whr[0].' IS NULL OR '.$esr_table_full.'.es_'.$whr[0].'=0)';
																	else
																		$cArr[]=$esr_table_full.'.es_'.$whr[0].'='.(int)$vLnew;
																	
																	


																	$PathValue[]=$fieldrow['fieldtitle'
																			.$this->langpostfix]
																			.' '
																			.$opt_title
																			.' '
																			.$filtertitle;
																}
															}
														}
														
														if(count($cArr)==1)
																$c=$cArr[0];
														elseif(count($cArr)>1)
																$c='('.implode(' OR ', $cArr).')';

													break;

												case 'text':
														$c=$this->Search_String($whr, $PathValue, $fieldrow,$opr);
														break;

												case 'multilangtext':
															$wrh2=array($whr[0].$this->langpostfix,$v);
															$c=$this->Search_String($whr2, $PathValue, $fieldrow,$opr);
													break;

										}

										if($c!='')
										{
												if($where!='')
														$where.=' '.strtoupper($item[0]).' ';

												$where.=$c;
										}



										}//isset
								}
						}


						}//if($item[0]=='or' or $item[0]=='and')

				}


				return $where;
	}//function getWhereExpression($param,&$PathValue)


	function Search_Date(&$whr, &$PathValue, $opr,$esr_table_full)
	{
		$fieldrow1=$this->getFieldRowByName($whr[0]);
		$title1='';
		if(!$fieldrow1)
			$title1=$fieldrow1['fieldtitle'.$this->langpostfix];
		else
			$title1=$whr[0];

		if(!isset($whr[1]))
			return '';
		

		$fieldrow2=$this->getFieldRowByName($whr[1]);

		$title2='';
		if($fieldrow2)
			$title2=$fieldrow2['fieldtitle'.$this->langpostfix];
		else
			$title2=$whr[1];

		$db = JFactory::getDBO();
		
		//Breadcrumbs
		$PathValue[]=$title1.' '.$opr.' '.$title2;

		$value1=$this->processDateSearchTags($whr[0],$fieldrow1,$esr_table_full);
		$value2=$this->processDateSearchTags($whr[1],$fieldrow2,$esr_table_full);

		if($value2=='NULL' and $opr=='=')
			$query=$value1.' IS NULL';
		else
			$query=$value1.' '.$opr.' '.$value2;
		
		return $query;
	}

	function processDateSearchTags($value,$fieldrow,$esr_table_full)
	{
		$v=str_replace('"','',$value);
		$v=str_replace("'",'',$v);
		$v=str_replace('/','',$v);
		$v=str_replace('\\','',$v);
		$value=str_replace('&','',$v);

		$db = JFactory::getDBO();

		if($fieldrow)
		{
			//field
			$options=explode(':',$value);

			if(isset($options[1]) and $options[1]!='')
			{
				$option=trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[1]));
				//https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
			    return 'DATE_FORMAT('.$esr_table_full.'.es_'.$fieldrow['fieldname'].', '.$db->quote($option).')';//%m/%d/%Y %H:%i
			}
			else
				return $esr_table_full.'.es_'.$fieldrow['fieldname'];

		}
		else
		{
			//value
			if($value=='{year}')
				return 'year()';

			if($value=='{month}')
				return 'month()';

			if($value=='{day}')
				return 'day()';

			if(trim(strtolower($value))=="null")
				return 'NULL';

			$options=array();
			$fList=JoomlaBasicMisc::getListToReplace('now',$options,$value,'{}');

			$i=0;

			foreach($fList as $fItem)
			{
				$option=trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $options[$i]));

				//https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html#function_date-format
				if($option!='')
					$v='DATE_FORMAT(now(), '.$db->quote($option).')';//%m/%d/%Y %H:%i
				else
					$v='now()';

				$value=str_replace($fItem,$v,$value);
				$i++;
			}

			if(count($fList)>0 or trim(strtolower($value))=="null")
				return $value;
			else

				return '"'.$value.'"';//$db->quotes($value);
		}

	}

	function getInt_vL($vL)
	{
		if(strpos($vL,'$get_')!==false)
		{
			$getPar=str_replace('$get_','',$vL);
			$a=JFactory::getApplication()->input->get($getPar,'','CMD');
			if($a=='')
				return '';
			return JFactory::getApplication()->input->getInt($getPar);
		}

		return $vL;
	}

	function getString_vL($vL)
	{
		if(strpos($vL,'$get_')!==false)
		{
			$getPar=str_replace('$get_','',$vL);
			//$v=JFactory::getApplication()->input->get($getPar,'','STRING');
			$v=(string)preg_replace('/[^A-Z0-9_\.,-]/i', '', JFactory::getApplication()->input->getString($getPar));
		}
		else
			$v=$vL;
			
		$v=str_replace('$','',$v);
		$v=str_replace('"','',$v);
		$v=str_replace("'",'',$v);
		$v=str_replace('/','',$v);
		$v=str_replace('\\','',$v);
		$v=str_replace('&','',$v);
		$v=str_replace('.','',$v);

		return $v;
	}

	function getCmd_vL($vL)
	{

		if(strpos($vL,'$get_')!==false)
		{
			$getPar=str_replace('$get_','',$vL);
			return JFactory::getApplication()->input->get($getPar,'','CMD');
		}

		return $vL;
	}


	function Search_String(&$whr, &$PathValue, &$fieldrow,$opr)
	{
		
		$db = JFactory::getDBO();

		$field='es_'.$whr[0];
		$v=$this->getString_vL($whr[1]);
		
		$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$opr;

		if($opr=='=' and $v!="")
		{
			$vList=explode(',',$v);
			$cArr=array();
			foreach($vList as $vL)
			{
				//this method breaks search sentance to words and creates the LIKE where filter
				$new_v_list=array();
				$v_list=explode(' ',$vL);
				foreach($v_list as $vl)
					$new_v_list[]=$db->quoteName($field).' LIKE '.$db->quote('%'.$vl.'%');


				$cArr[]='('.implode(' AND ',$new_v_list).')';
				/*
				other possible serach options
				return 'instr(es_'.$whr[0].',"'.$v.'")'; not usefull
				return 'MATCH('.$db->quoteName('es_'.$whr[0]).') AGAINST ('.$db->quote('%'.$vl.'%').' IN NATURAL LANGUAGE MODE)'; requires mysql 5.6
				*/
			}
			return '('.implode(' OR ',$cArr).')';
		}
		else
		{
			//search exactly what requested
			if($opr=='==')
				$opr='=';

			if($v=='' and $opr=='=')
				$where='('.$db->quoteName($field).' IS NULL OR '.$db->quoteName($field).'="")';
			elseif($v=='' and $opr=='!=')
				$where='('.$db->quoteName($field).' IS NOT NULL AND '.$db->quoteName($field).'!="")';
			else
				$where=$db->quoteName($field).$opr.$db->quote($v);
			
			return $where;
		}
	}

	function Search_Number(&$whr, &$PathValue, &$fieldrow,$opr)
	{
		if($opr=='==')
				$opr='=';

		$v=$this->getString_vL($whr[1]);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				$cArr[]='es_'.$whr[0].''.$opr.(int)$vL;
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$opr.' '.(int)$vL;
			}
		}
		
		if(count($cArr)==0)
			return '';
		
		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_Alias(&$whr, &$PathValue, &$fieldrow,$opr)
	{
		if($opr=='==')
				$opr='=';

		$db = JFactory::getDBO();

		$v=$this->getString_vL($whr[1]);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL=="null" and $opr=='=')
				$cArr[]='(es_'.$whr[0].'="" OR es_'.$whr[0].' IS NULL)';
			else
				$cArr[]='es_'.$whr[0].''.$opr.$db->quote($vL);

			$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$opr.' '.$vL;
		}

		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}



	function Search_UserGroup(&$whr, &$PathValue, &$fieldrow,$opr)
	{

		$v=$this->getString_vL($whr[1]);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				$cArr[]='es_'.$whr[0].''.$opr.(int)$vL;
				$filtertitle=JHTML::_('ESUserGroupView.render',  $vL);
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$opr.' '.$filtertitle;
			}
		}
		
		if(count($cArr)==0)
			return '';
		elseif(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_User(&$whr, &$PathValue, &$fieldrow,$opr)
	{
		$v=$this->getString_vL($whr[1]);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				if((int)$vL==0 and $opr=='=')
					$cArr[]='(es_'.$whr[0].'=0 OR es_'.$whr[0].' IS NULL)';
				else
					$cArr[]='es_'.$whr[0].''.$opr.(int)$vL;
			
				$filtertitle=JHTML::_('ESUserView.render',  $vL);
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$opr.' '.$filtertitle;
			}
		}
		
		if(count($cArr)==0)
			return '';
		elseif(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function ExplodeSmartParams($param)
	{
				$items=array();

				$a=JoomlaBasicMisc::csv_explode(' and ',$param,'"',true);
				foreach($a as $b)
				{
						$c=JoomlaBasicMisc::csv_explode(' or ',$b,'"',true);

						if(count($c)==1)
								$items[]=array('and', $b);
						else
						{
							foreach($c as $d)
								$items[]=array('or', $d);
						}
				}

				return $items;

		}//function ExplodeSmartParams($param)

	function getRangeWhere(&$fieldrow,&$PathValue,$value)
		{


				$fieldTitle=$fieldrow['fieldtitle'.$this->langpostfix];

				if($fieldrow['typeparams']=='date')
					$valuearr=explode('-to-',$value);
				else
					$valuearr=explode('-',$value);

				if($valuearr[0]=='' and $valuearr[1]=='')
						return '';

				if($fieldrow['typeparams']=='date')
				{
					$valuearr_new[0]='"'.$valuearr[0].'"';
					$valuearr_new[1]='"'.$valuearr[1].'"';
				}
				else
				{
					$valuearr_new[0]=(float)$valuearr[0];
					$valuearr_new[1]=(float)$valuearr[1];
				}

				$range=explode('_r_',$fieldrow['fieldname']);
				if(count($range)==1)
						return '';

				$valueTitle='';
				$rangewhere='';

				$from_field='';
				$to_field='';
				if(isset($range[0]))
				{
					$from_field=$range[0];
					if(isset($range[1]) and $range[1]!='')
						$to_field=$range[1];
					else
						$to_field=$from_field;
				}


				if($from_field=='' and $to_field=='')
					return '';

				$innerwherearray='';

				if($fieldrow['typeparams']=='date')
				{
					$v_min='"'.$valuearr[0].'"';
					$v_max='"'.$valuearr[1].'"';
				}
				else
				{
					$v_min=(float)$valuearr[0];
					$v_max=(float)$valuearr[1];
				}

				if($valuearr[0]!='' and $valuearr[1]!='')
					$rangewhere='(es_'.$from_field.'>='.$v_min.' AND es_'.$to_field.'<='.$v_max.')';
				elseif($valuearr[0]!='' and $valuearr[1]=='' )
					$rangewhere='(es_'.$from_field.'>='.$v_min.')';
				elseif($valuearr[1]!='' and $valuearr[0]=='' )
					$rangewhere='(es_'.$from_field.'<='.$v_max.')';

				if($rangewhere=='')
					return '';

				if($valuearr[0]!='')
						$valueTitle.=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FROM').' '.$valuearr[0].' ';
	
				if($valuearr[1]!='')
						$valueTitle.=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_TO').' '.$valuearr[1];
	
				$PathValue[]=$fieldTitle.': '.$valueTitle;

				if(count($rangewhere)>0)
					return $rangewhere;

				return '';
		}
	function getFieldRowByName($value1)
	{
		$parts=explode(':',$value1);
		
		$second_parts=explode('_',$parts[0]);
		$fieldname1=trim(preg_replace("/[^a-zA-Z,\-_=]/", "", $second_parts[0]));

		//normal fields
		foreach($this->esfields as $fld)
		{
			if($fld['fieldname']==$fieldname1)
				return $fld;
		}
		return false;
	}
}//end class

class LinkJoinFilters
{
	static public function getFilterBox($establename,$dynamic_filter,$control_name,$filtervalue)
	{
		$field=$dynamic_filter;
		$db = JFactory::getDBO();
		$typeparams='';
		$fieldtype=LinkJoinFilters::getFilterFieldType($establename,$field,$typeparams);
		if($fieldtype='sqljoin')
			return LinkJoinFilters::getFilterElement_SqlJoin($typeparams,$control_name,$filtervalue);

		return '';
	}

	static protected function getFilterElement_SqlJoin($typeparams,$control_name,$filtervalue)
	{
		$result='';

		$pair=explode(',',$typeparams);

		$tablename=$pair[0];
		if(isset($pair[1]))
			$field=$pair[1];
		else
			return '<p style="color:white;background-color:red;">sqljoin: field not set</p>';

		$db = JFactory::getDBO();

		$query = 'SELECT id, es_'.$field
		.' FROM #__customtables_table_'.$tablename.' WHERE published=1 ORDER BY es_'.$field;

		$db->setQuery($query);

		$records=$db->loadAssocList();
		$result.='
		<script>
		function '.$control_name.'removeOptions(selectobj)
		{
			var i;
			for(i=selectobj.options.length-1;i>=0;i--)
			{
				selectobj.remove(i);
			}
		}

		function '.$control_name.'removeEmptyParents()
		{
			var selectobj = document.getElementById("'.$control_name.'SQLJoinLink");
			for(var o=selectobj.options.length-1;o>=0;o--)
			{
				c=0;
				var v=selectobj.options[o].value;



				for (var i = 0; i<'.$control_name.'elementsFilter.length; i++)
				{
					var f='.$control_name.'elementsFilter[i];
					if(typeof f!="undefined")
					{
						if(f==v)
							c++;
						else
						{
							if(f.indexOf(","+v+",")!=-1)
								c++;
						}

					}
				}
			}
		}

		function '.$control_name.'UpdateSQLJoinLink()
		{
			setTimeout('.$control_name.'UpdateSQLJoinLink_do, 100);
		}
		
		var '.$control_name.'_current_value="";
		
		function '.$control_name.'UpdateSQLJoinLink_do()
		{
			var l = document.getElementById("'.$control_name.'");
			var o = document.getElementById("'.$control_name.'SQLJoinLink");
			if(o.selectedIndex==-1)
				return;
				
			var v=o.options[o.selectedIndex].value;

			var selectedValue='.$control_name.'_current_value;
			'.$control_name.'removeOptions(l);

			';
			if(strpos($control_name,'_selector')===false)
			{
				$result.='
			var opt = document.createElement("option");
			opt.value = 0;
			opt.innerHTML = "- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT').'";
			l.appendChild(opt);
			';
			}
			$result.='
			for (var i = 0; i<='.$control_name.'elements.length; i++)
			{
				var f='.$control_name.'elementsFilter[i];
				if(typeof f!="undefined" && '.$control_name.'elements[i]!="")
				{
					var eid='.$control_name.'elementsID[i];
					var published='.$control_name.'elementsPublished[i];


					if(f==v)
					{
							var opt = document.createElement("option");
							opt.value = eid;
							if(eid==selectedValue)
								opt.selected = true;

							if(published==0)
								opt.style.cssText="color:red;";

							opt.innerHTML = '.$control_name.'elements[i];
							l.appendChild(opt);
					}else
					{
						if(f.indexOf(","+v+",")!=-1)
						{
							var opt = document.createElement("option");
							opt.value = eid;
							if(eid==selectedValue)
								opt.selected = true;

							if(published==0)
								opt.style.cssText="color:red;";


							opt.innerHTML = '.$control_name.'elements[i];
							l.appendChild(opt);
						}
					}
				}
			}
		}
		</script>
		';


		$result.='<select id="'.$control_name.'SQLJoinLink" onchange="'.$control_name.'UpdateSQLJoinLink()">';
		foreach($records as $row)
		{
			if($row['id']==$filtervalue or strpos($filtervalue,','.$row['id'].',')!==false)
				$result.='<option value="'.$row['id'].'" selected>'.$row['es_'.$field].'</option>';
			else
				$result.='<option value="'.$row['id'].'">'.$row['es_'.$field].'</option>';
		}
		$result.='</select>
';

		return $result;
	}

	static protected function getFilterFieldType($tablename,$field,&$typeparams)
	{
		$db = JFactory::getDBO();

		$query = 'SELECT type, typeparams'
		.' FROM #__customtables_tables'
		.' INNER JOIN #__customtables_fields ON tableid=#__customtables_tables.id AND #__customtables_fields.published=1'
		.' WHERE tablename="'.$tablename.'" AND fieldname="'.$field.'"';

		$db->setQuery($query);
//		if (!$db->query())    die( $db->stderr());

		$records=$db->loadObjectList();
		if(count($records)==0)
			return '';

		$records=$db->loadObjectList();
		$row=$records[0];
		$typeparams=$row->typeparams;
		return $row->type;
	}
}
