<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class FindSimilarImage
{
    static public function find($uploadedfile, $level_identity, $realtablename, $realfieldname, $ImageFolder, $additional_filter = '')
    {
        if ($level_identity < 0)
            $level_identity = 0;

        $ci = new compareImages;

        $db = Factory::getDBO();

        $query = 'SELECT ' . $realfieldname . ' AS photoid FROM ' . $realtablename . ' WHERE ' . $realfieldname . '>0' . ($additional_filter != '' ? ' AND ' . $additional_filter : '');

        $db->setQuery($query);
        $photorows = $db->loadObjectList();

        foreach ($photorows as $photorow) {
            $photoid = $photorow->photoid;

            if ($photoid != 0) {
                //foreach($ext_list as $ext)
                //{
                $image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $photoid . '.jpg';///.$ext;
                if ($image_file != $uploadedfile) {
                    if (file_exists($image_file)) {
                        $index = $ci->compare($uploadedfile, $image_file);
                        if ($index <= $level_identity)
                            return $photoid;
                    }
                }
                //}//for each
            }//if
        }//foreach($photorows as $photorow)
    }//function
}//class
