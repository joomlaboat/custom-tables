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
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;
use CustomTables\ProInputBoxTableJoin;
use CustomTables\ProInputBoxTableJoinList;

class Inputbox
{
	var CT $ct;
	var Field $field;
	var ?array $row;

	var string $cssclass;
	var string $attributes;
	var array $attributesArray;
	var string $onchange;
	var array $option_list;
	var string $place_holder;
	var string $prefix;
	var bool $isTwig;
	var ?string $defaultValue;

	protected string $cssStyle;

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function __construct(CT &$ct, $fieldRow, array $option_list = [], $isTwig = true, string $onchange = '')
	{
		$this->ct = &$ct;
		$this->isTwig = $isTwig;
		$this->cssclass = $option_list[0] ?? '';
		$this->attributes = str_replace('****quote****', '"', $option_list[1] ?? '');//Optional Parameter

		preg_match('/onchange="([^"]*)"/', $this->attributes, $matches);
		$onchange_value = $matches[1] ?? '';

		$this->attributes = str_replace($onchange_value, '', $this->attributes);
		$this->attributes = str_replace('onchange=""', '', $this->attributes);
		$this->attributes = str_replace("onchange=''", '', $this->attributes);
		$this->cssStyle = '';
		$this->onchange = ($onchange_value != '' and $onchange_value[strlen($onchange_value) - 1] != ';') ? $onchange_value . ';' . $onchange : $onchange_value . $onchange;

		if (str_contains($this->cssclass, ':'))//it's a style, change it to attribute
		{
			$this->cssStyle = $this->cssclass;
			$this->cssclass = '';
		}

		if (str_contains($this->attributes, 'onchange="') and $this->onchange != '') {
			//if the attributes already contain "onchange" parameter then add onchange value to the attributes parameter
			$this->attributes = str_replace('onchange="', 'onchange="' . $this->onchange, $this->attributes);
		} elseif ($this->attributes != '')
			$this->attributes .= ' onchange="' . $onchange . '"';
		else
			$this->attributes = 'onchange="' . $onchange . '"';

		$this->field = new Field($this->ct, $fieldRow);

		if ($this->field->isrequired == 1)
			$this->cssclass .= ' required';

		$this->option_list = $option_list;
		$this->place_holder = $this->field->title;

		$this->attributesArray['class'] = $this->cssclass;
		$this->attributesArray['data-type'] = $this->field->type;
		$this->attributesArray['title'] = $this->field->title;
		$this->attributesArray['data-label'] = $this->field->title;
		$this->attributesArray['placeholder'] = $this->field->title;
		$this->attributesArray['data-valuerule'] = str_replace('"', '&quot;', $this->field->valuerule ?? '');
		$this->attributesArray['data-valuerulecaption'] = str_replace('"', '&quot;', $this->field->valuerulecaption ?? '');
		$this->attributesArray['onchange'] = $this->onchange;

		//For old input boxes
		if ($this->field->type != "records")
			$this->cssclass .= ($this->ct->Env->version < 4 ? ' inputbox' : ' form-control');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function render(?string $value, ?array $row): ?string
	{
		$this->row = $row;
		$this->field = new Field($this->ct, $this->field->fieldrow, $this->row);
		$this->prefix = $this->ct->Env->field_input_prefix . (!$this->ct->isEditForm ? $this->row[$this->ct->Table->realidfieldname] . '_' : '');
		$this->attributesArray['name'] = $this->prefix . $this->field->fieldname;
		$this->attributesArray['id'] = $this->prefix . $this->field->fieldname;

		if ($this->row === null)
			$this->attributesArray['placeholder'] = $this->place_holder;

		if ($this->field->defaultvalue !== '' and $value === null) {
			$twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
			$this->defaultValue = $twig->process($this->row);
		} else
			$this->defaultValue = null;


		//Try to instantiate a class dynamically
		$aliasMap = [
			'blob' => 'file',
			'userid' => 'user',
			'ordering' => 'int',
			'googlemapcoordinates' => 'gps',
			'multilangstring' => 'multilingualstring',
			'multilangtext' => 'multilingualtext',
			'sqljoin' => 'tablejoin',
			'records' => 'tablejoinlist'
		];

		$fieldTypeShort = str_replace('_', '', $this->field->type);
		if (key_exists($fieldTypeShort, $aliasMap))
			$fieldTypeShort = $aliasMap[$fieldTypeShort];

		$additionalFile = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . $fieldTypeShort . '.php';

		if (file_exists($additionalFile)) {
			require_once($additionalFile);
			$className = '\CustomTables\InputBox_' . $fieldTypeShort;
			$inputBoxRenderer = new $className($this->ct, $this->field, $this->row, $this->option_list, $this->attributesArray);
		}

		switch ($this->field->type) {

			case 'alias':
			case 'article':
			case 'blob':
			case 'checkbox':
			case 'color':
			case 'date':
			case 'email':
			case 'file':
			case 'filebox':
			case 'filelink':
			case 'float':
			case 'googlemapcoordinates':
			case 'int':
			case 'image':
			case 'imagegallery':
			case 'language':
			case 'multilangstring':
			case 'multilangtext':
			case 'ordering':
			case 'radio':
			case 'signature':
			case 'string':
			case 'text':
			case 'time':
			case 'url':
			case 'user':
			case 'userid':
			case 'usergroup':
			case 'usergroups':
				return $inputBoxRenderer->render($value, $this->defaultValue);

			case 'sqljoin':
				if (!$this->isTwig)
					return 'Old Table Join tags no longer supported';

				if (defined('_JEXEC')) {
					$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

					if (file_exists($path . 'tablejoin.php')) {
						require_once($path . 'tablejoin.php');

						$inputBoxRenderer = new ProInputBoxTableJoin($this->ct, $this->field, $this->row, $this->option_list, $this->attributesArray);
						return $inputBoxRenderer->render($value, $this->defaultValue);
					} else {
						return common::translate('COM_CUSTOMTABLES_AVAILABLE');
					}
				} else {
					return 'Table Join field type is not supported by WordPress version of the Custom Tables yet.';
				}

			case 'records':

				if (defined('_JEXEC')) {
					$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

					if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
						require_once($path . 'tablejoin.php');
						require_once($path . 'tablejoinlist.php');

						$inputBoxRenderer = new ProInputBoxTableJoinList($this->ct, $this->field, $this->row, $this->option_list, $this->attributesArray);
						return $inputBoxRenderer->render($value, $this->defaultValue);
					} else {
						return common::translate('COM_CUSTOMTABLES_AVAILABLE');
					}
				} else {
					return 'Table Join List field type is not supported by WordPress version of the Custom Tables yet.';
				}
		}
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getDefaultValueIfNeeded($row)
	{
		$value = null;

		if ($this->ct->isRecordNull($row)) {
			$value = common::inputPostString($this->field->realfieldname, null, 'create-edit-record');

			if ($value == '') {
				$f = str_replace($this->ct->Env->field_prefix, '', $this->field->realfieldname);//legacy support
				$value = common::getWhereParameter($f);
			}

			if ($value == '') {
				$value = $this->field->defaultvalue;

				//Process default value, not processing PHP tag
				if ($value != '') {
					if ($this->ct->Env->legacySupport) {
						tagProcessor_General::process($this->ct, $value, $row);
						tagProcessor_Item::process($this->ct, $value, $row);
						tagProcessor_If::process($this->ct, $value, $row);
						tagProcessor_Page::process($this->ct, $value);
						tagProcessor_Value::processValues($this->ct, $value, $row);
					}

					$twig = new TwigProcessor($this->ct, $value);
					$value = $twig->process($row);

					if ($twig->errorMessage !== null)
						$this->ct->errors[] = $twig->errorMessage;

					if ($value != '') {
						if ($this->ct->Params->allowContentPlugins)
							CTMiscHelper::applyContentPlugins($value);

						if ($this->field->type == 'alias') {
							$listing_id = $row[$this->ct->Table->realidfieldname] ?? 0;
							$saveField = new SaveFieldQuerySet($this->ct, $this->ct->Table->record, false);
							$saveField->field = $this->field;
							$value = $saveField->prepare_alias_type_value($listing_id, $value);
						}
					}
				}
			}
		} else {
			if ($this->field->type != 'multilangstring' and $this->field->type != 'multilangtext') {// and $this->field->type != 'multilangarticle') {
				$value = $row[$this->field->realfieldname] ?? null;
			}
		}
		return $value;
	}
}

abstract class BaseInputBox
{
	protected CT $ct;
	protected Field $field;
	protected ?array $row;
	protected array $attributes;
	protected array $option_list;

	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		$this->ct = $ct;
		$this->field = $field;
		$this->row = $row;
		$this->option_list = $option_list;
		$this->attributes = $attributes;

		if ($this->field->isrequired == 1)
			self::addCSSClass($this->attributes, 'required');
	}

	public static function addCSSClass(&$attributes, $className): void
	{
		if (isset($attributes['class'])) {
			$classes = explode(' ', $attributes['class']);
			if (!in_array($className, $classes)) {
				$classes [] = $className;
				$attributes['class'] = implode(' ', $classes);
			}
		} else {
			$attributes['class'] = $className;
		}
	}

	public static function selectBoxAddCSSClass(&$attributes, $joomlaVersion): void
	{
		if ($joomlaVersion < 4)
			self::addCSSClass($attributes, 'inputbox');
		else
			self::addCSSClass($attributes, 'form-select');
	}

	public static function inputBoxAddCSSClass(&$attributes, $joomlaVersion): void
	{
		if ($joomlaVersion < 4)
			self::addCSSClass($attributes, 'inputbox');
		else
			self::addCSSClass($attributes, 'form-control');
	}

	function renderSelect(string $value, array $options): string
	{
		// Start building the select element with attributes
		$select = '<select ' . self::attributes2String($this->attributes) . '>';

		// Optional default option
		$selected = ($value == '' ? ' selected' : '');
		$select .= '<option value=""' . $selected . '> - ' . common::translate('COM_CUSTOMTABLES_SELECT') . '</option>';

		// Generate options for each file in the folder
		foreach ($options as $option) {
			$selected = ($option->id == $value) ? ' selected' : '';
			$select .= '<option value="' . $option->id . '"' . $selected . '>' . $option->name . '</option>';
		}
		$select .= '</select>';
		return $select;
	}

	public static function attributes2String(array $attributes): string
	{
		$result = '';
		foreach ($attributes as $key => $attr)
			$result .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($attr ?? '') . '"';

		return $result;
	}
}
