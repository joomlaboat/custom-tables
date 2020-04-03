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
    protected static function get_CatalogTable_CSV(&$Model,$fields,&$SearchResult)
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
		$result.='"'.implode('","',$header_fields).'"'."\r\n";

        //Parse Header
        $Model->LayoutProc->layout=$result;
        $result=$Model->LayoutProc->fillLayout(array(), null, '');
        $result=str_replace('&&&&quote&&&&','"',$result);

		$number=1+$Model->limitstart; //table row number, it maybe use in the layout as {number}
		$Model->LayoutProc->layout=$recordline;

        $tablecontent='';
		foreach($SearchResult as $row)
		{
				$Model->LayoutProc->number=$number;

                if($tablecontent!="")
                    $tablecontent.="\r\n";

                $content=tagProcessor_Item::RenderResultLine($Model,$row,false);
		        $tablecontent.=strip_tags($content);
				$number++;
		}
        $result.=$tablecontent;

        if($Model->clean)
        {
            $filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

            if (ob_get_contents())
            	ob_end_clean();

            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Type: text/csv; charset=utf-8');
            header("Pragma: no-cache");
            header("Expires: 0");

            echo chr(255).chr(254).mb_convert_encoding($result, 'UTF-16LE', 'UTF-8');

            die ;//clean exit
        }

        return $result;
    }


	function get_CatalogTable_singleline_CSV(&$SearchResult,&$Model,$allowcontentplugins)
	{
		$filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'csv');

		if (ob_get_contents())
			ob_clean();

		$result='';

		//Prepare line layout
		$layout=$Model->LayoutProc->layout;
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);

		$a=str_replace("</td>",',',$layout);

		$commaAdded=false;
		if($layout!=$a)
			$commaAdded=true;


		$layout=$a;

		$Model->LayoutProc->layout=$layout;

		foreach($SearchResult as $row)
		{

			$vlu=strip_tags(tagProcessor_Item::RenderResultLine($Model,$row,false));
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


        //

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

		header("Content-type: text/csv");
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Type: text/html; charset=utf-8');
		header("Pragma: no-cache");
		header("Expires: 0");

        echo chr(255).chr(254).mb_convert_encoding($result, 'UTF-16LE', 'UTF-8');
        die ;//clean exit
    }


}
