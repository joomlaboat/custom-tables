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
    protected static function get_CatalogTable_HTML(&$ct, $fields, $class, $dragdrop = false)
	{
		//for reload single record functionality
		$listing_id = $ct->Env->jinput->getCmd('listing_id','');
		$custom_number = $ct->Env->jinput->getInt('number',0);
		$start = $ct->Env->jinput->getInt('start',0); //pagination
		// end of for reload single record functionality
		
		$catalogresult='';

		$fields=str_replace("\n",'',$fields);
		$fields=str_replace("\r",'',$fields);

		$fieldarray=JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts
		$result='
		<table id="ctTable_'.$ct->Table->tableid.'" '.($class!='' ? ' class="'.$class.'" ': '').' style="position: relative;"><thead><tr>';
		
		$recordline='<tr id="ctTable_'.$ct->Table->tableid.'_{{ record.id }}">';

		foreach($fieldarray as $field)
		{
			$fieldpair=JoomlaBasicMisc::csv_explode(':', $field, '"', false);

			if(isset($fieldpair[2]) and $fieldpair[2]!='')
				$result.='<th '.$fieldpair[2].'>'.$fieldpair[0].'</th>';//header
			else
				$result.='<th>'.$fieldpair[0].'</th>';//header

            if(!isset($fieldpair[1]))
			{
                $recordline.='<td>Catalog Layout Content field corrupted. Check the Layout.</td>';//content
			}
            else
			{
				$attribute = '';
				if($dragdrop)
				{
					$fields_found = tagProcessor_CatalogTableView::checkIfColumnIsASingleField($ct,$fieldpair[1]);
					
					if(count($fields_found) == 1)
						$attribute =' id="ctTable_'.$ct->Table->tableid.'_{{ record.id }}_'.$fields_found[0].'" draggable="true" '
						.'ondragstart="ctCatalogOnDragStart(event);" ondragover="ctCatalogOnDragOver(event);" ondrop="ctCatalogOnDrop(event);"';
				}
				
				$recordline.='<td'.$attribute.'>'.$fieldpair[1].'</td>';//content
			}
		}
		$result.='</tr></thead>';

        //Parse Header
		if($listing_id == '')
		{
			$ct->LayoutProc->layout=$result;
			$result=$ct->LayoutProc->fillLayout();
			$result=str_replace('&&&&quote&&&&','"',$result);
		
			$twig = new TwigProcessor($ct, $result);
			$result = $htmlresult = $twig->process();
		}

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
			$row['_number'] = ($custom_number >0 ? $custom_number : $number);
		    $tablecontent.=tagProcessor_Item::RenderResultLine($ct,$twig,$row);
			
			$number++;
		}
		
		if($listing_id != '')
			die($tablecontent);
		
        $result.='<tbody>'.$tablecontent.'</tbody></table>';
		
		//Add Ordering Field Type code
		if(isset($ct->LayoutVariables['ordering_field_type_found']) and $ct->LayoutVariables['ordering_field_type_found'])
		{
			$saveOrderingUrl = 'index.php?option=com_customtables&view=catalog&task=ordering&tableid='.$ct->Table->tableid.'&tmpl=component&clean=1';
			JHtml::_('sortablelist.sortable', 'ctTable_'.$ct->Table->tableid.'', 'ctTableForm_'.$ct->Table->tableid.'', 'asc', $saveOrderingUrl);
			
			$result = '<form id="ctTableForm_'.$ct->Table->tableid.'">'.$result.'</form>';
		}

		return $result;
	}
	
	protected static function checkIfColumnIsASingleField(&$ct,$htmlresult)
	{
		$fieldsFound = [];
		foreach($ct->Table->fields as $field)
		{
			$options=array();
			$fList=JoomlaBasicMisc::getListToReplace($field['fieldname'],$options,$htmlresult,'[]',':','"');
			if(count($fList) > 0)
				$fieldsFound[] = $field['fieldname'];
		}
		return $fieldsFound;
	}
}
