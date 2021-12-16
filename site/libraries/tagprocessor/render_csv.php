<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

trait render_csv
{
    protected static function get_CatalogTable_CSV(&$ct,$fields)
	{
		$catalogresult='';

		$fields=str_replace("\n",'',$fields);
		$fields=str_replace("\r",'',$fields);

		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts
		$result='';

		$recordline='';

        $header_fields=array();
        $line_fields=array();
		foreach($fieldarray as $field)
		{
			$fieldpair=JoomlaBasicMisc::csv_explode(':', $field, '"', false);
            $header_fields[]=trim(strip_tags(html_entity_decode($fieldpair[0])));//header
            if(isset($fieldpair[1]))
                $vlu=str_replace('"','',$fieldpair[1]);
            else
                $vlu="";

			$line_fields[]=$vlu;//content
		}

        $recordline.='"'.implode('","',$line_fields).'"';
		$result.='"'.implode('","',$header_fields).'"';//."\r\n";

        //Parse Header
        $ct->LayoutProc->layout=$result;
        $result=$ct->LayoutProc->fillLayout();
        $result=str_replace('&&&&quote&&&&','"',$result);

		//table row number, it maybe use in the layout as {number}
		$ct->LayoutProc->layout = $recordline;

		//Initiate the file output
		$filename = JoomlaBasicMisc::makeNewFileName($ct->Env->menu_params->get('page_title'),'csv');

		$result.= strip_tags($result);

		$result.= strip_tags(self::renderCSVoutput($ct));
				
		if($ct->Table->recordcount > $ct->LimitStart + $ct->Limit)
		{
			if($ct->Limit > 0)
			{
				for($limitstart = $ct->LimitStart + $ct->Limit; $limitstart < $ct->Table->recordcount; $limitstart+=$ct->Limit)
				{
					$ct->LimitStart=$limitstart;
				
					$ct->getRecords();//get records

					if(count($ct->Records)==0)
						break;//no records left - escape
				
					$result.= self::renderCSVoutput($ct);//output next chunk
				}
			}
		}

        return strip_tags($result);
    }
	
	protected static function renderCSVoutput(&$ct)
	{
		$twig = new TwigProcessor($ct, $ct->LayoutProc->layout);
		
		$number = 1 + $ct->LimitStart; //table row number, it can be used in the layout as {number}
		$tablecontent='';

		foreach($ct->Records as $row)
		{
			$row['_number']=$number;
		
            $tablecontent.='
'.strip_tags(tagProcessor_Item::RenderResultLine($ct,$twig,$row));

			$number++;
		}
        return $tablecontent;
	}

	public static function get_CatalogTable_singleline_CSV(&$ct,$allowcontentplugins,$pagelayout)
	{
		$filename = JoomlaBasicMisc::makeNewFileName($ct->Env->menu_params->get('page_title'),'csv');

		//Header
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplaceAdvanced('<style>','</style>',$options,$pagelayout);
		foreach($fList as $fItem)
			$pagelayout=str_replace($fItem,'',$pagelayout);
			
			
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplaceAdvanced('</th>','<th>',$options,$pagelayout);
		foreach($fList as $fItem)
			$pagelayout=str_replace($fItem,',',$pagelayout);
			
		$result=trim(strip_tags(html_entity_decode($pagelayout)));
		$result=str_replace("\n",'',$result);
		$result=str_replace("\r",'',$result);
		
		if($result!='')
			$result=$result.'
';

		//Prepare line layout
		$layout=$ct->LayoutProc->layout;
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);
		
		$commaAdded=false;

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplaceAdvanced('</td>','<td',$options,$layout);
		foreach($fList as $fItem)
		{
			$layout=str_replace($fItem,',<td',$layout);
			$commaAdded=true;
		}

		$twig = new TwigProcessor($ct, $layout);

		foreach($ct->Records as $row)
		{
			$vlu=trim(strip_tags(tagProcessor_Item::RenderResultLine($ct,$twig,$row)));
			$l=strlen($vlu);
			if($commaAdded and $l>0)
			{
				//delete comma in the end of the line
				if($vlu[$l-1]==',')
					$vlu=substr($vlu,0,$l-1);
			}
			$result.=$vlu.'
';
		}

        if($allowcontentplugins)
			$result = LayoutProcessor::applyContentPlugins($result);
                
		return $result;
    }
}
