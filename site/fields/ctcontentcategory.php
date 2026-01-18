<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
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

trait JFormFieldCTContentCategoryCommon
{
	protected static function getOptionList(int $default = 8, $multiple = false): array
	{
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();

		// Load all user groups with their parent IDs
		$categories = database::loadObjectList('#__categories', ['id', 'title', 'parent_id'], $whereClause, 'lft');

		$options = [];

		if ($default == 0 and !$multiple)
			$options [''] = ' - ' . common::translate('COM_CUSTOMTABLES_SELECT');

		if ($categories) {
			// Create a hierarchical structure
			$categories_ = self::buildGroupHierarchy($categories);
			// Convert the hierarchy to options
			self::addGroupOptions($categories_, $options);
		}
		return $options;
	}

	protected static function buildGroupHierarchy(array $categories, int $parentId = 0): array
	{
		$branch = [];

		foreach ($categories as $category) {
			if ($category->parent_id == $parentId) {
				$children = self::buildGroupHierarchy($categories, $category->id);
				if ($children) {
					$category->children = $children;
				}
				$branch[] = $category;
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

	class JFormFieldCTContentCategory extends JFormFieldList
	{
		use JFormFieldContentCategoryCommon;

		public $type = 'CTContentCategory';

		protected function getOptions(): array
		{
			$default = (int)$this->element['default'] ?? '';
			return self::getOptionList($default);
		}
	}
} else {

	class JFormFieldCTContentCategory extends FormField
	{
		use JFormFieldContentCategoryCommon;

		public $type = 'CTContentCategory';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$default = (int)$this->element['default'] ?? '';
			$data = $this->getLayoutData();

			$data['options'] = self::getOptionList($default);
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}
