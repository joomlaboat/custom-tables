<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

trait render_xml
{
    protected static function get_CatalogTable_XML(&$Model,$fields,&$SearchResult)
	{
		$catalogresult='';

		//$fields=str_replace("\n",'',$fields);
		//$fields=str_replace("\r",'',$fields);

		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts

		$result='';

		$recordline='';

        $header_fields=array();
        $line_fields=array();
		foreach($fieldarray as $field)
		{
			$fieldpair=JoomlaBasicMisc::csv_explode(':', $field, '"', false);

			$header_fields[]=str_replace("'",'"',$fieldpair[0]);

            if(isset($fieldpair[1]))
            {
                $vlu=str_replace("'",'"',$fieldpair[1]);

                if(strpos($vlu,',')!==false)
                    $vlu='"'.$vlu.'"';
            }
            else
                $vlu="";

			$line_fields[]=$vlu;//content
		}

        $recordline.=implode('',$line_fields);
		$result.=implode('',$header_fields);//."\r\n";

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

		        $vlu=tagProcessor_Item::RenderResultLine($Model,$row,false);
                $tablecontent.=str_replace('&','&amp;',$vlu);
				$number++;
		}
        $result.=$tablecontent;
/*
        if($Model->clean)
        {
            $filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'xml');

            if (ob_get_contents())
            	ob_end_clean();

            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Type: text/xml; charset=utf-8');
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $result;
            die ;//clean exit
        }
*/
        return $result;
    }

}
