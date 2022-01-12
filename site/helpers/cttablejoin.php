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
use \Joomla\CMS\Factory;

class JHTMLCTTableJoin
{
	static public function render($control_name, &$ct, &$field, $value, $place_holder, $cssclass='', $attribute='', &$option_list)
    {
		$filter = [];
		
		$parent_filter_field_name = JHTMLCTTableJoin::parseTagArguments($option_list,$filter);
		JHTMLCTTableJoin::parseTypeParams($field,$filter,$parent_filter_field_name);
		
		print_r($filter);
		echo '<hr/>';
		
		$key = JoomlaBasicMisc::generateRandomString();
		Factory::getApplication()->setUserState($key, $filter);
		
		//Get initial table filters based on the value
		
		$db = Factory::getDBO();
		$js_filters = [];
		
		$parent_id = $value;
		
		
		for($i = count($filter) - 1;$i >= 0; $i--)
		//for($i = 0;$i < count($filter); $i++)
		{
			$flt = $filter[$i];
			$tablename = $flt[0];
			
			$temp_ct = new CT;
			$temp_ct->getTable($tablename);
			
			foreach($temp_ct->Table->fields as $fld)
			{
				if($fld['type'] == 'sqljoin')
				{
					$type_params = JoomlaBasicMisc::csv_explode(',',$fld['typeparams'],'"',false);
					$join_tablename = $type_params[0];
					$join_to_tablename = $flt[5];
					
					if($join_tablename == $join_to_tablename)
					{
						$join_realdfieldname = $fld['realfieldname'];
						break;
					}
				}
			}
			
			$query = 'SELECT '.$temp_ct->Table->tablerow['query_selects'].' FROM '.$temp_ct->Table->realtablename.' WHERE '
				.$temp_ct->Table->realidfieldname.'='.$db->quote($parent_id).' LIMIT 1';
				
			echo '$query = '.$query.'<hr/>';

			$db->setQuery( $query );
			$recs = $db->loadAssocList();
			
			$parent_id = $recs[0][$join_realdfieldname];
			$js_filters[] = $parent_id;
		}
		
		$data = [];
		$data[] = 'data-key="'.$key.'"';
		$data[] = 'data-fieldname="'.$field['fieldname'].'"';
		$data[] = 'data-controlname="'.$control_name.'"';
		$data[] = 'data-filtercount='.count($filter);
		$data[] = 'data-valuefilters="'.implode(',',$js_filters).'"';
		$data[] = 'data-value="'.$value.'"';
		
		echo '<div id="'.$control_name.'Wrapper" '.implode(' ',$data).'><div id="'.$control_name.'Selector0"></div></div>
			<script>
				ctUpdateTableJoinLink("'.$control_name.'",0,true);
			</script>
';
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

		//Param version
		//2.5.9 version
		//[table_name,field_name,filter,DYNAMIC_FILTER,order_by,allow_unpublished,SELECTOR,ADD_FORIGN_KEY]
		//2.6.2 version
		//[table_name,field_name,allow_unpublished,filter,order_by,ADD_FORIGN_KEY]
		if(count($type_params) > 6 or (isset($type_params[7]) and ($type_params[7] == 'addforignkey' or $type_params[7] == 'noforignkey')))
		{
			//2.5.9 version
			//table_name,field_name,filter,DYNAMIC_FILTER,order_by,allow_unpublished
			//to
			//table_name,field_name,allow_unpublished,filter,order_by
			
			//Dynamic filter, 
			if($type_params[3] != null and $type_params[3] != '')
			{
				$temp_ct = new CT;
				$temp_ct->getTable($type_params[0]);
				
				if($temp_ct->Table->tablename=='')
				{
					JFactory::getApplication()->enqueueMessage('Dynamic filter field "'.$type_params[3].'" : Table "' . $temp_ct->Table->tablename . '" not found.','error');
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
			//>=2.6.2 version
			//table_name,field_name,allow_unpublished,filter,order_by
			//to
			//table_name,field_name,allow_unpublished,filter,order_by,fieldname_for_parent_table_value
			$filter[] = [$type_params[0],$type_params[1],$type_params[2],$type_params[3],$type_params[4],$parent_filter_field_name];
		}		
	}
}
