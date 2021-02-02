<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');

class JHTMLESSQLJoinView
{
        public static function render($value, $establename, $field, $filter,$langpostfix='')
        {
				if($value==0 or $value=='' or $value==',' or $value==',,')
						return '';

				$htmlresult='';

				$config=array();

				$paramsArray=array();
				$paramsArray['limit']=0;
				$paramsArray['establename']=$establename;
				$paramsArray['filter']=$filter;
				$paramsArray['showpublished']=0;
				$paramsArray['showpagination']=0;
				$paramsArray['groupby']='';
				$paramsArray['shownavigation']=0;
				$paramsArray['sortby']='';

				$_params= new JRegistry;
				$_params->loadArray($paramsArray);

				$model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);
				$model->load($_params, true);
				$model->showpagination=false;

				//Get Row
				$tablename='#__customtables_table_'.$establename;
				$query = 'SELECT *, id AS listing_id, published aS listing_published FROM '.$tablename.' WHERE id='.(int)$value;
				$db= JFactory::getDBO();
				$db->setQuery($query);
				//if (!$db->query())
					//die( $db->stderr());

				$SearchResult=$db->loadAssocList();


				if(strpos($field,':')===false)
				{
						//without layout
										$getGalleryRows=array();
										foreach($SearchResult as $row)
										{

												if($row['listing_id']==$value)
														$htmlresult.=JoomlaBasicMisc::processValue($field,$model,$row,$langpostfix);

										}

				}
				else
				{

						//$pair=JoomlaBasicMisc::csv_explode(':',$field,'"',false);
                                                $pair=explode(':',$field);

						if($pair[0]!='layout' and $pair[0]!='tablelesslayout' and $pair[0]!='value')
								return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'"..</p>';


						$isTableLess=false;
						if($pair[0]=='tablelesslayout' or $pair[0]=='value')
							$isTableLess=true;

						if($pair[0]=='value')
						{
							$layoutcode='[_value:'.$pair[1].']';
						}
						else
						{
							//load layout
							if(isset($pair[1]) or $pair[1]!='')
								$layout_pair[0]=$pair[1];
							else
								return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

							if(isset($pair[2]))
								$layout_pair[1]=$pair[2];
							else
								$layout_pair[1]=0;

							$layouttype=0;
							$layoutcode=ESLayouts::getLayout($layout_pair[0],$layouttype);
							if($layoutcode=='')
								return '<p>layout "'.$layout_pair[0].'" not found or is empty.</p>';
						}
						
						$model->LayoutProc->layout=$layoutcode;


						$valuearray=explode(',',$value);

						if(!$isTableLess)
							$htmlresult.='<!-- records view : table --><table style="border:none;">';

						$number=1;
						if(isset($layout_pair[1]) and (int)$layout_pair[1]>0)
								$columns=(int)$layout_pair[1];
						else
								$columns=1;

						$tr=0;

						$CleanSearchResult=array();
						foreach($SearchResult as $row)
						{
								if(in_array($row['listing_id'],$valuearray))
								{
										$CleanSearchResult[]=$row;
								}
						}
						$result_count=count($CleanSearchResult);

						foreach($CleanSearchResult as $row)
						{
								if($tr==$columns)
								{
										$tr	= 0;
								}

								if(!$isTableLess and $tr==0)
										$htmlresult.='<tr>';

								//process layout
								$model->LayoutProc->number=$number;

								if($isTableLess)
									$htmlresult.=$model->LayoutProc->fillLayout($row,'','');
								else
									$htmlresult.='<td valign="middle" style="border:none;">'.$model->LayoutProc->fillLayout($row,'','').'</td>';

								$tr++;
								if(!$isTableLess and $tr==$columns)
								{
										$htmlresult.='</tr>';
										//if($number+1<$result_count)
												//$htmlresult.='<tr>';


								}
								$number++;

						}
						if(!$isTableLess and $tr<$columns)
								$htmlresult.='</tr>';

						if(!$isTableLess)
							$htmlresult.='</table><!-- records view : end of table -->';

				}


				$o = new stdClass();
				$o->text=$htmlresult;
                $o->created_by_alias = 0;

				$dispatcher	= JDispatcher::getInstance();

				JPluginHelper::importPlugin('content');

				$r = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$_params, 0));


				return $o->text;



        }



}
