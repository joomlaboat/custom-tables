<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;

require_once('render_html.php');
require_once('render_xlsx.php');
require_once('render_csv.php');
require_once('render_json.php');
require_once('render_image.php');

use CustomTables\Fields;
use CustomTables\TwigProcessor;

class tagProcessor_Catalog
{
    use render_html;
	use render_xlsx;
	use render_csv;
	use render_json;
	use render_image;

    public static function process(&$ct,$layoutType,&$pagelayout,&$itemlayout,$new_replaceitecode)
    {
        $vlu='';
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('catalog',$options,$pagelayout,'{}');
		//---------------------
		$i=0;
		foreach($fList as $fItem)
		{
			$pair=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

			$tableclass=$pair[0];
			$notable=$pair[1] ?? '';
			$separator=$pair[2] ?? '';

			if($ct->Env->frmt=='csv')
			{
				$vlu.=self::get_CatalogTable_singleline_CSV($ct,$layoutType,$itemlayout);
			}
			elseif($ct->Env->frmt=='json')
			{
				//$pagelayout=str_replace($fItem,'',$pagelayout);//delete {catalog} tag
				$vlu=self::get_CatalogTable_singleline_JSON($ct,$layoutType,$itemlayout);
			}
			elseif($ct->Env->frmt=='image')
				self::get_CatalogTable_singleline_IMAGE($ct,$layoutType,$pagelayout);
			elseif($notable == 'notable')
				$vlu.=self::get_Catalog($ct,$layoutType,$itemlayout,$tableclass,false,$separator);
			else
				$vlu.=self::get_Catalog($ct,$layoutType,$itemlayout,$tableclass,true,$separator);

			$pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			$i++;
		}
        return $vlu;
    }

    protected static function get_Catalog(&$ct,$layoutType,$itemlayout,$tableclass,$showtable=true,$separator='')
	{
		$catalogResult='';

		if(is_null($ct->Records))
			return '';

		$CatGroups=array();
		
		$twig = new TwigProcessor($ct, $itemlayout);
		
		//Grouping
		if($ct->Env->menu_params->get('groupby')!='')
			$groupby=Fields::getRealFieldName($ct->Env->menu_params->get('groupby'),$ct->Table->fields);
		else
			$groupby='';

		if($groupby=='')
		{
				$number = 1 + $ct->LimitStart;
                $RealRows = [];
				foreach($ct->Records as $row)
				{
						$row['_number'] = $number;
				        $RealRows[]=tagProcessor_Item::RenderResultLine($ct,$layoutType,$twig,$row); //3ed parameter is to show record HTML anchor or not
						$number++;
				}
				$CatGroups[]=array('',$RealRows);
		}
		else
		{
			//Group Results
			$FieldRow=Fields::FieldRowByName($ct->groupby,$ct->Table->fields);

			$RealRows=array();
				$lastGroup='';

				$number = 1 + $ct->LimitStart;
				foreach($ct->Records as $row)
				{

						if($lastGroup!=$row[$ct->groupby] and $lastGroup!='')
						{
								if($FieldRow['type']=='customtables')
									$GroupTitle=implode(',',Tree::getMultyValueTitles($lastGroup,$ct->Languages->Postfix,1, ' - '));
								else
								{
									$row['_number'] = $number;
									$galleryrows=array();
									$FileBoxRows=array();
									$option=array();
									//getValueByType(&$ct,$ESField, $row, &$option_list,&$getGalleryRows,&$getFileBoxRows)
									$GroupTitle=tagProcessor_Value::getValueByType($ct,$FieldRow,$row,$option,$galleryrows,$FileBoxRows);
								}

								$CatGroups[]=array($GroupTitle,$RealRows);
								$RealRows=array();
						}
                        $RealRows[]=tagProcessor_Item::RenderResultLine($ct,$layoutType,$twig,$row); //3ed parameter is to show record HTML anchor or not

						$lastGroup=$row[$ct->groupby];

					$number++;
				}
				if(count($RealRows)>0)
				{
					if($FieldRow['type']=='customtables')
						$GroupTitle=implode(',',Tree::getMultyValueTitles($lastGroup,$ct->Languages->Postfix,1, ' - '));
					else
					{
						$galleryrows=array();
						$FileBoxRows=array();
						$option=array();

                        $row = $RealRows[0];

						$GroupTitle=tagProcessor_Value::getValueByType($ct,$FieldRow,$row,$option,$galleryrows,$FileBoxRows);
					}
					$CatGroups[]=array($GroupTitle,$RealRows);
				}
		}

		$CatGroups=self::reorderCatGroups($CatGroups);

		if($showtable)
		{
			$catalogResult.='
    <table'.( ($tableclass!='' ? ' class="'.$tableclass.'"' : '')).' cellpadding="0" cellspacing="0">
        <tbody>
';
		}

		$number_of_columns=3;

		foreach($CatGroups as $cGroup)
		{
			$tr=0;
			$RealRows=$cGroup[1];

			if($showtable)
			{
				if($cGroup[0]!='')
					$catalogResult.='<tr><td colspan="'.($number_of_columns).'"><h2>'.$cGroup[0].'</h2></td></tr>';
			}
			else
			{
				if($cGroup[0]!='')
					$catalogResult.='<h2>'.$cGroup[0].'</h2>';
			}

			$i = 0;
			
			foreach($RealRows as $row)
			{
				if($separator != '' and $i > 0)
					$catalogResult .= $separator;
					
				if($tr==0 and $showtable)
					$catalogResult .= '<tr>';

				if($showtable)
				{
					if(isset($row[$ct->Table->realidfieldname]))
						$catalogResult.='<td valign="top" align="left"><a name="a'.$row[$ct->Table->realidfieldname].'"></a>'.$row.'</td>';
					else
						$catalogResult.='<td valign="top" align="left">'.$row.'</td>';
				}
				else
					$catalogResult.=$row;

				$tr++;
				if($tr==$number_of_columns)
				{
					if($showtable)
						$catalogResult.='</tr>';

					$tr	=0;
				}
				
				$i += 1;
			}

			if($tr>0 and $showtable)
				$catalogResult.='<td'.($number_of_columns-$tr>1 ? ' colspan="'.($number_of_columns-$tr).'"' : '').'>&nbsp;</td></tr>';

			if($showtable)
				$catalogResult.='<tr><td'.($number_of_columns>1 ?  ' colspan="'.($number_of_columns).'"': '').'"><hr/></td></tr>';
		}	//	foreach($CatGroups as $cGroup)

		if($showtable)
		{
			$catalogResult.='</tbody>
    </table>';
		}

		return $catalogResult;
	}



    protected static function reorderCatGroups(&$CatGroups)
	{
		$newCat=array();
		$names=array();
		foreach($CatGroups as $c)
			$names[]=$c[0];

		sort($names);

		foreach($names as $n)
		{
			foreach($CatGroups as $c)
			{
				if($n==$c[0])
				{
					$newCat[]=$c;
					break;
				}
			}
		}

		return $newCat;
	}

}
