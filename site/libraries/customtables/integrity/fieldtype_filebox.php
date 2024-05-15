<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage integrity/fields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables\Integrity;

defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\TableHelper;
use CustomTables\Fields;
use CustomTables\IntegrityChecks;
use Joomla\CMS\Language\Text;

class IntegrityFieldType_FileBox extends IntegrityChecks
{
    public static function checkFileBox(CT &$ct, $fieldname)
    {
        $filebox_table_name = '#__customtables_filebox_' . $ct->Table->tablename . '_' . $fieldname;

        if (!TableHelper::checkIfTableExists($filebox_table_name)) {
            Fields::CreateFileBoxTable($ct->Table->tablename, $fieldname);
            common::enqueueMessage(sprintf("File Box Table '%s' created.", $filebox_table_name), 'notice');
        }

        $g_ExistingFields = database::getExistingFields($filebox_table_name, false);

        $moreThanOneLanguage = false;
        foreach ($ct->Languages->LanguageList as $lang) {
            $g_fieldname = 'title';
            if ($moreThanOneLanguage)
                $g_fieldname .= '_' . $lang->sef;

            $g_found = false;

            foreach ($g_ExistingFields as $g_existing_field) {
                $g_exst_field = $g_existing_field['column_name'];
                if ($g_exst_field == $g_fieldname) {
                    $g_found = true;
                    break;
                }
            }

            if (!$g_found) {
                Fields::AddMySQLFieldNotExist($filebox_table_name, $g_fieldname, 'varchar(100) null', '');
                common::enqueueMessage(sprintf("File Box Field '%s' added.", $g_fieldname));
            }
            $moreThanOneLanguage = true;
        }
    }
}