<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

trait JFormFieldCTEditLayoutCommon
{
	protected static function getOptionList(): array
	{
		$versionObject = new Version;
		$version = (int)$versionObject->getShortVersion();

		if ($version < 4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get('DatabaseDriver');

		$query = 'SELECT id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename FROM #__customtables_layouts'
			. ' WHERE published=1 AND layouttype=2'
			. ' ORDER BY tablename,layoutname';

		$db->setQuery($query);
		$layouts = $db->loadObjectList();

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_DEFAULT')];

		if ($layouts) {
			foreach ($layouts as $layout)
				$options[] = HTMLHelper::_('select.option', $layout->tablename, $layout->tablename);
		}
		return $options;
	}
}

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTEditLayout extends JFormFieldList
	{
		use JFormFieldCTEditLayoutCommon;

		protected $type = 'eseditlayout';

		protected function getOptions(): array
		{
			return self::getOptionList();
		}
	}
} else {

	class JFormFieldCTEditLayout extends FormField
	{
		use JFormFieldCTEditLayoutCommon;

		protected $type = 'CTEditLayout';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}