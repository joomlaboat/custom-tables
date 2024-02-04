<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

trait JFormFieldCTCatalogLayoutCommon
{
	protected static function getOptionList(): array
	{
		//$query = 'SELECT id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename'
		//. ' FROM #__customtables_layouts WHERE published=1 AND (layouttype=1 OR layouttype=5 OR layouttype=8 OR layouttype=9 OR layouttype=10)'
		//. ' ORDER BY tablename,layoutname';
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		$whereClause->addOrCondition('layouttype', 1);
		$whereClause->addOrCondition('layouttype', 5);
		$whereClause->addOrCondition('layouttype', 8);
		$whereClause->addOrCondition('layouttype', 9);
		$whereClause->addOrCondition('layouttype', 10);

		$layouts = database::loadObjectList('#__customtables_layouts',
			['id', 'layoutname', '(SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename'], $whereClause, 'tablename,layoutname');

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_DEFAULT')];

		if ($layouts) {
			foreach ($layouts as $layout)
				$options[] = HTMLHelper::_('select.option', $layout->layoutname, $layout->tablename . ' - ' . $layout->layoutname);
		}
		return $options;
	}
}

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTCatalogLayout extends JFormFieldList
	{
		use JFormFieldCTCatalogLayoutCommon;

		protected $type = 'CTCatalogLayout';

		protected function getOptions(): array
		{
			return self::getOptionList();
		}
	}
} else {

	class JFormFieldCTCatalogLayout extends FormField
	{
		use JFormFieldCTCatalogLayoutCommon;

		protected $type = 'CTCatalogLayout';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}
