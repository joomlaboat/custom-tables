<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\TwigProcessor;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESRecordsView
{
	public static function render($value, $establename, $field, $selector, $filter, $sortByField=""): ?string
    {
		if($value=='' or $value==',' or $value==',,')
			return '';

		$htmlresult='';
		$value_where_filter='INSTR(",'.$value.',",id)';

		$paramsArray=array();
		$paramsArray['limit']=0;
		$paramsArray['establename']=$establename;
		$paramsArray['filter']=$filter;
		$paramsArray['showpublished']=2;//0 - published only; 1 - hidden only;
		$paramsArray['showpagination']=0;
		$paramsArray['groupby']='';
		$paramsArray['shownavigation']=0;
		$paramsArray['sortby']=$sortByField;

		$_params= new JRegistry;
		$_params->loadArray($paramsArray);

        $ct = new CT;
        $ct->setParams($_params, true);

        // -------------------- Table

        $ct->getTable($ct->Params->tableName);

        if($ct->Table->tablename=='')
        {
            $ct->app->enqueueMessage('Catalog View: Table not selected.', 'error');
            return null;
        }

        // --------------------- Filter
        $ct->setFilter('', $ct->Params->showPublished);
        $ct->Filter->addMenuParamFilter();
        $ct->Filter->where[] = $value_where_filter;

        // --------------------- Sorting
        $ct->Ordering->parseOrderByParam();

        // --------------------- Limit
        $ct->applyLimits();

        $ct->getRecords();

		$selectorPair=explode(':',$selector);

		if(!str_contains($field, ':'))
		{
			//without layout
			$valueArray=explode(',',$value);
			switch($selectorPair[0])
			{
				case 'single' :

					//$getGalleryRows=array();
					foreach($ct->Records as $row)
					{
						if(in_array($row[$ct->Table->realidfieldname],$valueArray) )
							$htmlresult.=JoomlaBasicMisc::processValue($field, $ct, $row);
					}

					break;

                case 'checkbox':
                case 'multibox':
                case 'multi' :
					
					$vArray=array();

					foreach($ct->Records as $row)
					{
						if(in_array($row[$ct->Table->realidfieldname],$valueArray) )
							$vArray[]=JoomlaBasicMisc::processValue($field, $ct, $row);
					}
					$htmlresult.=implode(',',$vArray);

					break;

				case 'radio' :

					foreach($ct->Records as $row)
					{
						if(in_array($row[$ct->Table->realidfieldname],$valueArray) )
							$htmlresult.=JoomlaBasicMisc::processValue($field, $ct, $row);
					}

					break;

                default:
					return '<p>Incorrect selector</p>';
			}
		}
		else
		{
			$pair=JoomlaBasicMisc::csv_explode(':',$field);

			if($pair[0]!='layout' and $pair[0]!='tablelesslayout')
				return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

			$isTableLess=false;
			if($pair[0]=='tablelesslayout')
				$isTableLess=true;

			if(isset($pair[1]))
				$layoutname = $pair[1];
			else
				return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

			if(isset($pair[2]))
                $columns = (int)$pair[2];
			else
                $columns = 0;

			$Layouts = new Layouts($ct);
			$layoutcode = $Layouts->getLayout($layoutname);
				
			if($layoutcode=='')
				return '<p>layout "'.$layoutname.'" not found or is empty.</p>';

			$valueArray=explode(',',$value);

			if(!$isTableLess)
				$htmlresult.='<table style="border:none;">';

			$number=1;


			$tr=0;

			$CleanSearchResult = [];
			foreach($ct->Records as $row)
			{
				if(in_array($row[$ct->Table->realidfieldname],$valueArray))
					$CleanSearchResult[]=$row;
			}
				
			foreach($CleanSearchResult as $row)
			{
				if($tr==$columns)
					$tr	= 0;

				if(!$isTableLess and $tr==0)
					$htmlresult.='<tr>';

				//process layout
				$row['_number'] = $number;

				if($ct->Env->legacysupport) {
                    $LayoutProc = new LayoutProcessor($ct);
                    $LayoutProc->layout=$layoutcode;
                    $vlu = $LayoutProc->fillLayout($row);
                }
				else
					$vlu = $layoutcode;

				$twig = new TwigProcessor($ct, '{% autoescape false %}'.$vlu.'{% endautoescape %}');
				$vlu = $twig->process($row);

				if($isTableLess)
					$htmlresult.= $vlu;
				else
					$htmlresult.='<td style="border:none;">'.$vlu.'</td>';

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
