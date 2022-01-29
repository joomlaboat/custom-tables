<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\DataTypes\Tree;

use \ESTables;
use \JoomlaBasicMisc;
use \LayoutProcessor;

use \Joomla\CMS\Factory;

use \JHTML;

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

class Filtering
{
	var $ct;
	var $PathValue;
	var $where;
	var $showpublished;
	
	function __construct(&$ct, $showpublished = 0)
	{
		$this->ct = $ct;
		$this->PathValue = [];
		$this->where = [];
		$this->showpublished = $showpublished;
		
		if($this->ct->Table->published_field_found)
		{
			//$showpublished = 0 - show published
			//$showpublished = 1 - show unpublished
			//$showpublished = 2 - show any
			
			if($this->showpublished==1)
				$this->where[] = $this->ct->Table->realtablename.'.published=0';
			elseif($this->showpublished!=2)
				$this->where[] = $this->ct->Table->realtablename.'.published=1';
		}
	}
	
	function addMenuParamFilter()
	{
		if($this->ct->Env->menu_params->get( 'filter' ) !== null)
		{
			$filter_string = $this->ct->Env->menu_params->get( 'filter' );

			//Parse using layout, has no effect to layout itself
			$filter_string = LayoutProcessor::applyContentPlugins($filter_string);
			$filter_string = $this->sanitizeAndParseFilter($filter_string, true);
			
			if($filter_string!='')
				$this->addWhereExpression($filter_string);
		}
	}
	
	function addQueryWhereFilter()
	{
		if($this->ct->Env->jinput->get('where','','BASE64'))
		{
			$decodedurl = $this->ct->Env->jinput->get('where','','BASE64');
			$decodedurl=urldecode($decodedurl);
			$decodedurl=str_replace(' ','+',$decodedurl);
			$filter_string = $this->sanitizeAndParseFilter(base64_decode($decodedurl));
			
			if($filter_string!='')
				$this->addWhereExpression($filter_string);
		}
	}
	
	function addWhereExpression(string $param)
	{
		if($param == '')
			return;
			
		$db = Factory::getDBO();
		$numerical_fields=['int','float','checkbox','viewcount','userid','user','id','sqljoin','article','multilangarticle'];

		$wheres=[];
				
		$items=$this->ExplodeSmartParams($param);
				
		$logic_operator = '';
		
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
						$fieldnames_string=trim(preg_replace("/[^a-zA-Z,:\-_;]/", "",trim($whr[0])));

						$fieldnames = explode(';',$fieldnames_string);
						$value = trim($whr[1]);
						
						foreach($fieldnames as $fieldname_)
						{
							$fieldname_parts = explode(':',$fieldname_);
							$fieldname = $fieldname_parts[0];
							$field_extra_param = '';
							if(isset($fieldname_parts[1]))
								$field_extra_param = $fieldname_parts[1];
							
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
							{
								$fieldrow = Fields::FieldRowByName($fieldname,$this->ct->Table->fields);
							}

							if(count($fieldrow)>0)
							{
								$w = $this->processSingleFieldWhereSyntax($fieldrow,$comparison_operator,$fieldname,$value,$field_extra_param);
								if($w!='')
									$multy_field_where[] = $w;
							}
						}
					}
				}
			}
			
			if(count($multy_field_where)==1)
				$wheres[]=implode(' OR ',$multy_field_where);
			elseif(count($multy_field_where)>1)
				$wheres[]='('.implode(' OR ',$multy_field_where).')';
		}
		
		if($logic_operator =='')
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Search parameter "'.$param.'" is incorrect'), 'error');
			return;
		}

		if(count($wheres) > 0)
		{
			if($logic_operator == 'or' and count($wheres) > 1)
				$this->where[] = '('.implode(' '.$logic_operator.' ',$wheres).')';
			else
				$this->where[] = implode(' '.$logic_operator.' ',$wheres);
		}
	}

	function processSingleFieldWhereSyntax(&$fieldrow,$comparison_operator,$fieldname,$value,$field_extra_param = '')
	{
		$db = Factory::getDBO();
		
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

																$this->PathValue[]='ID '.$comparison_operator.' '.(int)$vL;
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

														$this->PathValue[]='Published '.$comparison_operator.' '.(int)$value;
														$c=$cArr[0];

														break;

												case 'user':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_User($value, $fieldrow,$comparison_operator, $field_extra_param);
														break;

												case 'userid':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_User($value, $fieldrow,$comparison_operator, $field_extra_param);
														break;

												case 'usergroup':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_UserGroup($value, $fieldrow,$comparison_operator);
														break;

												case 'int':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $fieldrow,$comparison_operator);
														break;
												
												case 'image':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $fieldrow,$comparison_operator);
														break;

												case 'viewcount':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $fieldrow,$comparison_operator);
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
																		$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix];
																}
																else
																{
																		$cArr[]=$fieldrow['realfieldname'].'=0';

																		$this->PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT').' '.$fieldrow['fieldtitle'.$this->ct->Languages->Postfix];
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

														$c=$this->getRangeWhere($fieldrow,$value);
														break;

												case 'float':
														if($comparison_operator=='==')
															$comparison_operator='=';

														$c=$this->Search_Number($value, $fieldrow,$comparison_operator);
														break;

												case 'phponadd':

														$c=$this->Search_String($value, $fieldrow,$comparison_operator);
														break;

												case 'phponchange':

														$c=$this->Search_String($value, $fieldrow,$comparison_operator);
														break;

												case 'string':

														$c=$this->Search_String($value, $fieldrow,$comparison_operator);
														break;

												case 'alias':

														$c=$this->Search_Alias($value, $fieldrow,$comparison_operator);
														break;

												case 'md5':

														$c=$this->Search_Alias($value, $fieldrow,$comparison_operator);

														break;

												case 'email':

														$c=$this->Search_String($value, $fieldrow,$comparison_operator);
														break;
												case 'url':

														$c=$this->Search_String($value, $fieldrow,$comparison_operator);
														break;

												case 'date':

														$c=$this->Search_Date($fieldname, $value, $comparison_operator);
														break;

												case 'creationtime':

														$c=$this->Search_Date($fieldname,$value, $comparison_operator);
														break;

												case 'changetime':

														$c=$this->Search_Date($fieldname,$value, $comparison_operator);
														break;

												case 'lastviewtime':

														$c=$this->Search_Date($fieldname,$value, $comparison_operator);
														break;



												case 'multilangstring':
													
													$c=$this->Search_String($value, $fieldrow,$comparison_operator,true);
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

																	$vTitle=Tree::getMultyValueTitles($v,$this->ct->Languages->Postfix,1, ' - ');
																	$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].': '.implode(',',$vTitle);
																}

																elseif($comparison_operator=='!=')
																{
																	$cArr[]='!instr('.$fieldrow['realfieldname'].','.$db->quote($v).')';

																	$vTitle=Tree::getMultyValueTitles($v,$this->ct->Languages->Postfix,1, ' - ');
																	$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].': '.implode(',',$vTitle);
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
															$esr_table_full=$this->ct->Table->realtablename;
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
																	$this->PathValue[]=$fieldrow['fieldtitle'
																			.$this->ct->Languages->Postfix]
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
															$esr_table_full=$this->ct->Table->realtablename;
															$esr_field=$typeparamsarray[1];


															if(isset($typeparamsarray[2]))
																$esr_filter=$typeparamsarray[2];
															else
																$esr_filter='';

															$vLnew=$vL;

															$filtertitle.=JHTML::_('ESSQLJoinView.render',
																$vL,
																$esr_table,
																$esr_field,
																$esr_filter,
																$this->ct->Languages->Postfix);

															$opt_title='';

															if($vLnew!='')
															{
																if($comparison_operator=='!=')
																{
																	$opt_title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT');

																	$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'!='.$db->quote($vLnew);
																	$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix]
																			.' '
																			.$opt_title
																			.' '
																			.$filtertitle;
																}
																elseif($comparison_operator=='=')
																{
																	$opt_title=':';

																	$ivLnew=$vLnew;
																	if($ivLnew==0 or $ivLnew=="" or $ivLnew==-1)
																	{
																		$cArr[]='('.$esr_table_full.'.'.$fieldrow['realfieldname'].'=0 OR '.$esr_table_full.'.'.$fieldrow['realfieldname'].'="" OR '
																			.$esr_table_full.'.'.$fieldrow['realfieldname'].' IS NULL)';
																	}
																	else
																		$cArr[]=$esr_table_full.'.'.$fieldrow['realfieldname'].'='.$db->quote($vLnew);
																
																	$this->PathValue[]=$fieldrow['fieldtitle'
																			.$this->ct->Languages->Postfix]
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
													$c=$this->Search_String($value, $fieldrow,$comparison_operator);
													break;

												case 'multilangtext':
													$c=$this->Search_String($value, $fieldrow,$comparison_operator,true);
													break;

										}
		return $c;
	}

	function Search_Date($fieldname, $value, $comparison_operator)
	{
		$fieldrow1 = Fields::FieldRowByName($fieldname,$this->ct->Table->fields);

		$title1='';
		if(count($fieldrow1)>0)
		{
			$title1=$fieldrow1['fieldtitle'.$this->ct->Languages->Postfix];
		}
		else
			$title1=$fieldname;

		$fieldrow2 = Fields::FieldRowByName($value,$this->ct->Table->fields);

		$title2='';
		if(count($fieldrow2)>0)
			$title2=$fieldrow2['fieldtitle'.$this->ct->Languages->Postfix];
		else
			$title2=$value;

		$db = Factory::getDBO();
		
		//Breadcrumbs
		$this->PathValue[]=$title1.' '.$comparison_operator.' '.$title2;

		$value1=$this->processDateSearchTags($fieldname,$fieldrow1,$this->ct->Table->realtablename);
		$value2=$this->processDateSearchTags($value,$fieldrow2,$this->ct->Table->realtablename);

		if($value2=='NULL' and $comparison_operator=='=')
			$query=$value1.' IS NULL';
		elseif($value2=='NULL' and $comparison_operator=='!=')
			$query=$value1.' IS NOT NULL';
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

		$db = Factory::getDBO();

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

			if(count($fList)>0)// or trim(strtolower($value))=="null")
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
			$a=Factory::getApplication()->input->get($getPar,'','CMD');
			if($a=='')
				return '';
			return Factory::getApplication()->input->getInt($getPar);
		}

		return $vL;
	}

	function getString_vL($vL)
	{
		if(strpos($vL,'$get_')!==false)
		{
			$getPar=str_replace('$get_','',$vL);
			//$v=Factory::getApplication()->input->get($getPar,'','STRING');
			$v=(string)preg_replace('/[^A-Z0-9_\.,-]/i', '', Factory::getApplication()->input->getString($getPar));
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
			return Factory::getApplication()->input->get($getPar,'','CMD');
		}

		return $vL;
	}

	function Search_String($value, &$fieldrow,$comparison_operator,$isMultilingual = false)
	{
		$db = Factory::getDBO();

		$realfieldname=$fieldrow['realfieldname'].($isMultilingual ? $this->ct->Languages->Postfix : '');
		
		$v=$this->getString_vL($value);
		
		if($comparison_operator=='=' and $v!="")
		{
			$PathValue = [];
			
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
					
					$PathValue[] = $vl;
				}

				if(count($new_v_list)>1)
					$cArr[]='('.implode(' AND ',$new_v_list).')';
				else
					$cArr[]=implode(' AND ',$new_v_list);
			}

			$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.implode(', ',$PathValue);

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
			
			$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.($v == '' ? 'NOT SELECTED' : $v);
			
			return $where;
		}
	}

	function Search_Number($value, &$fieldrow,$comparison_operator)
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
				$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.(int)$vL;
			}
		}
		
		if(count($cArr)==0)
			return '';
		
		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' OR ', $cArr).')';
	}

	function Search_Alias($value, &$fieldrow,$comparison_operator)
	{
		if($comparison_operator=='==')
				$comparison_operator='=';

		$db = Factory::getDBO();

		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		foreach($vList as $vL)
		{
			if($vL=="null" and $comparison_operator=='=')
				$cArr[]='('.$fieldrow['realfieldname'].'='.$db->quote('').' OR '.$fieldrow['realfieldname'].' IS NULL)';
			else
				$cArr[]=$fieldrow['realfieldname'].$comparison_operator.$db->quote($vL);

			$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.$vL;
		}

		if(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_UserGroup($value, &$fieldrow,$comparison_operator)
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
				$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.$filtertitle;
			}
		}
		
		if(count($cArr)==0)
			return '';
		elseif(count($cArr)==1)
			return $cArr[0];
		else
			return '('.implode(' AND ', $cArr).')';
	}

	function Search_User($value, &$fieldrow,$comparison_operator, $field_extra_param = '')
	{
		$db = Factory::getDBO();
		
		$v=$this->getString_vL($value);

		$vList=explode(',',$v);
		$cArr=array();
		
		if($field_extra_param == 'usergroups')
		{
			foreach($vList as $vL)
			{
				if($vL!='')
				{
					$select1 = '(SELECT title FROM #__usergroups AS g WHERE g.id = m.group_id LIMIT 1)';
					$cArr[]='(SELECT m.group_id FROM #__user_usergroup_map AS m WHERE user_id='.$fieldrow['realfieldname'].' AND '
						.$select1.$comparison_operator.$db->quote($v).')';
					
					$filtertitle=JHTML::_('ESUserView.render',  $vL);
					$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.$filtertitle;
				}
			}
		}
		else
		{
			foreach($vList as $vL)
			{
				if($vL!='')
				{
					if((int)$vL==0 and $comparison_operator=='=')
						$cArr[]='('.$fieldrow['realfieldname'].'=0 OR '.$fieldrow['realfieldname'].' IS NULL)';
					else
						$cArr[]=$fieldrow['realfieldname'].$comparison_operator.(int)$vL;
			
					$filtertitle=JHTML::_('ESUserView.render',  $vL);
					$this->PathValue[]=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix].' '.$comparison_operator.' '.$filtertitle;
				}
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
		
		if($param == null)
			return $items;
		
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

	function getRangeWhere(&$fieldrow,$value)
	{
		$fieldTitle=$fieldrow['fieldtitle'.$this->ct->Languages->Postfix];

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
	
		$this->PathValue[]=$fieldTitle.': '.$valueTitle;

		if(count($rangewhere)>0)
			return $rangewhere;

		return '';
	}

	function sanitizeAndParseFilter($paramwhere, $parse = false)
	{
		$paramwhere=str_ireplace('*','=',$paramwhere);
		$paramwhere=str_ireplace('\\','',$paramwhere);

		//$paramwhere=str_replace(';','',$paramwhere);
		$paramwhere=str_ireplace('drop ','',$paramwhere);
		$paramwhere=str_ireplace('select ','',$paramwhere);
		$paramwhere=str_ireplace('delete ','',$paramwhere);
		$paramwhere=str_ireplace('update ','',$paramwhere);
		$paramwhere=str_ireplace('insert ','',$paramwhere);

		if($parse)
		{
			//Parse using layout, has no effect to layout itself
			$this->ct->LayoutProc->layout = $paramwhere;
			return  $this->ct->LayoutProc->fillLayout();
		}
			
		return $paramwhere;
	}

}//end class

class LinkJoinFilters
{
	static public function getFilterBox($establename,$dynamic_filter_fieldname,$control_name,$filtervalue)
	{
		$db = Factory::getDBO();
		
		$fieldrow=Fields::getFieldRowByName($dynamic_filter_fieldname, $tableid=0,$establename);
		
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

		$fieldrow=Fields::getFieldRowByName($field, $tablerow['id']);
		if(!is_object($fieldrow))
			return '<p style="color:white;background-color:red;">sqljoin: field "'.$field.'" not found</p>';
			
		$db = Factory::getDBO();
		$where = '';
		if($tablerow['published_field_found'])
			$where = 'WHERE published=1';

		$query = 'SELECT '.$tablerow['query_selects'].' FROM '.$tablerow['realtablename'].' '.$where.' ORDER BY '.$fieldrow->realfieldname;

		$db->setQuery($query);

		$records=$db->loadAssocList();
		$result.='
		<script>
			ctTranslates["COM_CUSTOMTABLES_SELECT"] = "- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT').'";
			ctInputboxRecords_current_value["'.$control_name.'"]="";
		</script>
		';
		
		$control_name_postfix = '';
		if(strpos($control_name,'_selector' !== false))
			$control_name_postfix = '_selector';

		$result.='<select id="'.$control_name.'SQLJoinLink" onchange="ctInputbox_UpdateSQLJoinLink(\''.$control_name.'\',\''.$control_name_postfix.'\')">';
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
