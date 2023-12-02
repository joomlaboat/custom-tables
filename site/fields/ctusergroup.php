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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;

trait JFormFieldCTUserGroupCommon
{
	protected static function getOptionList(): array
	{
		$versionObject = new Version;
		$version = (int)$versionObject->getShortVersion();

		if ($version < 4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get('DatabaseDriver');

		$query = 'SELECT id,title FROM #__usergroups ORDER BY title';

		$db->setQuery($query);
		$userGroups = $db->loadObjectList();

		$options = [];

		if ($userGroups) {
			foreach ($userGroups as $userGroup)
				$options[] = HTMLHelper::_('select.option', $userGroup->id, $userGroup->title);
		}
		return $options;
	}
}

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if ($version < 4) {

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
