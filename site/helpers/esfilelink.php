<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESFileLink
{
    static public function render($control_name, $value, $style, $cssclass, $path = '/images', $attribute = '')
    {
        if ($path != '' and $path[0] != '/')
            $path = '/images/' . $path;


        $parts = explode('/', $path);

        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        if ($parts[0] == 'images' or (isset($parts[1]) and $parts[1] == 'images')) {
            $relativePath = JPATH_SITE . DIRECTORY_SEPARATOR;
            $real_path = $relativePath . $path; //use path relative to website root directory
        } else {
            $relativePath = '';
            $real_path = $path;//unrelative path
        }

        if (file_exists($real_path)) {
            $options[] = array('id' => '', 'name' => '- ' . JText::_('COM_CUSTOMTABLES_SELECT'));
            $files = scandir($real_path);
            foreach ($files as $f) {
                if (!is_dir($relativePath . $f) and strpos($f, '.') !== false)
                    $options[] = array('id' => $f, 'name' => $f);
            }
        } else
            $options[] = array('id' => '', 'name' => '- ' . JText::_('COM_CUSTOMTABLES_PATH') . ' (' . $path . ') ' . JText::_('COM_CUSTOMTABLES_NOTFOUND'));

        return JHTML::_('select.genericlist', $options, $control_name, $cssclass . ' style="' . $style . '" ' . $attribute . ' ', 'id', 'name', $value, $control_name);

    }


}
