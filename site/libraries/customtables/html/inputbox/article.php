<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class InputBox_article extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value == '')
			$value = null;

		if ($value === null) {
			$value = common::inputGetInt($this->ct->Env->field_prefix . $this->field->fieldname);
			if ($value === null)
				$value = (int)$defaultValue;
		}

		self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

		$whereClause = new MySQLWhereClause();

		$catId = (int)((count($this->field->params) > 0 and $this->field->params[0] != '') ? $this->field->params : 0);

		if ($catId != 0)
			$whereClause->addCondition('catid', $catId);

		try {
			$articles = database::loadObjectList('#__content', ['id', 'title AS name'], $whereClause, 'title');
		} catch (Exception $e) {
			return 'InputBox_Article::render_article() - Cannot load a list of articles. Details: ' . $e->getMessage();
		}
		return $this->renderSelect(strval($value ?? ''), $articles);
	}
}