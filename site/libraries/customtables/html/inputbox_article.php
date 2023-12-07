<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use Joomla\CMS\HTML\HTMLHelper;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class InputBox_Article extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render_article(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname);
			if ($value === null)
				$value = (int)$defaultValue;
		}

		$this->selectBoxAddCSSClass();

		$catId = (int)$this->field->params[0] ?? '';
		$query = 'SELECT id, title FROM #__content';

		if ($catId != 0)
			$query .= ' WHERE catid=' . $catId;

		$query .= ' ORDER BY title';
		$options = database::loadObjectList($query);
		$options = array_merge(array(array(
			'id' => '',
			'data-type' => 'article',
			'title' => '- ' . common::translate('COM_CUSTOMTABLES_SELECT'))), $options);

		return HTMLHelper::_('select.genericlist', $options, $this->prefix . $this->field->fieldname,
			$this->attributes2String(), 'id', 'title', $value, $this->prefix . $this->field->fieldname);
	}
}