<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'ordering.php');

class CustomTablesKeywordSearch
{

		var $establename;
		var $langpostfix;
		var $esfields;
		var $groupby;
		var $esordering;

		function getRowsByKeywords($keywords,&$PathValue,&$TotalRows,$limit,$limitstart)
		{



				$result_rows=array();
				$idList=array();

				if(!JFactory::getApplication()->input->getString('esfieldlist',''))
						return $result_rows;

				if($keywords=='')
						return $result_rows;


				$keywords=trim(preg_replace("/[^a-zA-Z0-9áéíóúýñÁÉÍÓÚÝÑ [:punct:]]/", "", $keywords));

				$keywords=str_replace('\\','',$keywords);


				$mod_fieldlist=explode(',',JFactory::getApplication()->input->getString('esfieldlist',''));

				//Strict (all words in a serash must be there)
				$result_rows=$this->getRowsByKeywords_Processor($keywords,$PathValue,$mod_fieldlist,'AND');


				//At least one word is match
				if(count($result_rows)==0)
						$result_rows=$this->getRowsByKeywords_Processor($keywords,$PathValue,$mod_fieldlist,'OR');


				$TotalRows=count($result_rows);


				//Process Limit
				$result_rows=$this->processLimit($result_rows,$limit,$limitstart);


				return $result_rows;
		}
		function processLimit($result_rows,$limit,$limitstart)
		{

			$result_rows_new=array();

			if($limitstart+$limit>count($result_rows))
				$limit_=count($result_rows)-$limitstart;
			else
				$limit_=$limit;


			for($i=$limitstart;$i<$limitstart+$limit_;$i++)
			{
				$result_rows_new[]=$result_rows[$i];
			}

			return $result_rows_new;
		}

		function getRowsByKeywords_ProcessTypes($fieldtype,$fieldname,$typeparams, $regexpression,&$inner)
		{
				$where='';
				$inner='';


				switch($fieldtype)
						{
								case 'string':

										$where=' es_'.$fieldname.' REGEXP "'.$regexpression.'"';

										break;

								case 'phponadd':

										$where=' es_'.$fieldname.' REGEXP "'.$regexpression.'"';

										break;

								case 'phponchange':

										$where=' es_'.$fieldname.' REGEXP "'.$regexpression.'"';

										break;

								case 'text':

										$where=' es_'.$fieldname.' REGEXP "'.$regexpression.'"';
										break;

								case 'multilangstring':

										$where=' es_'.$fieldname.$this->langpostfix.' REGEXP "'.$regexpression.'"';
										break;

								case 'multilangtext':

										$where=' es_'.$fieldname.$this->langpostfix.' REGEXP "'.$regexpression.'"';
										break;


								case 'records':

										$typeparamsarray=explode(',',$typeparams);

										if(count($typeparamsarray)<3)
												return '';

										$esr_table='#__customtables_table_'.$typeparamsarray[0];
										$esr_field=$typeparamsarray[1];

										$inner='INNER JOIN '.$esr_table.' ON instr(#__customtables_table_'.$this->establename.'.es_'.$fieldname.',concat(",",'.$esr_table.'.id,","))';
										$where=' '.$esr_table.'.es_'.$esr_field.' REGEXP "'.$regexpression.'"';

								break;

								case 'sqljoin':
										echo 'search box not ready yet';
								break;

								case 'customtables':

										$esr_table='#__customtables_options';

										$inner='INNER JOIN '.$esr_table.' ON instr('.$esr_table.'.familytreestr, #__customtables_table_'.$this->establename.'.es_'.$fieldname.')';

										$where=' '.$esr_table.'.title'.$this->langpostfix.' REGEXP "'.$regexpression.'"';

								break;

								case 'user':

										$inner='INNER JOIN #__users ON #__users.id=#__customtables_table_'.$this->establename.'.es_'.$fieldname;
										$where=' #__users.name REGEXP "'.$regexpression.'"';

								break;

								case 'userid':

										$inner='INNER JOIN #__users ON #__users.id=#__customtables_table_'.$this->establename.'.es_'.$fieldname;
										$where=' #__users.name REGEXP "'.$regexpression.'"';

								break;

						}
				return 	$where;

		}


		function getRowsByKeywords_Processor($keywords,&$PathValue,$mod_fieldlist,$AndOrOr)
		{
				$keyword_arr=explode(' ',$keywords);

				$count=0;//TotalRows;

				$result_rows=array();
				$idList=array();

				if($AndOrOr=='OR')
						$AndOrOr_text=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR' );

				if($AndOrOr=='AND')
						$AndOrOr_text=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AND' );


				foreach($mod_fieldlist as $mod_field)
				{
						$where='';
						$inner='';

						$f=trim($mod_field);



						$fieldrow=ESTables::FieldRowByName($f,$this->esfields);//2011.6.1


						//exact match
						$fields=array();
						if(isset($fieldrow['type']) and isset($fieldrow['fieldname']))
								$where=$this->getRowsByKeywords_ProcessTypes($fieldrow['type'],$fieldrow['fieldname'],$fieldrow['typeparams'],'[[:<:]]'.$keywords.'[[:>:]]',$inner,$this->langpostfix);

						if($where!='')
								$this->getKeywordSearch($inner, $where,$result_rows,$count,$idList);

						$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS').' "'.$keywords.'"';

						if(count($keyword_arr)>1) //Do not search because there is only one keyword, and it's already checked
						{
							$where='';
							$inner='';

							$where_arr=array();
							$inner_arr=array();

							$kw_text_array=array();
							foreach($keyword_arr as $kw)
							{
								$inner='';
								$w=$this->getRowsByKeywords_ProcessTypes($fieldrow['type'],$fieldrow['fieldname'],$fieldrow['typeparams'],'[[:<:]]'.$kw.'[[:>:]]',$inner);
								if($w!='')
								{
									$where_arr[]=$w;
									if(!in_array($inner,$inner_arr))
									{
											$inner_arr[]=$inner;

										$kw_text_array[]=$kw;
									}

								}//if($w!='')
							}

							$where=implode(' '.$AndOrOr.' ', $where_arr);
							$inner=implode(' ', $inner_arr);

							if($where!='')
								$this->getKeywordSearch($inner, $where,$result_rows,$count,$idList);


							$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS').' "'.implode('" '.$AndOrOr_text.' "',$kw_text_array).'"';
						}

						$where='';
						$inner='';

						$where_arr=array();
						$inner_arr=array();

						$kw_text_array=array();
						foreach($keyword_arr as $kw)
						{
								$inner='';

								if(isset($fieldrow['type']) and isset($fieldrow['fieldname']))
										$w=$this->getRowsByKeywords_ProcessTypes($fieldrow['type'],$fieldrow['fieldname'],$fieldrow['typeparams'],'[[:<:]]'.$kw,$inner);
								else
										$w='';

								if($w!='')
								{
										$where_arr[]=$w;
										if(!in_array($inner,$inner_arr))
										{
												$inner_arr[]=$inner;

												$kw_text_array[]=$kw;
										}
								}

						}

						$where=implode(' '.$AndOrOr.' ', $where_arr);
						$inner=implode(' ', $inner_arr);

						$where=str_replace('\\','',$where);

						if($where!='')
								$this->getKeywordSearch($inner, $where,$result_rows,$count,$idList);

						$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS').' "'.implode('" '.$AndOrOr_text.' "',$kw_text_array).'"';

				}
				// -------------------
				foreach($mod_fieldlist as $mod_field)
				{
						if(isset($fieldrow['fieldtitle'.$this->langpostfix]) )
								$fields[]=$fieldrow['fieldtitle'.$this->langpostfix];


						$where='';
						$f=trim($mod_field);
						$fieldrow=ESTables::FieldRowByName($f,$this->esfields);//2011.6.1

						//any
						$keyword_arr=explode(' ',$keywords);
						$where='';
						$inner='';
						$inner_arr=array();
						$where_arr=array();
						$fieldtypefound=false;

						$kw_text_array=array();

						foreach($keyword_arr as $kw)
						{
								$kw_text_array[]=$kw;
								$t='';
								if(isset($fieldrow['type']) )
										$t=$fieldrow['type'];

								switch($t)
								{

										case 'email':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'string':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'phponadd':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'phponchange':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'text':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'multilangstring':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].$this->langpostfix.', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'multilangtext':

												$where_arr[]=' INSTR(es_'.$fieldrow['fieldname'].$this->langpostfix.', "'.$kw.'")';
												$fieldtypefound=true;
												break;

										case 'records':


												$typeparamsarray=explode(',',$fieldrow['typeparams']);
												$filtertitle='';
												if(count($typeparamsarray)<1)
													$filtertitle.='table not specified';

												if(count($typeparamsarray)<2)
													$filtertitle.='field or layout not specified';

												if(count($typeparamsarray)<3)
													$filtertitle.='selector not specified';

												$esr_table='#__customtables_table_'.$typeparamsarray[0];
												$esr_field=$typeparamsarray[1];

												$inner='INNER JOIN '.$esr_table.' ON instr(#__customtables_table_'.$this->establename.'.es_'.$fieldrow['fieldname'].',concat(",",'.$esr_table.'.id,","))';
												if(!in_array($inner,$inner_arr))
														$inner_arr[]=$inner;


												$where_arr[]='instr('.$esr_table.'.es_'.$esr_field.',"'.$kw.'")';
												$fieldtypefound=true;

												break;

										case 'sqljoin':
												echo 'search box not ready yet';

												$typeparamsarray=explode(',',$fieldrow['typeparams']);
												$filtertitle='';
												if(count($typeparamsarray)<1)
													$filtertitle.='table not specified';

												if(count($typeparamsarray)<2)
													$filtertitle.='field or layout not specified';

												if(count($typeparamsarray)<3)
													$filtertitle.='selector not specified';

												$esr_table='#__customtables_table_'.$typeparamsarray[0];
												$esr_field=$typeparamsarray[1];

												$inner='INNER JOIN '.$esr_table.' ON instr(#__customtables_table_'.$this->establename.'.es_'.$fieldrow['fieldname'].',concat(",",'.$esr_table.'.id,","))';
												if(!in_array($inner,$inner_arr))
														$inner_arr[]=$inner;


												$where_arr[]='instr('.$esr_table.'.es_'.$esr_field.',"'.$kw.'")';
												$fieldtypefound=true;

												break;

										case 'customtables':


												$inner='INNER JOIN #__customtables_options ON instr(#__customtables_options.familytreestr, #__customtables_table_'.$this->establename.'.es_'.$fieldrow['fieldname'].')';
												if(!in_array($inner,$inner_arr))
														$inner_arr[]=$inner;

												$where_arr[]='instr(#__customtables_options.title'.$this->langpostfix.',"'.$kw.'")';
												$fieldtypefound=true;

										break;

										case 'user':

												$inner='INNER JOIN #__users ON #__users.id=#__customtables_table_'.$this->establename.'.es_'.$fieldrow['fieldname'];
												if(!in_array($inner,$inner_arr))
														$inner_arr[]=$inner;

												$where_arr[]=' #__users.name REGEXP "'.$kw.'"';
												$fieldtypefound=true;

										break;



										case 'userid':

												$inner='INNER JOIN #__users ON #__users.id=#__customtables_table_'.$this->establename.'.es_'.$fieldrow['fieldname'];
												if(!in_array($inner,$inner_arr))
														$inner_arr[]=$inner;

												$where_arr[]=' #__users.name REGEXP "'.$kw.'"';
												$fieldtypefound=true;
										break;
								}
						}



								$where=implode(' '.$AndOrOr.' ', $where_arr);
								$inner=implode(' ', $inner_arr);

								$where=str_replace('\\','',$where);

								if($where!='')
									$this->getKeywordSearch($inner, $where,$result_rows,$count,$idList);

								$PathValue[]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CONTAINS').' "'.implode('" '.$AndOrOr_text.' "',$kw_text_array).'"';
				}


				return $result_rows;

		}

		function getKeywordSearch($inner_str,$where,&$result_rows,&$count,&$idList)
		{

				$db = JFactory::getDBO();
				$inner=array($inner_str);
				$tablename='#__customtables_table_'.$this->establename;
				$query = 'SELECT *, '.$tablename.'.id AS listing_id, '.$tablename.'.published As  listing_published ';

				$ordering=array();

				if($this->groupby!='')
						$ordering[]='es_'.$this->groupby;

				if($this->esordering)
						CTOrdering::getOrderingQuery($ordering,$query,$inner,$this->esordering,$this->langpostfix,$tablename);

				$query.=' FROM '.$tablename.' ';

				$query.=implode(' ',$inner).' ';

				$query.=' WHERE '.$where.' ';



				$query.=' GROUP BY listing_id ';

				if(count($ordering)>0)
						$query.=' ORDER BY '.implode(',',$ordering);


				$db->setQuery($query);
				if (!$db->query())    die ;

				$rows=$db->loadAssocList();


				foreach($rows as $row)
				{

						if(in_array($row['listing_id'],$idList))
								$exist=true;
						else
								$exist=false;

						if(!$exist)
						{
								$result_rows[]=$row;


								$idList[]=$row['listing_id'];

								$count++;
						}
				}
		}
}
