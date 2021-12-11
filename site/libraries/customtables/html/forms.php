<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

//use CustomTables\CTUser;
use \Joomla\CMS\Factory;
//use \JoomlaBasicMisc;
//use \Joomla\CMS\Uri\Uri;



class Forms
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function renderFieldLabel(&$esfield)
	{
		if($esfield['type']=='dummy')
        {
			$field_label=$esfield['fieldtitle'.$this->ct->Languages->Postfix];
        }
        else
        {
			if(!array_key_exists('fieldtitle'.$this->ct->Languages->Postfix,$esfield))
			{
				Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
				$title = '*fieldtitle'.$this->ct->Languages->Postfix.' - not found*';
			}
			else	
				$title = $esfield['fieldtitle'.$this->ct->Languages->Postfix];
			
			if(!array_key_exists('description'.$this->ct->Languages->Postfix,$esfield))
			{
				Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
				$description = '*description'.$this->ct->Languages->Postfix.' - not found*';
			}
			else	
				$description = str_replace('"','',$esfield['description'.$this->ct->Languages->Postfix]);
			
			$isrequired=(bool)$esfield['isrequired'];

			$field_label='<label id="'.$this->ct->Env->field_input_prefix.$esfield['fieldname'].'-lbl" for="'.$this->ct->Env->field_input_prefix.$esfield['fieldname'].'" ';
			$class=($description!='' ? 'hasPopover' : '').''.($isrequired ? ' required' : '');

			if($class!='')
			    $field_label.=' class="'.$class.'"';

			$field_label.=' title="'.$title.'"';

			if($description)
			    $field_label.=' data-content="'.$description.'"';

			$field_label.=' data-original-title="'.$title.'">'.$title;

			if($isrequired)
			    $field_label.='<span class="star">&#160;*</span>';

			$field_label.='</label>';
		}
		
		return $field_label;
	}
	
	
}