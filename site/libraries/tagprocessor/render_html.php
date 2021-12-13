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

trait render_html
{
    protected static function get_CatalogTable_HTML(&$ct,$fields,$class)
	{
		$catalogresult='';

		$fields=str_replace("\n",'',$fields);
		$fields=str_replace("\r",'',$fields);

		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts

		$result='<table'.($class!='' ? ' class="'.$class.'" ': '').'><thead><tr>';

		$recordline='<tr>';

		foreach($fieldarray as $field)
		{
			$fieldpair=JoomlaBasicMisc::csv_explode(':', $field, '"', false);

			if(isset($fieldpair[2]) and $fieldpair[2]!='')
				$result.='<th '.$fieldpair[2].'>'.$fieldpair[0].'</th>';//header
			else
				$result.='<th>'.$fieldpair[0].'</th>';//header

            if(!isset($fieldpair[1]))
                $recordline.='<td>Catalog Layout Content field corrupted. Check the Layout.</td>';//content
            else
                $recordline.='<td>'.$fieldpair[1].'</td>';//content
		}
		$result.='</tr></thead>';

        //Parse Header
        $ct->LayoutProc->layout=$result;
        $result=$ct->LayoutProc->fillLayout();
        $result=str_replace('&&&&quote&&&&','"',$result);
		
		$twig = new TwigProcessor($ct, $result);
		$result = $htmlresult = $twig->process();

        //Complete record layout
		$recordline.='</tr>';
		$recordline=str_replace('|(','{',$recordline);//to support old parsing way
		$recordline=str_replace(')|','}',$recordline);//to support old parsing way
		$recordline=str_replace('&&&&quote&&&&','"',$recordline);

		$number = 1 + $ct->LimitStart; //table row number, it maybe use in the layout as {number}

        $tablecontent='';
		
		$twig = new TwigProcessor($ct, $recordline);
		
		foreach($ct->Records as $row)
		{
			$row['_number'] = $number;
		    $tablecontent.=tagProcessor_Item::RenderResultLine($ct,$twig,$row);
			
			$number++;
		}
        $result.='<tbody>'.$tablecontent.'</tbody></table>';

		return $result;
	}
}
