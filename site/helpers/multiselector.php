<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'multiselector.php');

class JHTMLMultiSelector
{
    public static function render($prefix, $parentid, $parentname, $langpostfix, $establename, $esfieldname, $field_value, $attribute = '', $place_holder = '')
    {
        $ObjectName = $prefix . 'esmulti_' . $establename . '_' . $esfieldname;
        $ms = new ESMultiSelector;
        $result = '';
        $ItemList = "";
        $count = 0;
        $listhtml = $ms->getMultiSelector($parentid, $parentname, $langpostfix, $ObjectName, $ItemList, $count, $field_value, $place_holder);

        if ($count > 0)
            $result .= $listhtml;

        return $result;
    }
}
