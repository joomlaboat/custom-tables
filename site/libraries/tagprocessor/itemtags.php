<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\RecordToolbar;

class tagProcessor_Item
{
    public static function process($advancedtagprocessor,&$Model,&$row,&$htmlresult,$aLink,$recordlist,$number,$add_label=false,$fieldNamePrefix='comes_')
	{
        tagProcessor_Item::processLink($Model,$row,$htmlresult,$recordlist,$number,$aLink);

		tagProcessor_Field::process($Model->ct,$htmlresult,$add_label,$fieldNamePrefix);

		if($advancedtagprocessor)
			tagProcessor_Server::process($htmlresult);

		tagProcessor_Shopping::getShoppingCartLink($Model,$htmlresult,$row);

		$p=strpos($aLink,'&returnto');
		$p2=strpos($aLink,'&amp;returnto');

		if($p===false and $p2===false )
		{
		   $htmlresult=str_replace('{linknoreturn}',$aLink,$htmlresult);
		}
		else
		{
			//here we assume that the return to is always the last parameter
			if($p)
			{
				$aLinkNoReturn=substr($aLink,0,$p);
				$htmlresult=str_replace('{linknoreturn}',$aLinkNoReturn,$htmlresult);
			}

			if($p2)
			{
				$aLinkNoReturn=substr($aLink,0,$p2);
				$htmlresult=str_replace('{linknoreturn}',$aLinkNoReturn,$htmlresult);
			}
		}

		$htmlresult=str_replace('{recordlist}',$recordlist,$htmlresult);

		$listing_id = 0;
		
		if(isset($row) and isset($row['listing_id']))
			$listing_id = (int)$row['listing_id'];
			
		$htmlresult=str_replace('{id}',$listing_id,$htmlresult);
		$htmlresult=str_replace('{number}',$number,$htmlresult);

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::processPublishStatus($row,$htmlresult);

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::GetSQLJoin($Model,$htmlresult,$row['listing_id']);

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::GetCustomToolBar($Model,$htmlresult,$row);

		CT_FieldTypeTag_ct::ResolveStructure($Model,$htmlresult);
	}

    public static function checkAccess(&$Model,$ug,&$row)
	{
        if(!isset($Model->ct->Env->isUserAdministrator))
            return false;

		$user = JFactory::getUser();

		if($ug==1)
			$usergroups =array();
		else
			$usergroups = $user->get('groups');

		$isok=false;

		if($Model->ct->Env->isUserAdministrator or in_array($ug,$usergroups))
			$isok=true;
		else
		{
			if(isset($row) and isset($row['listing_published']) and $Model->ct->Table->useridfieldname!='')
			{
				$uid=$row[$Model->ct->Table->useridrealfieldname];

				if($uid==$Model->ct->Env->userid and $Model->ct->Env->userid!=0)
					$isok=true;
			}
		}

		return $isok;
	}

    

    protected static function GetSQLJoin(&$Model,&$htmlresult,$id)
	{
		$ct = $Model->ct;
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('sqljoin',$options,$htmlresult,'{}');
		if(count($fList)==0)
			return;

		$db = JFactory::getDBO();
		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

			if(count($opts)>=5) //dont even try if less than 5 parameters
			{
				$field2_type='';

				$order_by_option='';

				$isOk=true;

				$sj_function=$opts[0];

				$sj_tablename=$opts[1];

				if($sj_tablename=='')
				{
					$isOk=false;
				}
				else
				{
					$tablerow = ESTables::getTableRowByNameAssoc($sj_tablename);
					if(!is_array($tablerow))
						$isOk=false;
				}
				
				//field1_findwhat
				$field1_findwhat=$opts[2];
				if($field1_findwhat=='')
					$isOk=false;

				if($isOk)
				{
					if($field1_findwhat=='_id')
					{
						$field1_findwhat=$ct->Table->tablerow['realidfieldname'];
					}
					elseif($field1_findwhat=='_published')
					{
						if($ct->Table->tablerow['published_field_found'])
							$field1_findwhat='published';
						else
						{
							$field1_findwhat='';
							$isOk=false;
						}
					}
					else
					{
						$field1_row=Fields::getFieldRowByName($field1_findwhat, $ct->Table->tablerow['id']);
						if(is_object($field1_row))
							$field1_findwhat=$field1_row->realfieldname;
						else
						{
							$field1_findwhat='';
							$isOk=false;
						}
					}
				}

				//field2_lookwhere
				$field2_lookwhere=$opts[3];
				if($field2_lookwhere=='')
					$isOk=false;
				
				if($isOk)
				{
					if($field2_lookwhere=='_id')
						$field2_lookwhere=$tablerow['realidfieldname'];
					elseif($field2_lookwhere=='_published')
					{
						if($tablerow['published_field_found'])
							$field2_lookwhere='published';
						else
						{
							$field2_lookwhere='';
							$isOk=false;
						}
					}
					else
					{
						$field2_type_row=Fields::getFieldRowByName($field2_lookwhere, $tablerow['id']);
						if(is_object($field2_type_row))
						{
							$field2_type=$field2_type_row->type;
							$field2_lookwhere=$field2_type_row->realfieldname;
						}
						else
						{
							$field2_type='';
							$field2_lookwhere='';
							$isOk=false;
						}
					}
				}

				$opt4_pair=JoomlaBasicMisc::csv_explode(':', $opts[4], '"', false);
				$FieldName=$opt4_pair[0]; //The field to get value from
				if(isset($opt4_pair[1])) //Custom parameters
					$field_option=$opt4_pair[1];
				else
					$field_option='';

				$field3_readvalue=$FieldName;

				if($field3_readvalue=='')
					$isOk=false;
				else
				{
					if($field3_readvalue=='_id')
							$field3_readvalue=$tablerow['realidfieldname'];
					elseif($field3_readvalue=='_published')
					{
						if($tablerow['published_field_found'])
							$field3_readvalue='published';
						else
						{
							$field3_readvalue='';
							$isOk=false;
						}
					}
					else
					{
				
						$field3_row=Fields::getFieldRowByName($field3_readvalue, $tablerow['id']);
						if(is_array($tablerow))
							$field3_readvalue=$field3_row->realfieldname;
						else
						{
							$field3_readvalue='';
							$isOk=false;
						}
					}
				}
				
				$additional_where='';
				if(isset($opts[5]) and $opts[5]!='')
				{
					$w=array();
					$af=explode(' ',$opts[5]);
					foreach($af as $a)
					{
						$b=strtolower(trim($a));
						if($b!='')
						{
							if($b!='and' and $b!='or')
							{
								$b=str_replace('$now','now()',$b);

								//read $get_ values
								$b=tagProcessor_Value::ApplyQueryGetValue($b,$sj_tablename);

								$w[]=$b;
							}
							else
								$w[]=$b;
						}

					}
					$additional_where=implode(' ',$w);
				}
               
				//order by
				if(isset($opts[6]) and $opts[6]!='')
				{
					$order_by_option=$opts[6];
					$order_by_option_row=Fields::getFieldRowByName($order_by_option, $tablerow['id']);
					$order_by_option=$order_by_option_row->realfieldname;
				}

				if($isOk)
				{

					if($sj_function=='count')
						$query = 'SELECT count('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='sum')
						$query = 'SELECT sum('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='avg')
						$query = 'SELECT avg('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='min')
						$query = 'SELECT min('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='max')
						$query = 'SELECT max('.$tablerow['realtablename'].'.'.$field3_readvalue.') AS vlu ';
					else
					{
						//need to resolve record value if it's "records" type
						$query = 'SELECT '.$tablerow['realtablename'].'.'.$field3_readvalue.' AS vlu '; //value or smart
					}

					$query.=' FROM '.$ct->Table->realtablename.' ';

					if($ct->Table->tablename!=$sj_tablename)
					{
						// Join not needed when we are in the same table
						$query.=' LEFT JOIN '.$tablerow['realtablename'].' ON ';

						if($field2_type=='records')
						{
							$query.='INSTR('.$tablerow['realtablename'].'.'.$field2_lookwhere.',  CONCAT(",",'.$ct->Table->realtablename.'.'.$field1_findwhat.',","))' ;
						}
						else
						{
							$query.=' '.$ct->Table->realtablename.'.'.$field1_findwhat.' = '
							.' '.$tablerow['realtablename'].'.'.$field2_lookwhere;
						}
					}

                        $wheres=array();

						if($ct->Table->tablename!=$sj_tablename)
						{
							//don't attach to specific record when it is the same table, example : to find averages
							$wheres[]=$ct->Table->realtablename.'.'.$ct->Table->tablerow['realidfieldname'].'='.$id;
						}
						else
						{
                            //$wheres[]='#__customtables_table_'.$sj_tablename.'.published=1';//to join with published record only, preferably set in parameters
                        }


						if($additional_where!='')
							$wheres[]='('.$additional_where.')';

                        if(count($wheres)>0)
                            $query.=' WHERE '.implode(' AND ', $wheres);

						if($order_by_option!='')
							$query.=' ORDER BY '.$tablerow['realtablename'].'.'.$order_by_option;

						$query.=' LIMIT 1';

						$db->setQuery($query);

						$rows=$db->loadAssocList();

						if(count($rows)==0)
							$vlu='';
						else
						{
							$row=$rows[0];

							if($sj_function=='smart')
							{
								$getGalleryRows=array();
								$getFileBoxRows=array();
								$vlu=$row['vlu'];

								$temp_ctfields = Fields::getFields($tablerow['id']);

								foreach($temp_ctfields as $ESField)
								{
									if($ESField['fieldname']==$FieldName)
									{
                                        $value_option_list=explode(',',$field_option);
										$vlu=tagProcessor_Value::getValueByType($Model,$row['vlu'],$FieldName, $ESField['type'],$ESField['typeparams'],$value_option_list,$getGalleryRows,$getFileBoxRows,0,$row,$ESField['id']);
										break;
									}
								}
							}
							else
								$vlu=$row['vlu'];
						}

				}//if($isOk)
				else
					$vlu='syntax error';


				$htmlresult=str_replace($fItem,$vlu,$htmlresult);
				$i++;

			}//if(count($opts)=5)




		}//foreach($fList as $fItem)

	}//function GetSQLJoin(&$htmlresult)



	protected static function processPublishStatus(&$row,&$htmlresult)
	{
		$htmlresult=str_replace('{_value:published}',$row['listing_published']==1,$htmlresult);

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('published',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$vlu='';
			if($options[$i]=='number')
				$vlu = (int)$row['listing_published'];
			elseif($options[$i]=='boolean')
				$vlu = $row['listing_published']==1 ? 'true' : 'false';
			else
				$vlu = $row['listing_published']==1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');
			
			$htmlresult=str_replace($fItem,$vlu,$htmlresult);
			
			$i++;
		}
	}

	
    protected static function GetCustomToolBar(&$Model,&$htmlresult,&$row)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('toolbar',$options,$htmlresult,'{}');
		
		if(count($fList) == 0)
			return;
		
		
		$edit_userGroup=(int)$Model->params->get( 'editusergroups' );
		$publish_userGroup=(int)$Model->params->get( 'publishusergroups' );
		if($publish_userGroup==0)
			$publish_userGroup=$edit_userGroup;

		$delete_userGroup=(int)$Model->params->get( 'deleteusergroups' );
		if($delete_userGroup==0)
			$delete_userGroup=$edit_userGroup;
		
		$isEditable=tagProcessor_Item::checkAccess($Model,$edit_userGroup,$row);
		$isPublishable=tagProcessor_Item::checkAccess($Model,$publish_userGroup,$row);
		$isDeletable=tagProcessor_Item::checkAccess($Model,$delete_userGroup,$row);
		
		$RecordToolbar = new RecordToolbar($Model->ct,$isEditable, $isPublishable, $isDeletable, $Model->Itemid);

		$i=0;
		foreach($fList as $fItem)
		{
			if($Model->ct->Env->print==1)
			{
				$htmlresult=str_replace($fItem,'',$htmlresult);
			}
			else
			{
				$modes = explode(',',$options[$i]);
				if(count($modes)==0 or $options[$i] == '')
					$modes = ['edit','refresh','publish','delete'];

				$icons=[];
				foreach($modes as $mode)
					$icons[] = $RecordToolbar->render($row,$mode);
				
				$vlu = implode('',$icons);
				$htmlresult=str_replace($fItem,$vlu,$htmlresult);
			}
			
			$i++;
		}
	}

    protected static function processLink(&$Model,&$row,&$pagelayout,$recordlist,$number,$aLink)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('link',$options,$pagelayout,'{}',':','"');

		$i=0;

		foreach($fList as $fItem)
		{
            $opt=$options[$i];
            if($opt=='')
                $vlu=$aLink;
            else
                $vlu=tagProcessor_Item::prepareDetailsLink($Model,$row,$opt);

            $pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
        }
    }

    protected static function prepareDetailsLink($Model,$row,$menu_item_alias="",$returnto='')
    {
            //SEF Off
            $menu_item_id=0;
            $viewlink='';

            if($menu_item_alias!="")
            {
                $menu_item=JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
                if($menu_item!=0)
                {
                    $menu_item_id=(int)$menu_item['id'];
                    $link=$menu_item['link'];

                    if($link!='')
                    {
                        $viewlink=$link;

                        $view=JoomlaBasicMisc::getURLQueryOption($viewlink,'view');
                        $viewlink=JoomlaBasicMisc::deleteURLQueryOption($viewlink, 'view');
                    }

                }
            }


            if($viewlink=='')
            {
                $viewlink='index.php?option=com_customtables';//&amp;view=details';
                if($Model->ct->Table->alias_fieldname=='' or $row['es_'.$Model->ct->Table->alias_fieldname]=='')
                    $viewlink.='&view=details';
            }

            if($Model->ct->Table->alias_fieldname!='' and $row['es_'.$Model->ct->Table->alias_fieldname]!='')
                $viewlink.='&alias='.$row['es_'.$Model->ct->Table->alias_fieldname];
            else
                $viewlink.='&listing_id='.$row['listing_id'];


            if($menu_item_id==0)
                $menu_item_id=$Model->Itemid;

            $viewlink.='&Itemid='.$menu_item_id;//.'&amp;returnto='.$returnto;
			
			if($returnto!='')
				$viewlink.='&returnto='.$returnto;
			
            $viewlink=JRoute::_($viewlink);

        return $viewlink;
    }

    public static function RenderResultLine(&$Model,&$row,$showanchor)
    {
        $jinput=JFactory::getApplication()->input;

		if($Model->ct->Env->print)
				$viewlink='#z';
		else
		{
            $returnto=base64_encode($Model->ct->Env->current_url.'#a'.$row['listing_id']);
			$viewlink=tagProcessor_Item::prepareDetailsLink($Model,$row,'',$returnto);

			if($jinput->getCmd('tmpl')!='')
				$viewlink.='&tmpl='.$jinput->getCmd('tmpl');
		}

		if($showanchor)
			$htmlresult='<a name="a'.$row['listing_id'].'"></a>';
		else
			$htmlresult='';

		$layout='';

		if($Model->LayoutProc->layoutType==2)
        {
            require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');
            $pagelayout_temp=$Model->LayoutProc->layout;//Temporary remember original layout
            $htmlresult=$pagelayout_temp;

            $prefix='table_'.$Model->ct->Table->tablename.'_'.$row['listing_id'].'_';
            tagProcessor_Edit::process($Model,$htmlresult,$row,$prefix);//Process edit form layout
            
            $Model->LayoutProc->layout=$htmlresult;//Temporary replace original layout with processed result
			$htmlresult=$Model->LayoutProc->fillLayout($row,null,'||',false,true,$prefix);//Process field values

            $Model->LayoutProc->layout=$pagelayout_temp;//Set original layout as it was before, to process other records
        }
        else
            $htmlresult.=$Model->LayoutProc->fillLayout($row,$viewlink,'[]',false);

		return $htmlresult;
    }
}
