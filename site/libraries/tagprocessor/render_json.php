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
		foreach($ct->Records as $row)
		{
				$row['_number'] = $number;
                $i=0;
                $vlus=array();
                foreach($header_fields as $header_field)
                {
                    $ct->LayoutProc->layout=$line_fields[$i];
                    $vlus[$header_field] = $ct->LayoutProc->fillLayout($row,null,'[]',false,false);
                    $i++;
                }

                $records[] = $vlus;
                $number++;
		}

        $result=json_encode($records);
        return $result;
    }
	
	function get_CatalogTable_singleline_JSON(&$ct,$allowcontentplugins,$pagelayout)
	{
		if (ob_get_contents())
			ob_clean();

		//Prepare line layout
		$layout=$ct->LayoutProc->layout;
		$layout=str_replace("\n",'',$layout);
		$layout=str_replace("\r",'',$layout);

		$twig = new TwigProcessor($ct, $layout);
		
		$records=[];

		foreach($ct->Records as $row)
			$records[]=trim(strip_tags(tagProcessor_Item::RenderResultLine($ct, $twig, $row)));
		
		$result = implode(',',$records);

		return $result;
    }

}
