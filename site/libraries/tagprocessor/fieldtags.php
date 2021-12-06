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

class tagProcessor_Field
{
    public static function process(&$ct,&$pagelayout,bool $add_label=false, string $fieldNamePrefix = 'comes_')
    {
        tagProcessor_Field::ProcessFieldTitles($ct,$pagelayout,$add_label,$fieldNamePrefix);
    }

    protected static function ProcessFieldTitles(&$ct,&$pagelayout, bool $add_label=false, $fieldNamePrefix = 'comes_')
	{
		//field title
        if($add_label)
        {
            foreach($ct->Table->fields as $esfield)
            {
				$field_label = Forms::renderFieldLabel($ct, $esfield, $fieldNamePrefix);
				
            	$pagelayout=str_replace('*'.$esfield['fieldname'].'*',$field_label,$pagelayout);
            }
        }
        else
        {
            foreach($ct->Table->fields as $esfield)
            {
                if(!array_key_exists('fieldtitle'.$ct->Languages->Postfix,$esfield))
				{
					JFactory::getApplication()->enqueueMessage(
						JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
                    $pagelayout=str_replace('*'.$esfield['fieldname'].'*','*fieldtitle'.$ct->Languages->Postfix.' - not found*',$pagelayout);
				}
                else
                    $pagelayout=str_replace('*'.$esfield['fieldname'].'*',$esfield['fieldtitle'.$ct->Languages->Postfix],$pagelayout);
            }
        }
		return $pagelayout;
	}
}
