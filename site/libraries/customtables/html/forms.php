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
	public static function renderFieldLabel(&$ct, &$esfield, $fieldNamePrefix = 'comes_')
	{
		if($esfield['type']=='dummy')
        {
			$field_label=$esfield['fieldtitle'.$ct->Languages->Postfix];
        }
        else
        {
			if(!array_key_exists('fieldtitle'.$ct->Languages->Postfix,$esfield))
			{
				Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
				$title = '*fieldtitle'.$ct->Languages->Postfix.' - not found*';
			}
			else	
				$title = $esfield['fieldtitle'.$ct->Languages->Postfix];
			
			if(!array_key_exists('description'.$ct->Languages->Postfix,$esfield))
			{
				Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
				$description = '*description'.$ct->Languages->Postfix.' - not found*';
			}
			else	
				$description = str_replace('"','',$esfield['description'.$ct->Languages->Postfix]);
			
			$isrequired=(bool)$esfield['isrequired'];

			$field_label='<label id="'.$fieldNamePrefix.$esfield['fieldname'].'-lbl" for="'.$fieldNamePrefix.$esfield['fieldname'].'" ';
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