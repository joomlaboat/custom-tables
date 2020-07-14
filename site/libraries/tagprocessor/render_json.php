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
                    $vlu=$Model->LayoutProc->fillLayout($row,null,'','[]',false,false);
                    $vlus[$header_field]=$vlu;
                    $i++;
                }

                $records[]=$vlus;
                $number++;
		}

        $result=json_encode($records);

        if($Model->clean)
        {
            $filename = JoomlaBasicMisc::makeNewFileName($Model->params->get('page_title'),'json');

            if (ob_get_contents())
            	ob_end_clean();

            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Type: application/json; charset=utf-8');
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $result;
            die ;//clean exit
        }

        return $result;
    }

}
