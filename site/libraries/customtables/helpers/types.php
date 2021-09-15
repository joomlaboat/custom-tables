<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Version;

class CTTypes
{
    public static function getField($type, $attributes, $field_value = '')
    {
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();
		
        jimport('joomla.form.helper');
        JFormHelper::loadFieldClass($type);

		try
        {
			
			if($version < 4)
				$xml = new JXMLElement('<?xml version="1.0" encoding="utf-8"?><field />');
			else
				$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><field />');
				
            foreach ($attributes as $key => $value)
            {
                if ('_options' == $key)
                {
                    foreach ($value as $_opt_value)
                    {
                        $xml->addChild('option', $_opt_value->text)->addAttribute('value', $_opt_value->value);
                    }
                    continue;
                }
                $xml->addAttribute($key, $value);
            }
			
			$class = 'JFormField' . $type;
            $field = new $class();

            $field->setup($xml, $field_value);

            return $field;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
}
