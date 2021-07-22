<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

trait render_html
{
    protected static function get_CatalogTable_HTML(&$Model,$fields,$class,&$SearchResult)
	{
		$catalogresult='';

		$fields=str_replace("\n",'',$fields);
		$fields=str_replace("\r",'',$fields);

		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts

		$result='<!-- table view --><table'.($class!='' ? ' class="'.$class.'" ': '').'><thead><tr>';

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
        $Model->LayoutProc->layout=$result;
        $result=$Model->LayoutProc->fillLayout(array(), null, '');
        $result=str_replace('&&&&quote&&&&','"',$result);

        //Complete record layout
		$recordline.='</tr>';
		$recordline=str_replace('|(','{',$recordline);//to support old parsing way
		$recordline=str_replace(')|','}',$recordline);//to support old parsing way
		$recordline=str_replace('&&&&quote&&&&','"',$recordline);

		$number=1+$Model->limitstart; //table row number, it maybe use in the layout as {number}

		$Model->LayoutProc->layout=$recordline;
		
        $tablecontent='';
		foreach($SearchResult as $row)
		{
				$Model->LayoutProc->number=$number;
		        $tablecontent.=tagProcessor_Item::RenderResultLine($Model,$row,true);
				$number++;
		}
        $result.=$tablecontent.'</tbody></table><!-- end of table view -->';

		return $result;
	}
}
