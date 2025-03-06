<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

function createLayout_Edit(array $fields, bool $addToolbar = true): string
{
	$result = '<legend>{{ table.title }}</legend>{{ html.goback() }}<div class="form-horizontal">';

	$fieldTypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy', 'virtual'];

	foreach ($fields as $field) {
		if ((int)$field['published'] === 1) {
			if (!in_array($field['type'], $fieldTypes_to_skip)) {
				$result .= '<div class="control-group">';
				$result .= '<div class="control-label">{{ ' . $field['fieldname'] . '.label }}</div><div class="controls">{{ ' . $field['fieldname'] . '.edit }}</div>';
				$result .= '</div>';
			}
		}
	}

	$result .= '</div>';

	foreach ($fields as $field) {
		if ((int)$field['published'] === 1) {
			if ($field['type'] === "dummy") {
				$result .= '<p><span style="color: #FB1E3D; ">*</span>' . ' {{ ' . $field['fieldname'] . '.title }}</p>';
				break;
			}
		}
	}

	if ($addToolbar)
		$result .= '<div style="text-align:center;">{{ html.button("save") }} {{ html.button("saveandclose") }} {{ html.button("saveascopy") }} {{ html.button("cancel") }}</div>';
	return $result;
}
