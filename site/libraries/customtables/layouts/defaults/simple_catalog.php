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
use CustomTables\Fields;

defined('_JEXEC') or die();

function createLayout_SimpleCatalog(array $fields, bool $addToolbar = true): string
{
	$result = '<style>' . PHP_EOL . '.datagrid th{text-align:left;}' . PHP_EOL . '.datagrid td{text-align:left;}' . PHP_EOL . '</style>' . PHP_EOL;
	$result .= '<div style="float:right;">{{ html.recordcount }}</div>' . PHP_EOL;

	if ($addToolbar) {
		$result .= '<div style="float:left;">{{ html.add }}</div>' . PHP_EOL;

		if (defined('_JEXEC'))
			$result .= '<div style="text-align:center;">{{ html.print }}</div>' . PHP_EOL;
	}

	$result .= '<div class="datagrid">' . PHP_EOL;

	if ($addToolbar)
		$result .= '<div>{{ html.batch(\'publish\',\'unpublish\',\'refresh\',\'delete\') }}</div>';

	$result .= PHP_EOL;

	$fieldTypes_to_skip = ['log', 'filebox', 'dummy'];
	$fieldTypesWithSearch = ['email', 'string', 'multilangstring', 'text', 'multilangtext', 'sqljoin', 'records', 'user', 'userid', 'int', 'checkbox', 'radio'];
	$fieldTypes_allowed_to_orderBy = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime',
		'date', 'multilangstring', 'userid', 'user', 'virtual'];

	$result .= PHP_EOL . '<table>' . PHP_EOL;

	$result .= renderTableHead($fields, $addToolbar, $fieldTypes_to_skip, $fieldTypesWithSearch, $fieldTypes_allowed_to_orderBy);

	$result .= PHP_EOL . '<tbody>';
	$result .= PHP_EOL . '{% block record %}';
	$result .= PHP_EOL . '<tr>' . PHP_EOL;

//Look for ordering field type
	if ($addToolbar) {
		foreach ($fields as $field) {
			if ((int)$field['published'] === 1 and $field['type'] == 'ordering') {
				$result .= '<td style="text-align:center;">{{ ' . $field['fieldname'] . ' }}</td>' . PHP_EOL;
			}
		}
	}

	if ($addToolbar)
		$result .= '<td style="text-align:center;">{{ html.toolbar("checkbox") }}</td>' . PHP_EOL;

	$result .= '<td style="text-align:center;"><a href="{{ record.link(true) }}">{{ record.id }}</a></td>' . PHP_EOL;

	$imageGalleryFound = false;
	$fileBoxFound = false;

	foreach ($fields as $field) {
		if ((int)$field['published'] === 1) {
			if ($field['type'] == 'imagegallery')
				$imageGalleryFound = true;

			if ($field['type'] == 'filebox') {
				$fileBoxFound = true;
			} elseif ($field['type'] != 'ordering' && !in_array($field['type'], $fieldTypes_to_skip)) {

				if ($field['type'] == 'url')
					$fieldValue = '<a href="{{ ' . $field['fieldname'] . ' }}" target="_blank">{{ ' . $field['fieldname'] . ' }}</a>';
				else
					$fieldValue = '{{ ' . $field['fieldname'] . ' }}';

				$result .= '<td>' . $fieldValue . '</td>' . PHP_EOL;
			}
		}
	}

	if ($addToolbar) {

		$toolbarButtons = ['edit', 'publish', 'refresh', 'delete'];

		if ($imageGalleryFound)
			$toolbarButtons [] = 'gallery';

		if ($fileBoxFound)
			$toolbarButtons [] = 'filebox';

		$result .= '<td>{{ html.toolbar("' . implode('","', $toolbarButtons) . '") }}</td>' . PHP_EOL;
	}

	$result .= '</tr>';

	$result .= PHP_EOL . '{% endblock %}';
	$result .= PHP_EOL . '</tbody>';
	$result .= PHP_EOL . '</table>' . PHP_EOL;

	$result .= PHP_EOL;
	$result .= '</div>' . PHP_EOL;

	if (defined('_JEXEC')) {
		if ($addToolbar)
			$result .= '<br/><div style="text-align:center;">{{ html.pagination }}</div>' . PHP_EOL;
	}

	return $result;
}

function renderTableHead(array $fields, bool $addToolbar, array $fieldtypes_to_skip, array $fieldTypesWithSearch, array $fieldtypes_allowed_to_orderby): string
{
	$result = '<thead><tr>' . PHP_EOL;

	//Look for ordering field type
	if ($addToolbar) {
		foreach ($fields as $field) {
			if ((int)$field['published'] === 1 and $field['type'] == 'ordering')
				$result .= '<th class="short">{{ ' . $field['fieldname'] . '.label(true) }}</th>' . PHP_EOL;
		}
	}

	if ($addToolbar)
		$result .= '<th class="short">{{ html.batch("checkbox") }}</th>' . PHP_EOL;

	if ($addToolbar)
		$result .= '<th class="short">{{ record.label(true) }}</th>' . PHP_EOL;
	else
		$result .= '<th class="short">{{ record.label(false) }}</th>' . PHP_EOL;

	foreach ($fields as $field) {
		if ((int)$field['published'] === 1) {
			$result .= renderTableColumnHeader($field, $addToolbar, $fieldtypes_to_skip, $fieldTypesWithSearch, $fieldtypes_allowed_to_orderby);
		}
	}

	if ($addToolbar)
		$result .= '<th>Action<br/>{{ html.searchbutton }}</th>' . PHP_EOL;

	$result .= '</tr></thead>' . PHP_EOL . PHP_EOL;

	return $result;
}

function renderTableColumnHeader(array $field, bool $addToolbar, array $fieldtypes_to_skip, array $fieldtypesWithSearch, array $fieldtypes_allowed_to_orderby): string
{
	$result = '';

	if ($field['type'] != 'ordering' && !in_array($field['type'], $fieldtypes_to_skip)) {

		$result .= '<th>';

		if (in_array($field['type'], $fieldtypes_allowed_to_orderby)) {
			if (Fields::isVirtualField($field))
				$result .= '{{ ' . $field['fieldname'] . '.title }}';
			else
				$result .= '{{ ' . $field['fieldname'] . '.label(true) }}';
		} else
			$result .= '{{ ' . $field['fieldname'] . '.title }}';

		if ($addToolbar and in_array($field['type'], $fieldtypesWithSearch)) {

			if ($field['type'] == 'checkbox' || $field['type'] == 'sqljoin' || $field['type'] == 'records')
				$result .= '<br/>{{ html.search("' . $field['fieldname'] . '","","reload") }}';
			else
				$result .= '<br/>{{ html.search("' . $field['fieldname'] . '") }}';
		}

		$result .= '</th>' . PHP_EOL;
	}

	return $result;
}