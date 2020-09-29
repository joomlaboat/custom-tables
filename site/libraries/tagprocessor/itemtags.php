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

class tagProcessor_Item
{
    public static function process($advancedtagprocessor,&$Model,&$row,&$htmlresult,$aLink,$toolbar,$recordlist,$number,$add_label=false,$fieldNamePrefix='comes_')
	{
        tagProcessor_Item::createUserButton($Model,$row,$htmlresult,$recordlist,$number);
        tagProcessor_Item::processLink($Model,$row,$htmlresult,$recordlist,$number,$aLink);

		tagProcessor_Field::process($Model,$htmlresult,$add_label,$fieldNamePrefix);

		if($advancedtagprocessor)
			tagProcessor_Server::process($Model,$htmlresult);

		tagProcessor_Shopping::getShoppingCartLink($Model,$htmlresult,$row);

		$p=strpos($aLink,'&returnto');
		$p2=strpos($aLink,'&amp;returnto');

		if($p===false and $p2===false )
		{
		   $htmlresult=str_replace('{linknoreturn}',$aLink,$htmlresult);
		}
		else
		{
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

        $id=0;
		if(isset($row) and isset($row['listing_id']))
            $id=(int)$row['listing_id'];

		$htmlresult=str_replace('{id}',$id,$htmlresult);
		$htmlresult=str_replace('{number}',$number,$htmlresult);

		if(isset($row) and isset($row['published']))
		{
			$htmlresult=str_replace('{published}',($row['published']==1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO')),$htmlresult);
			$htmlresult=str_replace('{_value:published}',$row['published']==1,$htmlresult);
		}

		if(isset($row) and isset($row['published']))
			tagProcessor_Item::GetSQLJoin($Model,$htmlresult,$row['listing_id']);


		if(isset($row) and isset($row['published']))
			tagProcessor_Item::GetCustomToolBar($htmlresult,$toolbar);

		CT_FieldTypeTag_ct::ResolveStructure($Model,$htmlresult);
	}

    public static function checkAccess(&$Model,$ug,&$row)
	{
        if(!isset($Model->isUserAdministrator))
            return false;

		$user = JFactory::getUser();

		if($ug==1)
			$usergroups =array();
		else
			$usergroups = $user->get('groups');

		$isok=false;

		if($Model->isUserAdministrator or in_array($ug,$usergroups))
			$isok=true;
		else
		{
			if(isset($row) and isset($row['published']) and  $Model->useridfieldname!='')
			{
				$uid=$row['es_'.$Model->useridfieldname];

				if($uid==$Model->userid and $Model->userid!=0)
					$isok=true;
			}
		}

		return $isok;
	}

    public static function getToolbar(&$imagegalleries,&$fileboxes,&$Model,&$row)
	{
		$WebsiteRoot=JURI::root(true);
		if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
			$WebsiteRoot.='/';

		$toolbar=array();

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

		$id=$row['listing_id'];

		if($isEditable)
		{
			//Edit
			$toolbar['edit']=tagProcessor_Item::renderEditIcon($Model,$row);

			//Refresh
			$rid='esRefreshIcon'.$Model->estableid.'x'.$id;
            $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_REFRESH' );
			$img='<img src="'.JURI::root(true).'/components/com_customtables/images/refresh.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			$toolbar['refresh']='<div id="'.$rid.'" class="toolbarIcons"><a href="javascript:esRefreshObject('.$id.', \''.$rid.'\');">'.$img.'</a></div>';

			//Image Gallery
			if(count($imagegalleries)>0)
				$toolbar['gallery']=tagProcessor_Item::renderImageGalleryIcon($imagegalleries,$Model,$row,$WebsiteRoot,$Model->current_url);

			//Filebox
			if(is_array($fileboxes) and count($fileboxes)>0)
				$toolbar['filebox']=tagProcessor_Item::renderFileBoxIcon($id,$fileboxes,$Model,$row,$WebsiteRoot,$Model->current_url);
		}

		if($isDeletable)
			$toolbar['delete']=tagProcessor_Item::renderDeleteIcon($Model,$row);

		if($isPublishable)
		{
			$rid='esPublishIcon'.$Model->estableid.'x'.$id;

			if($row['listing_published'])
			{
				$link='javascript:esPublishObject('.$id.', \''.$rid.'\',0);';
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNPUBLISH' );
				$img='<img src="'.JURI::root(true).'/components/com_customtables/images/publish.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			}
			else
			{
				$link='javascript:esPublishObject('.$id.', \''.$rid.'\',1);';
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISH' );
				$img='<img src="'.JURI::root(true).'/components/com_customtables/images/unpublish.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			}
			$toolbar['publish']='<div id="'.$rid.'" class="toolbarIcons"><a href="'.$link.'">'.$img.'</a></div>';
		}
		else
		{
			if(!$row['listing_published'])
				$toolbar['publish']=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED');
		}


		$rid='esCheckbox'.$Model->estableid.'x'.$id;
		$toolbar['checkbox']='<input type="checkbox" name="esCheckbox'.$Model->estableid.'" id="'.$rid.'" value="'.$id.'" />';

		return $toolbar;
	}


    protected static function renderEditIcon(&$Model,&$row)
	{
		$id=$row['listing_id'];

		$WebsiteRoot=JURI::root(true);
			if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
				$WebsiteRoot.='/';

		$WebsiteRoot=JURI::root(true);
		if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
		$WebsiteRoot.='/';

		$editlink=$WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'
						.'&amp;returnto='.$Model->encoded_current_url
						.'&amp;listing_id='.$row['listing_id'];

		if(JFactory::getApplication()->input->get('tmpl','','CMD')!='')
			$editlink.='&tmpl='.JFactory::getApplication()->input->get('tmpl','','CMD');

		if($Model->Itemid>0)
			$editlink.='&amp;Itemid='.$Model->Itemid;

        $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT' );
		$img='<img src="'.JURI::root(true).'/components/com_customtables/images/edit.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

		$rid='esEditIcon'.$Model->estableid.'x'.$id;
		$link=$editlink;

		return '<div id="'.$rid.'" class="toolbarIcons"><a href="'.$link.'">'.$img.'</a></div>';
	}

	protected static function renderImageGalleryIcon($imagegalleries,&$Model,&$row,$WebsiteRoot)
	{


		foreach($imagegalleries as $gallery)
		{
			$imagemanagerlink='index.php?option=com_customtables&amp;view=editphotos'
				.'&amp;establename='.$Model->establename
				.'&amp;galleryname='.$gallery[0]
				.'&amp;listing_id='.$row['listing_id']
				.'&amp;returnto='.$Model->encoded_current_url;

			if(JFactory::getApplication()->input->get('tmpl','','CMD')!='')
				$imagemanagerlink.='&tmpl='.JFactory::getApplication()->input->get('tmpl','','CMD');

			if($Model->Itemid>0)
				$imagemanagerlink.='&amp;Itemid='.$Model->Itemid;

			$rid='esImageGalleryIcon'.$Model->estableid.'x'.$row['listing_id'];
            $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PHOTO_MANAGER' ).' ('.$gallery[1].')';
			$img='<img src="'.JURI::root(true).'/components/com_customtables/images/photomanager.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

			return '<div id="'.$rid.'" class="toolbarIcons"><a href="'.$WebsiteRoot.$imagemanagerlink.'">'.$img.'</a></div>';

		}
	}

	protected static function renderFileBoxIcon($id,&$fileboxes,&$Model,&$row,$WebsiteRoot)
	{
		foreach($fileboxes as $filebox)
		{
			$filemanagerlink='index.php?option=com_customtables&amp;view=editfiles'
				.'&amp;establename='.$Model->establename
				.'&amp;fileboxname='.$filebox[0]
				.'&amp;listing_id='.$row['listing_id']
				.'&amp;returnto='.$Model->encoded_current_url;

			if(JFactory::getApplication()->input->get('tmpl','','CMD')!='')
				$filemanagerlink.='&tmpl='.JFactory::getApplication()->input->get('tmpl','','CMD');

			if($Model->Itemid>0)
				$filemanagerlink.='&amp;Itemid='.$Model->Itemid;

            $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_MANAGER').' ('.$filebox[1].')';
			$img='<img src="'.JURI::root(true).'/components/com_customtables/images/filemanager.png" border="0" '
							.'alt="'.$alt.'" '
							.'title="'.$alt.'">';

			$rid='esFileBoxIcon'.$Model->estableid.'x'.$id;

			return '<div id="'.$rid.'" class="toolbarIcons"><a href="'.$WebsiteRoot.$filemanagerlink.'">'.$img.'</a></div>';
		}
	}

	protected static function getFieldCleanValue4RDI(&$Model,&$row,&$mFld)
	{
		$titlefield=$mFld['fieldname'];
		if(strpos($mFld['type'],'multi')!==false)
			$titlefield.=$Model->langpostfix;

		$fieldtitlevalue=$row['es_'.$titlefield];
		$deleteLabel=strip_tags($fieldtitlevalue);

		$deleteLabel=trim(preg_replace("/[^a-zA-Z0-9 ,.]/", "", $deleteLabel));
		$deleteLabel = preg_replace('/\s{3,}/',' ', $deleteLabel);

		return $deleteLabel;
	}

	protected static function renderDeleteIcon(&$Model,&$row)
	{
		$id=$row['listing_id'];

				$fieldtitlevalue='';

				//First, find default field
				$selectedfiled=array();
				foreach($Model->esfields as $mFld)
				{
					$ordering=(int)$mFld['ordering'];
					if($ordering==-1)
					{
						$fieldtitlevalue=tagProcessor_Item::getFieldCleanValue4RDI($Model,$row,$mFld);
						if($fieldtitlevalue!='')
							break;
					}
				}

				if($fieldtitlevalue=='') //Default field not found
				{
					//Find any available field
					foreach($Model->esfields as $mFld)
					{
						if($mFld['type']!='dummy')
						{
							$fieldtitlevalue=tagProcessor_Item::getFieldCleanValue4RDI($Model,$row,$mFld);
							if($fieldtitlevalue!='')
								break;
						}
					}
				}

				$deleteLabel=substr($fieldtitlevalue,-100);

				$rid='esDeleteIcon'.$Model->estableid.'x'.$id;
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE' );
				$img='<img src="'.JURI::root(true).'/components/com_customtables/images/delete.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

				return '<div id="'.$rid.'" class="toolbarIcons"><a href=\'javascript:esDeleteObject("'.$deleteLabel.'", '.$row['listing_id'].', "'.$rid.'")\'>'.$img.'</a></div>';

	}

    protected static function GetSQLJoin(&$Model,&$htmlresult,$id)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');

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

				$order_by_option='';

				$isOk=true;

				$sj_function=$opts[0];

				$sj_tablename=$opts[1];
				if($sj_tablename=='')
					$isOk=false;

				//field1_findwhat
				$field1_findwhat=$opts[2];
				if($field1_findwhat=='')
					$isOk=false;

				if($field1_findwhat[0]!='_')
					$field1_findwhat='es_'.$field1_findwhat;
				else
					$field1_findwhat=substr($field1_findwhat,1);

				//field2_lookwhere
				$field2_lookwhere=$opts[3];
				if($field2_lookwhere=='')
					$isOk=false;

				if($field2_lookwhere[0]!='_')
				{
					$field2_type_row=ESFields::getFieldRowByName($field2_lookwhere, 0,$sj_tablename);

					$field2_type=$field2_type_row->type;
					$field2_lookwhere='es_'.$field2_lookwhere;

				}
				else
				{
					$field2_lookwhere=substr($field2_lookwhere,1);
					$field2_type='';
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
					if($field3_readvalue[0]!='_')
						$field3_readvalue='es_'.$field3_readvalue;
					else
						$field3_readvalue=substr($field3_readvalue,1);
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

								//$w[]='#__customtables_table_'.$sj_tablename.'.es_'.$b;
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
					$order_by_option=$opts[6];



				if($isOk)
				{

					if($sj_function=='count')
						$query = 'SELECT count(#__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='sum')
						$query = 'SELECT sum(#__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='avg')
						$query = 'SELECT avg(#__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='min')
						$query = 'SELECT min(#__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.') AS vlu ';
					elseif($sj_function=='max')
						$query = 'SELECT max(#__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.') AS vlu ';

					else
					{
						//need to resolve record value if it's "records" type

						$query = 'SELECT #__customtables_table_'.$sj_tablename.'.'.$field3_readvalue.' AS vlu '; //value or smart
					}


					$query.=' FROM #__customtables_table_'.$Model->establename.' ';

					if($Model->establename!=$sj_tablename)
					{
						// Join not needed when we are in the same table
						$query.=' LEFT JOIN #__customtables_table_'.$sj_tablename.' ON ';

						if($field2_type=='records')
						{
							$query.='INSTR(#__customtables_table_'.$sj_tablename.'.'.$field2_lookwhere.',  CONCAT(",",#__customtables_table_'.$Model->establename.'.'.$field1_findwhat.',","))' ;

						}
						else
						{
							$query.=' #__customtables_table_'.$Model->establename.'.'.$field1_findwhat.' = '
							.' #__customtables_table_'.$sj_tablename.'.'.$field2_lookwhere;
						}

					}


                        $wheres=array();


						if($Model->establename!=$sj_tablename)
						{
							//don't attach to specific record when it is the same table, example : to find averages
							$wheres[]='#__customtables_table_'.$Model->establename.'.id='.$id;
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
						{
							$query.=' ORDER BY #__customtables_table_'.$sj_tablename.'.es_'.$order_by_option;
						}

						$query.=' LIMIT 1';

					$db->setQuery($query);

//					if (!$db->query())
//                    {
//						$vlu='query error: '.$db->stderr();
  //                  }
//					else
				//	{
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

								$temp_esTable=new ESTables;
								$temp_tablerow = $temp_esTable->getTableRowByNameAssoc($sj_tablename);
								$temp_estableid=$temp_tablerow['id'];
								$temp_esfields = ESFields::getFields($temp_estableid);

								foreach($temp_esfields as $ESField)
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
				//	}


				}//if($isOk)
				else
					$vlu='syntax error';


				$htmlresult=str_replace($fItem,$vlu,$htmlresult);
				$i++;

			}//if(count($opts)=5)




		}//foreach($fList as $fItem)

	}//function GetSQLJoin(&$htmlresult)





	
    protected static function GetCustomToolBar(&$htmlresult,$toolbar)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('toolbar',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$vlu='';
			if($options[$i]=='')
				$modes=array('edit','gallery','publish','delete','refresh');
			else
				$modes=explode(',',$options[$i]);

			foreach($modes as $mode)
			{
				if(isset($toolbar[$mode]))
					$vlu.=$toolbar[$mode];
			}

			$htmlresult=str_replace($fItem,$vlu,$htmlresult);
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

    protected static function prepareDetailsLink($Model,$row,$menu_item_alias="")
    {
        //if(strpos($Model->current_url,'option=com_customtables')!==false or $menu_item_alias!="")
        //{
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
                if($Model->alias_fieldname=='' or $row['es_'.$Model->alias_fieldname]=='')
                    $viewlink.='&view=details';
            }

            if($Model->alias_fieldname!='' and $row['es_'.$Model->alias_fieldname]!='')
                $viewlink.='&alias='.$row['es_'.$Model->alias_fieldname];
            else
                $viewlink.='&listing_id='.$row['listing_id'];


            if($menu_item_id==0)
                $menu_item_id=$Model->Itemid;

            $viewlink.='&Itemid='.$menu_item_id;//.'&amp;returnto='.$returnto;
            $viewlink=JRoute::_($viewlink);
        //}
        //else
        //{
          //  $pair=explode('?',$Model->current_url);
            //$viewlink=$Model->current_sef_url.$row['es_'.$Model->alias_fieldname];
            //if($Model->current_sef_url_query!='')
              //  $viewlink.=$Model->current_sef_url_query;
        //}


        return $viewlink;
    }

    public static function RenderResultLine(&$Model,&$row,$showanchor)
    {
        $jinput=JFactory::getApplication()->input;

		if($Model->print)
				$viewlink='#z';
		else
		{
            $returnto=base64_encode($Model->current_url.'#a'.$row['listing_id']);
			$viewlink=tagProcessor_Item::prepareDetailsLink($Model,$row);//$Model->WebsiteRoot.'index.php?option=com_customtables&amp;view=details&amp;listing_id='.$row['listing_id'].'&amp;Itemid='.$Model->Itemid;//.'&amp;returnto='.$returnto;

			if($jinput->getCmd('tmpl')!='')
				$viewlink.='&tmpl='.$jinput->getCmd('tmpl');
		}

		if($showanchor)
			$htmlresult='<a name="a'.$row['listing_id'].'"></a>';
		else
			$htmlresult='';

		$layout='';

		if($Model->print==1)
			$toolbar=array();
		else
			$toolbar=tagProcessor_Item::getToolbar($Model->imagegalleries,$Model->fileboxes,$Model,$row);
            
        if($Model->LayoutProc->layoutType==2)
        {
            require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');
            $pagelayout_temp=$Model->LayoutProc->layout;//Temporary remember original layout
            $htmlresult=$pagelayout_temp;

            $prefix='table_'.$Model->establename.'_'.$row['id'].'_';
            tagProcessor_Edit::process($Model,$htmlresult,$row,$prefix);//Process edit form layout
            
            $Model->LayoutProc->layout=$htmlresult;//Temporary replace original layout with processed result
			$htmlresult=$Model->LayoutProc->fillLayout($row,null,'','||',false,true,$prefix);//Process field values

            $Model->LayoutProc->layout=$pagelayout_temp;//Set original layout as it was before, to process other records
        }
        else
            $htmlresult.=$Model->LayoutProc->fillLayout($row,$viewlink,$toolbar,'[]',false);

		return $htmlresult;
    }

    protected static function createUserButton(&$Model,&$row,&$pagelayout,$recordlist,$number)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('createuser',$options,$pagelayout,'{}',':','"');

		$i=0;

		foreach($fList as $fItem)
		{
            $parts=JoomlaBasicMisc::csv_explode(",",$options[$i],'"', false);//true

            if(count($parts)!=5)
            {
                $vlu=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_ALL_PARAMETERS_PROVIDED' );
            }
            else
            {
                $user_name=preg_replace("/[^A-Za-z0-9 ]/", "", $parts[1]);//Sanitize user name
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_CREATEUSER' );
                $img='<img src="'.JURI::root(true).'/components/com_customtables/images/key.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
                $vlu='<div class="toolbarIcons"><a href=\'javascript:ctCreateUser("'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USERWILLBECREATED' ).' '.$user_name.'?", '.$row['listing_id'].')\'>'.$img.'</a></div>';
            }

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}

	}
}
