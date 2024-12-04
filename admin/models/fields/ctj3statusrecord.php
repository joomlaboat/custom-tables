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
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldctj3statusrecord extends JFormFieldList
	{
		public $type = 'ctj3statusrecord';

		public function getOptions()
		{
			$options = [];
			$options[] = HTMLHelper::_('select.option', '', common::translate('JOPTION_SELECT_PUBLISHED'));
			$options[] = HTMLHelper::_('select.option', 1, common::translate('COM_CUSTOMTABLES_PUBLISHED'));
			$options[] = HTMLHelper::_('select.option', 0, common::translate('COM_CUSTOMTABLES_UNPUBLISHED'));
			return $options;
		}
	}

} else {
	class JFormFieldctj3statusrecord extends FormField
	{
		public $type = 'ctj3statusrecord';
		protected $layout = 'joomla.form.field.list';

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = $this->getOptions();
			return $this->getRenderer($this->layout)->render($data);
		}

		public function getOptions()
		{
			$options = [];
			$options[] = ['value' => '', 'text' => common::translate('JOPTION_SELECT_PUBLISHED')];
			$options[] = ['value' => '1', 'text' => common::translate('COM_CUSTOMTABLES_PUBLISHED')];
			$options[] = ['value' => '0', 'text' => common::translate('COM_CUSTOMTABLES_UNPUBLISHED')];
			return $options;
		}
	}
}