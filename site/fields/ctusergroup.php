<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;

$version = new Version();
if (!defined('CUSTOMTABLES_JOOMLA_MIN_4')) {
	if (version_compare($version->getShortVersion(), '4.0', '>='))
		define('CUSTOMTABLES_JOOMLA_MIN_4', true);
	else
		define('CUSTOMTABLES_JOOMLA_MIN_4', false);
}

trait JFormFieldCTUserGroupCommon
{
	protected static function getOptionList(): array
	{
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();

		// Load all user groups with their parent IDs
		$userGroups = database::loadObjectList('#__usergroups', ['id', 'title', 'parent_id'], $whereClause, 'lft');
		$options = ['' => ' - ' . common::translate('COM_CUSTOMTABLES_SELECT')];

		if ($userGroups) {
			// Create a hierarchical structure
			$groups = self::buildGroupHierarchy($userGroups);
			// Convert the hierarchy to options
			self::addGroupOptions($groups, $options);
		}
		return $options;
	}


	protected static function buildGroupHierarchy(array $userGroups, int $parentId = 0): array
	{
		$branch = [];

		foreach ($userGroups as $group) {
			if ($group->parent_id == $parentId) {
				$children = self::buildGroupHierarchy($userGroups, $group->id);
				if ($children) {
					$group->children = $children;
				}
				$branch[] = $group;
			}
		}

		return $branch;
	}

	protected static function addGroupOptions(array $groups, array &$options, int $level = 0): void
	{
		foreach ($groups as $group) {
			$prefix = str_repeat('—', $level);
			$options[] = HTMLHelper::_('select.option', $group->id, ($level > 0 ? '└' : '') . $prefix . ' ' . $group->title);

			if (!empty($group->children)) {
				self::addGroupOptions($group->children, $options, $level + 1);
			}
		}
	}
}

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTUserGroup extends JFormFieldList
	{
		use JFormFieldCTUserGroupCommon;

		public $type = 'CTUserGroup';

		protected function getOptions(): array
		{
			return self::getOptionList();
		}
	}
} else {

	class JFormFieldCTUserGroup extends FormField
	{
		use JFormFieldCTUserGroupCommon;

		public $type = 'CTUserGroup';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}
