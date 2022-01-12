<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use CustomTables\Layouts;
use CustomTables\LinkJoinFilters;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESSqlJoin
{
    static public function render($typeparams, $value, $force_dropdown, $langpostfix,$control_name,$place_holder,$cssclass='', $attribute='',$addNoValue=false)
    {
		if(count($typeparams)<1)
        {
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'), 'error');
            return '';
        }

        if(count($typeparams)<2)
        {
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOW_FIELD_LAYOUT'), 'error');
            return '';
        }

		$establename=$typeparams[0];

		if(isset($typeparams[1]))
			$value_field=$typeparams[1];
		else
			$value_field='';

		if(isset($typeparams[2]))
            $filter=$typeparams[2];
		else
            $filter='';
			
		if(isset($typeparams[3]))
			$dynamic_filter=$typeparams[3];
		else
			$dynamic_filter='';

		if(isset($typeparams[4]))
			$order_by_field=$typeparams[4];
		else
        	$order_by_field='';

        if(isset($typeparams[5]) and $typeparams[5]=='true')
			$allowunpublished=true;
		else
        	$allowunpublished=false;

        if(isset($typeparams[6]))
		{
			if($typeparams[6]=='radio')
				$selector='radio';
			if($typeparams[6]=='json')
				$selector='json';
			else
				$selector='dropdown';
		}
		else
			$selector='dropdown';

        if(ESTables::getTableID($establename)=='')
        {
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND'), 'error');
            return '';
        }

		if($order_by_field=='')
			$order_by_field=$value_field;

		if($place_holder=='')
			$place_holder='- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' );

        $config=array();
        $model = JModelLegacy::getInstance('Catalog', 'CustomTablesModel', $config);

        //Get Database records
		JHTMLESSqlJoin::get_searchresult($model,$filter, $establename, $order_by_field,$allowunpublished);

        //Process records depending on field type and layout
        $list_values=JHTMLESSqlJoin::get_List_Values($model,$value_field,$langpostfix,$dynamic_filter);

        $htmlresult='';
        //Output slection box
        if($model->ct->Env->print==1)
        {
			$htmlresult.=JHTMLESSqlJoin::renderPrintResult($list_values,$value,$control_name);
        }
		elseif($selector=='json')
		{
			//$list_value[0] - value
			//$list_value[1] - title
			//$list_value[2] - published
			//$list_value[3] - filter
			$new_list = [];
			foreach($list_values as $value)
				$new_list[] = (object)['value' => $value[0], 'title' => $value[1]];
			
			return $new_list;
		}
		elseif($selector=='dropdown' or $force_dropdown or $dynamic_filter)
        {
			$htmlresult.=JHTMLESSqlJoin::renderDynamicFilter($model->ct,$value,$establename,$dynamic_filter,$control_name);
            $htmlresult.=JHTMLESSqlJoin::renderDropdownSelector_Box($list_values,$value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue);
        }
		else
			$htmlresult.=JHTMLESSqlJoin::renderRadioSelector_Box($list_values,$value,$control_name,$cssclass,$attribute,$value_field);

		return $htmlresult;
    }

    static protected function get_searchresult(&$model,$filter, $establename, $order_by_field,$allowunpublished)
    {
		
		$paramsArray=array();

        $paramsArray['limit']=0;
        $paramsArray['establename']=$establename;
        if($allowunpublished)
			$paramsArray['showpublished']=2;//0 - published only; 1 - hidden only; 2 - Any
        else
			$paramsArray['showpublished']=0;//0 - published only; 1 - hidden only; 2 - Any

        $paramsArray['showpagination']=0;
        $paramsArray['groupby']='';
        $paramsArray['shownavigation']=0;
		
		if(strpos($order_by_field,':')===false) //cannot sort by layout only by field name
			$paramsArray['forcesortby']=$order_by_field;

        if($filter!='')
			$paramsArray['filter']=str_replace('|',',',str_replace('****quote****','"',$filter));
        else
			$paramsArray['filter']=''; //!IMPORTANT - NO FILTER

        $_params= new JRegistry;
		$_params->loadArray($paramsArray);
		
		if(!$model->load($_params, true))
		{
			JFactory::getApplication()->enqueueMessage('Could not load table [151].', 'error');
		}
		else
		{
			if(!$model->getSearchResult())
				JFactory::getApplication()->enqueueMessage('Could not load table [156].', 'error');
		}
    }
        
	static protected function renderDynamicFilter(&$ct, $value,$establename,$dynamic_filter,$control_name)
	{
		$htmlresult='';

		if($dynamic_filter!='')
		{
			$filtervalue='';
			foreach($ct->Records as $row)
			{
				if($row['listing_id']==$value)
				{
					$filtervalue=$row[$ct->Env->field_prefix.$dynamic_filter];
					break;
				}
			}
	
			$htmlresult.=LinkJoinFilters::getFilterBox($establename,$dynamic_filter,$control_name,$filtervalue);
		}

		return $htmlresult;
	}

    static protected function get_List_Values(&$model,$field,$langpostfix,$dynamic_filter)
	{
		$layout_mode=false;

        $pair=explode(':',$field);
        if(count($pair)==2)
        {
			$layout_mode=true;
            if($pair[0]!='layout' and $pair[0]!='tablelesslayout' )
            {
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_UNKNOW_FIELD_LAYOUT').' "'.$field.'"', 'error');
                return array();
            }

			$Layouts = new Layouts($model->ct);
			$layoutcode = $Layouts->getLayout($pair[1]);

            if(!isset($layoutcode) or $layoutcode=='')
            {
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND').' "'.$pair[1].'"', 'error');
				return array();
            }

			$model->ct->LayoutProc->layout=$layoutcode;
        }

        $list_values=array();

		foreach($model->ct->Records as $row)
        {
			if($layout_mode)
				$v=$model->ct->LayoutProc->fillLayout($row);
            else
				$v=JoomlaBasicMisc::processValue($field,$model->ct,$row,$langpostfix);

            if($dynamic_filter!='')
				$d=$row[$model->ct->Env->field_prefix.$dynamic_filter];
            else
				$d='';

            $list_values[]=[$row['listing_id'],$v,(int)$row['listing_published'],$d];
        }

		return $list_values;
	}

    static protected function renderPrintResult($list_values,$current_value,$control_name)
	{
		$htmlresult='';

		foreach($list_values as $list_value)
		{
			if($list_value[0]==$current_value)
			{
				$htmlresult.='<input type="hidden" name="'.$control_name.'" id="'.$control_name.'" value="'.$list_value[0].'" >';
				$htmlresult.=$list_value[1];
                break;
			}
		}

		if($htmlresult=='')
			$htmlresult.='<input type="hidden" name="'.$control_name.'" id="'.$control_name.'" value="">';

		return $htmlresult;
	}

    static protected function renderDropdownSelector_Box($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue=false)
    {
		if(strpos($cssclass,' ct_improved_selectbox')!==false)
			return JHTMLESSqlJoin::renderDropdownSelector_Box_improved($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter);
        else
			return JHTMLESSqlJoin::renderDropdownSelector_Box_simple($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue);
	}

	static protected function renderDropdownSelector_Box_improved($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue=false)
    {
		JHtml::_('formbehavior.chosen', '.ct_improved_selectbox');
        return JHTMLESSqlJoin::renderDropdownSelector_Box_simple($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue);
    }

	static protected function renderDropdownSelector_Box_simple($list_values,$current_value,$control_name,$cssclass,$attribute,$place_holder,$dynamic_filter,$addNoValue=false)
    {
                        $htmlresult='';

                        $htmlresult_select='';
						
                        $htmlresult_select.='<SELECT '
							.'name="'.$control_name.'" '
							.'id="'.$control_name.'" '
							.($cssclass!='' ? 'class="'.$cssclass.'" ' : '')
							.($attribute!='' ? ' '.$attribute.' ' : '')
							.'data-label="'.$place_holder.'" '
							.'>';

		$htmlresult_select.='<option value="">- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).' '.$place_holder.'</option>';

        foreach($list_values as $list_value)
                        {
                                if($list_value[2]==0)//if unpublished
                                        $style=' style="color:red"';
                                else
                                        $style='';

                                if($dynamic_filter=='')
                                        $htmlresult_select.='<option value="'.$list_value[0].'"'.($list_value[0]==$current_value ? ' selected="SELECTED"' : '').''.$style.'>'.strip_tags($list_value[1]).'</option>';
			}
			
			if($addNoValue)
				$htmlresult_select.='<option value="-1"'.((int)$current_value==-1 ? ' selected="SELECTED"' : '').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_SPECIFIED' ).'</option>';
			
			$htmlresult_select.='</SELECT>';


                        if($dynamic_filter!='')
                        {
                                $elements=array();
                                $elementsID=array();
                                $elementsFilter=array();
                                $elementsPublished=array();

                                foreach($list_values as $list_value)
                                {
                                                                        $elementsID[]=$list_value[0];
                                                        		$elements[]='"'.$list_value[1].'"';
                                                                        $elementsPublished[]=$list_value[2];
                                                                        $elementsFilter[]='"'.$list_value[3].'"';
                                }

                                                                $htmlresultjs='
						<script>
							var '.$control_name.'elements=['.implode(',',$elements).'];
							var '.$control_name.'elementsID=['.implode(',',$elementsID).'];
							var '.$control_name.'elementsFilter=['.implode(',',$elementsFilter).'];
                                                        var '.$control_name.'elementsPublished=['.implode(',',$elementsPublished).'];
						</script>
					';
                                        
                                $htmlresult.=$htmlresult_select;

                                $htmlresult=$htmlresultjs.$htmlresult.'
				<script>
                                        '.$control_name.'_current_value="'.$current_value.'";
					'.$control_name.'removeEmptyParents();
					'.$control_name.'UpdateSQLJoinLink();
				</script>
				';

                        }
                        else
                        {
                                $htmlresult.=$htmlresult_select;
                        }



                        return $htmlresult;
                }

    static protected function renderRadioSelector_Box($list_values,$current_value,$control_name,$cssclass,$attribute,$field)
	{
		$pair=explode(':',$field);

        $withtable=false;

		if($pair[0]=='layout')
			$withtable=true;

        $htmlresult='';

        if($withtable)
			$htmlresult.='<table rel="radioboxselector" style="border:none;" id="sqljoin_table_'.$control_name.'" '.($cssclass!='' ? 'class="'.$cssclass.'"' : '').'>';
		else
        	$htmlresult.='<div rel="radioboxselector" id="sqljoin_table_'.$control_name.'" '.($cssclass!='' ? 'class="'.$cssclass.'"' : '').'>';

		$i=0;
        foreach($list_values as $list_value)
        {
			if($withtable)
				$htmlresult.='<tr><td valign="middle">';
			else
				$htmlresult.='<div id="sqljoin_table_'.$control_name.'_'.$list_value[0].'">';

			$htmlresult.='<input type="radio" '
					.'name="'.$control_name.'" '
					.'id="'.$control_name.'_'.$i.'" '
					.'value="'.$list_value[0].'" '
					.($list_value==$current_value ? ' checked="checked" ' : '')
					.' />';

			if($withtable)
				$htmlresult.='</td><td valign="middle">';

			$htmlresult.='<label for="'.$control_name.'_'.$i.'">'.$list_value[1].'</label>';

			if($withtable)
				$htmlresult.='</td></tr>';
			else
				$htmlresult.='</div>';

			$i++;
		}

		if($withtable)
			$htmlresult.='</table>';
		else
			$htmlresult.='</div>';
		
		return $htmlresult;
	}
}
