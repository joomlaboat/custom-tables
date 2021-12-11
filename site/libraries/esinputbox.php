<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\DataTypes\Tree;
use CustomTables\Inputbox;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;

class ESInputBox
{
	var $width=0;
	var $requiredlabel='';
	var $ct;
	var $jinput;
	
	function __construct(&$ct)
	{
		$this->ct = $ct;
		$this->jinput = Factory::getApplication()->input;
		$this->requiredlabel='COM_CUSTOMTABLES_REQUIREDLABEL';
	}

	function renderFieldBox(&$esfield,&$row,$class_, string $attributes,$option_list)
	{
		$Inputbox = new Inputbox($this->ct, $esfield, $class_, $attributes, $option_list);
		
		$realFieldName=$esfield['realfieldname'];

		if($this->ct->Env->frmt == 'json')
		{
			//This is the field options for JSON output
			
			$shortFieldObject = Fields::shortFieldObject($esfield,(isset($row[$realFieldName]) ? $row[$realFieldName] : null),$option_list);
			
			if($esfield['type'] == 'sqljoin')
			{
				$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);
							
				if(isset($option_list[2]) and $option_list[2]!='')
					$typeparams[2]=$option_list[2];//Overwrites field type filter parameter.
				
				$typeparams[6] = 'json'; // to get the Object instead of the HTML element.
				
				$attributes_ = '';
				$value = '';
				$place_holder = '';
				$class = '';

				$list_of_values = JHTML::_('ESSQLJoin.render',
											  $typeparams,
											  $value,
											  false,
											  $this->ct->Languages->Postfix,
											  $prefix.$esfield['fieldname'],
											  $place_holder,
											  $class,
											  $attributes_);
			
				$shortFieldObject['value_options'] = $list_of_values;
			}
			
			return $shortFieldObject;
		}
		
		$value='';
		
		if($row==null)
			$row=array();

		if(count($row)==0 or (isset($row['listing_id']) and $row['listing_id'] == 0))
		{
			$value=$this->ct->Env->jinput->getString($realFieldName);
			if($value=='')
				$value=$Inputbox->getWhereParameter($realFieldName);

			if($value=='')
			{
				$value=$esfield['defaultvalue'];

				//Process default value, not processing PHP tag
				if($value!='')
				{
					tagProcessor_General::process($this->ct,$value,$row,'',1);
					tagProcessor_Item::process($this->ct,$row,$value,'','',0);
					tagProcessor_If::process($this->ct,$value,$row,'',0);
					tagProcessor_Page::process($this->ct,$value);
					tagProcessor_Value::processValues($this->ct,$row,$value,'[]');

					if($value!='')
					{
						LayoutProcessor::applyContentPlugins($htmlresult);

						if($esfield['type']=='alias')
						{
							$listing_id=isset($row['listing_id']) ? $row['listing_id'] : 0;
							$value=$this->ct->Table->prepare_alias_type_value($listing_id,$value,$esfield['realfieldname']);
						}
			        }
				}
			}
		}
		else
		{
			if($esfield['type']!='multilangstring' and $esfield['type']!='multilangtext' and $esfield['type']!='multilangarticle')
			{
				$value = isset($row[$realFieldName]) ? $row[$realFieldName] : null;
			}
		}
		
		return $Inputbox->render($value, $row);
	}
}
