<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\database;

//jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldCTUserGroup extends JFormFieldList
{
	public $type = 'CTUserGroup';

	protected function getOptions()
	{
		$query = 'SELECT id,title FROM #__usergroups ORDER BY title';
		$records = database::loadObjectList($query);
		$options = [];

		if ($records) {
			foreach ($records as $record)
				$options[] = '<option value="' . htmlspecialchars($record->id) . '">' . htmlspecialchars($record->title) . '</option>';
			//$options[] = HTMLHelper::_('select.option', $record->id, $record->title);
		}
		return $options;
	}
}
