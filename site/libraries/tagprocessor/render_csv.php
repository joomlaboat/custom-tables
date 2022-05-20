<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

trait render_csv
{
    protected static function get_CatalogTable_CSV(&$ct,$layoutType,$fields)
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
		$LayoutProc = new LayoutProcessor($ct);
        $LayoutProc->layout=$result;
        $result=$LayoutProc->fillLayout();
        $result=str_replace('&&&&quote&&&&','"',$result);
		
		//Initiate the file output

		$result= strip_tags($result);
		$result.= strip_tags(self::renderCSVoutput($ct,$layoutType));
		
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
				
					$result.= self::renderCSVoutput($ct,$layoutType,$recordline);//output next chunk
				}
			}
		}

        return strip_tags($result);
    }
	
	protected static function renderCSVoutput(&$ct, int $layoutType, string $itemlayout)
	{
		$twig = new TwigProcessor($ct, $itemlayout);
		
		$number = 1 + $ct->LimitStart; //table row number, it can be used in the layout as {number}
		$tablecontent='';

		foreach($ct->Records as $row)
		{
			$row['_number']=$number;
		
            $tablecontent.='
'.strip_tags(tagProcessor_Item::RenderResultLine($ct,$layoutType,$twig,$row));//TODO

			$number++;
		}
        return $tablecontent;
	}

	public static function get_CatalogTable_singleline_CSV(&$ct,$layoutType,$layout)
	{
		if (ob_get_contents())
			ob_clean();

		//Prepare line layout
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);

		$twig = new TwigProcessor($ct, $layout);
		
		$records=[];

		foreach($ct->Records as $row)
			$records[]=trim(strip_tags(tagProcessor_Item::RenderResultLine($ct,$layoutType, $twig, $row)));//TO DO

		$result = implode('
',$records);

		return $result;
    }
}
