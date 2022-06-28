<?php

namespace CustomTables;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class common
{
    static function translate($text, $value = null)
    {
        if (defined('WPINC')) {
            return $text;
        }

        if (is_null($value))
            $new_text = Text::_($text);
        else
            $new_text = Text::sprintf($text, $value);

        if ($new_text == $text) {
            $parts = explode('_', $text);
            if (count($parts) > 1) {
                $type = $parts[0];
                if ($type == 'PLG' and count($parts) > 2) {
                    $extension = strtolower($parts[0] . '_' . $parts[1] . '_' . $parts[2]);
                } else
                    $extension = strtolower($parts[0] . '_' . $parts[1]);

                $lang = Factory::getLanguage();
                $lang->load($extension, JPATH_BASE);

                if (is_null($value))
                    return Text::_($text);
                else
                    return Text::sprintf($text, $value);
            } else
                return $text;
        } else
            return $new_text;
    }

    function renderTabs()
    {

    }
}