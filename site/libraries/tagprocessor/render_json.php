<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

trait render_json
{
    protected static function get_CatalogTable_JSON(&$Model,$fields,&$SearchResult)
	{
		$fields=str_replace("\n",'',$fields);
		$fields=str_replace("\r",'',$fields);
		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);
		foreach($fieldarray as $field)
		{
			$fieldpair=JoomlaBasicMisc::csv_explode(':', $field, '"', false);
			$header_fields[]=$fieldpair[0];//$result;//header

            if(isset($fieldpair[1]))
                $vlu=$fieldpair[1];
            else
                $vlu="";

			$line_fields[]=$vlu;//content
		}

		$number=1+$Model->limitstart; //table row number, it maybe use in the layout as {number}
        $records=array();
		foreach($SearchResult as $row)
		{
				$Model->LayoutProc->number=$number;
                $i=0;
                $vlus=array();
                foreach($header_fields as $header_field)
                {
                    $Model->LayoutProc->layout=$line_fields[$i];
                    $vlus[$header_field] = $Model->LayoutProc->fillLayout($row,null,'','[]',false,false);
                    $i++;
                }

                $records[] = $vlus;
                $number++;
		}

        $result=json_encode($records);
        return $result;
    }
	
	function get_CatalogTable_singleline_JSON(&$SearchResult,&$Model,$allowcontentplugins,$pagelayout)
	{
		//$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'json');

		if (ob_get_contents())
			ob_clean();
/*
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
*/

		//Prepare line layout
		$layout=$Model->LayoutProc->layout;
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);
		/*
		
		
		$commaAdded=false;

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplaceAdvanced('</td>','<td',$options,$layout);
		foreach($fList as $fItem)
		{
			$layout=str_replace($fItem,',<td',$layout);
			$commaAdded=true;
		}
*/
		$Model->LayoutProc->layout=$layout;
		
		$records=[];

		foreach($SearchResult as $row)
		{

			$records[]=trim(strip_tags(tagProcessor_Item::RenderResultLine($Model,$row,false)));
			/*
			$l=strlen($vlu);
			if($commaAdded and $l>0)
			{
				//delete comma in the end of the line
				if($vlu[$l-1]==',')
					$vlu=substr($vlu,0,$l-1);
			}
			$result.=$vlu.'
';*/
		}
		
		
		$result = implode(',',$records);
/*
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
        */
		/*		
		if (ob_get_contents())
			ob_end_clean();

		@header("Content-type: text/json");
		@header('Content-Disposition: attachment; filename="'.$filename.'"');
		@header('Content-Type: text/html; charset=utf-8');
		@header("Pragma: no-cache");
		@header("Expires: 0");

        echo chr(255).chr(254).mb_convert_encoding($result, 'UTF-16LE', 'UTF-8');
        die;//clean exit
		*/
		//$result=json_encode($records);
		return $result;
    }

}
