<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @version 1.6.1
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'catalog.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

class JHTMLESUserGroups
{


        static public function render($control_name, $value,$selector)
        {
				$htmlresult='';
				
				$valuearray=explode(',',$value);
				
				
				$db = JFactory::getDBO();
				
				$query = $db->getQuery(true);
				$query->select('#__usergroups.id AS id, #__usergroups.title AS name');
	 			$query->from('#__usergroups');
				
				
				$query->order('#__usergroups.title');
				
				$db->setQuery($query);
				if (!$db->query())    die( $db->stderr());
				
				$SearchResult=$db->loadObjectList();
				
		
				$valuearray=explode(',',$value);
				
				
				
						switch($selector)
						{
								
								case 'single' :
										$htmlresult=JHTMLESUserGroups::getSingle($SearchResult,$control_name,$valuearray);
										
										break;
								
								case 'multi' :
										
										
										$htmlresult.='<SELECT name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE >';
												
										foreach($SearchResult as $row)
										{
												$htmlresult.='<option value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
														.'>';
												
												$htmlresult.=$row->name.'</option>';
										}
										
										$htmlresult.='</SELECT>';
										break;
							
								case 'radio' :

										$htmlresult.='<table style="border:none;" id="usergroups_table_'.$control_name.'">';
										$i=0;
										foreach($SearchResult as $row)
										{
										
												$htmlresult.='<tr><td valign="middle">'
														.'<input type="radio" '
														.'name="'.$control_name.'" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' checked="checked" ' : '')
														.' /></td>';
														
												$htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row->name.'</label>'
														.'</td></tr>';
												$i++;
										}
										$htmlresult.='</table>';
										break;
								
								case 'checkbox' :
										
										$htmlresult.='<table style="border:none;">';
										$i=0;
										foreach($SearchResult as $row)
										{
												$htmlresult.='<tr><td valign="middle">'
														.'<input type="checkbox" '
														.'name="'.$control_name.'[]" '
														.'id="'.$control_name.'_'.$i.'" '
														.'value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0 ) ? ' checked="checked" ' : '')
														
														.' /></td>';
														
												$htmlresult.='<td valign="middle">'
														.'<label for="'.$control_name.'_'.$i.'">'.$row->name.'</label>'
														.'</td></tr>';
												$i++;
										}
										$htmlresult.='</table>';
										break;
								
								case 'multibox' :
									
										$htmlresult.=JHTMLESUserGroups::getMultibox($SearchResult,$valuearray,$selector,$control_name);
										
										break;
								
								default:
										return '<p>Incorrect selector</p>';
								break;
						}
							
				
				return $htmlresult;
		
				
        }
	
	static protected function getSingle(&$SearchResult,$control_name,$valuearray)
	{
		$htmlresult='<SELECT name="'.$control_name.'[]" id="'.$control_name.'">';
												
										foreach($SearchResult as $row)
										{
												$htmlresult.='<option value="'.$row->id.'" '
														.((in_array($row->id,$valuearray) and count($valuearray)>0) ? ' SELECTED ' : '')
														.'>';
												
												$htmlresult.=$row->name.'</option>';
										}
										
										$htmlresult.='</SELECT>';
										
		return $htmlresult;
	}
	
	
	static protected function getMultibox(&$SearchResult,&$valuearray,$selector,$control_name)
	{
		
		
		
		$deleteimage='components/com_customtables/images/cancel_small.png';
		$htmlresult='
		<script>
			var '.$control_name.'_r=new Array();
			var '.$control_name.'_v=new Array();
			';
			$i=0;
			foreach($SearchResult as $row)
			{
				if(in_array($row->id,$valuearray) and count($valuearray)>0)
				{
					$htmlresult.='
					'.$control_name.'_r['.$i.']="'.$row->id.'";
					'.$control_name.'_v['.$i.']="'.$row->name.'";
';
					$i++;
				}
			}
			
			$htmlresult.='
			function '.$control_name.'removeOptions(selectobj)
			{
				for(var i=selectobj.options.length-1;i>=0;i--)
				{
					selectobj.remove(i);
				}
			}
			';
			
			$htmlresult.='
		
			function '.$control_name.'addItem(index)
			{
				var o = document.getElementById("'.$control_name.'_selector");
				o.selectedIndex=0;
				';
				
				
				$htmlresult.='
				

				var btn = document.getElementById("'.$control_name.'_addButton");
				btn.style.display="none";
				
				var box = document.getElementById("'.$control_name.'_addBox");
				box.style.display="block";
				
			}
			
			';

			
			$htmlresult.='
			
			function '.$control_name.'DoAddItem()
			{
				var o = document.getElementById("'.$control_name.'_selector");
				if(o.selectedIndex==-1)
						return;
						
				var r=o.options[o.selectedIndex].value;
				var t=o.options[o.selectedIndex].text;
				var i='.$control_name.'_r.length;
				
				for(var x=0;x<'.$control_name.'_r.length;x++)
				{
					if('.$control_name.'_r[x]==r)
					{
						alert("Item already exists");
						return false;
					}
				}
				
				'.$control_name.'_r[i]=r;
				'.$control_name.'_v[i]=t;
				
				//'.$control_name.'cancel();
				
				
				o.remove(o.selectedIndex);
				
				
				'.$control_name.'showMultibox();
				
				//'.$control_name.'DeleteExistingItems();
			}
			
			function '.$control_name.'cancel()
			{
				
			
				var btn = document.getElementById("'.$control_name.'_addButton");
				btn.style.display="block";
				
				var box = document.getElementById("'.$control_name.'_addBox");
				box.style.display="none";
			}
			
			function '.$control_name.'deleteItem(index)
			{
				//alert(index);
				'.$control_name.'_r.splice(index,1);
				'.$control_name.'_v.splice(index,1);
				
				'.$control_name.'showMultibox();
			}
			
			function '.$control_name.'showMultibox()
			{
				var l = document.getElementById("'.$control_name.'");
				'.$control_name.'removeOptions(l);	
			
				var v=\'<table style="width:100%;"><tbody>\';
				for(var i=0;i<'.$control_name.'_r.length;i++)
				{
					v+=\'<tr><td style="border-bottom:1px dotted grey;">\';
					v+='.$control_name.'_v[i];
					v+=\'<td style="border-bottom:1px dotted grey;width:16px;"><img src="'.$deleteimage.'" alt="Delete" title="Delete" style="cursor: pointer;" onClick="'.$control_name.'deleteItem(\'+i+\')" /></td>\';
					v+=\'</tr>\';
					
					
					var opt = document.createElement("option");
					opt.value = '.$control_name.'_r[i];
					opt.innerHTML = '.$control_name.'_v[i];
					opt.setAttribute("selected","selected");
					l.appendChild(opt);
				
				}
				v+=\'</tbody></table>\';
				
				var o = document.getElementById("'.$control_name.'_box");
				o.innerHTML = v;

			}
			
			
		</script>
		';
		
		$value='';
		$single_box='';
		
		
		$single_box.=JHTMLESUserGroups::getSingle($SearchResult,$control_name.'_selector',$valuearray);
		
		$htmlresult.='<div style="padding-bottom:20px;"><div style="width:90%;" id="'.$control_name.'_box"></div>'
		.'<div style="height:30px;">'
			.'<div id="'.$control_name.'_addButton" style="display:block;"><img src="'.JURI::root(true).'/components/com_customtables/images/new.png" alt="Add" title="Add" style="cursor: pointer;" onClick="'.$control_name.'addItem()" /></div>'
			.'<div id="'.$control_name.'_addBox" style="display:none;">'
				.'<div style="float:left;">'.$single_box.'</div>'
				.'<img src="'.JURI::root(true).'/components/com_customtables/images/plus_13.png" alt="Add" title="Add" style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;" onClick="'.$control_name.'DoAddItem()" />'
				.'<img src="'.JURI::root(true).'/components/com_customtables/images/cancel_small.png" alt="Cancel" title="Cancel" style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;" onClick="'.$control_name.'cancel()" />'
				
			.'</div>'
		.'</div>'
			.'<div style="display:none;"><select name="'.$control_name.'[]" id="'.$control_name.'" MULTIPLE ></select></div>'
		.'</div>
		
		<script>
			'.$control_name.'showMultibox();
		</script>
		';
		
		return $htmlresult;
		
	}
		
	
}

?>
