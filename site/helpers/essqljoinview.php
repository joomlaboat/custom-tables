<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use CustomTables\Layouts;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESSQLJoinView
{
    public static function render($value, $establename, $field, $filter,$langpostfix='')
    {
		if($value==0 or $value=='')// or $value==',' or $value==',,')
			return '';

		$htmlresult='';

		$config=array();

		$paramsArray=array();
		$paramsArray['limit']=0;
		$paramsArray['establename']=$establename;
		$paramsArray['filter']=$filter;
		$paramsArray['showpublished']=0;
		$paramsArray['showpagination']=0;
		$paramsArray['groupby']='';
		$paramsArray['shownavigation']=0;
		$paramsArray['sortby']='';

		$_params= new JRegistry;
		$_params->loadArray($paramsArray);

		$model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);
		$model->load($_params, true);
		$model->showpagination=false;
				
		//Get Row
		$query = 'SELECT '.$model->ct->Table->tablerow['query_selects'].' FROM '.$model->ct->Table->realtablename.' WHERE '.$model->ct->Table->tablerow['realidfieldname'].'='.(int)$value;
		$db= JFactory::getDBO();
		$db->setQuery($query);

		$SearchResult=$db->loadAssocList();

		if(strpos($field,':')===false)
		{
			//without layout
			$getGalleryRows=array();
			foreach($SearchResult as $row)
			{
				if($row['listing_id']==$value)
					$htmlresult.=JoomlaBasicMisc::processValue($field,$model,$row,$langpostfix);
			}
		}
		else
		{
			$pair=explode(':',$field);

			if($pair[0]!='layout' and $pair[0]!='tablelesslayout' and $pair[0]!='value')
				return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'"..</p>';


			$isTableLess=false;
			if($pair[0]=='tablelesslayout' or $pair[0]=='value')
				$isTableLess=true;

			if($pair[0]=='value')
			{
				$layoutcode='[_value:'.$pair[1].']';
			}
			else
			{
				//load layout
				if(isset($pair[1]) or $pair[1]!='')
					$layout_pair[0]=$pair[1];
				else
					return '<p>unknown field/layout command "'.$field.'" should be like: "layout:'.$pair[1].'".</p>';

				if(isset($pair[2]))
					$layout_pair[1]=$pair[2];
				else
					$layout_pair[1]=0;

				$layouttype=0;
				$layoutcode=Layouts::getLayout($layout_pair[0],$layouttype);
		
				if($layoutcode=='')
					return '<p>layout "'.$layout_pair[0].'" not found or is empty.</p>';
			}

			$model->LayoutProc->layout=$layoutcode;

			$valuearray=explode(',',$value);

			if(!$isTableLess)
				$htmlresult.='<table style="border:none;">';

			$number=1;
			if(isset($layout_pair[1]) and (int)$layout_pair[1]>0)
				$columns=(int)$layout_pair[1];
			else
				$columns=1;

			$tr=0;

			$CleanSearchResult=array();
			foreach($SearchResult as $row)
			{
				if(in_array($row['listing_id'],$valuearray))
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
				$model->LayoutProc->number=$number;

				if($isTableLess)
					$htmlresult.=$model->LayoutProc->fillLayout($row,'','');
				else
					$htmlresult.='<td valign="middle" style="border:none;">'.$model->LayoutProc->fillLayout($row,'','').'</td>';

				$tr++;
				if(!$isTableLess and $tr==$columns)
					$htmlresult.='</tr>';

				$number++;
			}

			if(!$isTableLess and $tr<$columns)
				$htmlresult.='</tr>';

			if(!$isTableLess)
				$htmlresult.='</table>';
		}

		LayoutProcessor::applyContentPlugins($htmlresult);
		return $htmlresult;
	}
}
