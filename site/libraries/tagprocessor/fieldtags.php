<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
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
    public static function process(CT &$ct, &$pagelayout, bool $add_label = false)
    {
        if (is_null($ct->Table->fields))
            return $pagelayout;

        //field title
        if ($add_label) {
            foreach ($ct->Table->fields as $fieldrow) {
                $forms = new Forms($ct);
                $field = new Field($ct, $fieldrow);
                $field_label = $forms->renderFieldLabel($field);

                $pagelayout = str_replace('*' . $field->fieldname . '*', $field_label, $pagelayout);
            }
        } else {
            foreach ($ct->Table->fields as $fieldrow) {
                if (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldrow)) {
                    Factory::getApplication()->enqueueMessage(
                        JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND'), 'Error');

                    $pagelayout = str_replace('*' . $fieldrow['fieldname'] . '*', '*fieldtitle' . $ct->Languages->Postfix . ' - not found*', $pagelayout);
                } else
                    $pagelayout = str_replace('*' . $fieldrow['fieldname'] . '*', $fieldrow['fieldtitle' . $ct->Languages->Postfix], $pagelayout);
            }
        }
        return $pagelayout;
    }
}
