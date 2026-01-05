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

function createLayout_Email(array $fields): string
{
	$result = 'Dear ...<br/>A new records has been added to {{ table.title }} table.<br/><br/>Details below:<br/>';

	$fieldTypes_to_skip = ['log', 'filebox', 'dummy'];

	foreach ($fields as $field) {
		if (!in_array($field['type'], $fieldTypes_to_skip) and (int)$field['published'] === 1)
			$result .= '{{ ' . $field['fieldname'] . '.title }}: {{ ' . $field['fieldname'] . ' }}<br/>';
	}
	return $result;
}
