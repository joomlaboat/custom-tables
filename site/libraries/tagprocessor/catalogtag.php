<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
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

use CustomTables\TwigProcessor;

class tagProcessor_Catalog
{
    use render_html;
	use render_xlsx;
	use render_csv;
	use render_json;
	use render_image;

    public static function process(&$ct,&$pagelayout,$new_replaceitecode)
    {
        $vlu='';
        $allowcontentplugins=$ct->Env->menu_params->get( 'allowcontentplugins' );

        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('catalog',$options,$pagelayout,'{}');
		//---------------------
		$i=0;
		foreach($fList as $fItem)
		{
			$pair=explode(',',$options[$i]);

			$tableclass=$pair[0];

			if($ct->Env->frmt=='csv')
			{
				$pagelayout=str_replace($fItem,'',$pagelayout);//delete {catalog} tag
				self::get_CatalogTable_singleline_CSV($ct,$allowcontentplugins,$pagelayout);
			}
			elseif($ct->Env->frmt=='json')
			{
				//$pagelayout=str_replace($fItem,'',$pagelayout);//delete {catalog} tag
				$vlu=self::get_CatalogTable_singleline_JSON($ct,$allowcontentplugins,$pagelayout);
			}
			elseif($ct->Env->frmt=='image')
				self::get_CatalogTable_singleline_IMAGE($pagelayout,$allowcontentplugins);
			elseif(isset($pair[1]) and $pair[1]=='notable')
				$vlu=self::get_Catalog($ct,$tableclass,false,$allowcontentplugins);
			else
				$vlu=self::get_Catalog($ct,$tableclass,true,$allowcontentplugins);

			$pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			$i++;
		}

        return $vlu;
    }

    protected static function get_Catalog(&$ct,$tableclass,$showtable=true,$allowcontentplugins=false)
	{
		$catalogresult='';

		if($ct->Records == null or count($ct->Records)==0)
			return '';

		$CatGroups=array();
		
		$twig = new TwigProcessor($ct, $ct->LayoutProc->layout);
		
		//Grouping
		if($ct->Env->menu_params->get('groupby')!='')
			$groupby=Fields::getRealFieldName($ct->Env->menu_params->get('groupby'),$ct->Table->fields);
		else
			$groupby='';

		if($groupby=='')
		{
				$number = 1 + $ct->LimitStart;
								
				foreach($ct->Records as $row)
				{
						$row['_number'] = $number;
				        $RealRows[]=tagProcessor_Item::RenderResultLine($ct,$twig,$row); //3ed parameter is to show record HTML anchor or not
						$number++;
				}
				$CatGroups[]=array('',$RealRows);
		}
		else
		{
			//Group Results

			$FieldRow=ESFields::FieldRowByName($ct->groupby,$ct->Table->fields);


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
									$GroupTitle=$ct->LayoutProc->getValueByType($ct,$FieldRow,$row,$option,$galleryrows,$FileBoxRows);
								}

								$CatGroups[]=array($GroupTitle,$RealRows);
								$RealRows=array();
						}
                        $RealRows[]=tagProcessor_Item::RenderResultLine($ct,$twig,$row); //3ed parameter is to show record HTML anchor or not

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

						$GroupTitle=$ct->LayoutProc->getValueByType($ct,$FieldRow,$row,$option,$galleryrows,$FileBoxRows);
					}
					$CatGroups[]=array($GroupTitle,$RealRows);
				}
		}

		$CatGroups=self::reorderCatGroups($CatGroups);

	if($showtable)
	{
        $catalogresult.='
    <table'.( ($tableclass!='' ? ' class="'.$tableclass.'"' : '')).' cellpadding="0" cellspacing="0">
        <tbody>
';
	}

		$number_of_columns=3;

		$content_width=100;
		$column_width=floor($content_width/$number_of_columns);


		foreach($CatGroups as $cGroup)
		{
			$tr=0;
			$RealRows=$cGroup[1];

			if($showtable)
			{
				if($cGroup[0]!='')
					$catalogresult.='<tr><td'.($number_of_columns>1 ?  ' colspan="'.($number_of_columns).'"': '').'><h2>'.$cGroup[0].'</h2></td></tr>';
			}
			else
			{
				if($cGroup[0]!='')
					$catalogresult.='<h2>'.$cGroup[0].'</h2>';
			}

			foreach($RealRows as $row)
			{
				if($tr==0 and $showtable)
					$catalogresult.='<tr>';

				if($showtable)
				{
					$catalogresult.='<td valign="top" align="left"><a name="a'.$row['listing_id'].'"></a>'.$row.'</td>';
				}
				else
					$catalogresult.=$row;

				$tr++;
				if($tr==$number_of_columns)
				{
					if($showtable)
						$catalogresult.='</tr>';

					$tr	=0;
				}
			}

			if($tr>0 and $showtable)
				$catalogresult.='<td'.($number_of_columns-$tr>1 ? ' colspan="'.($number_of_columns-$tr).'"' : '').'>&nbsp;</td></tr>';

			if($showtable)
				$catalogresult.='<tr><td'.($number_of_columns>1 ?  ' colspan="'.($number_of_columns).'"': '').'"><hr/></td></tr>';
		}	//	foreach($CatGroups as $cGroup)

		if($showtable)
		{
			$catalogresult.='</tbody>

    </table>';
		}

		return $catalogresult;
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
