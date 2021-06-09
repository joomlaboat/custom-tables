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
            {
                $vlu=str_replace('"','',$fieldpair[1]);
                if(strpos($vlu,',')!==false)
                    $vlu='"'.$vlu.'"';
            }
            else
                $vlu="";

			$line_fields[]=$vlu;//content
		}

        $recordline.='"'.implode('","',$line_fields).'"';
		$result.='"'.implode('","',$header_fields).'"';//."\r\n";
		
        //Parse Header
        $Model->LayoutProc->layout=$result;
        $result=$Model->LayoutProc->fillLayout(array(), null, '');
        $result=str_replace('&&&&quote&&&&','"',$result);

		$number=1+$Model->limitstart; //table row number, it maybe use in the layout as {number}
		$Model->LayoutProc->layout=$recordline;

		//Initiate the file output
		$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

        if (ob_get_contents())
          	ob_end_clean();

		ob_start();
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Type: text/csv; charset=utf-8');
		//header('Content-Type: text/plain; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");
		
		echo chr(255).chr(254).mb_convert_encoding($result, 'UTF-16LE', 'UTF-8');
		
		//Output first chunk
		
		flush();
		ob_flush();//flush to not force the browser to wait
		ob_start();
			
		echo self::renderCSVoutput($Model,$SearchResult);
				
		if($Model->TotalRows>$Model->limitstart+$Model->limit)
		{
			flush();
			ob_flush();//flush to not force the browser to wait
			ob_start();
			
			for($limitstart=$Model->limitstart+$Model->limit;$limitstart<$Model->TotalRows;$limitstart+=$Model->limit)
			{
				$Model->limitstart=$limitstart;
				$SearchResult=$Model->getSearchResult();//get records
				if(count($SearchResult)==0)
					break;//no records left - escape
				
				echo self::renderCSVoutput($Model,$SearchResult);//output next chunk
				flush();
				ob_flush();//flush to not force the browser to wait
				ob_start();
			}
		}
        die;//clean exit
        //no return here
    }
	
	protected static function renderCSVoutput(&$Model,&$SearchResult)
	{
		$number=1+$Model->limitstart; //table row number, it can be used in the layout as {number}
		$tablecontent='';
		foreach($SearchResult as $row)
		{
			$Model->LayoutProc->number=$number;
		
            $content=strip_tags(tagProcessor_Item::RenderResultLine($Model,$row,false));
	        $tablecontent.=mb_convert_encoding('
'.$content, 'UTF-16LE', 'UTF-8');//New line
			$number++;
		}
        return $tablecontent;
	}


	function get_CatalogTable_singleline_CSV(&$SearchResult,&$Model,$allowcontentplugins,$pagelayout)
	{
		$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

		if (ob_get_contents())
			ob_clean();

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
        {
            $jinput=JFactory::getApplication()->input;
            $jinput->set('frmt','');
        	//$mydoc = JFactory::getDocument();
        	//$pagetitle=$mydoc->getTitle(); //because content plugins may overwrite the title


			$mainframe = JFactory::getApplication('site');
			$params_ = $mainframe->getParams('com_content');

			$o = new stdClass();
			$o->text = $result;
            $o->created_by_alias = 0;

			$dispatcher	= JDispatcher::getInstance();
			JPluginHelper::importPlugin('content');


			$results = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$params_, 0));
			$result=$o->text;

            	//$mydoc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle)); //because content plugins may overwrite the title
        }
                
		if (ob_get_contents())
			ob_end_clean();

		@header("Content-type: text/csv");
		@header('Content-Disposition: attachment; filename="'.$filename.'"');
		@header('Content-Type: text/html; charset=utf-8');
		@header("Pragma: no-cache");
		@header("Expires: 0");

        echo chr(255).chr(254).mb_convert_encoding($result, 'UTF-16LE', 'UTF-8');
        die ;//clean exit
    }
}
