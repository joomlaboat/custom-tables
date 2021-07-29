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
		$wheres=[];
				
		$items=$this->ExplodeSmartParams($param);
				
		foreach($items as $item)
		{
			$logic_operator = $item[0];
			$comparison_operator_str = $item[1];
			$comparison_operator='';
			$multy_field_where=[];
			
			if($logic_operator=='or' or $logic_operator=='and')
			{
				$opr='';

				if(!(strpos($comparison_operator_str,'<=')===false))
					$comparison_operator='<=';
				elseif(!(strpos($comparison_operator_str,'>=')===false))
					$comparison_operator='>=';
				elseif(strpos($comparison_operator_str,'!==')!==false)
					$comparison_operator='!==';
				elseif(!(strpos($comparison_operator_str,'!=')===false))
					$comparison_operator='!=';
				elseif(strpos($comparison_operator_str,'==')!==false)
					$comparison_operator='==';
				elseif(strpos($comparison_operator_str,'=')!==false)
					$comparison_operator='=';
				elseif(!(strpos($comparison_operator_str,'<')===false))
					$comparison_operator='<';
				elseif(!(strpos($comparison_operator_str,'>')===false))
					$comparison_operator='>';

				if($comparison_operator!='')
				{
					$whr=JoomlaBasicMisc::csv_explode($comparison_operator,$comparison_operator_str,'"',false);
					
					if(count($whr)==2)
					{
						$fieldnames_string=trim(preg_replace("/[^a-zA-Z,\-_;]/", "",trim($whr[0])));

						$fieldnames = explode(';',$fieldnames_string);
						$value = trim($whr[1]);
						
						foreach($fieldnames as $fieldname)
						{
							$fieldrow = array();
							
							if($fieldname=='_id')
							{
								$fieldrow=array(
									'fieldname' => '_id',
									'type' => '_id',
									'typeparams' => '',
									'realfieldname' => 'id'
								);
							}
							elseif($fieldname=='_published')
							{
								$fieldrow=array(
									'fieldname' => '_published',
									'type' => '_published',
									'typeparams' => '',
									'realfieldname' => 'published'
								);
							}
							else
								$fieldrow = ESFields::FieldRowByName($fieldname,$this->esfields);

							if(count($fieldrow)>0)
								$multy_field_where[] = $this->processSingleFieldWhereSyntax($fieldrow,$comparison_operator,$fieldname,$value,$PathValue);
						}
					}
				}
			}
			
			if(count($multy_field_where)==1)
				$wheres[]=implode(' OR ',$multy_field_where);
			elseif(count($multy_field_where)>1)
				$wheres[]='('.implode(' OR ',$multy_field_where).')';
		}

		return implode(' '.$logic_operator.' ',$wheres);
	}//function getWhereExpression($param,&$PathValue)


	function processSingleFieldWhereSyntax(&$fieldrow,$comparison_operator,$fieldname,$value,&$PathValue)
	{
		$db = JFactory::getDBO();
		
		$c = '';
		
		$realfieldname = $fieldrow['realfieldname'];
		
		switch($fieldrow['type']	)
										{
												case '_id':

														if($comparison_operator=='==')
															$comparison_operator='=';

														$vList=explode(',',$value);
														$cArr=array();
														foreach($vList as $vL)
														{
																$cArr[]='id'.$comparison_operator.(int)$vL;

																$PathValue[]='ID '.$comparison_operator.' '.(int)$vL;
														}
														if(count($cArr)==1)
																$c=$cArr[0];
														else
																$c='('.implode(' OR ', $cArr).')';

														break;

												case '_published':

														if($comparison_operator=='==')
															$comparison_operator='=';

														$c='published'.$comparison_operator.(int)$value;

														$PathValue[]='Published '.$comparison_operator.' '.(int)$value;
														$c=$cArr[0];

														break;

												case 'user':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_User($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'userid':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_User($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'usergroup':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_UserGroup($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'int':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $PathValue, $fieldrow,$comparison_operator);
														break;
												
												case 'image':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'viewcount':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'checkbox':

														if($comparison_operator=='==')
															$comparison_operator='=';

														$vList=explode(',',$value);
														$cArr=array();
														foreach($vList as $vL)
														{

																if($vL=='true' or $vL=='1')
																{
																		$cArr[]=$fieldrow['realfieldname'].'=1';
																		$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix];
																}
																else
																{
																		$cArr[]=$fieldrow['realfieldname'].'=0';

																		$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT').' '.$fieldrow['fieldtitle'.$this->langpostfix];
																}
														}
														if(count($cArr)==1)
																$c=$cArr[0];
														else
																$c='('.implode(' OR ', $cArr).')';

														break;

												case 'range':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->getRangeWhere($fieldrow,$PathValue,$value);
														break;

												case 'float':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'phponadd':

														$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'phponchange':

														$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'string':

														$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'alias':

														$c=$this->Search_Alias($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'md5':

														$c=$this->Search_Alias($value, $PathValue, $fieldrow,$comparison_operator);

														break;

												case 'email':

														$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
														break;
												case 'url':

														$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'date':

														$c=$this->Search_Date($fieldname,$value,$PathValue,$comparison_operator,$this->estable);
														break;

												case 'creationtime':

														$c=$this->Search_Date($fieldname,$value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'changetime':

														$c=$this->Search_Date($fieldname,$value, $PathValue, $fieldrow,$comparison_operator);
														break;

												case 'lastviewtime':

														$c=$this->Search_Date($fieldname,$value, $PathValue, $fieldrow,$comparison_operator);
														break;



												case 'multilangstring':

														$v=str_replace('"','',$value);
														if($db->serverType == 'postgresql')
															$c='POSITION('.$db->quote($v).' IN '.$fieldrow['realfieldname'].$this->langpostfix.')>0';
														else
															$c='instr('.$realfieldname.$this->langpostfix.','.$db->quote($v).')';

														break;

												case 'customtables':

														if($comparison_operator=='==')
															$comparison_operator='=';

														$vList=explode(',',$value);


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


																if($comparison_operator=='=')
																{
																	$cArr[]='instr('.$fieldrow['realfieldname'].','.$db->quote($v).')';

																	$vTitle=$this->es->getMultyValueTitles($v,$this->langpostfix,1, ' - ');
																	$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].': '.implode(',',$vTitle);
																}

																elseif($comparison_operator=='!=')
																{
																	$cArr[]='!instr('.$fieldrow['realfieldname'].','.$db->quote($v).')';

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

													$vList=explode(',',$this->getString_vL($value));
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
																if($comparison_operator=='!=')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_CONTAINS');
																elseif($comparison_operator=='=')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS');
																elseif($comparison_operator=='==')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IS');
																elseif($comparison_operator=='!==')
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ISNOT');
																else
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOW_OPERATION');
															}
															elseif($esr_selector=='radio' or $esr_selector=='single')
																$opt_title=':';


															$vLnew=$this->getInt_vL($vL);

															if($vLnew=='')
															{

															}
															else
															{

										
																if($comparison_operator=='!=')
																	$cArr[]='!instr('.$esr_table_full.'.'.$fieldrow['realfieldname'].','.$db->quote(','.$vLnew.',').')';
																elseif($comparison_operator=='!==')
																	$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'!='.$db->quote(','.$vLnew.',');//not exact value
																elseif($comparison_operator=='=')
																	$cArr[]='instr('.$esr_table_full.'.'.$fieldrow['realfieldname'].','.$db->quote(','.$vLnew.',').')';
																elseif($comparison_operator=='==')
																	$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'='.$db->quote(','.$vLnew.',');//exact value
																else
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNKNOW_OPERATION' );
																	

																if($comparison_operator=='!=' or $comparison_operator=='=')
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

														if($comparison_operator=='==')
															$comparison_operator='=';

														$vList=explode(',',$this->getString_vL($value));
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

																if($comparison_operator=='!=')
																{
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT');

																	$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'!='.(int)$vLnew;
																	$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix]
																			.' '
																			.$opt_title
																			.' '
																			.$filtertitle;
																}
																elseif($comparison_operator=='=')
																{
																	$opt_title=':';

																	$ivLnew=(int)$vLnew;
																	if($ivLnew==0)
																		$cArr[]='('.$esr_table_full.'.'.$fieldrow['realfieldname'].'='.(int)$vLnew.' OR '.$esr_table_full.'.'.$fieldrow['realfieldname'].' IS NULL)';
																	elseif($ivLnew==-1)
																		$cArr[]='('.$esr_table_full.'.'.$fieldrow['realfieldname'].' IS NULL OR '.$esr_table_full.'.'.$fieldrow['realfieldname'].'=0)';
																	else
																		$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'='.(int)$vLnew;
																	
																	


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
													$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator);
													break;

												case 'multilangtext':
													$c=$this->Search_String($value, $PathValue, $fieldrow,$comparison_operator,$this->langpostfix);
													break;

										}
		return $c;
	}

	function Search_Date($fieldname, $value, &$PathValue, $comparison_operator,$esr_table_full)
	{
		$fieldrow1 = ESFields::FieldRowByName($fieldname,$this->esfields);

		$title1='';
		if(count($fieldrow1)>0)
		{
			$title1=$fieldrow1['fieldtitle'.$this->langpostfix];
		}
		else
			$title1=$fieldname;

		$fieldrow2 = ESFields::FieldRowByName($value,$this->esfields);

		$title2='';
		if(count($fieldrow2)>0)
			$title2=$fieldrow2['fieldtitle'.$this->langpostfix];
		else
			$title2=$value;

		$db = JFactory::getDBO();
		
		//Breadcrumbs
		$PathValue[]=$title1.' '.$comparison_operator.' '.$title2;

		$value1=$this->processDateSearchTags($fieldname,$fieldrow1,$esr_table_full);
		$value2=$this->processDateSearchTags($value,$fieldrow2,$esr_table_full);

		if($value2=='NULL' and $comparison_operator=='=')
			$query=$value1.' IS NULL';
		else
			$query=$value1.' '.$comparison_operator.' '.$value2;
		
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
			    return 'DATE_FORMAT('.$esr_table_full.'.'.$fieldrow['realfieldname'].', '.$db->quote($option).')';//%m/%d/%Y %H:%i
			}
			else
				return $esr_table_full.'.'.$fieldrow['realfieldname'];
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

			if(trim(strtolower($value))=='null')
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

				return $db->quote($value);
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

	function Search_String($value, &$PathValue, &$fieldrow,$comparison_operator,$langpostfix = '')
	{
		$db = JFactory::getDBO();

		$realfieldname=$fieldrow['realfieldname'].$langpostfix;
		
		$v=$this->getString_vL($value);
		
		$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$comparison_operator;

		if($comparison_operator=='=' and $v!="")
		{
			$vList=explode(',',$v);
			$cArr=array();
			foreach($vList as $vL)
			{
				//this method breaks search sentance to words and creates the LIKE where filter
				$new_v_list=array();
				$v_list=explode(' ',$vL);
				foreach($v_list as $vl)
				{
					
					if($db->serverType == 'postgresql')
						$new_v_list[]='CAST ( '.$db->quoteName($realfieldname).' AS text ) LIKE '.$db->quote('%'.$vl.'%');
					else
						$new_v_list[]=$db->quoteName($realfieldname).' LIKE '.$db->quote('%'.$vl.'%');
				}

				if(count($new_v_list)>1)
					$cArr[]='('.implode(' AND ',$new_v_list).')';
				else
					$cArr[]=implode(' AND ',$new_v_list);
			}

			if(count($cArr)>1)
				return '('.implode(' OR ',$cArr).')';
			else
				return implode(' OR ',$cArr);
				
		}
		else
		{
			//search exactly what requested
			if($comparison_operator=='==')
				$comparison_operator='=';

			if($v=='' and $comparison_operator=='=')
				$where='('.$db->quoteName($realfieldname).' IS NULL OR '.$db->quoteName($realfieldname).'='.$db->quote('').')';
			elseif($v=='' and $comparison_operator=='!=')
				$where='('.$db->quoteName($realfieldname).' IS NOT NULL AND '.$db->quoteName($realfieldname).'!='.$db->quote('').')';
			else
				$where=$db->quoteName($realfieldname).$comparison_operator.$db->quote($v);
			
			return $where;
		}
	}

	function Search_Number($value, &$PathValue, &$fieldrow,$comparison_operator)
	{
		if($comparison_operator=='==')
				$comparison_operator='=';

		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				$cArr[]=$fieldrow['realfieldname'].$comparison_operator.(int)$vL;
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$comparison_operator.' '.(int)$vL;
			}
		}
		
		if(count($cArr)==0)
			return '';
		
		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' OR ', $cArr).')';
	}

	function Search_Alias($value, &$PathValue, &$fieldrow,$comparison_operator)
	{
		if($comparison_operator=='==')
				$comparison_operator='=';

		$db = JFactory::getDBO();

		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL=="null" and $comparison_operator=='=')
				$cArr[]='('.$fieldrow['realfieldname'].'='.$db->quote('').' OR '.$fieldrow['realfieldname'].' IS NULL)';
			else
				$cArr[]=$fieldrow['realfieldname'].$comparison_operator.$db->quote($vL);

			$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$comparison_operator.' '.$vL;
		}

		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_UserGroup($value, &$PathValue, &$fieldrow,$comparison_operator)
	{
		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				$cArr[]=$fieldrow['realfieldname'].$comparison_operator.(int)$vL;
				$filtertitle=JHTML::_('ESUserGroupView.render',  $vL);
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$comparison_operator.' '.$filtertitle;
			}
		}
		
		if(count($cArr)==0)
			return '';
		elseif(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_User($value, &$PathValue, &$fieldrow,$comparison_operator)
	{
		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL!='')
			{
				if((int)$vL==0 and $comparison_operator=='=')
					$cArr[]='('.$fieldrow['realfieldname'].'=0 OR '.$fieldrow['realfieldname'].' IS NULL)';
				else
					$cArr[]=$fieldrow['realfieldname'].$comparison_operator.(int)$vL;
			
				$filtertitle=JHTML::_('ESUserView.render',  $vL);
				$PathValue[]=$fieldrow['fieldtitle'.$this->langpostfix].' '.$comparison_operator.' '.$filtertitle;
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
			$valuearr_new[0]=$db->quote($valuearr[0]);
			$valuearr_new[1]=$db->quote($valuearr[1]);
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
			$v_min=$db->quote($valuearr[0]);
			$v_max=$db->quote($valuearr[1]);
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

}//end class

class LinkJoinFilters
{
	static public function getFilterBox($establename,$dynamic_filter_fieldname,$control_name,$filtervalue)
	{
		$db = JFactory::getDBO();
		
		$fieldrow=ESFields::getFieldRowByName($dynamic_filter_fieldname, $tableid=0,$establename);
		
		if($fieldrow->type=='sqljoin' or $fieldrow->type=='records')
			return LinkJoinFilters::getFilterElement_SqlJoin($fieldrow->typeparams,$control_name,$filtervalue);

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
			
		$tablerow = ESTables::getTableRowByNameAssoc($tablename);
		if(!is_array($tablerow))
			return '<p style="color:white;background-color:red;">sqljoin: table "'.$tablename.'" not found</p>';

		$fieldrow=ESFields::getFieldRowByName($field, $tablerow['id']);
		if(!is_object($fieldrow))
			return '<p style="color:white;background-color:red;">sqljoin: field "'.$field.'" not found</p>';
			
		$db = JFactory::getDBO();
		$where = '';
		if($tablerow['published_field_found'])
			$where = 'WHERE published=1';

		$query = 'SELECT '.$tablerow['query_selects'].' FROM '.$tablerow['realtablename'].' '.$where.' ORDER BY '.$fieldrow->realfieldname;

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
		$result.='<option value="">- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';
		
		foreach($records as $row)
		{
			if($row['listing_id']==$filtervalue or strpos($filtervalue,','.$row['listing_id'].',')!==false)
				$result.='<option value="'.$row['listing_id'].'" selected>'.$row[$fieldrow->realfieldname].'</option>';
			else
				$result.='<option value="'.$row['listing_id'].'">'.$row[$fieldrow->realfieldname].'</option>';
		}
		$result.='</select>
';

		return $result;
	}
}
