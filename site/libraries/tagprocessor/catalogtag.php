<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

require_once('render_html.php');
require_once('render_xlsx.php');
require_once('render_csv.php');
require_once('render_image.php');

class tagProcessor_Catalog
{
    use render_html;
	use render_xlsx;
	use render_csv;
	use render_image;

    public static function process(&$Model,&$pagelayout,&$SearchResult,$new_replaceitecode)
    {
        $vlu='';
        $allowcontentplugins=$Model->params->get( 'allowcontentplugins' );

        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('catalog',$options,$pagelayout,'{}');
		//---------------------
		$i=0;
		foreach($fList as $fItem)
		{
			$pair=explode(',',$options[$i]);

			$tableclass=$pair[0];

			if($Model->frmt=='csv')
			{
				$pagelayout=str_replace($fItem,'',$pagelayout);//delete {catalog} tag
				self::get_CatalogTable_singleline_CSV($SearchResult,$Model,$allowcontentplugins,$pagelayout);
			}
			if($Model->frmt=='image')
				self::get_CatalogTable_singleline_IMAGE($pagelayout,$allowcontentplugins);
			elseif(isset($pair[1]) and $pair[1]=='notable')
				$vlu=self::get_Catalog($Model,$SearchResult,$tableclass,false,false,$allowcontentplugins);
			else
				$vlu=self::get_Catalog($Model,$SearchResult,$tableclass,false,true,$allowcontentplugins);

			$pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			$i++;
		}

        return $vlu;
    }


    protected static function get_Catalog(&$Model,&$SearchResult,$tableclass,$showhr=true,$showtable=true,$allowcontentplugins=false)
	{
		$catalogresult='';

		if(count($SearchResult)==0)
			return '';

		$CatGroups=array();

		if($Model->groupby=='')
		{
				$number=1+$Model->limitstart;
				foreach($SearchResult as $row)
				{
						$Model->LayoutProc->number=$number;
				        $RealRows[]=tagProcessor_Item::RenderResultLine($Model,$row,$showtable==true); //3ed parameter is to show record HTML anchor or not
						$number++;
				}
				$CatGroups[]=array('',$RealRows);
		}
		else
		{
				//Group Results

				$FieldRow=$Model->esTable->FieldRowByName($Model->groupby,$Model->esfields);


				$RealRows=array();
				$lastGroup='';

				$number=1+$Model->limitstart;
				foreach($this->SearchResult as $row)
				{

						if($lastGroup!=$row['es_'.$Model->groupby] and $lastGroup!='')
						{
								if($FieldRow['type']=='customtables')
									$GroupTitle=implode(',',$Model->es->getMultyValueTitles($lastGroup,$Model->langpostfix,1, ' - '));
								else
								{
									$Model->LayoutProc->number=$number;
									$galleryrows=array();
									$FileBoxRows=array();
									$option=array();
									$GroupTitle=$Model->LayoutProc->getValueByType($Model,$lastGroup,$FieldRow['fieldname'],$FieldRow['type'],$FieldRow['typeparams'],$option,$galleryrows,$FileBoxRows,$row['id']);
								}

								$CatGroups[]=array($GroupTitle,$RealRows);
								$RealRows=array();
						}
                        $RealRows[]=tagProcessor_Item::RenderResultLine($Model,$row,$showtable==true); //3ed parameter is to show record HTML anchor or not
						//$RealRows[]=$this->RenderResultLine($row,$showtable,$allowcontentplugins);//,$Itemid,$Model,$userid,$this->isUserAdministrator,$current_url,$print);

						$lastGroup=$row['es_'.$Model->groupby];

					$number++;
				}
				if(count($RealRows)>0)
				{
					if($FieldRow['type']=='customtables')
						$GroupTitle=implode(',',$Model->es->getMultyValueTitles($lastGroup,$Model->langpostfix,1, ' - '));
					else
					{
						$galleryrows=array();
						$FileBoxRows=array();
						$option=array();

						$GroupTitle=$Model->LayoutProc->getValueByType($Model,$lastGroup,$FieldRow['fieldname'],$FieldRow['type'],$FieldRow['typeparams'],$option,$galleryrows,$FileBoxRows,$row['id'],$FieldRow['id']);
					}
					$CatGroups[]=array($GroupTitle,$RealRows);
				}
		}//if($Model->groupby=='')


		$CatGroups=self::reorderCatGroups($CatGroups);


	if($showtable)
	{
        $catalogresult.='
    <table'.( ($tableclass!='' ? ' class="'.$tableclass.'"' : '')).' cellpadding="0" cellspacing="0">
        <tbody>
';
	}

		$number_of_columns=$Model->columns;
		if($number_of_columns<1)
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
							$catalogresult.='<td'.($number_of_columns>1 ? ' width="'.$column_width.'%"' : '').' valign="top" align="left">'.$row.'</td>';
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

				if($showhr and $showtable)
					$catalogresult.='<tr><td'.($number_of_columns>1 ?  ' colspan="'.($number_of_columns).'"': '').'"><hr/></td></tr>';
		}	//	foreach($CatGroups as $cGroup)



//}
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

/*
    	function renderDeleteFunction()
	{
		if($this->isDeleteFunctionRendered)
			return false;


		$this->isDeleteFunctionRendered=true;
	}
    */

}
