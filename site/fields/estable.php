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

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

trait JFormFieldESTableCommon
{
	protected static function getOptionList(): array
	{
		$versionObject = new Version;
		$version = (int)$versionObject->getShortVersion();

		if ($version < 4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get('DatabaseDriver');

		$query = 'SELECT id,tablename FROM #__customtables_tables WHERE published=1 ORDER BY tablename';
		$db->setQuery($query);
		$tables = $db->loadObjectList();

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT')];

		if ($tables) {
			foreach ($tables as $table)
				$options[] = HTMLHelper::_('select.option', $table->tablename, $table->tablename);
		}
		return $options;
	}
}

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldESTable extends JFormFieldList
	{
		use JFormFieldESTableCommon;

		protected $type = 'estable';

		protected function getOptions()//$name, $value, &$node, $control_name)
		{
			return self::getOptionList();
		}
	}
} else {
	class JFormFieldESTable extends FormField
	{
		use JFormFieldESTableCommon;

		public $type = 'estable';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}