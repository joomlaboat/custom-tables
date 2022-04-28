<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use \CustomTables\Forms;
use \CustomTables\Field;

class tagProcessor_Field
{
    public static function process(&$ct,&$pagelayout,bool $add_label=false)
    {
		//field title
        if($add_label)
        {
            foreach($ct->Table->fields as $fieldrow)
            {
				$forms = new Forms($ct);
				$field = new Field($ct,$fieldrow,$ct->Table->record);
				$field_label = $forms->renderFieldLabel($field);
				
            	$pagelayout=str_replace('*'.$field->fieldname.'*',$field_label,$pagelayout);
            }
        }
        else
        {
            foreach($ct->Table->fields as $fieldrow)
            {
                if(!array_key_exists('fieldtitle'.$ct->Languages->Postfix,$fieldrow))
				{
					JFactory::getApplication()->enqueueMessage(
						JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
                    $pagelayout=str_replace('*'.$fieldrow['fieldname'].'*','*fieldtitle'.$ct->Languages->Postfix.' - not found*',$pagelayout);
				}
                else
                    $pagelayout=str_replace('*'.$fieldrow['fieldname'].'*',$fieldrow['fieldtitle'.$ct->Languages->Postfix],$pagelayout);
            }
        }
		return $pagelayout;
	}
}
