<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class CTOrdering
{
    public static function getOrderingQuery(&$ordering,&$query,&$inner,$esordering,$langpostfix,$tablename,&$esfields)
	{
	
						if(stripos($esordering,'.user')!==false)
						{		//user

								$oPair=explode(' ',$esordering);
								$oPair2=explode('.',$oPair[0]);

								$fieldname=$oPair2[0];
								$realfieldname=ESFields::getRealFieldName($fieldname,$esfields);
								
								if($realfieldname!='')
								{
									if(isset($oPair[1]))
										$direction=$oPair[1];
									else
										$direction='';

									$inner[]='LEFT JOIN #__users ON #__users.id='.$tablename.'.'.$realfieldname.'';
									$query.=', name AS t1';

									$ordering[]='#__users.name'.($direction!='' ? ' DESC' : '');
								}

						}
						elseif(stripos($esordering,'.customtables')!==false)
						{		//custom tables

								$oPair=explode(' ',$esordering);
								$oPair2=explode('.',$oPair[0]);

								$fieldname=$oPair2[0];
								if(isset($oPair[1]))
										$direction=$oPair[1];
								else
										$direction='';

								$join_found=false;
								foreach($inner as $i)
								{
									if(!(strpos($i,'#__customtables_options')===false))
									{
										$join_found=true;
									}
								}

								if(!$join_found)
								{
									$realfieldname=ESFields::getRealFieldName($fieldname,$esfields);
									if($realfieldname!='')
										$inner[]='LEFT JOIN #__customtables_options ON familytreestr='.$realfieldname.'';
								}

								$query.=', #__customtables_options.title'.$langpostfix.' AS t1';

								$ordering[]='title'.$langpostfix.($direction!='' ? ' DESC' : '');
						}
						elseif(stripos($esordering,'.sqljoin')!==false)
						{		//sql join
								$oPair=explode(' ',$esordering);
								$oPair2=explode('.',$oPair[0]);

								$fieldname=$oPair2[0];
								if(isset($oPair[1]))
										$direction=$oPair[1];
								else
										$direction='';

								if(isset($oPair2[2]))
								{

										$typeparams=explode(',',$oPair2[2]);

										$join_table=$typeparams[0];
										
										$join_field='';
										if(isset($typeparams[1]))
											$join_field=$typeparams[1];

										if($join_table!='' and $join_field!='')
										{
											$real_joined_fieldname=$join_field;//CTOrdering::getRealFieldName($join_field,$esfields);
											
											$realfieldname=ESFields::getRealFieldName($fieldname,$esfields);
											if($realfieldname!='' and $real_joined_fieldname!='')
											{
												$w='#__customtables_table_'.$join_table.'.id='.$tablename.'.'.$realfieldname;
												$ordering[]='(SELECT #__customtables_table_'.$join_table.'.es_'.$real_joined_fieldname.' FROM #__customtables_table_'.$join_table.' WHERE '.$w.') '.($direction!='' ? ' DESC' : '');
											}

										}
								}
						}
						else
						{
							if(strpos($esordering,"DATE_FORMAT")!==false)
							{
								$ordering[]=$esordering;
							}
                            else
							{
								$oPair=explode(' ',$esordering);
								
								$fieldname=$oPair[0];
								$realfieldname=ESFields::getRealFieldName($fieldname,$esfields);
								
								if($realfieldname!='')
								{
									if(isset($oPair[1]))
										$direction=' '.$oPair[1];
									else
										$direction='';
								
									$ordering[]=$realfieldname.$direction;
								}
							}
						}
	}


    public static function loadOrderFields($blockExternalVars,&$params,&$esfields,$langpostfix,&$order_list,&$order_values)
	{
				//get sort field (and direction) example "price desc"
				$jinput = JFactory::getApplication()->input;

				$mainframe = JFactory::getApplication();
				$esordering='';

				if($blockExternalVars)
				{
						//module or plugin
						if($params->get( 'sortby' )!='')
								$esordering=$params->get( 'sortby' );
				}
				else
				{
						if($params->get( 'forcesortby' )!='')
						{
							$esordering=$params->get( 'forcesortby' );

						}
						elseif(JFactory::getApplication()->input->get('esordering','','CMD'))
						{
								$esordering=JFactory::getApplication()->input->get('esordering','','CMD');
						}
						else
						{
								$esordering = $mainframe->getUserState( 'com_customtables.esorderby','' );

								if($esordering=='')

								{
									if($params->get( 'sortby' )!='')
										$esordering=$params->get( 'sortby' );
								}
						}
				}

				// Check if field exist
				$parts =explode(':',$esordering);


              	$esorderingtemp_arr=explode(' ' ,$parts[0]);
		$esorderingtemp_arr_pair=explode('.' ,$esorderingtemp_arr[0]);
		$fieldname=$esorderingtemp_arr_pair[0];
		$desc='';
		if(isset($esorderingtemp_arr[1]) and $esorderingtemp_arr[1]=='desc')
			$desc=' DESC';


                $order_params='';
                if(isset($parts[1]))
                    $order_params=trim(preg_replace("/[^a-zA-Z-+% ,_]/", "", $parts[1]));

		$esordering ='';
		$found=false;
		foreach($esfields as $row)
		{
            if($row['allowordering']==1)// and $row['hidden']==0
			{
				if($row['fieldname']==$fieldname)
				{
					$fieldtype=$row['type'];
					$typeparams=$row['typeparams'];

										if($fieldtype=='sqljoin')
												$esordering = $fieldname.'.sqljoin.'.$typeparams.$desc;
										elseif($fieldtype=='customtables')
												$esordering = $fieldname.'.customtables.'.$desc;
										elseif($fieldtype=='userid' or $fieldtype=='user')
												$esordering = $fieldname.'.user.'.$desc;
										elseif($fieldtype!='dummy')
												$esordering = $fieldname.$desc;

                                        if($fieldtype=='date' or $fieldtype=='creationtime' or $fieldtype=='changetime' or $fieldtype=='lastviewtime')
                                        {
											if($order_params!='')
											{
												$db = JFactory::getDBO();
												$esordering ='DATE_FORMAT('.$row['realfieldname'].', '.$db->quote($order_params).')'.$desc;
											}
											else
												$esordering = $fieldname.$desc;

                                        }

									$found=true;
									break;
								}//if($row['fieldname']==$fieldname)
						}//if($row['allowordering']==1)// and $row['hidden']==0
				}

				if(!$found)
				{
						//default sort by
						
						if($fieldname=='_id')
						{
							$esordering = '_id'.$desc;
							$order_list[]='ID '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );
							$order_list[]='ID '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );
							
							$order_values[]='_id';
							$order_values[]='_id desc';
						}
						elseif($fieldname=='_published')
						{
							
							$esordering = '_published'.$desc;
							$label=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED' ).' ';
							$order_list[]=$label.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );
							$order_list[]=$label.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );
							
							$order_values[]='_published';
							$order_values[]='_published desc';
						}
						else
						{
							foreach($esfields as $row)
							{
								$fieldtype=$row['type'];

								if($fieldtype=='changetime' or $fieldtype=='creationtime' or $fieldtype=='date')
								{
										$esordering=$row['fieldname'].' desc';
										break;
								}
								elseif(	$fieldtype=='multilangstring' or $fieldtype=='multilangtext' )
								{
										$esordering=$row['fieldname'].$langpostfix;
										break;
								}
								elseif($fieldtype!='dummy')
								{
										$esordering=$row['fieldname'];
										break;
								}
							}
						}

				}


				//for component only
				//prepare list of available fields to sort by
				if(!$blockExternalVars and $fieldname!='_id')
				{
						foreach($esfields as $row)
						{
								if($row['allowordering']==1)
								{
										$fieldtype=$row['type'];
										$fieldname=$row['fieldname'];
										
										if(!isset($row['fieldtitle'.$langpostfix]))
										{
											JFactory::getApplication()->enqueueMessage(
														JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
											return '';	
										}
										
										$fieldtitle=$row['fieldtitle'.$langpostfix];
										
										            
										
										$typeparams=$row['typeparams'];

										CTOrdering::getOrderByFieldString($order_list,$order_values,$fieldtype,$fieldname,$fieldtitle,$typeparams,$langpostfix);

								}

						}//foreach($rows as $row)

				}//if(!$blockExternalVars)

				//set state
				if(!$blockExternalVars)
				{
						//component

						$mainframe->setUserState( 'com_customtables.esorderby',$esordering );
				}

            return $esordering;
		}

        protected static function getOrderByFieldString(&$order_list,&$order_values,$fieldtype,$fieldname,$fieldtitle,$typeparams,$langpostfix)
		{

										if($fieldtype=='string' or $fieldtype=='email' or $fieldtype=='url')
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname;
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.' desc';
										}

										elseif($fieldtype=='sqljoin')
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.sqljoin.'.$typeparams;
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.sqljoin.'.$typeparams.' desc';
										}

										elseif($fieldtype=='phponadd' or $fieldtype=='phponchange')
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname;
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.' desc';
										}

										elseif(
												$fieldtype=='int' or
												$fieldtype=='float'
										)
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MINMAX' );			$order_values[]=$fieldname;
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MAXMIN' );			$order_values[]=$fieldname." desc";
										}
										elseif(
												$fieldtype=='changetime' or $fieldtype=='creationtime' or $fieldtype=='date'

										)
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEWOLD' );			$order_values[]=$fieldname." desc";
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OLDNEW' );			$order_values[]=$fieldname;

										}
										elseif(	$fieldtype=='multilangstring')
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.$langpostfix;
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.$langpostfix." desc";

										}
										elseif(	$fieldtype=='customtables')
										{
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.customtables';
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.customtables desc';
										}
										elseif(	$fieldtype=='userid' or $fieldtype=='user')
										{

												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ' );			$order_values[]=$fieldname.'.user';
												$order_list[]=$fieldtitle.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA' );			$order_values[]=$fieldname.'.user desc';
										}

		}

}
