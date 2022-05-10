<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\LinkJoinFilters;
use CustomTables\TwigProcessor;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESRecordsView
{
	public static function render($value, $establename, $field, $selector, $filter,$langpostfix='',$sortbyfield="")
	{
		if($value=='' or $value==',' or $value==',,')
			return '';

		$fieldvalues=JoomlaBasicMisc::csv_explode(':',$field,'"',false);
		$htmlresult='';
		$config=array();
		$value_where_filter='INSTR(",'.$value.',",id)';

		$paramsArray=array();
		$paramsArray['limit']=0;
		$paramsArray['establename']=$establename;
		$paramsArray['filter']=$filter;
		$paramsArray['showpublished']=2;//0 - published only; 1 - hidden only;
		$paramsArray['showpagination']=0;
		$paramsArray['groupby']='';
		$paramsArray['shownavigation']=0;
		$paramsArray['sortby']=$sortbyfield;

		$_params= new JRegistry;
		$_params->loadArray($paramsArray);

		$model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);
		$model->load($_params, true);
		$model->showpagination=false;

		$model->getSearchResult($value_where_filter);

		$selectorpair=explode(':',$selector);

		if(strpos($field,':')===false)
		{
			//without layout
			$valuearray=explode(',',$value);
			switch($selectorpair[0])
			{
				case 'single' :

					$getGalleryRows=array();
					foreach($model->ct->Records as $row)
					{
						if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) )
							$htmlresult.=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
					}

					break;

				case 'multi' :
					
					$vArray=array();

					foreach($model->ct->Records as $row)
					{
						if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) )
							$vArray[]=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
					}
					$htmlresult.=implode(',',$vArray);

					break;

				case 'radio' :

					foreach($model->ct->Records as $row)
					{
						if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) )
							$htmlresult.=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
					}

					break;

				case 'checkbox' :

					$vArray=array();

					foreach($model->ct->Records as $row)
					{
						if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) )
							$vArray[]=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
					}
					$htmlresult.=implode(',',$vArray);
					break;

				case 'multibox' :

					$vArray=array();

					foreach($model->ct->Records as $row)
					{
						if(in_array($row[$model->ct->Table->realidfieldname],$valuearray) )
							$vArray[]=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);
					}
					$htmlresult.=implode(',',$vArray);
					break;

				default:
					return '<p>Incorrect selector</p>';

				break;
			}
		}
		else
		{
			$pair=JoomlaBasicMisc::csv_explode(':',$field,'"',false);

			if($pair[0]!='layout' and $pair[0]!='tablelesslayout')
				return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

			$isTableLess=false;
			if($pair[0]=='tablelesslayout')
				$isTableLess=true;

			if(isset($pair[1]))
				$layout_pair[0]=$pair[1];
			else
				return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

			if(isset($pair[2]))
				$layout_pair[1]=$pair[2];
			else
				$layout_pair[1]=0;

			$Layouts = new Layouts($model->ct);
			$layoutcode = $Layouts->getLayout($layout_pair[0]);
				
			if($layoutcode=='')
				return '<p>layout "'.$layout_pair[0].'" not found or is empty.</p>';

			if($model->ct->Env->legacysupport)
			{
				$LayoutProc = new LayoutProcessor($model->ct);
				$LayoutProc->layout=$layoutcode;
			}

			$valuearray=explode(',',$value);

			if(!$isTableLess)
				$htmlresult.='<!-- records view : table --><table style="border:none;">';

			$number=1;
			if(isset($layout_pair[1]) and (int)$layout_pair[1]>0)
				$columns=(int)$layout_pair[1];
			else
				$columns=1;

			$tr=0;

			$CleanSearchResult = [];
			foreach($model->ct->Records as $row)
			{
				if(in_array($row[$model->ct->Table->realidfieldname],$valuearray))
					$CleanSearchResult[]=$row;
			}
				
			$result_count=count($CleanSearchResult);

			foreach($CleanSearchResult as $row)
			{
				if($tr==$columns)
					$tr	= 0;

				if(!$isTableLess and $tr==0)
					$htmlresult.='<tr>';

				//process layout
				$row['_number'] = $number;

				if($model->ct->Env->legacysupport)
					$vlu = $LayoutProc->fillLayout($row);
				else
					$vlu = $layoutcode;

				$twig = new TwigProcessor($model->ct, '{% autoescape false %}'.$vlu.'{% endautoescape %}');
				$vlu = $twig->process($row);

				if($isTableLess)
					$htmlresult.= $vlu;
				else
					$htmlresult.='<td valign="middle" style="border:none;">'.$vlu.'</td>';

				$tr++;
				if(!$isTableLess and $tr==$columns)
					$htmlresult.='</tr>';
				
				$number++;
			}
				
			if(!$isTableLess and $tr<$columns)
				$htmlresult.='</tr>';

			if(!$isTableLess)
				$htmlresult.='</table><!-- records view : end of table -->';
		}
		return $htmlresult;
	}
}
