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

trait render_xml
{
    protected static function get_CatalogTable_XML(&$ct,$layoutType,$fields)
	{
		$catalogresult='';

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
		$LayoutProc = new LayoutProcessor($ct);
        $LayoutProc->layout=$result;
        $result=$LayoutProc->fillLayout();
        $result=str_replace('&&&&quote&&&&','"',$result);

		$number = 1 + $ct->LimitStart; //table row number, it maybe use in the layout as {number}
		
		$twig = new TwigProcessor($ct, $recordline);

        $tablecontent='';
		foreach($ct->Records as $row)
		{
			$row['_number'] = $number;

            if($tablecontent!="")
                $tablecontent.="\r\n";
               	$tablecontent.=tagProcessor_Item::RenderResultLine($ct,$layoutType,$twig,$row);
				$number++;
		}
        $result.=$tablecontent;
        return $result;
    }
}
