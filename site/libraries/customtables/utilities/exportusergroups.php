<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\ImportTables;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

//------------- CURRENTLY UNUSED

class ImportExportUserGroups
{
    public static function processFile($filename, &$msg)
    {
        if (file_exists($filename)) {
            $data = file_get_contents($filename);

            if (!str_contains($data, '<usergroupsexport>')) {
                $msg = 'Uploaded file does not contain User Groups JSON data.';
                return false;
            }

            $jsondata = json_decode(str_replace('<usergroupsexport>', '', $data), true);

            return ImportExportUserGroups::processData($jsondata, $msg);
        } else {
            $msg = 'Uploaded file not found. Code EX02';
            return false;
        }
    }

    protected static function processData($jsondata, &$msg)
    {

        $usergroups = $jsondata['usergroups'];


        foreach ($usergroups as $usergroup) {
            $old = ImportTables::getRecordByField('#__usergroups', 'id', $usergroup['id'], false);
            if (is_array($old) and count($old) > 0)
                ImportTables::updateRecords('#__usergroups', $usergroup, $old, false, array(), true);
            else
                $usergroupid = ImportTables::insertRecords('#__usergroups', $usergroup, false, array(), true);
        }

        $viewlevels = $jsondata['viewlevels'];

        foreach ($viewlevels as $viewlevel) {
            $old = ImportTables::getRecordByField('#__viewlevels', 'id', $viewlevel['id'], false);
            if (is_array($old) and count($old) > 0)
                ImportTables::updateRecords('#__viewlevels', $viewlevel, $old, false, array(), true);
            else
                $usergroupidid = ImportTables::insertRecords('#__viewlevels', $viewlevel, false, array(), true);
        }

        return true;
    }

    public static function exportUserGroups()
    {
        //This function will export user groups

        $output = array();

        $db = Factory::getDBO();

        $query = 'SELECT * FROM #__usergroups';
        $db->setQuery($query);

        $usergroups = $db->loadAssocList();
        if (count($usergroups) == 0)
            return false;

        $query = 'SELECT * FROM #__viewlevels';
        $db->setQuery($query);

        $viewlevels = $db->loadAssocList();
        if (count($viewlevels) == 0)
            return false;


        $output = ['usergroups' => $usergroups, 'viewlevels' => $viewlevels];

        if (count($output) > 0) {
            $output_str = '<usergroupsexport>' . json_encode($output);

            $tmp_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
            $filename = 'usergroups';
            $filename_available = $filename;
            $a = '';
            $i = 0;
            do {
                if (!file_exists($tmp_path . $filename . $a . '.txt')) {
                    $filename_available = $filename . $a . '.txt';
                    break;
                }

                $i++;
                $a = $i . '';

            } while (1 == 1);

            $link = '/tmp/' . $filename_available;
            file_put_contents($tmp_path . $filename_available, $output_str);
            $output_str = null;
        }

        return $link;
    }

}
