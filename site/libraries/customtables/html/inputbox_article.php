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

use Exception;

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
		if ($value == '')
			$value = null;

		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname);
			if ($value === null)
				$value = (int)$defaultValue;
		}

		$this->selectBoxAddCSSClass();

		$catId = (int)$this->field->params[0] ?? '';
		$query = 'SELECT id, title AS name FROM #__content';

		if ($catId != 0)
			$query .= ' WHERE catid=' . $catId;

		$query .= ' ORDER BY title';

		try {
			$articles = database::loadObjectList($query);
		} catch (Exception $e) {
			return 'InputBox_Article::render_article() - Cannot load a list of articles. Details: ' . $e->getMessage();
		}
		return $this->renderSelect(strval($value ?? ''), $articles);
	}
}