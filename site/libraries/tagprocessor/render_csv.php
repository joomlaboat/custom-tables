<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

trait render_csv
{
    protected static function get_CatalogTable_CSV(&$Model,$fields,$SearchResult)
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
        $Model->LayoutProc->layout=$result;
        $result=$Model->LayoutProc->fillLayout();
        $result=str_replace('&&&&quote&&&&','"',$result);

		$number=1+$Model->limitstart; //table row number, it maybe use in the layout as {number}
		$Model->LayoutProc->layout=$recordline;

		//Initiate the file output
		$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

		$result.= self::renderCSVoutput($Model,$SearchResult);
				
		if($Model->TotalRows>$Model->limitstart+$Model->limit)
		{
			for($limitstart=$Model->limitstart+$Model->limit;$limitstart<$Model->TotalRows;$limitstart+=$Model->limit)
			{
				$Model->limitstart=$limitstart;
				$SearchResult=$Model->getSearchResult();//get records

				if(count($SearchResult)==0)
					break;//no records left - escape
				
				$result.= self::renderCSVoutput($Model,$SearchResult);//output next chunk
			}
		}

        return $result;
    }
	
	protected static function renderCSVoutput(&$Model,&$SearchResult)
	{
		$number=1+$Model->limitstart; //table row number, it can be used in the layout as {number}
		$tablecontent='';

		foreach($SearchResult as $row)
		{
			$Model->LayoutProc->number=$number;
		
            $tablecontent.='
'.strip_tags(tagProcessor_Item::RenderResultLine($Model,$row,false));

			$number++;
		}
        return $tablecontent;
	}

	public static function get_CatalogTable_singleline_CSV(&$SearchResult,&$Model,$allowcontentplugins,$pagelayout)
	{
		$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

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
		$layout=$Model->LayoutProc->layout;
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

		$Model->LayoutProc->layout=$layout;

		foreach($SearchResult as $row)
		{
			$vlu=trim(strip_tags(tagProcessor_Item::RenderResultLine($Model,$row,false)));
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
