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

function createLayout_CSV(array $fields): string
{
	$result = '';

	$fieldTypes_to_skip = ['log', 'filebox', 'dummy', 'ordering'];
	$fieldTypes_to_pureValue = ['image', 'filebox', 'file'];

	foreach ($fields as $field) {
		if (!in_array($field['type'], $fieldTypes_to_skip) and (int)$field['published'] === 1) {
			if ($result !== '')
				$result .= ',';

			$result .= '"{{ ' . $field['fieldname'] . '.title }}"';
		}
	}

	$result .= PHP_EOL . "{% block record %}";

	$firstField = true;
	foreach ($fields as $field) {
		if (!in_array($field['type'], $fieldTypes_to_skip) and (int)$field['published'] === 1) {

			if (!$firstField)
				$result .= ',';

			if (!in_array($field['type'], $fieldTypes_to_pureValue))
				$result .= '"{{ ' . $field['fieldname'] . ' }}"';
			else
				$result .= '"{{ ' . $field['fieldname'] . '.value }}"';

			$firstField = false;
		}
	}
	return $result . PHP_EOL . "{% endblock %}";
}