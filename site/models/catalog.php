<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');



use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Ordering;
use CustomTables\DataTypes\Tree;

jimport('joomla.application.component.model');

$sitelib=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
require_once($sitelib.'layout.php');
require_once($sitelib.'filtering.php');

class CustomTablesModelCatalog extends JModelLegacy
{
	var $ct;
	
		var $filtering;

		var $TotalRows=0;
		var $_pagination = null;

		var $ordering=null;

		var $filterparam;

		var $class="mag-phone mag-field formCol";
		var $columns;

		var $ShowDatailsLink;

		var $imagefolder;
		var $imagefolderweb;
		var $imagegalleryprefix;
		var $showdescription;

		var $showpagination;
		var $groupby;	//is a real dtabase field name. example es_name

		var $showpublished;

		var $params;
		var $blockExternalVars;

		var $Itemid;

		var $layout;
		
		var $shownavigation;

		var $limit;
		var $limitstart;
		var $recordlist;


		var $showcartitemsonly;
		var $showcartitemsprefix;


		var $imagegalleries;
		var $fileboxes;

		var $PathValue;

		var $current_url;
		var $current_sef_url;
		var $current_sef_url_query;
		var $alias_fieldname;

		var $WebsiteRoot;
		


		function __construct()
		{
			parent::__construct();
		
			$this->ct = new CT;
		
			$jinput=JFactory::getApplication()->input;

			$this->showcartitemsprefix='customtables_';
		}

		function prepareSEFLinkBase()
		{
			if(strpos($this->ct->Env->current_url,'option=com_customtables')===false)
		    {
				$pair=explode('?',$this->ct->Env->current_url);
				$this->ct->Env->current_sef_url=$pair[0].'/';
				if(isset($pair[1]))
					$this->ct->Env->current_sef_url='?'.$pair[1];

				foreach($this->ct->Table->fields as $fld)
				{
					if($fld['type']=='alias')
					{
						$this->alias_fieldname=$fld['fieldname'];
						break;
					}
				}
			}
		}

		function setFrmt($frmt)
		{
			$this->ct->Env->frmt=$frmt;
		}
		
		function load(&$params,$blockExternalVars=false,$layout='')
		{
				$this->blockExternalVars=$blockExternalVars;
				$jinput = JFactory::getApplication()->input;

				$mainframe = JFactory::getApplication('site');
				$db = JFactory::getDBO();

				//get params
				if($this->blockExternalVars or (isset($params) and count($params)>1))
				{

					$this->params=$params;
				}
				else
				{
					$app		= JFactory::getApplication();
					$this->params=$app->getParams();


				}//if($this->blockExternalVars)


				if(!$this->blockExternalVars)
						$this->showpagination=$this->params->get('showpagination');
				else
						$this->showpagination=0;

				//misc

				$this->layout=$layout;
				$this->showpublished=(int)$this->params->get('showpublished');

				$this->shownavigation=$this->params->get( 'shownavigation' );
				$this->ShowDatailsLink=(bool)$this->params->get('linktodetails');

				$forceitemid=$this->params->get('forceitemid');
				if(isset($forceitemid) and $forceitemid!='')
				{
					//Find Itemid by alias
					if(((int)$forceitemid)>0)
						$this->Itemid=$forceitemid;
					else
					{
						if($forceitemid!="0")
							$this->Itemid=(int)JoomlaBasicMisc::FindItemidbyAlias($forceitemid);//Accepts menu Itemid and alias
						else
							$this->Itemid=$jinput->get('Itemid',0,'INT');
					}
				}
				else
				{
					$this->Itemid=$jinput->get('Itemid',0,'INT');
					$forceitemid=null;
				}

				$this->columns=0;//2(int)$this->params->get('columns');

				$this->ct->getTable($this->params->get( 'establename' ), $this->params->get('useridfield'));
				
				if($this->ct->Table->tablename=='')
				{
					JFactory::getApplication()->enqueueMessage('Table not selected.', 'error');
					return;
				}
					

				//sorting
				$this->ordering = new CustomTables\Ordering($this->ct->Table);

				$this->ordering->parseOrderByParam($this->blockExternalVars,$this->params);


				//Limit
				$this->applyLimits();
				
				//Grouping
				if($this->params->get('groupby')!='')
					$this->groupby=Fields::getRealFieldName($this->params->get('groupby'),$this->ct->Table->fields);
				else
					$this->groupby='';


				//Layout

				$this->LayoutProc=new LayoutProcessor;
				$this->LayoutProc->Model=$this;
				$this->LayoutProc->fields=$this->ct->Table->fields;

				$this->LayoutProc->ShowDatailsLink=$this->ShowDatailsLink;
				$this->LayoutProc->imagefolder=$this->imagefolder;
				$this->LayoutProc->imagefolderweb=$this->imagefolderweb;
				$this->LayoutProc->imagegalleryprefix=$this->imagegalleryprefix;
				$this->LayoutProc->Itemid=$this->Itemid;

				//filtering set in back-end

				$this->filterparam='';
				if($this->blockExternalVars)
				{
					$this->filterparam=$this->params->get( 'filter' );
				}
				else
				{
					if($jinput->get('filter','','STRING'))
						$this->filterparam=$jinput->get('filter','','STRING');
					else
						$this->filterparam=$this->params->get( 'filter' );
				}

				if($this->filterparam!='')
				{
					//Parse using layout, has no effect to layout itself
					$this->LayoutProc->applyContentPlugins($this->filterparam);
					$this->filterparam = $this->sanitizeAndParseFilter($this->filterparam);
					$this->LayoutProc->layout=$this->filterparam;
					$this->filterparam=$this->LayoutProc->fillLayout();
				}

				//user filtering from module
				$this->filter='';
				if(!$this->blockExternalVars)
				{
					if($jinput->get('where','','BASE64'))
					{
						$decodedurl=$jinput->get('where','','BASE64');;
						$decodedurl=urldecode($decodedurl);
						$decodedurl=str_replace(' ','+',$decodedurl);
						$this->filter = $this->sanitizeAndParseFilter(base64_decode($decodedurl));
					}
				}//if(!$this->blockExternalVars)

				$this->filtering = new ESFiltering($this->ct);
				
				if($this->params->get( 'showcartitemsonly' )!='')
						$this->showcartitemsonly=(bool)(int)$this->params->get( 'showcartitemsonly' );
				else
						$this->showcartitemsonly=false;

				$this->prepareSEFLinkBase();
		}
		
		
		function sanitizeAndParseFilter($paramwhere)
		{
			$paramwhere=str_ireplace('*','=',$paramwhere);
			$paramwhere=str_ireplace('\\','',$paramwhere);

			//$paramwhere=str_replace(';','',$paramwhere);
			$paramwhere=str_ireplace('drop ','',$paramwhere);
			$paramwhere=str_ireplace('select ','',$paramwhere);
			$paramwhere=str_ireplace('delete ','',$paramwhere);
			$paramwhere=str_ireplace('update ','',$paramwhere);
			$paramwhere=str_ireplace('insert ','',$paramwhere);

			//Parse using layout, has no effect to layout itself
			$this->LayoutProc->layout=$paramwhere;
			$filter = $this->LayoutProc->fillLayout();
			
			return $filter;
		}
		
		function applyLimits()
		{
			$mainframe = JFactory::getApplication('site');
			$jinput=JFactory::getApplication()->input;
			
			if($this->ct->Env->frmt!='html')
			{
				//export all records if firmat is csv, xml etc.
				$this->limit=0;
				$this->setState('limit', $this->limit);
				$this->limitstart=0;
				return;
			}
			
			if($this->blockExternalVars)
			{
						if((int)$this->params->get( 'limit' )>0)
						{
							$limit=(int)$this->params->get( 'limit' );
							$this->limit=$limit;
							$this->setState('limit', $limit);
							$this->limitstart = $jinput->getInt('start',0);
							$this->limitstart = ($limit != 0 ? (floor($this->limitstart / $limit) * $limit) : 0);
						}
						else
						{
							$this->setState('limit', 0);
							$this->limit=0;
							$this->limitstart=0;
						}


				}
				else
				{
								$this->limitstart = $jinput->getInt('start',0);

								if((int)$this->params->get( 'limit' )>0)
								{
										$limit=(int)$this->params->get( 'limit' );
										$this->limit=$limit;

										$this->setState('limit', $limit);
								}
								else
								{
										$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
										$this->limit=$limit;
										$this->setState('limit', $limit);



								}
								// In case limit has been changed, adjust it
								$this->limitstart = ($limit != 0 ? (floor($this->limitstart / $limit) * $limit) : 0);

				}//if($this->blockExternalVars)
		}

	function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'pagination.php');
			return new JESPagination($this->TotalRows, $this->limitstart, $this->getState('limit') );
		}
		return $this->_pagination;
	}

		function getAlphaWhere($alpha,&$wherearr)
		{
				if($this->blockExternalVars)
						return;

				$jinput = JFactory::getApplication()->input;
				$esfieldtype=$jinput->get('esfieldtype','','CMD');
				$esfieldname=$jinput->get('esfieldname','','CMD');

				if($esfieldtype!='customtables')
				{
						$fName=$esfieldname;
						if(!(strpos($esfieldname,'multi')===false))
							$fName.=$this->ct->Languages->Postfix;

						$wherearr[]='SUBSTRING(es_'.$fName.',1,1)="'.$alpha.'"';
				}
				else
				{
						$db = JFactory::getDBO();

						$parentid=Tree::getOptionIdFull($jinput->get('optionname','','STRING'));


						$query = 'SELECT familytreestr, optionname '
								.' FROM #__customtables_options'
								.' WHERE INSTR(familytree,"-'.$parentid.'-") AND SUBSTRING(title'.$this->ct->Languages->Postfix.',1,1)="'.
								$jinput->get('alpha','','STRING').'"'
								.' ';

						$db->setQuery( $query );
						
						$rows=$db->loadAssocList();

						$wherelist=array();
						foreach($rows as $row)
						{

								if($row['familytreestr'])
										$a=$row['familytreestr'].'.'.$row['optionname'];
								else
										$a=$row['optionname'];

								if(!in_array($a,$wherelist))
										$wherelist[]=$a;
						}

						$wherearr_=array();
						foreach($wherelist as $row)
						{

								$wherearr_[]='instr(es_'.$jinput->getCMD('esfieldname','').',"'.$row.'")';
						}
						$wherearr[]=' ('.implode(' OR ',$wherearr_).')';
				}

		}



	function getSearchResult($addition_filter='')
	{
		$this->PathValue='';
		$jinput = JFactory::getApplication()->input;

		if(!isset($this->ct->Table->tableid))
			return array();

		$this->TotalRows=0;
		$db= JFactory::getDBO();
		$wherearr=array();

		$PathValue=array();

		if($this->ct->Table->published_field_found)
		{
			if($this->showpublished==1)
				$wherearr[]= $this->ct->Table->realtablename.'.published=0';
			elseif($this->showpublished!=2)
				$wherearr[]= $this->ct->Table->realtablename.'.published=1';
		}
				
		if($this->layout=='currentuser' or $this->layout=='customcurrentuser')
		{
				if($this->ct->Table->useridfieldname!='')
				{
						$user = JFactory::getUser();
						$wherearr[]= $this->ct->Table->useridrealfieldname.'='.(int)$user->get('id');
				}

		}

		$moduleid=$jinput->get('moduleid',0,'INT');

		if(!$this->blockExternalVars)
		{
				if($moduleid!=0)
				{

					$eskeysearch_=$jinput->get('eskeysearch_'.$moduleid,'','STRING');
					if($eskeysearch_!='')
					{

								require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'keywordsearch.php');

								$KeywordSearcher=new CustomTablesKeywordSearch($this->ct);

								$KeywordSearcher->groupby=$this->groupby;
								$KeywordSearcher->esordering=$this->ordering->ordering_processed_string;

								$result_rows=$KeywordSearcher->getRowsByKeywords(
																	  $eskeysearch_,
																	  $PathValue,
																	  $TotalRows,
																	  (int)$this->getState('limit'),
																	  $this->limitstart

																	  );
								$this->TotalRows=$TotalRows;


								if($TotalRows<$this->limitstart )
								{
										$this->limitstart=0;
								}

								return $result_rows;
						}
						elseif($jinput->get('alpha','','STRING')!='')
						{
							$this->getAlphaWhere($jinput->get('alpha','','STRING'),$wherearr);
						}

				}//if($moduleid!=0)
		}//if(!$this->blockExternalVars)

		if($this->filterparam != null)
			$paramwhere = $this->filtering->getWhereExpression($this->filterparam,$PathValue);
		else
			$paramwhere = '';

		if($addition_filter!='')
		{
			$wherearr[]=$addition_filter;
		}
		
		if($paramwhere!='')
			$wherearr[]=$paramwhere;

		if($this->filter!='' and !$this->blockExternalVars)
		{
			$paramwhere=$this->filtering->getWhereExpression($this->filter,$PathValue);

			if($paramwhere!='')
				$wherearr[]=$paramwhere;
		}

		//Shopping Cart

		if($this->showcartitemsonly)
		{
			$jinput = JFactory::getApplication()->input;
			
			$cookieValue = $jinput->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

			if (isset($cookieValue))
			{
				if($cookieValue=='')
					$wherearr[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
				else
				{
					$items=explode(';',$cookieValue);
					$warr=array();
					foreach($items as $item)
					{
						$pair=explode(',',$item);
						$warr[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'='.(int)$pair[0];//id must be a number
					}
				$wherearr[]='('.implode(' OR ', $warr).')';
			}
		}
		else
			$wherearr[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
		}

		if(count($wherearr)>0)
			$where = ' WHERE '.implode(' AND ',$wherearr);
		else
			$where='';

		$where=str_replace('\\','',$where);

		

		//to fullfill the "Clear" task
		if($jinput->get('task','','CMD')=='clear')
		{
			$cQuery='DELETE FROM '.$this->ct->Table->realtablename.' '.$where;
			$db->setQuery($cQuery);
			$db->execute();

			return true;
		}

		$ordering=array();
		
		if($this->groupby!='')
				$ordering[]=$this->groupby;

		$selects = [$this->ct->Table->tablerow['query_selects']];
		
		
		
		if($this->ordering->ordering_processed_string!=null)
		{
			$this->ordering->parseOrderByString();

			if($this->ordering->orderby!=null)
			{
				if($this->ordering->selects!=null)
				$selects[]=$this->ordering->selects;
				$ordering[]=$this->ordering->orderby;
			}
		}

		$query='SELECT '.implode(',',$selects).' FROM '.$this->ct->Table->realtablename.' ';
		
		if($this->ordering->inner!=null)
			$query.=' '.implode(' ',$this->ordering->inner).' ';
			
		$query.=$where;
		
		//Not really necessary
		$query_analytical='SELECT COUNT('.$this->ct->Table->tablerow['realidfieldname'].') AS count FROM '.$this->ct->Table->realtablename.' '.$where;

		if(count($ordering)>0)
			$query.=' ORDER BY '.implode(',',$ordering);
			
		$db->setQuery($query_analytical);
		$rows=$db->loadObjectList();	
		if(count($rows)==0)
			$this->TotalRows=0;
		else
			$this->TotalRows=$rows[0]->count;
		
		$this->recordlist=array();
		
		
		if($this->TotalRows>0)
		{
			$the_limit=(int)$this->getState('limit');
			if($the_limit>20000)
				$the_limit=20000;

			if($the_limit==0)
				$the_limit=20000; //or we will run out of memory
				
			$this->limit=$the_limit;
			
			

			if(!$this->blockExternalVars and $the_limit!=0)
			{
				if($this->TotalRows<$this->limitstart or $this->TotalRows<$the_limit)
					$this->limitstart=0;

				$db->setQuery($query, $this->limitstart, $the_limit);
			}
			else
			{
				if($the_limit>0)
					$db->setQuery($query, 0, $the_limit);
			}

			$rows=$db->loadAssocList();
			
			foreach($rows as $row)
				$this->recordlist[]=$row['listing_id'];
		}
		else
			$rows=array();
		
		$this->LayoutProc->recordlist=implode(',',$this->recordlist);

		$this->PathValue=$PathValue;

		return $rows;
	}


		function cart_emptycart()
		{
				$app = JFactory::getApplication();
				$jinput = $app->input;
				$jinput->cookie->set($this->showcartitemsprefix.$this->ct->Table->tablename, '', time()-3600, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());

				return true;
		}

		function cart_deleteitem()
		{
				$jinput = JFactory::getApplication()->input;
				if($jinput->get('listing_id',0,'INT')==0)
						return false;

				$this->cart_setitemcount(0);
				return true;
		}

		function cart_form_addtocart($itemcount=-1)
		{
				$jinput=JFactory::getApplication()->input;

				if(!$jinput->get('listing_id',0,'INT'))
						return false;

				$objectid=$jinput->getInt('listing_id',0);

				if($itemcount==-1)
					$itemcount=$jinput->getInt('itemcount',0);

				$app = JFactory::getApplication();
				$cookieValue = $app->input->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

				if (isset($cookieValue))
				{
						$items=explode(';',$cookieValue);
						$cnt=count($items);
						$found=false;
						for($i=0;$i<$cnt;$i++)
						{
								$pair=explode(',',$items[$i]);
								if(count($pair)!=2)
										unset($items[$i]); //delete the shit
								else
								{
										if((int)$pair[0]==$objectid)
										{
												$new_itemcount=(int)$pair[1]+$itemcount;
												if($new_itemcount==0)
												{
														unset($items[$i]); //delete item
														$found=true;
												}
												else
												{
														//update counter
														$pair[1]=$new_itemcount;
														$items[$i]=implode(',',$pair);
														$found=true;
												}
										}
								}
						}//for

						if(!$found)
								$items[]=$objectid.','.$itemcount; // add new item

						$items=array_values($items);
				}
				else
						$items=array($objectid.','.$itemcount); //add new

				$nc=implode(';',$items);
				setcookie($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24);

				return true;
		}

		function cart_setitemcount($itemcount=-1)
		{

				$jinput = JFactory::getApplication()->input;

				if(!$jinput->get('listing_id',0,'INT'))
						return false;

				$objectid=$jinput->get('listing_id',0,'INT');

				$app = JFactory::getApplication();

				if($itemcount==-1)
					$itemcount=$jinput->getInt('itemcount',0);

				$cookieValue = $app->input->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

				if (isset($cookieValue))
				{
						$items=explode(';',$cookieValue);
						$cnt=count($items);
						$found=false;
						for($i=0;$i<$cnt;$i++)
						{
								$pair=explode(',',$items[$i]);
								if(count($pair)!=2)
										unset($items[$i]); //delete the shit
								else
								{
										if((int)$pair[0]==$objectid)
										{
												if($itemcount==0)
												{
														unset($items[$i]); //delete item
														$found=true;
												}
												else
												{
														//update counter
														$pair[1]=$itemcount;
														$items[$i]=implode(',',$pair);
														$found=true;
												}
										}
								}
						}//for

						if(!$found)
								$items[]=$objectid.','.$itemcount; // add new item

						$items=array_values($items);
				}
				else
						$items=array($objectid.','.$itemcount); //add new

				$nc=implode(';',$items);
				setcookie($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24);

				return true;
		}

		function cart_addtocart()
		{
				$app = JFactory::getApplication();

				$jinput=$app->input;

				if(!$jinput->get('listing_id',0,'INT'))
						return false;

				$objectid=$jinput->get('listing_id',0,'INT');

				$cookieValue = $app->input->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

				if (isset($cookieValue))
				{
						$items=explode(';',$cookieValue);
						$cnt=count($items);
						$found=false;
						for($i=0;$i<$cnt;$i++)
						{
								$pair=explode(',',$items[$i]);
								if(count($pair)!=2)
										unset($items[$i]); //delete the shit
								else
								{
										if((int)$pair[0]==$objectid)
										{
												//update counter
												$pair[1]=((int)$pair[1])+1;
												$items[$i]=implode(',',$pair);
												$found=true;
										}
								}
						}//for

						if(!$found)
							$items[]=$objectid.',1'; // add new item

						$items=array_values($items);
				}
				else
						$items=array($objectid.',1'); //add new

				$nc=implode(';',$items);

				$app->input->cookie->set($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());

				return true;
		}

		function CleanUpPath($thePath)
		{
				$newPath=array();
				if(count($thePath)==0)
						return $newPath;

				for($i=count($thePath)-1;$i>=0;$i--)
				{
						$item=$thePath[$i];
						if(count($newPath)==0)
								$newPath[]=$item;
						else
						{
								$found=false;
								foreach($newPath as $newitem)
								{

										if(!(strpos($newitem,$item)===false))
										{
												$found=true;
												break;
										}
								}

								if(!$found)
										$newPath[]=$item;
						}
				}

				return array_reverse ($newPath);
		}

		function FindItemidbyAlias($alias)
		{
			$db = JFactory::getDBO();
			$query = 'SELECT id FROM #__menu WHERE alias='.$db->Quote($alias);

			$db->setQuery( $query );

			$recs = $db->loadAssocList( );
			if(!$recs) return 0;
			if (count($recs)<1) return 0;

			$r=$recs[0];
			return $r['id'];
		}
}
