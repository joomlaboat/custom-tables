<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Exception;

defined('_JEXEC') or die();

class Diagram
{
	var array $AllTables;
	var array $AllFields;
	var array $tables;
	var array $colors;
	var array $text_colors;

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function __construct(?int $categoryId = null)
	{
		$this->AllTables = TableHelper::getAllTables($categoryId);
		$this->AllFields = $this->getAllFields();
		$this->tables = $this->prepareTables();
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getAllFields(): array
	{
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		return database::loadAssocList('#__customtables_fields', ['*'], $whereClause, 'fieldname');
	}

	protected function prepareTables(): array
	{
		$this->getColors();

		$tables = [];

		$color_index = 0;
		foreach ($this->AllTables as $table) {
			$joincount = 0;
			$fields = $this->getTableFields($table['id']);

			$field_names = [];
			foreach ($fields as $field) {
				if ((int)$field['published'] === 1) {
					$attr = ["name" => $field['fieldname'], "type" => $field['type']];

					if ($field['type'] == 'sqljoin' or $field['type'] == 'records') {
						$params = CTMiscHelper::csv_explode(',', $field['typeparams']);
						$jointable = $params[0];
						$attr["join"] = $jointable;
						$attr["joincolor"] = '';
						$joincount++;
					}

					$field_names[] = $attr;
				}
			}

			$tables[] = ['name' => $table['tablename'], 'columns' => $field_names, 'joincount' => $joincount, 'dependencies' => 0
				, "color" => $this->colors[$color_index], "text_color" => $this->text_colors[$color_index]];

			$color_index++;
			if ($color_index >= count($this->colors))
				$color_index = 0;
		}

		for ($i = 0; $i < count($tables); $i++) {
			$dependencies = 0;
			foreach ($tables as $table) {
				foreach ($table['columns'] as $column) {
					if (isset($column['join']) and $column['join'] == $tables[$i]['name'])
						$dependencies++;
				}
			}

			$tables[$i]['dependencies'] = $dependencies;


			//Get join table color
			for ($c = 0; $c < count($tables[$i]['columns']); $c++) {
				$column = $tables[$i]['columns'][$c];


				if ($column['type'] == 'sqljoin' or $column['type'] == 'records') {
					foreach ($tables as $table) {
						if ($table['name'] == $column['join']) {
							$tables[$i]['columns'][$c]['joincolor'] = $table['color'];
							break;
						}
					}

				}
			}
		}

		//Reorganize the list
		$tables_with_join = [];
		$tables_without_join = [];
		foreach ($tables as $table) {
			if ($table['joincount'] > 0)
				$tables_with_join[] = $table;
			else
				$tables_without_join[] = $table;
		}

		return array_merge($tables_with_join, $tables_without_join);
	}

	protected function getColors(): void
	{
		//Colors
		$colors = [];
		$colors[] = '#FF0000';
		$colors[] = '#00FFFF';
		$colors[] = '#C0C0C0';
		$colors[] = '#0000FF';
		$colors[] = '#808080';
		$colors[] = '#00008B';
		$colors[] = '#ADD8E6';
		$colors[] = '#FFA500';
		$colors[] = '#800080';
		$colors[] = '#A52A2A';
		$colors[] = '#FFFF00';
		$colors[] = '#800000';
		$colors[] = '#00FF00';
		$colors[] = '#008000';
		$colors[] = '#FF00FF';
		$colors[] = '#808000';
		$colors[] = '#FFC0CB';
		$colors[] = '#7fffd4';

		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#FFFFFF';
		$text_colors[] = '#000000';
		$text_colors[] = '#000000';

		$this->colors = $colors;
		$this->text_colors = $text_colors;
	}

	protected function getTableFields($tableid): array
	{
		$fields = [];

		foreach ($this->AllFields as $field) {
			if ($field['tableid'] == $tableid)
				$fields[] = $field;
		}

		return $fields;
	}
}