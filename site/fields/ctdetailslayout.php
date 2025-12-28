<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

$version = new Version();
if (!defined('CUSTOMTABLES_JOOMLA_MIN_4')) {
	if (version_compare($version->getShortVersion(), '4.0', '>='))
		define('CUSTOMTABLES_JOOMLA_MIN_4', true);
	else
		define('CUSTOMTABLES_JOOMLA_MIN_4', false);
}

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG'))
	define('CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG', 1);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM'))
	define('CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM', 2);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_DETAILS'))
	define('CUSTOMTABLES_LAYOUT_TYPE_DETAILS', 4);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE'))
	define('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE', 5);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM'))
	define('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM', 6);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_EMAIL'))
	define('CUSTOMTABLES_LAYOUT_TYPE_EMAIL', 7);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_XML'))
	define('CUSTOMTABLES_LAYOUT_TYPE_XML', 8);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CSV'))
	define('CUSTOMTABLES_LAYOUT_TYPE_CSV', 9);

if (!defined('CUSTOMTABLES_LAYOUT_TYPE_JSON'))
	define('CUSTOMTABLES_LAYOUT_TYPE_JSON', 10);


trait JFormFieldCTDetailsLayoutCommon
{
	protected static function getOptionList(): array
	{
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addOrCondition('layouttype', CUSTOMTABLES_LAYOUT_TYPE_DETAILS);
		$whereClause->addOrCondition('layouttype', CUSTOMTABLES_LAYOUT_TYPE_XML);
		$whereClause->addOrCondition('layouttype', CUSTOMTABLES_LAYOUT_TYPE_CSV);
		$whereClause->addOrCondition('layouttype', CUSTOMTABLES_LAYOUT_TYPE_JSON);

		$layouts = database::loadObjectList('#__customtables_layouts AS a',
			['id', 'layoutname', 'TABLE_NAME'], $whereClause, 'TABLE_NAME, layoutname');

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_DEFAULT')];

		if ($layouts) {
			foreach ($layouts as $layout)
				$options[] = HTMLHelper::_('select.option', $layout->layoutname, $layout->TABLE_NAME . ' - ' . $layout->layoutname);
		}
		return $options;
	}
}

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTDetailsLayoutLayout extends JFormFieldList
	{
		use JFormFieldCTDetailsLayoutCommon;

		protected $type = 'CTDetailsLayout';

		protected function getOptions(): array
		{
			return self::getOptionList();
		}
	}
} else {

	class JFormFieldCTDetailsLayout extends FormField
	{
		use JFormFieldCTDetailsLayoutCommon;

		protected $type = 'esdetailslayout';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}