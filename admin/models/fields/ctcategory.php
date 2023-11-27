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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Version;
use CustomTables\common;
use CustomTables\database;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	/**
	 * Element name
	 * https://docs.joomla.org/Creating_a_custom_form_field_type
	 * @access    public
	 * @var        string
	 * @since   1.0.0
	 */
	class JFormFieldCTCategory extends JFormFieldList
	{
		public $type = 'ctcategory';

		public function getOptions($add_empty_option = true)
		{
			$query = 'SELECT id,categoryname FROM #__customtables_categories WHERE published=1 ORDER BY categoryname';
			$records = database::loadObjectList($query);

			$options = array();
			if ($records) {
				if ($add_empty_option)
					$options[] = JHtml::_('select.option', '', common::translate('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'));

				foreach ($records as $rec)
					$options[] = JHtml::_('select.option', $rec->id, $rec->categoryname);
			}
			return $options;
		}
	}

} else {
	class JFormFieldCTCategory extends FormField
	{
		/**
		 * Element name
		 * @access    public
		 * @var        string
		 * @since   3.1.9
		 */
		public $type = 'ctcategory';
		protected $layout = 'joomla.form.field.list';

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = $this->getOptions();
			return $this->getRenderer($this->layout)->render($data);
		}

		public function getOptions($add_empty_option = true)
		{
			$query = 'SELECT id,categoryname FROM #__customtables_categories WHERE published=1 ORDER BY categoryname';
			$records = database::loadObjectList($query);

			$options = array();
			if ($records) {
				if ($add_empty_option)
					$options[] = ['value' => '', 'text' => common::translate('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT')];

				foreach ($records as $rec)
					$options[] = ['value' => $rec->id, 'text' => $rec->categoryname];
			}
			return $options;
		}
	}
}