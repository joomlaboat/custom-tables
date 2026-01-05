<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;

class Value_article extends BaseValue
{
	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		parent::__construct($ct, $field, $rowValue, $option_list);
	}

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	function render(): ?string
	{

		if (defined('WPINC'))
			return 'CustomTables for WordPress: "article" field type is not available.';

		if (isset($this->option_list[0]) and $this->option_list[0] != '')
			$field = strtolower($this->option_list[0]);
		else
			$field = 'title';


		$format = $this->option_list[5] ?? null;

		$allowedFields = array('id', 'asset_id', 'title', 'alias', 'introtext', 'fulltext', 'state', 'catid', 'created', 'created_by', 'created_by_alias',
			'modified', 'modified_by', 'checked_out', 'checked_out_time', 'publish_up', 'publish_down', 'images', 'urls', 'attribs' . 'version',
			'ordering', 'metakey', 'metadesc', 'access', 'hits', 'metadata', 'featured', 'language', 'note');

		if (!in_array($field, $allowedFields)) {

			$customFieldValue = self::tryToGetArticleCustomFieldValue($field, (int)$this->rowValue);
			if ($customFieldValue !== null) {
				$article = $customFieldValue['value'];
			} else
				return 'Wrong article field "' . $field . '". Available fields: ' . implode(', ', $allowedFields) . '.';
		} else {
			$article = $this->getArticle((int)$this->rowValue, $field);
		}

		if (isset($this->option_list[1])) {
			return BaseValue::TextFunctions($article, [
				($this->option_list[1] ?? null),
				($this->option_list[2] ?? null),
				($this->option_list[3] ?? null),
				($this->option_list[4] ?? null)
			]);
		} elseif (!empty($format))
			return common::formatDate($article, $format, null);
		else
			return $article;
	}

	/**
	 * @throws Exception
	 * @since 3.3.5
	 */
	private static function tryToGetArticleCustomFieldValue(string $fieldName, int $articleId): ?array
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('context', 'com_content.article');
		$whereClause->addCondition('name', $fieldName);

		$select = ['CUSTOM_FIELD', '', '', 'value', $articleId];//'(SELECT value FROM #__fields_values WHERE #__fields_values.field_id=a.asset_id AND item_id=' . $variable . ') AS ' . $asValue;

		$values = database::loadObjectList('#__fields', [$select], $whereClause, null, null, 1);

		if (count($values) == 0)
			return null;

		return ['value' => $values[0]->value];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getArticle($articleId, $field): ?string
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', (int)$articleId);

		$rows = database::loadAssocList('#__content', [$field], $whereClause, null, null, 1);

		if (count($rows) != 1)
			return null; //return nothing if article not found

		return $rows[0][$field];
	}
}