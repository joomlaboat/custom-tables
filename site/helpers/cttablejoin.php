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
use CustomTables\Fields;
use \Joomla\CMS\Factory;

class JHTMLCTTableJoin
{
	static public function render($control_name, &$ct, &$field, $value, $place_holder, $cssclass='', $attribute='', &$option_list)
    {
		$filter = [];
		
		$parent_filter_field_name = JHTMLCTTableJoin::parseTagArguments($option_list,$filter);
		JHTMLCTTableJoin::parseTypeParams($field,$filter,$parent_filter_field_name);
		
		//Get initial table filters based on the value
		
		$js_filters = [];
		$js_filters_selfparent = [];
		$parent_id = $value;
		JHTMLCTTableJoin::processValue($filter,$parent_id,$js_filters,$js_filters_selfparent);
		
		$js_filters[] = $value;
		
		$key = JoomlaBasicMisc::generateRandomString();
		Factory::getApplication()->setUserState($key, $filter);

		$data = [];
		$data[] = 'data-key="'.$key.'"';
		$data[] = 'data-fieldname="'.$field['fieldname'].'"';
		$data[] = 'data-controlname="'.$control_name.'"';
		$data[] = 'data-valuefilters="'.base64_encode(json_encode($js_filters)).'"';
		$data[] = 'data-value="'.$value.'"';
		
		echo '<div id="'.$control_name.'Wrapper" '.implode(' ',$data).'><div id="'.$control_name.'Selector0_0"></div></div>
			<script>
				ctUpdateTableJoinLink("'.$control_name.'",0,true,0,"");
			</script>
';
	}
	
	protected static function processValue(&$filter,&$parent_id,&$js_filters,&$js_filters_selfparent)
	{
		for($i = count($filter) - 1;$i >= 0; $i--)
		{
			$flt = $filter[$i];
			$tablename = $flt[0];
			$temp_ct = new CT;
			$temp_ct->getTable($tablename);
			
			$is_selfParentTable = false;
			$selfParentJoinField = '';
			
			if($i > 0)//No need to filter first select element values
			{
				$join_to_tablename = $flt[5];
				$parent_id = JHTMLCTTableJoin::getParentFieltrID($temp_ct,$parent_id,$join_to_tablename);
				$js_filters[] = $parent_id;
			}

			//Check if this table has self-parent field - the TableJoing field linked with the same table.
			$selfParentField = Fields::getSelfParentField($temp_ct);
			if($selfParentField != null)
			{
				$selfParent_type_params = JoomlaBasicMisc::csv_explode(',',$selfParentField['typeparams'],'"',false);
				
				if($filter[$i][3] == '')
					$filter[$i][3] = $selfParent_type_params[2];
				
				if($filter[$i][4] == '')
					$filter[$i][4] = $selfParent_type_params[4];
				
				$filter[$i][6] = $selfParentField['fieldname'];
				$js_filters_selfparent[] = ($selfParentField != null ? 1 : 0);
				
				$join_to_tablename = $filter[$i][0];
				
				$selfparnt_filters = [];
				while($parent_id != null)
				{
					$parent_id = JHTMLCTTableJoin::getParentFieltrID($temp_ct,$parent_id,$join_to_tablename);
					if($parent_id != null)
						$selfparnt_filters[] = $parent_id;
				}
				$selfparnt_filters[] = "";
				$js_filters[] = array_reverse($selfparnt_filters);
			}
		}

		if(!is_array(end($js_filters)))
			$js_filters[] = "";
		
		$js_filters = array_reverse($js_filters);
	}
	
	protected static function getParentFieltrID(&$temp_ct,$parent_id,$join_to_tablename)
	{
		$db = Factory::getDBO();
        $join_realfieldname = '';
		
		foreach($temp_ct->Table->fields as $fld)
		{
			if($fld['type'] == 'sqljoin')
			{
				$type_params = JoomlaBasicMisc::csv_explode(',',$fld['typeparams'],'"',false);
				$join_tablename = $type_params[0];

				if($join_tablename == $join_to_tablename)
				{
					$join_realfieldname = $fld['realfieldname'];
					break;
				}
			}
		}
			
		if($join_realfieldname=='')
			return null;
			
		$query = 'SELECT '.$join_realfieldname.' FROM '.$temp_ct->Table->realtablename.' WHERE '
			.$temp_ct->Table->realidfieldname.'='.$db->quote($parent_id).' LIMIT 1';
			
		$db->setQuery( $query );
		$recs = $db->loadAssocList();
		if(count($recs)==0)
			return null;

        return $recs[0][$join_realfieldname];
	}
	
	protected static function parseTagArguments(&$option_list,&$filter)
	{
		//Preselectors
		//example: city.edit("cssclass","attributes",[["province","name",true,"active=1","name"],["city","name",false,"active=1","name"],["streets","layout:TheStreeName",false,"active=1","streename"]])
		//parameter 3 can be 1 or 2 dimentional array.
		//One dimentioinal array will be converted to 2 dimentional array.
		//$cssclass = $option_list[0]; // but it's have been already procressed
		//$attribute = $option_list[1]; // but it's have been already procressed
		
		//Twig teg example:
		//{{ componentid.edit("mycss","readyonly",[["grades","grade"],["classes","class"]]) }}
		//{{ componentid.edit("mycss","readyonly",["grades","grade"]) }}
		//{{ componentid.edit("mycss","readyonly","grades","grade") }}
		
		$parent_filter_field_name = '';
		
		if(isset($option_list[2]))
		{
			$opt = $option_list[2];
			if(is_array($opt))
			{
				if(is_array($opt[0]))
				{
					foreach($opt as $fltr)
					{
						$fltr[5] = $parent_filter_field_name;
						$filter[] = $fltr;
						$parent_filter_field_name = $fltr[0];
					}
				}
				else
				{
					$filter[] = [$opt[0], $opt[1], $opt[2], $opt[3], $opt[4],$parent_filter_field_name];
					$parent_filter_field_name = $opt[0];
				}
			}
			else
			{
				//$filter[] = [table_name, field_name, allow_unpublished, filter, order_by];
				$filter[] = [$opt, $option_list[3], $option_list[4], $option_list[5], $option_list[6],$parent_filter_field_name];
				$parent_filter_field_name = $opt;
			}
		}

		return $parent_filter_field_name;
	}
	
	protected static function parseTypeParams(&$field,&$filter,$parent_filter_field_name)
	{
		$type_params = JoomlaBasicMisc::csv_explode(',',$field['typeparams'],'"',false);

		if(count($type_params) > 6 or (isset($type_params[7]) and ($type_params[7] == 'addforignkey' or $type_params[7] == 'noforignkey')))
		{
			//Dynamic filter, 
			if($type_params[3] != null and $type_params[3] != '')
			{
				$temp_ct = new CT;
				$temp_ct->getTable($type_params[0]);
				
				if($temp_ct->Table->tablename=='')
				{
					Factory::getApplication()->enqueueMessage('Dynamic filter field "'.$type_params[3].'" : Table "' . $temp_ct->Table->tablename . '" not found.','error');
					return '';
				}
				
				//Find dynamic filter field
				foreach($temp_ct->Table->fields as $fld)
				{
					if($fld['fieldname'] == $type_params[3])
					{
						//Add dynamic filter parameters
						$temp_type_params = JoomlaBasicMisc::csv_explode(',',$fld['typeparams'],'"',false);
						$filter[] = [$temp_type_params[0],$temp_type_params[1],$temp_type_params[5],$temp_type_params[2],$temp_type_params[4],$parent_filter_field_name];
						
						$parent_filter_field_name = $temp_type_params[0];
						break;
					}
				}
			}
			
			$filter[] = [$type_params[0],$type_params[1],$type_params[5],$type_params[2],$type_params[4],$parent_filter_field_name];
			$parent_filter_field_name = $type_params[0];
		}
		else
		{
			$filter[] = [$type_params[0],$type_params[1],$type_params[2],$type_params[3],$type_params[4],$parent_filter_field_name];
		}		
	}
}
