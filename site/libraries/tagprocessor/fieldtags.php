<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use \CustomTables\Forms;
use \CustomTables\Field;
use Joomla\CMS\Factory;

class tagProcessor_Field
{
    public static function process(CT &$ct, &$pageLayout, bool $add_label = false)
    {
        if (is_null($ct->Table->fields))
            return $pageLayout;

        //field title
        if ($add_label) {
            foreach ($ct->Table->fields as $fieldrow) {
                $forms = new Forms($ct);
                $field = new Field($ct, $fieldrow);
                $field_label = $forms->renderFieldLabel($field);

                $pageLayout = str_replace('*' . $field->fieldname . '*', $field_label, $pageLayout);
            }
        } else {
            foreach ($ct->Table->fields as $fieldrow) {
                if (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldrow)) {
                    Factory::getApplication()->enqueueMessage(
                        JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND'), 'Error');

                    $pageLayout = str_replace('*' . $fieldrow['fieldname'] . '*', '*fieldtitle' . $ct->Languages->Postfix . ' - not found*', $pageLayout);
                } else
                    $pageLayout = str_replace('*' . $fieldrow['fieldname'] . '*', $fieldrow['fieldtitle' . $ct->Languages->Postfix], $pageLayout);
            }
        }
        return $pageLayout;
    }
}
