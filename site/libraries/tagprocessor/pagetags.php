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

/* All tags are implemented using Twig

Implemented:

{print} - {{ html.print() }}
{recordlist} - {{ record.list }}
{format:csv} - {{ html.format('csv') }}
{searchbutton} - {{ html.searchbutton }}
{search:email} - {{ html.search('email') }}
{recordcount} - {{ record.count_sentence }}
{count} - {{ record.count }}
{navigation} - {{ html.navigation(|type|,|css_class|) }}
{add} - {{ html.add(|alias_or_itemid|) }}
{add:,importcsv} - {{ html.importcsv }}
{pagination} - {{ html.pagination }}
{pagination:limit} - {{ html.limit(|step|) }}
{pagination:order} - {{ html.orderby }}
{batchtoolbar:modes} - {html.batch('delete','publish')}
{checkbox} - {html.batch('checkbox')
{html.batch('delete','checkbox')} - new tag

New:

{{ html.message("text",|type|) }} - types: Message(Green), Notice(Blue), Warning(Yellow), Error(Red)

*/
 
use \CustomTables\Twig_Html_Tags;
use \CustomTables\Twig_Record_Tags;

class tagProcessor_Page
{
    public static function process(&$ct,&$pagelayout)
    {
		$ct_html = new Twig_Html_Tags($ct, false);
		$ct_record = new Twig_Record_Tags($ct, false);
		
        tagProcessor_Page::FormatLink($ct_html,$pagelayout);//{format:xls}  the link to the same page but in xls format
		
        tagProcessor_Page::PathValue($ct_html,$pagelayout); //Converted to Twig. Original replaced.
        tagProcessor_Page::AddNew($ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::Pagination($ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::PageToolBar($ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::PageToolBarCheckBox($ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::SearchButton($ct_html,$pagelayout); //Converted to Twig. Original replaced.
        tagProcessor_Page::SearchBOX($ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::RecordCountValue($ct_record,$pagelayout); //Converted to Twig. Original replaced.
        tagProcessor_Page::RecordCount($ct_record,$ct_html,$pagelayout); //Converted to Twig. Original replaced.

        tagProcessor_Page::PrintButton($ct_html,$pagelayout); //Converted to Twig. Original replaced.
		
		tagProcessor_Page::processRecordlist($ct_record,$pagelayout); //Twig version added - original replaced
    }
	
	protected static function processRecordlist(&$ct_record,&$pagelayout)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('recordlist',$options,$pagelayout,'{}',':','"');

		$i=0;

		foreach($fList as $fItem)
		{
			$vlu = $ct_record->list();

            $pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
        }
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

    public static function PathValue(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('navigation',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$pair=explode(',',$options[$i]);
			
			$ul_css_class = $pair[0];
			$list_type = $pair[1] ?? 'list';
			
			$vlu = $ct_html->navigation($list_type, $ul_css_class);

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function AddNew(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('add',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
            $opt=explode(',',$options[$i]);
			
			if(isset($opt[1]) and $opt[1]=='importcsv')
			{
				$vlu = $ct_html->importcsv();
			}
			else
			{
				$Alias_or_ItemId = $opt[0];
				$vlu = $ct_html->add($Alias_or_ItemId);
			}

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

	protected static function Pagination(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('pagination',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$opt=explode(',',$options[$i]);
			
			$element_type = $opt[0];

			switch($element_type)
			{
				case '':
				case 'paginaton':
					$vlu = $ct_html->pagination();
					break;
					
				case 'limit' :
					$vlu = $ct_html->limit();
					break;
					
				case 'order' :
					$vlu = $ct_html->orderby();
					break;
					
				default:
					$vlu = 'pagination: type "'.$element_type.'" is unknown.';
            }

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function PageToolBar(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('batchtoolbar',$options,$pagelayout,'{}');
        
		$i=0;
		foreach($fList as $fItem)
		{
			$modes = explode(',',$options[$i]);
			$vlu = $ct_html->batch($modes);
			$pagelayout=str_replace($fItem,$vlu,$pagelayout);

			$i++;
		}
	}

    static protected function PageToolBarCheckBox(&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('checkbox',$options,$pagelayout,'{}');

		foreach($fList as $fItem)
		{
			$vlu = $ct_html->batch('checkbox');
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

    static protected function RecordCount(&$ct_record,&$ct_html,&$pagelayout)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('recordcount',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			if($options[$i]=='numberonly')
				$vlu = $ct_record->count();
			else
				$vlu = $ct_html->recordcount();
			
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
			$vlu = $ct_record->count();
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

}
