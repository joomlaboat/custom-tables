<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

function createLayout_Edit_WP(array $fields, bool $addToolbar = true, bool $addLegend = true, bool $addGoBack = true, string $fieldInputPrefix = ''): string
{
	$result = '';

	if ($addLegend)
		$result .= '<legend>{{ table.title }}</legend>';

	if ($addGoBack)
		$result .= '{{ html.goback() }}';

	$result .= '<table class="form-table" role="presentation">';

	//, 'imagegallery'
	$fieldTypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy', 'virtual'];

	foreach ($fields as $field) {
		if (!in_array($field['type'], $fieldTypes_to_skip) and (int)$field['published'] === 1) {

			$attribute = 'for="' . $fieldInputPrefix . $field['fieldname'] . '"';
			$label = '<th scope="row">
                            <label ' . $attribute . '>'
				. '{{ ' . $field['fieldname'] . '.title }}'
				. ((int)$field['isrequired'] == 1 ? '<span class="description">(' . __('required', 'customtables') . ')</span>' : '')//WP version
				. '</label>
                        </th>';

			$input = '<td>
                            {{ ' . $field['fieldname'] . '.edit }}
                        </td>';

			$result .= '<tr class="form-field ' . ((int)$field['isrequired'] == 1 ? 'form-required' : 'form') . '">'
				. $label
				. $input
				. '</tr>';
		}
	}

	$result .= '</table>';

	if ($addToolbar)
		$result .= '<div style="text-align:center;">{{ html.button("save") }} {{ html.button("saveandclose") }} {{ html.button("saveascopy") }} {{ html.button("cancel") }}</div>
';
	return $result;
}
