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
use CustomTables\SearchInputBox;

/* Not sll tags already implemented using Twig

Implemented:

{print} - {{ html.print() }}
{format:csv} - {{ html.format('csv') }}
{searchbutton} - {{ html.searchbutton }}
{search:email} - {{ html.search('email') }}
{recordcount} - {{ record.count(true) }}
{count} - {{ record.count }}

 */
 
 
use \CustomTables\Twig_Html_Tags;
use \CustomTables\Twig_Record_Tags;

class tagProcessor_Page
{
    public static function process(&$Model,&$pagelayout)
    {
		$ct_html = new Twig_Html_Tags($Model->ct);
		$ct_record = new Twig_Record_Tags($Model->ct);
		
        tagProcessor_Page::FormatLink($ct_html,$pagelayout);//{format:xls}  the link to the same page but in xls format
		
        tagProcessor_Page::PathValue($Model,$pagelayout);
        tagProcessor_Page::AddNew($Model,$pagelayout);

        tagProcessor_Page::Pagination($Model,$pagelayout);

        tagProcessor_Page::PageToolBar($Model,$pagelayout);

        tagProcessor_Page::PageToolBarCheckBox($Model,$pagelayout);

        tagProcessor_Page::SearchButton($ct_html,$pagelayout);
        tagProcessor_Page::SearchBOX($ct_html,$pagelayout);

        tagProcessor_Page::RecordCountValue($ct_record,$pagelayout);
        tagProcessor_Page::RecordCount($ct_record,$pagelayout);

        tagProcessor_Page::PrintButton($ct_html,$pagelayout);
    }

    public static function FormatLink(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('format',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$option_list=explode(',',$options[$i]);
    		$format=$option_list[0];
			
			//$format, $link_type = 'anchor', $image = '', $imagesize = '', $menu_item_alias = '', $csv_column_separator = ','
			
			$link_type = isset($option_list[1]) ? $option_list[1] : '';
			$image = isset($option_list[2]) ? $option_list[2] : '';			
			$imagesize = isset($option_list[3]) ? $option_list[3] : '';
			$menu_item_alias = isset($option_list[4]) ? $option_list[4] : '';
			
			$vlu = $ct_html->format($format, $link_type, $image, $imagesize, $menu_item_alias, ',');

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
        $isEditable=tagProcessor_Item::checkAccess($Model->ct,$edit_userGroup,$row);

		foreach($fList as $fItem)
		{
            if($isEditable and $Model->ct->Env->print==0 and ($Model->ct->Env->frmt=='html' or $Model->ct->Env->frmt==''))
            {
                $opt=explode(',',$options[$i]);

                if((int)$opt[0]>0)
                	$link='/index.php?option=com_customtables&view=edititem&returnto='.$Model->ct->Env->encoded_current_url.'&Itemid='.$opt[0];
                if($opt[0]!='')
                	$link='/index.php/'.$opt[0].'?returnto='.$Model->ct->Env->encoded_current_url;
                else
                	$link='/index.php?option=com_customtables&view=edititem&returnto='.$Model->ct->Env->encoded_current_url.'&Itemid='.$Model->Itemid;

    			if($jinput->getCmd('tmpl','')!='')
    				$link.='&tmpl='.$jinput->get('tmpl','','CMD');
                    
                if(isset($opt[1]) and $opt[1]=='importcsv')
                {
                    $document = JFactory::getDocument();
					
					if($Model->ct->Env->version < 4)
					{
						$document->addCustomTag('<script src="'.JURI::root(true).'/media/jui/js/jquery.min.js"></script>');
						$document->addCustomTag('<script src="'.JURI::root(true).'/media/jui/js/bootstrap.min.js"></script>');
					}
		
                    $document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
                    $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/uploader.js"></script>');
                    $max_file_size=JoomlaBasicMisc::file_upload_max_size();
                    
                    $Itemid=$jinput->getInt('Itemid',0);
                    $prefix='comes_';
                    $fileid=JoomlaBasicMisc::generateRandomString();
                    $fieldid='9999';//some unique number
                    $objectname='importcsv';

                    $result='<div>';

					JHtml::_('behavior.formvalidator');
    
                    $result.='
                    <div id="ct_fileuploader_'.$objectname.'"></div>
                    <div id="ct_eventsmessage_'.$objectname.'"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        UploadFileCount=1;

                    	var urlstr="/index.php?option=com_customtables&view=fileuploader&tmpl=component&'
                        .'tableid='.$Model->ct->Table->tableid.'&'
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
        			$vlu='<a href="'.JURI::root(true).$link.'" id="ctToolBarAddNew'.$Model->ct->Table->tableid.'" class="toolbarIcons"><img src="'.JURI::root(true).'/components/com_customtables/images/new.png" alt="Add New" title="Add New" /></a>';
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
            if($Model->ct->Env->print==0)
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
					return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.CustomTables\OrderingHTML::getOrderBox($Model->ordering);
					break;
				case 'limitorder' :
					return '
			<table cellpadding="0" cellspacing="0" width="100%" >
		    <tr height="30">
                <td width="140" valign="top">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($number_of_columns).'</td>
                <td align="right" valign="top">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.CustomTables\OrderingHTML::getOrderBox($Model->ordering).'</td>
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
                <td width="230" align="right" valign="top" style="border:none;">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '
					.CustomTables\OrderingHTML::getOrderBox($Model->ordering).'</td>
		    </tr>
		    </table>
		';
					break;

					case 'paginationorder' :
						return '
					<table cellpadding="0" cellspacing="0" width="100%" >
					    <tr><td valign="top" align="right">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY').': '.CustomTables\OrderingHTML::getOrderBox($Model->ordering).'</td></tr>
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

            if($Model->ct->Env->print==0 and ($Model->ct->Env->frmt=='html' or $Model->ct->Env->frmt==''))
            {
                if($options[$i]=='')
                	$modes=$available_modes;
                else
                	$modes=explode(',',$options[$i]);

                foreach($modes as $mode)
                {
                	if(in_array($mode,$available_modes))
                	{
                		$rid='esToolBar_'.$mode.'_box_'.$Model->ct->Table->tableid;
                		$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_'.strtoupper($mode).'_SELECTED' );
                   		$img='<img src="'.JURI::root(true).'/components/com_customtables/images/'.$mode.'.png" border="0" alt="'.$alt.'" title="'.$alt.'" />';
                		$link='javascript:esToolBarDO("'.$mode.'", '.$Model->ct->Table->tableid.')';
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
            if($Model->ct->Env->print==0 and $Model->ct->Env->frmt!='csv')
                $vlu='<input type="checkbox" id="esCheckboxAll'.$Model->ct->Table->tableid.'" onChange="esCheckboxAllclicked('.$Model->ct->Table->tableid.')" />';
            else
                $vlu='';

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
		}
	}
       
    static protected function SearchBOX(&$ct_html,&$pagelayout)
	{
    	$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('search',$options,$pagelayout,'{}');

		if(count($fList) == 0)
			return false;
		
		$i=0;
		
		foreach($fList as $fItem)
		{
			$vlu='';
			
			if($options[$i]!='')
			{
				$opair=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
			
				$list_of_fields_string_array=explode(',',$opair[0]);
				
				$class = $opair[1] ?? '';
				$reload = isset($opair[2]) and $opair[2]=='reload';
				$improved = isset($opair[3]) and $opair[3]=='improved';
				
				$vlu = $ct_html->search($list_of_fields_string_array, $class, $reload, $improved);
			}

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}
	
    static protected function SearchButton(&$ct_html,&$pagelayout)
	{
    	$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('searchbutton',$options,$pagelayout,'{}');
        
        if(count($fList)>0)
        {
			$opair=explode(',',$options[0]);
			$vlu = $ct_html->searchbutton($opair[0]);
        
            foreach($fList as $fItem)
                $pagelayout=str_replace($fItem,$vlu,$pagelayout);
        }
	}

    static protected function RecordCount(&$ct_record,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('recordcount',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$full_sentence = ! ($options[$i]=='numberonly');
			
			$vlu = $ct_record->count($full_sentence);

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}


	static protected function RecordCountValue(&$ct_record,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('count',$options,$pagelayout,'{}');

		foreach($fList as $fItem)
		{
			$vlu = $ct_record->count(false);
			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
		}
	}

    static protected function PrintButton(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('print',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$class='ctEditFormButton btn button';
			if(isset($opair[0]) and $opair[0]!='')
				$class=$opair[0];
			
			$vlu = $ct_html->print($class);
			
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
