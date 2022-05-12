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

trait render_json
{
    protected static function get_CatalogTable_JSON(&$ct,$fields)
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

		$number=1 + $ct->LimitStart; //table row number, it maybe use in the layout as {number}
        $records=array();
		
		$LayoutProc = new LayoutProcessor($ct);
		
		foreach($ct->Records as $row)
		{
				$row['_number'] = $number;
                $i=0;
                $vlus=array();
                foreach($header_fields as $header_field)
                {
                    $LayoutProc->layout=$line_fields[$i];
                    $vlus[$header_field] = $LayoutProc->fillLayout($row,null,'[]',false,false);
                    $i++;
                }

                $records[] = $vlus;
                $number++;
		}

        $result=json_encode($records);
        return $result;
    }
	
	function get_CatalogTable_singleline_JSON(&$ct,$layoutType,$allowcontentplugins,$layout) //TO DO
	{
		if (ob_get_contents())
			ob_clean();

		//Prepare line layout
		
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);

		$twig = new TwigProcessor($ct, $layout);
		
		$records=[];

		foreach($ct->Records as $row)
			$records[]=trim(strip_tags(tagProcessor_Item::RenderResultLine($ct,$layoutType, $twig, $row)));
		
		$result = implode(',',$records);

		return $result;
    }

}
