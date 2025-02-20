<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\TableHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

//Returns the Options object with the list of tables (specified by table id in url)

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldAnyTables extends JFormFieldList
	{

		protected $type = 'anytables';

		protected function getOptions(): array
		{
			$options = array();
			$options[] = HTMLHelper::_('select.option', '', ' - ' . common::translate('COM_CUSTOMTABLES_SELECT'));

			$tables = TableHelper::getListOfExistingTables();

			foreach ($tables as $table)
				$options[] = HTMLHelper::_('select.option', $table, $table);

			$options[] = HTMLHelper::_('select.option', '-new-', '- Create New Table');

			return $options;
		}


	}
} else {

	class JFormFieldAnyTables extends FormField
	{
		protected $type = 'anytables';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		/**
		 * @throws Exception
		 * @since 3.2.2
		 */
		protected function getInput(): string
		{
			$data = $this->getLayoutData();
			$data['options'] = $this->getOptions();
			return $this->getRenderer($this->layout)->render($data);
		}

		/**
		 * @throws Exception
		 * @since 3.2.2
		 */
		protected function getOptions($add_empty_option = true): array
		{
			$tables = TableHelper::getListOfExistingTables();

			$options = array();
			if ($tables) {
				if ($add_empty_option)
					$options[] = ['value' => '', 'text' => ' - ' . common::translate('COM_CUSTOMTABLES_SELECT')];

				foreach ($tables as $table)
					$options[] = ['value' => $table, 'text' => $table];
			}
			return $options;
		}
	}
}