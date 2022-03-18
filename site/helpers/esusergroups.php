<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');

class JHTMLESUserGroups
{
	static public function render($control_name, $value, $typeparams)
    {
		$typeparams_array=JoomlaBasicMisc::csv_explode(',',$typeparams,'"',false);
		
		$selector = $typeparams_array[0];
		$availableusergroups = $typeparams_array[1] ?? '';
		
		$availableusergroups_list = (trim($availableusergroups) == '' ? [] : explode(',',trim($availableusergroups)));

		$htmlresult='';
		$valuearray=explode(',',$value);
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('#__usergroups.id AS id, #__usergroups.title AS name');
		$query->from('#__usergroups');

		if(count($availableusergroups_list) == 0)
		{
			$query->where('#__usergroups.title!='.$db->quote('Super Users'));
		}
		else
		{
			$where = [];
			foreach($availableusergroups_list as $availableusergroup)
			{
				if($availableusergroup != '')
					$where[] = '#__usergroups.title='.$db->quote($availableusergroup);
			}
			$query->where(implode(' OR ',$where));
		}
		
		$query->order('#__usergroups.title');
		$db->setQuery($query);
		$records=$db->loadObjectList();
	
		$valuearray=explode(',',$value);

		switch($selector)
		{
			case 'single' :
				$htmlresult=JHTMLESUserGroups::getSingle($records,$control_name,$valuearray);
				break;

			case 'multi' :
				$htmlresult.='<SELECT name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE >';
				foreach($records as $row)
				{
					$htmlresult.='<option value="'.$row->id.'" '
								.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
								.'>'.$row->name.'</option>';
				}
			
				$htmlresult.='</SELECT>';
				break;

				case 'radio' :
					$htmlresult.='<table style="border:none;" id="usergroups_table_'.$control_name.'">';
					$i=0;
					foreach($records as $row)
					{
						$htmlresult.='<tr><td valign="middle">'
														.'<input type="radio" '
														.'name="'.$control_name.'" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
														.' /></td>'
														.'<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row->name.'</label>'
														.'</td></tr>';
						$i++;
					}
					$htmlresult.='</table>';
					break;

				case 'checkbox' :
					$htmlresult.='<table style="border:none;">';
					$i=0;
					foreach($records as $row)
					{
						$htmlresult.='<tr><td valign="middle">'
														.'<input type="checkbox" '
														.'name="'.$control_name.'[]" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0 ) ? ' checked="checked" ' : '')
														.' /></td>'
														.'<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row->name.'</label>'
														.'</td></tr>';
						$i++;
					}
					$htmlresult.='</table>';
					break;

				case 'multibox' :
					$htmlresult.=JHTMLESUserGroups::getMultibox($records,$valuearray,$selector,$control_name);
					break;

				default:
					return '<p>Incorrect selector</p>';
		}
		return $htmlresult;
	}
	
	static protected function getSingle(&$records,$control_name,$valuearray)
	{
		$htmlresult='<SELECT name="'.$control_name.'[]" id="'.$control_name.'">';

		foreach($records as $row)
		{
			$htmlresult.='<option value="'.$row->id.'" '
				.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
				.'>'.$row->name.'</option>';
		}
		
		$htmlresult.='</SELECT>';
		
		return $htmlresult;
	}
		
	static protected function getMultibox(&$records,&$valuearray,$selector,$control_name)
	{
		$ctInputboxRecords_r = [];
		$ctInputboxRecords_v = [];
		$ctInputboxRecords_p = [];
		
		foreach((array)$records as $rec)
		{
			$row = (array)$rec;
			if(in_array($row['id'],$valuearray) and count($valuearray)>0)
			{
				$ctInputboxRecords_r[]=$row['id'];
				$ctInputboxRecords_v[]=$row['name'];
				$ctInputboxRecords_p[]=1;
			}
		}
		
		$htmlresult='
		<script>
			ctInputboxRecords_r["'.$control_name.'"] = '.json_encode($ctInputboxRecords_r).';
			ctInputboxRecords_v["'.$control_name.'"] = '.json_encode($ctInputboxRecords_v).';
			ctInputboxRecords_p["'.$control_name.'"] = '.json_encode($ctInputboxRecords_p).';
		</script>
		';
		
		$value='';
		$single_box='';
		
		$single_box.=JHTMLESUserGroups::getSingle($records,$control_name.'_selector',$valuearray);
		
		$htmlresult.='<div style="padding-bottom:20px;"><div style="width:90%;" id="'.$control_name.'_box"></div>'
		.'<div style="height:30px;">'
			.'<div id="'.$control_name.'_addButton" style="visibility:visible;"><img src="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="Add" title="Add" style="cursor: pointer;" '
			.'onClick="ctInputboxRecords_addItem(\''.$control_name.'\',\'_selector\')" /></div>'
			.'<div id="'.$control_name.'_addBox" style="visibility:hidden;">'
				.'<div style="float:left;">'.$single_box.'</div>'
				.'<img src="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/plus.png" alt="Add" title="Add" '
					.'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;" onClick="ctInputboxRecords_DoAddItem(\''.$control_name.'\',\'_selector\')" />'
				.'<img src="'.JURI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/cancel.png" alt="Cancel" title="Cancel" style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;" '
					.'onClick="ctInputboxRecords_cancel(\''.$control_name.'\')" />'
				
			.'</div>'
		.'</div>'
			.'<div style="display:none;"><select name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE ></select></div>'
		.'</div>
		
		<script>
			ctInputboxRecords_showMultibox("'.$control_name.'","");
		</script>
		';
		return $htmlresult;
	}
}
