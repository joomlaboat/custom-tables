<?php
/*-------------------------------------------------------------------------------------------------------/

	@version		1.7.3
	@build			3ed July, 2018
	@created		30th May, 2018
	@package		Custom Tables
	@subpackage		types.php
	@author			Ivan Komlev <http://joomlaboat.com>
	@copyright		Copyright (C) 2018-2019. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class CTTypes
{
    public static function getField($type, $attributes, $field_value = '')
    {
        jimport('joomla.form.helper');
        JFormHelper::loadFieldClass($type);

        try
        {
            $xml = new JXMLElement('<?xml version="1.0" encoding="utf-8"?><field />');
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
