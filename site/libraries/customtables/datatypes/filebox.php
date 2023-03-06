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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class CT_FieldTypeTag_FileBox
{
    public static function process($FileBoxRows, &$field, $listing_id, array $option_list): string
    {
        $fileSRCListArray = array();

        foreach ($FileBoxRows as $fileRow) {
            $filename = $field->ct->Table->tableid . '_' . $field->fieldname . '_' . $fileRow->fileid . '.' . $fileRow->file_ext;
            $fileSRCListArray[] = CT_FieldTypeTag_file::process($filename, $field, $option_list, $listing_id);
        }

        $listFormat = '';
        if (isset($option_list[4]))
            $listFormat = $option_list[4];

        switch ($listFormat) {
            case 'ul':

                $fileTagListArray = array();

                foreach ($fileSRCListArray as $filename)
                    $fileTagListArray[] = '<li>' . $filename . '</li>';

                return '<ul>' . implode('', $fileTagListArray) . '</ul>';

            case ',':
                return implode(',', $fileSRCListArray);

            case ';':
                return implode(';', $fileSRCListArray);

            default:
                //INCLUDING OL
                $fileTagListArray = array();

                foreach ($fileSRCListArray as $filename)
                    $fileTagListArray[] = '<li>' . $filename . '</li>';

                return '<ol>' . implode('', $fileTagListArray) . '</ol>';
        }
    }

    public static function getFileBoxRows($tablename, $fieldname, $listing_id)
    {
        $db = Factory::getDBO();
        $fileBoxTableName = '#__customtables_filebox_' . $tablename . '_' . $fieldname;

        $query = 'SELECT fileid, file_ext FROM ' . $fileBoxTableName . ' WHERE listingid=' . (int)$listing_id . ' ORDER BY fileid';
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    public static function renderFileBoxIcon(CT $ct, string $listing_id, string $fileBoxName, string $title): string
    {
        $iconPath = Uri::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/';
        $rid = $ct->Table->tableid . 'x' . $listing_id;

        $fileManagerLink = 'index.php?option=com_customtables&amp;view=editfiles'
            . '&amp;establename=' . $ct->Table->tablename
            . '&amp;fileboxname=' . $fileBoxName
            . '&amp;listing_id=' . $listing_id
            . '&amp;returnto=' . $ct->Env->encoded_current_url;

        if ($ct->Env->jinput->get('tmpl', '', 'CMD') != '')
            $fileManagerLink .= '&tmpl=' . $ct->Env->jinput->get('tmpl', '', 'CMD');

        if ($ct->Params->ItemId > 0)
            $fileManagerLink .= '&amp;Itemid=' . $ct->Params->ItemId;

        if (!is_null($ct->Params->ModuleId))
            $fileManagerLink .= '&amp;ModuleId=' . $ct->Params->ModuleId;

        if ($ct->Env->toolbarIcons != '')
            $img = '<i class="ba-btn-transition ' . $ct->Env->toolbarIcons . ' fa-folder" data-icon="' . $ct->Env->toolbarIcons . ' fa-folder" title="' . $title . '"></i>';
        else
            $img = '<img src="' . $iconPath . 'filemanager.png" border="0" alt="' . $title . '" title="' . $title . '">';

        return '<div id="esFileBoxIcon' . $rid . '" class="toolbarIcons"><a href="' . $ct->Env->WebsiteRoot . $fileManagerLink . '">' . $img . '</a></div>';
    }
}
