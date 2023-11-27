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

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTj3status extends JFormFieldList
	{
		public $type = 'ctj3table';

		function __construct($form = null)
		{
			parent::__construct($form);
		}

		public function getOptions()
		{
			$options = [];

			$options[] = JHtml::_('select.option', '', common::translate('JOPTION_SELECT_PUBLISHED'));
			$options[] = JHtml::_('select.option', 1, common::translate('JPUBLISHED'));
			$options[] = JHtml::_('select.option', 0, common::translate('JUNPUBLISHED'));
			$options[] = JHtml::_('select.option', -2, common::translate('JTRASHED'));
			$options[] = JHtml::_('select.option', '*', common::translate('JALL'));

			return $options;
		}
	}
} else {
	class JFormFieldCTj3status extends FormField
	{
		public $type = 'ctj3table';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		/**
		 * Method to get the field input markup.
		 *
		 * @return  string    The field input markup.
		 *
		 * @since   3.9.2
		 */
		protected function getInput()
		{
			$options = [
				['value' => '', 'text' => common::translate('JOPTION_SELECT_PUBLISHED')],
				['value' => '1', 'text' => common::translate('JPUBLISHED')],
				['value' => '0', 'text' => common::translate('JUNPUBLISHED')],
				['value' => '-2', 'text' => common::translate('JTRASHED')],
				['value' => '*', 'text' => common::translate('JALL')]];

			$data = $this->getLayoutData();
			$data['options'] = $options;
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}