<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Forms;
use CustomTables\Field;

class tagProcessor_Field
{
	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public static function process(CT &$ct, &$pageLayout, bool $add_label = false)
	{
		if (is_null($ct->Table->fields))
			return $pageLayout;

		//field title
		if ($add_label) {
			foreach ($ct->Table->fields as $fieldRow) {
				$forms = new Forms($ct);
				$field = new Field($ct, $fieldRow);
				$field_label = $forms->renderFieldLabel($field);

				$pageLayout = str_replace('*' . $field->fieldname . '*', $field_label, $pageLayout);
			}
		} else {
			foreach ($ct->Table->fields as $fieldRow) {
				if (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldRow)) {
					common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND'));
					$pageLayout = str_replace('*' . $fieldRow['fieldname'] . '*', '*fieldtitle' . $ct->Languages->Postfix . ' - not found*', $pageLayout);
				} else {
					$pageLayout = str_replace('*' . $fieldRow['fieldname'] . '*', $fieldRow['fieldtitle' . $ct->Languages->Postfix] ?? '', $pageLayout);
				}
			}
		}
		return $pageLayout;
	}
}
