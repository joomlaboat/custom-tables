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

class tagProcessor_Page
{
    public static function process(&$Model,&$pagelayout)
    {

        tagProcessor_Page::FormatLink($Model,$pagelayout);//{format:xls}  the link to the same page but in xls format

        tagProcessor_Page::PathValue($Model,$pagelayout);
        tagProcessor_Page::AddNew($Model,$pagelayout);

        tagProcessor_Page::Pagination($Model,$pagelayout);

        tagProcessor_Page::PageToolBar($Model,$pagelayout);

        tagProcessor_Page::PageToolBarCheckBox($Model,$pagelayout);

        tagProcessor_Page::SearchButton($Model,$pagelayout);
        tagProcessor_Page::SearchBOX($Model,$pagelayout);

        tagProcessor_Page::RecordCountValue($Model,$pagelayout);

        tagProcessor_Page::RecordCount($Model,$pagelayout);
        tagProcessor_Page::PrintButton($Model,$pagelayout);
    }

    public static function FormatLink(&$Model,&$pagelayout)
	{
		//Depricated. Use 	{currenturl:base64} instead
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('format',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
            if($Model->frmt!='csv')
            {
    			$link=JoomlaBasicMisc::deleteURLQueryOption($Model->current_url, 'frmt');
    			if(strpos($link,'?')===false)
    				$link.='?';
    			else
    				$link.='&';

    			$option_list=explode(',',$options[$i]);
    			$format=$option_list[0];

    			//check if format supported
    			$allowed_formats=['csv','xlsx','pdf','image'];
    			if($format=='' or !in_array($format,$allowed_formats))
				$format='csv';

    			$link.='frmt='.$format.'&clean=1';
    			$vlu='';


    			$value=(isset($option_list[1]) ? $option_list[1] : '');






    			if($value=='anchor' or $value=='')
    			{
    				$image=(isset($option_list[2]) ? $option_list[2] : '');
    				$imagesize=(isset($option_list[3]) ? $option_list[3] : '');

    				$allowed_sizes=['16','32','48','512'];
    				if($imagesize=='' or !in_array($imagesize,$allowed_sizes))
    					$imagesize=32;

    				if($format=='image')
    					$format_image='jpg';
    				else
    					$format_image=$format;

    				if($image=='')
    					$image='/components/com_customtables/images/fileformats/'.$imagesize.'px/'.$format_image.'.png';


    				$alt='Download '.strtoupper($format).' file';
    				//add image anchor link
    				$vlu='<a href="'.$link.'" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank"><img src="'.$image.'" alt="'.$alt.'" title="'.$alt.'" width="'.$imagesize.'" height="'.$imagesize.'"></a>';
    			}
    			elseif($value=='_value')
    			{
    				//link only
    				$vlu=$link;
    			}
            }
            else
            {
                $vlu='';
            }

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    public static function PathValue(&$Model,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('navigation',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
            $PathValue=tagProcessor_Page::CleanUpPath($Model->PathValue);
			if(count($PathValue)==0)
				$vlu='';
			else
			{
				$pair=explode(',',$options[$i]);

				$element_class=$pair[0];

				if(isset($pair[1]))
					$list_type=$pair[1];
				else
					$list_type='';

				if($list_type=='' or $list_type=='list')
				{
					if($element_class!='' )
						$vlu='<ul class="'.$element_class.'"><li>'.implode('</li><li>',$PathValue).'</li></ul>';
					else
						$vlu='<ul><li>'.implode('</li><li>',$PathValue).'</li></ul>';
				}
				elseif($list_type=='comma')
					$vlu=implode(',',$PathValue);
				else
					$vlu='navigation: Unknown list type';

			}


			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function AddNew(&$Model,&$pagelayout)
	{
        $jinput=JFactory::getApplication()->input;

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('add',$options,$pagelayout,'{}');

		$i=0;

        if(isset($Model->params))
            $edit_userGroup=(int)$Model->params->get( 'editusergroups' );
        else
            $edit_userGroup=0;

        $row=array();
        $isEditable=tagProcessor_Item::checkAccess($Model,$edit_userGroup,$row);

		foreach($fList as $fItem)
		{
            if($isEditable and $Model->print==0)
            {
                $opt=explode(',',$options[$i]);

                if((int)$opt[0]>0)
                	$link='/index.php?option=com_customtables&view=edititem&returnto='.$Model->encoded_current_url.'&Itemid='.$opt[0];
                if($opt[0]!='')
                	$link='/index.php/'.$opt[0].'?returnto='.$Model->encoded_current_url;
                else
                	$link='/index.php?option=com_customtables&view=edititem&returnto='.$Model->encoded_current_url.'&Itemid='.$Model->Itemid;

    			if($jinput->getCmd('tmpl','')!='')
    				$link.='&tmpl='.$jinput->get('tmpl','','CMD');
                    
                if(isset($opt[1]) and $opt[1]=='importcsv')
                {
                    $document = JFactory::getDocument();
                    $document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/uploader.js"></script>');
                    $max_file_size=JoomlaBasicMisc::file_upload_max_size();
                    
                    $Itemid=$jinput->getInt('Itemid',0);
                    $prefix='comes_';
                    $fileid=JoomlaBasicMisc::generateRandomString();
                    $fieldid='99';//$fileid;//some unique number
                    $objectname='importcsv';
                    // class="esUploadFileBox"
                    $result='<div>';
                    

                    
  //                  jimport('joomla.html.html.bootstrap');
//JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
//JHtml::_('behavior.calendar');
//JHtml::_('bootstrap.popover');
                    
                    
                    $result.='
                    <div id="ct_fileuploader_'.$objectname.'"></div>
                    <div id="ct_eventsmessage_'.$objectname.'"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        UploadFileCount=1;

                    	var urlstr="/index.php?option=com_customtables&view=fileuploader&tmpl=component&'
                        .'tableid='.$Model->estableid.'&'
                        .'task=importcsv&'
                        .$objectname.'_fileid='.$fileid.'&Itemid='.$Itemid.'&fieldname='.$objectname.'";
                        
                    	ct_getUploader('.$fieldid.',urlstr,'.$max_file_size.',"csv","ctUploadCSVForm",true,"ct_fileuploader_'.$objectname.'","ct_eventsmessage_'.$objectname.'","'.$fileid.'","'
                        .$prefix.$objectname.'","ct_uploadedfile_box_'.$objectname.'");
                    </script>
                    <input type="hidden" name="'.$prefix.$objectname.'" id="'.$prefix.$objectname.'" value="" />
			'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                    </form>
                </div>
                ';
                  //  $result.='</div>';
                    $vlu=$result;
                }
                else
        			$vlu='<a href="'.JURI::root(true).$link.'" id="ctToolBarAddNew'.$Model->estableid.'" class="toolbarIcons"><img src="'.JURI::root(true).'/components/com_customtables/images/new.png" alt="Add New" title="Add New" /></a>';
            }
            else
                $vlu='';

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function Pagination(&$Model,&$pagelayout)
	{
		$PaginationFound=false;


		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('pagination',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$minRecords=0;//(int)$options[$i];
			$vlu='';
            if($Model->print==0)
            {
                $a=tagProcessor_Page::get_Pagination($Model,$minRecords,$options[$i]);
                $vlu=$a;
            }

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}


		return $PaginationFound;

	}


    protected static function get_Pagination(&$Model,$minRecords,$option)
	{
		if($Model->TotalRows>$minRecords)
		{
			$number_of_columns=$Model->columns;
			if($number_of_columns<1)
				$number_of_columns=3;

			//if($pagination == null)
			$pagination=$Model->getPagination();

			$SelectedCategory='';// ???????????????????????????

			switch($option)
			{
				case 'limit' :
					return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($number_of_columns);
					break;
				case 'pagination' :
					return '<div class="pagination">'.$pagination->getPagesLinks("").'</div>';
					break;
				case 'order' :
					return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.$Model->getOrderBox($SelectedCategory);
					break;
				case 'limitorder' :
					return '
			<table cellpadding="0" cellspacing="0" width="100%" >
		    <tr height="30">
                <td width="140" valign="top">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($number_of_columns).'</td>
                <td align="right" valign="top">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.$Model->getOrderBox($SelectedCategory).'</td>
		    </tr>
		    </table>
		';
					break;

				case 'limitpagination' :
					return '
			<table cellpadding="0" cellspacing="0" width="100%" style="border:none;" >
		    <tr height="30">
                <td width="230" valign="top" style="border:none;">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($number_of_columns).'</td>
                <td valign="top" style="border:none;text-align:center;"><div class="pagination">'.$pagination->getPagesLinks("").'</div></td>
                <td width="230" align="right" valign="top" style="border:none;"></td>
		    </tr>
		    </table>
		';
					break;

				case 'limitpaginationorder' :
					return '
			<table cellpadding="0" cellspacing="0" width="100%" border="0" >
		    <tr height="30">
                <td width="230" valign="top" style="border:none;">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($number_of_columns).'</td>
                <td align="center" valign="top" style="border:none;"><div class="pagination">'.$pagination->getPagesLinks("").'</div></td>
                <td width="230" align="right" valign="top" style="border:none;">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.$Model->getOrderBox($SelectedCategory).'</td>
		    </tr>
		    </table>
		';
					break;

					case 'paginationorder' :
						return '
					<table cellpadding="0" cellspacing="0" width="100%" >
					    <tr><td valign="top" align="right">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY').': '.$Model->getOrderBox($SelectedCategory).'</td></tr>
						<tr><td valign="top" align="center"><br/></td></tr>
						<tr><td valign="top" align="center"><div class="pagination">'.$pagination->getPagesLinks("").'</div></td></tr>
				    </table>';
					break;

				default:

					return '<div class="pagination">'.$pagination->getPagesLinks("").'</div>';
					break;
			}


		}
	}

    protected static function PageToolBar(&$Model,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('batchtoolbar',$options,$pagelayout,'{}');

        $available_modes=array();
        
        $user = JFactory::getUser();
		if($user->id!=0)
        {
            $publish_userGroup=(int)$Model->params->get( 'publishusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($publish_userGroup))
            {
                $available_modes[]='publish';
                $available_modes[]='unpublish';
            }
            
            $edit_userGroup=(int)$Model->params->get( 'editusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($edit_userGroup))
                $available_modes[]='refresh';
                
            $delete_userGroup=(int)$Model->params->get( 'deleteusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($delete_userGroup))
                $available_modes[]='delete';
        }
        
		$found=false;

		$i=0;
		$count=0;
		foreach($fList as $fItem)
		{
			$vlu='';

            if($Model->print==0)
            {

                if($options[$i]=='')
                	$modes=$available_modes;
                else
                	$modes=explode(',',$options[$i]);

                foreach($modes as $mode)
                {
                    
                    
                    
                	if(in_array($mode,$available_modes))
                	{
                		$rid='esToolBar_'.$mode.'_box_'.$Model->estableid;
                		$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_'.strtoupper($mode).'_SELECTED' );
                   		$img='<img src="'.JURI::root(true).'/components/com_customtables/images/'.$mode.'.png" border="0" alt="'.$alt.'" title="'.$alt.'" />';
                		$link='javascript:esToolBarDO("'.$mode.'", '.$Model->estableid.')';
                		$vlu.='<div id="'.$rid.'" class="toolbarIcons"><a href=\''.$link.'\'>'.$img.'</a></div>';
                	}
                }

                if($vlu!='')
                	$found=true;
            }

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);


			$i++;
		}

		if($found)
			tagProcessor_Page::PageToolBarCheckBox($Model,$pagelayout);
	}


    static protected function PageToolBarCheckBox(&$Model,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('checkbox',$options,$pagelayout,'{}');

		foreach($fList as $fItem)
		{
            if($Model->print==0 and $Model->frmt!='csv')
                $vlu='<input type="checkbox" id="esCheckboxAll'.$Model->estableid.'" onChange="esCheckboxAllclicked('.$Model->estableid.')" />';
            else
                $vlu='';

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
		}

	}

    static protected function getFieldTitles(&$Model,$list_of_fields)
    {
        $fieldtitles=array();
        foreach($list_of_fields as $fieldname)
        {
            foreach($Model->esfields as $fld)
            {
				if($fld['fieldname']==$fieldname)
                    $fieldtitles[]=$fld['fieldtitle'.$Model->langpostfix];
            }
        }
        return $fieldtitles;
    }
    
    static protected function SearchBOX(&$Model,&$pagelayout)
	{
    	$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('search',$options,$pagelayout,'{}');

		$fields=array();
		if(count($fList))
		{
			require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'essearchinputbox.php');
			$ESSIB=new ESSerachInputBox;
			$ESSIB->langpostfix=$Model->langpostfix;
			$ESSIB->es=$Model->es;
			$ESSIB->establename=$Model->establename;
			$ESSIB->modulename='esSearchBox';
		}


		$i=0;
		$count=0;
        $firstFieldFound=false;
		foreach($fList as $fItem)
		{
            $opair=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
			$o=$opair[0];

            $vlu='';

			if($o!='' and $Model->print==0 and $Model->frmt!='csv')
			{


				if($o!='')
				{
					if($o=='button')
					{
                        //this is for legacy purposes when {search:button} was used,
                        //now we have dedicated tag {searchbutton} to render the search button
						$style='';

						if(isset($opair[1]) and $opair[1]!='')
							$style=$opair[1];

						$class='ctSearchBox';
						if(isset($opair[2]) and $opair[2]!='')
							$class.=' '.$opair[2];
						else
							$class.=' btn button-apply btn-primary';

						if($Model->print==1)
							$vlu='';
						else
							$vlu= '<input type=\'button\' value=\''.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SEARCH' ).'\' style=\''.$style.'\' class=\''.$class.'\' onClick=\'es_SearchBoxDo()\' />';

						
					}
					else
					{
                        //In case of multifield search:
                        $list_of_fields=explode(',',$o);
                        $first_field_name=$list_of_fields[0];
                        
						foreach($Model->esfields as $fld)
						{
							if($fld['fieldname']==$first_field_name)
							{
								$count++;
                                if(count($list_of_fields)>1)
                                {
                                    $fld['fields']=$list_of_fields;
                                    $fields[]=$fld;
                                    $fieldtitles=tagProcessor_Page::getFieldTitles($Model,$list_of_fields);
                                    $field_title=implode(' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR' ).' ',$fieldtitles);
                                }
                                else
                                {
                                    if($fld['type']=='date')
                                    {
                                    	$fld['typeparams']='date';
                                    	$fld['type']='range';
                                    	$fields[]=$fld;
                                    }
                                    else
                                    	$fields[]=$fld;
                                        
                                    $field_title=$fld['fieldtitle'.$Model->langpostfix];
                                }

								$cssclass='ctSearchBox';
								if(isset($opair[1]))
									$cssclass.=' '.$opair[1];

                                $default_Action=" ";//action should be a space not empty or this.value=this.value    
                                if(isset($opair[2]) and $opair[2]=='reload')
                                    $default_Action=' onChange="es_SearchBoxDo();"';

                                if(isset($opair[3]) and $opair[3]=='improved')
									$cssclass.=' ct_improved_selectbox';
                                    
								$vlu=$ESSIB->renderFieldBox($Model,'es_search_box_',$first_field_name,$fld,$cssclass,$count,'',false,'',$default_Action,$field_title);//action should be a space not empty or 

                                if(!$firstFieldFound)
                                {
                                    $vlu.= '<input type=\'hidden\' id=\'esSearchBoxFields\' value=\'&&&&fieldlist&&&&\' />';
                                    $firstFieldFound=true;
                                }
                                
								$vlu=str_replace('"','&&&&quote&&&&',$vlu);
							}
						}
					}
				}
			}

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}

        if($count>0 and $Model->print==0 and $Model->frmt!='csv')
        {
            $field2search=tagProcessor_Page::prepareSearchElements($Model,$pagelayout,$fields);
            $pagelayout=str_replace('&&&&fieldlist&&&&',implode(',',$field2search),$pagelayout);
        }
        else
            $pagelayout=str_replace('&&&&fieldlist&&&&','',$pagelayout);

	}

    /*
    static protected function SearchButton_GetFieldList(&$Model,&$pagelayout)
    {
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('search',$options,$pagelayout,'{}');

		$fields=array();
        $i=0;

		foreach($fList as $fItem)
		{
			$opair=explode(',',$options[$i]);
			$o=$opair[0];



			if($o!='')
			{
				$vlu='';

						foreach($Model->esfields as $fld)
						{
							if($fld['fieldname']==$o)
									$fields[]=$fld;
						}
			}
			$i++;
		}


        return $fields;

    }
*/
    
    
    static protected function prepareSearchElements(&$Model,&$pagelayout,$fields)
    {
        $url=JoomlaBasicMisc::deleteURLQueryOption($Model->current_url, 'where');

			$fieldlist=array();
            //if($fields==null)
                //$fields=tagProcessor_Page::SearchButton_GetFieldList($Model,$pagelayout);

			foreach($fields as $fld)
			{
                if(isset($fld['fields']) and count($fld['fields'])>0)
                {
                    $fieldlist[]='es_search_box_'.$fld['fieldname'].':'.implode('_',$fld['fields']).':';
                    //$fieldlist[]='es_search_box_'.$fld['fieldname'].':'.$fld['fieldname'].':';
                }
                else
                {
                    if($fld['type']=='customtables')
                    {
    					$exparams=explode(',',$fld['typeparams']);
    					if(count($exparams)>1)
    					{
    						$esroot=$exparams[0];
    						$fieldlist[]='es_search_box_combotree_'.$Model->establename.'_'.$fld['fieldname'].'_1:'.$fld['fieldname'].':'.$esroot;
    					}
    				}
    				else
    					$fieldlist[]='es_search_box_'.$fld['fieldname'].':'.$fld['fieldname'].':';
                }
			}

            //$pagelayout=str_replace('&&&&fieldlist&&&&',implode(',',$fieldlist),$pagelayout
            
            $document = JFactory::getDocument();
			$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/base64.js"></script>');
     
        return $fieldlist;       
    }
    
    static protected function renderSearchButton(&$Model,&$pagelayout,$class_)
    {
        $class='ctSearchBox';
		if(isset($class_) and $class_!='')
			$class.=' '.$class_;
		else
			$class.='  btn button-apply btn-primary';
                    
        //JavascriptFunction
        $vlu= '<input type=\'button\' value=\'SEARCH\' class=\''.$class.'\' onClick=\'es_SearchBoxDo()\' />';
       
        return $vlu;
    }
    
    static protected function SearchButton(&$Model,&$pagelayout)
	{
    	$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('searchbutton',$options,$pagelayout,'{}');
        
        if(count($fList)>0)
        {
            if($Model->print==1 or $Model->frmt=='csv')
            {
                foreach($fList as $fItem)
                    $pagelayout=str_replace($fItem,'',$pagelayout);
            
                return true;
            }
        
            //Only one search button possible, the rest button will look similar
            $opair=explode(',',$options[0]);
            $vlu=tagProcessor_Page::renderSearchButton($Model,$pagelayout,$opair[0]);
            
            foreach($fList as $fItem)
                $pagelayout=str_replace($fItem,$vlu,$pagelayout);
        }
	}


    static protected function RecordCount(&$Model,&$pagelayout)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('recordcount',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			if($options[$i]=='numberonly')
				$vlu=tagProcessor_Page::get_RecordCount($Model,true);
			elseif($options[$i]=='pagelimit')
				$vlu=tagProcessor_Page::get_RecordCount($Model,true,true);
			else
				$vlu=tagProcessor_Page::get_RecordCount($Model);

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    static protected function get_RecordCount(&$Model,$numberonly=false,$pagelimit=false)
	{
		if($pagelimit)
		{
			return (int)$Model->params->get( 'limit' );
		}
		else
		{
			if($numberonly)
				return $Model->TotalRows;
			else
			{
				if($Model->frmt=='csv')
					return '';
				else
					return '<span class="ctCatalogRecordCount">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FOUND' ).': '.$Model->TotalRows.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RESULT_S' ).'</span>';
			}
		}
	}

	static protected function RecordCountValue(&$Model,&$pagelayout)
	{
		if(!isset($Model->TotalRows))
			return;
		
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('count',$options,$pagelayout,'{}');

		if(count($fList)>0)
		{
			$vlu='';
			
			if($Model->frmt!='csv')
				$vlu=$Model->TotalRows;	
	
			foreach($fList as $fItem)
				$pagelayout=str_replace($fItem,$vlu,$pagelayout);
		}
	}


    static protected function PrintButton(&$Model,&$pagelayout)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('print',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$link=$Model->current_url.(strpos($Model->current_url,'?')===false ? '?' : '&').'tmpl=component&print=1';


			if(JFactory::getApplication()->input->get('moduleid',0,'INT')!=0)
			{
					//search module

					$moduleid = JFactory::getApplication()->input->get('moduleid',0,'INT');


					$link.='&moduleid='.$moduleid;

					//keyword search
					$inputbox_name='eskeysearch_'.$moduleid ;
					$link.='&'.$inputbox_name.'='.JFactory::getApplication()->input->getString($inputbox_name,'');
			}


			if($Model->print==1)
				$vlu='<p><a href="#" onclick="window.print();return false;"><img src="'.JURI::root(true).'/components/com_customtables/images/printButton.png" alt="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT').'"  /></a></p>	';
			else
            {
                $class='ctEditFormButton btn button';
				if(isset($opair[0]) and $opair[0]!='')
					$class=$opair[0];

				$vlu='<input type="button" class="'.$class.'" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT' ).'" onClick=\'window.open("'.$link.'","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no"); return false; \'> ';
            }


			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

        static protected function CleanUpPath($thePath)
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
}
