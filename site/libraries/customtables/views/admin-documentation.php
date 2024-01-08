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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\Version;
use JoomlaBasicMisc;

class Documentation
{
	var bool $internal_use = false;
	var float $version;
	var bool $onlyWordpress;
	var bool $hideProVersion;

	function __construct(bool $onlyWordpress = false, bool $hideProVersion = false)
	{
		if (defined('_JEXEC')) {
			$version = new Version;
			$this->version = (float)$version->getShortVersion();
		} else
			$this->version = 6;

		$this->internal_use = true;
		$this->onlyWordpress = $onlyWordpress;
		$this->hideProVersion = $hideProVersion;
	}

	function getFieldTypes(): string
	{
		$xml = JoomlaBasicMisc::getXMLData('fieldtypes.xml');
		if (count($xml) == 0 or !isset($xml->type))
			return '';

		return $this->renderFieldTypes($xml->type);
	}

	function renderFieldTypes($types): string
	{
		if ($this->internal_use)
			return $this->renderFieldTypesInternal($types);
		else
			return $this->renderFieldTypesGitHub($types);
	}

	function renderFieldTypesInternal($types): string
	{
		$result = '';

		foreach ($types as $type) {
			$type_att = $type->attributes();

			$is4Pro = (bool)(int)$type_att->proversion;
			$hideDefaultExample = (bool)(int)$type_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$type_att->deprecated;

			$active = true;

			if ($this->onlyWordpress) {
				if (!((string)$type_att->wordpress == 'true'))
					$active = false;
			}

			if ($this->hideProVersion and $is4Pro)
				$active = false;

			if ($active and !$isDeprecated) {

				$class = 'ct_doc_free';
				if ($is4Pro)
					$class = 'ct_doc_pro';
				if ($this->internal_use) {
					$result .= '<div class="' . $class . ' ct_readmoreClosed" id="ctDocType_' . $type_att->ct_name . '">';
					$result .= '<h4 onClick="readmoreOpenClose(\'ctDocType_' . $type_att->ct_name . '\')">' . $type_att->label;
				} else {
					$result .= '<div class="' . $class . '" id="ctDocType_' . $type_att->ct_name . '">';
					$result .= '<h4>' . $type_att->ct_name . ' - <span>' . $type_att->label . '</span>';
				}

				if ($is4Pro)
					$result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h4>';

				$result .= '<p>' . $type_att->description . '</p>';

				if (isset($type_att->image)) {
					$result .= '<p><img src="' . $type_att->image . '" alt="' . $type_att->label . '" /></p>';
				}

				if (!empty($type->params) and count($type->params) > 0) {
					$content = $this->renderParametersInternal($type->params, '', '', '', '', true);
					if ($content != '')
						$result .= '<hr/><h5>' . common::translate('COM_CUSTOMTABLES_FIELDTYPEPARAMS') . ':</h5>' . $content;
				}

				$result .= '<hr/><h5>' . common::translate('COM_CUSTOMTABLES_VALUEPARAMS') . ':</h5><p>Example 1:<pre class="ct_doc_pre">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i> }}</pre></p>';

				if (!empty($type->valueparams)) {
					foreach ($type->valueparams as $p) {
						$params = $p->params;
						//$result.='<h5>'.common::translate('COM_CUSTOMTABLES_VALUEPARAMS').':</h5>'
						$result .= $this->renderParametersInternal($params,
							'{{ ',
							'<i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
							'',
							' }}',
							$hideDefaultExample);
						break;
					}
				}

				$result .= '<h5>' . common::translate('Pure Value (As it is)') . ':</h5>'
					. '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ':<br/><pre class="ct_doc_pre">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.value }}'
					. '</pre></p>';


				$result .= '<hr/><h5>' . common::translate('COM_CUSTOMTABLES_EDITRECPARAMS') . ':</h5>';


				if (!empty($type->editparams)) {

					$editparams_att = $type->editparams->attributes();

					if (isset($editparams_att->image)) {
						$result .= '<p><img src="' . $editparams_att->image . '" alt="' . $editparams_att->label . '" /></p>';
					}

					if (isset($editparams_att->description)) {
						$result .= '<p>' . $editparams_att->description . '</p>';
					}
				}

				$result .= '<p>Example 1:<pre class="ct_doc_pre">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.edit }}</pre></p>';

				if (!empty($type->editparams)) {

					foreach ($type->editparams as $p) {
						$params = $p->params;
						$result .= $this->renderParametersInternal($params,
							'{{ ',
							'<i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
							'.edit',
							' }}',
							$hideDefaultExample);
						break;
					}
				}

				if (!empty($type->subvalueparams)) {

					foreach ($type->subvalueparams as $p) {

						$params_att = $p->attributes();
						$result .= '<hr/><h5>' . $params_att->label . ':</h5>';
						$params = ((array)$p->params)['param'];

						if (is_object($params)) {
							$params = [$params];
						}

						$result .= $this->renderParametersInternal2($params,
							'{{ ',
							'<i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
							'.' . $params_att->name,
							' }}',
							$hideDefaultExample);
					}
				}
				$result .= '</div>';
			}
		}
		return $result;
	}

	function renderParametersInternal($params, $opening_char, $tag_name, $postfix, $closing_char, $hideDefaultExample): string
	{
		$result = '';
		$example_values = array();
		$example_values_count = 0;

		foreach ($params->param as $param) {

			$param_att = $param->attributes();

			if (isset($param_att->description)) {
				$param_description = $param_att->description;
				$param_description = str_replace('***pre***', '<br/><pre>', $param_description);
				$param_description = str_replace('***end-of-pre***', '</pre><br/>', $param_description);
			} else
				$param_description = '';

			$result .= '<li><h6>' . $param_att->label . ($param_description != '' ? ' - ' . $param_description : '') . '</h6>';

			if (isset($param_att->image)) {
				$result .= '<p><img src="' . $param_att->image . '" alt="' . $param_att->label . '" /></p>';
			}

			if (!empty($param_att->type)) {
				$value_example = '';
				$result .= $this->renderParamTypeInternal($param, $param_att, $value_example);
				$example_values[] = $value_example;

				if ($value_example != '')
					$example_values_count++;
			}
			$result .= '</li>';

		}

		$result_new = '';
		$cleanedParamsStr = implode(',', $this->cleanParams($example_values));

		$cleanedParamsStr = str_replace('***italic***', '<i>', $cleanedParamsStr);
		$cleanedParamsStr = str_replace('***end-of-italic***', '</i>', $cleanedParamsStr);

		$cleanedParamsStr = str_replace('***pre***', '<br/><pre>', $cleanedParamsStr);
		$cleanedParamsStr = str_replace('***end-of-pre***', '</pre><br/>', $cleanedParamsStr);

		if ($tag_name == '') {
			if (!(int)$hideDefaultExample) {
				$result_new .= '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
					. $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '</pre></p>';
			}
		} else {
			if ($example_values_count > 0) {
				$result_new .= '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
					. $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '</pre></p>';
			}
		}
		return '<ol>' . $result . '</ol>' . $result_new;
	}

	function renderParamTypeInternal($param, $param_att, &$value_example): string
	{
		$result = '';

		$value_example = $param_att->example;

		switch ($param_att->type) {
			case 'number':

				$result .= '<ul class="ct_doc_param_options">
					<li><b>' . common::translate('COM_CUSTOMTABLES_DEFAULT') . '</b>: ' . $param_att->default . '</li>
';
				if (!empty($param_att->min))
					$result .= '<li><b>' . common::translate('COM_CUSTOMTABLES_MIN') . '</b>: ' . $param_att->min . '</li>';

				if (!empty($param_att->max))
					$result .= '<li><b>' . common::translate('COM_CUSTOMTABLES_MAX') . '</b>: ' . $param_att->max . '</li>';

				$result .= '</ul>';

				$value_example = $param_att->min;

				break;

			case 'radio':
				$options = explode(',', $param_att->options);
				$value_example = '';
				//<p>'.common::translate('COM_CUSTOMTABLES_OPTIONS').':</p>
				$result .= '<ul class="ct_doc_param_options">';
				foreach ($options as $option) {
					$parts = explode('|', $option);

					if ($parts[0] == '')
						$result .= '<li>(' . $parts[1] . ' - default)</li>';
					else
						$result .= '<li><b>' . $parts[0] . '</b>: ' . $parts[1] . '</li>';

					if ($value_example == '' && $parts[0] != '')
						$value_example = $parts[0];
				}

				$result .= '</ul>';
				break;

			case 'list':

				$options = $param->option;
				$value_example = '';

				if (!empty($param_att->example))
					$value_example = $param_att->example;

				$result .= '<p><ul class="ct_doc_param_options">';
				foreach ($options as $option) {
					$option_att = $option->attributes();

					$result .= '<li>';

					if ($option_att->value == '')
						$par = '(Default. ';
					else
						$par = '<b>' . $option_att->value . '</b> - (';

					$result .= $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description . '.' : '') . ')';

					$result .= '</li>';

					if ($value_example == '' and $option_att->value != '')
						$value_example = $option_att->value;
				}

				$result .= '</ul>';
				break;

			//case 'array':
		}

		if (!((int)$param_att->examplenoquotes) and $value_example != null)
			$value_example = $this->prepareExample($value_example);

		if (isset($param_att->optional) and $param_att->optional == "1")
			$value_example = '***italic***' . $value_example . '***end-of-italic***';

		return $result;
	}

	function prepareExample(string $param): string
	{
		$output = preg_replace('/[^0-9]/', '', $param);
		if ($output != '')
			return $param;

		if (is_numeric($param))
			return $param;

		if ($param == 'true' or $param == 'false')
			return $param;

		return '"' . $param . '"';
	}

	function cleanParams(array $params): array
	{
		$new_params = array();
		$count = 0;

		foreach ($params as $param_) {
			$count++;
			if ($param_ !== null) {
				$param = trim($param_);
				if ($param != '' and $param != '""') {
					for ($i = 1; $i < $count; $i++)
						$new_params[] = '""';

					$param = str_replace('<', '&lt;', $param);
					$param = str_replace('>', '&gt;', $param);
					$new_params[] = $param;
					$count = 0;
				}
			}
		}
		return $new_params;
	}

	function renderParametersInternal2($paramsArray, $opening_char, $tag_name, $postfix, $closing_char, $hideDefaultExample): string
	{
		$result = '';
		$example_values = array();
		$example_values_count = 0;

		foreach ($paramsArray as $param) {

			$param_att = $param->attributes();

			if (count($param_att) != 0) {

				if (isset($param_att->optional) and $param_att->optional == '1')
					$optional = true;
				else
					$optional = false;

				$result .= '<li><h6>' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . ($optional ? ' (Optional)' : '') . '</h6>';

				if (isset($param_att->image)) {
					$result .= '<p><img src="' . $param_att->image . '" alt="' . $param_att->label . '" /></p>';
				}

				if (!empty($param_att->type)) {
					$value_example = '';
					$result .= $this->renderParamTypeInternal($param, $param_att, $value_example);
					$example_values[] = $value_example;

					if ($value_example != '')
						$example_values_count++;
				}
				$result .= '</li>';
			}
		}

		$result_new = '';
		$cleanedParamsStr = implode(',', $this->cleanParams($example_values));

		$cleanedParamsStr = str_replace('***italic***', '<i>', $cleanedParamsStr);
		$cleanedParamsStr = str_replace('***end-of-italic***', '</i>', $cleanedParamsStr);

		if ($tag_name == '') {
			if (!(int)$hideDefaultExample) {
				$result_new .= '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
					. $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '</pre></p>';
			}
		} else {
			if ($example_values_count > 0) {
				$result_new .= '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
					. $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '</pre></p>';
			}
		}
		return '<ol>' . $result . '</ol>' . $result_new;
	}

	function renderFieldTypesGitHub($types): string
	{
		$result = '';

		foreach ($types as $type) {
			$type_att = $type->attributes();

			$hideDefaultExample = (bool)(int)$type_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$type_att->deprecated;

			if (!$isDeprecated) {
				$result .= '# ' . $type_att->label . '<br/><br/>' . $type_att->description . '<br/><br/>';

				if (isset($type_att->image)) {
					$result .= '![' . $type_att->label . '](' . $type_att->image . ')<br/><br/>';
				}

				if (!empty($type->params) and count($type->params) > 0) {
					$content = $this->renderParametersGitHub($type->params, '', '', '', '', true);
					if ($content != '')
						$result .= '**' . common::translate('COM_CUSTOMTABLES_FIELDTYPEPARAMS') . ':**<br/><br/>' . $content;
				}

				$result .= '**' . common::translate('COM_CUSTOMTABLES_VALUEPARAMS') . ':**<br/><br/>Example:'
					. '`{{ ' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . ' }}`<br/><br/>';

				if (!empty($type->valueparams)) {
					foreach ($type->valueparams as $p) {
						$params = $p->params;

						$result .= $this->renderParametersGitHub($params,
							'{{ ', str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')),
							'',
							' }}',
							$hideDefaultExample);
						break;

					}

				}

				$result .= '**' . common::translate('COM_CUSTOMTABLES_FIELDTYPE_PUREVALUE') . ':**<br/><br/>'
					. common::translate('COM_CUSTOMTABLES_EXAMPLE') . ':<br/><br/>'
					. '`{{ ' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '.value }}`'
					. '<br/><br/>';

				$result .= '**' . common::translate('COM_CUSTOMTABLES_EDITRECPARAMS') . ':**<br/><br/>Example:'
					. '`{{ ' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '.edit }}`<br/><br/>';

				if (!empty($type->editparams)) {
					foreach ($type->editparams as $p) {
						$params = $p->params;

						$result .= $this->renderParametersGitHub($params,
							'{{ ',
							str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')),
							'.edit',
							' }}',
							$hideDefaultExample);
						break;
					}
				}
			}

			if (!empty($type->subvalueparams)) {

				foreach ($type->subvalueparams as $p) {

					$params_att = $p->attributes();
					$result .= '<br/><br/>**' . $params_att->label . ':**<br/><br/>';
					$params = ((array)$p->params)['param'];

					if (is_object($params)) {
						$params = [$params];
					}

					$result .= $this->renderParametersGitHub2($params,
						'{{ ',
						str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')),
						'.' . $params_att->name,
						' }}',
						$hideDefaultExample);
				}
				$result .= '<br/>';
			}
		}
		return $result;
	}

	function renderParametersGitHub($params_, $opening_char, $tag_name, $postfix, $closing_char, $hidedefaultexample): string
	{
		$example_values = [];
		$example_values_count = 0;

		$result = '';
		if ($params_ !== null) {
			$params = $params_->param;

			$count = 1;

			foreach ($params as $param) {
				$param_att = $param->attributes();

				if (count($param_att) != 0) {

					if (isset($param_att->description)) {
						$param_description = $param_att->description;
						$param_description = str_replace('***pre***', '<br/>`', $param_description);
						$param_description = str_replace('***end-of-pre***', '</pre>`', $param_description);
					} else
						$param_description = '';

					$result .= $count . '. ' . $param_att->label . ($param_description != '' ? ' - ' . $param_description : '') . '<br/>';

					if (isset($param_att->image)) {
						$result .= '![' . $param_att->label . '](' . $param_att->image . ')<br/><br/>';
					}

					if (!empty($param_att->type)) {
						$value_example = '';
						$result .= $this->renderParamTypeGitHub($param, $param_att, $value_example) . '<br/>';

						if ($value_example != '') {
							$example_values[] = $value_example;
							$example_values_count++;
						}
					}
				}
				$count += 1;
			}
		}

		$result_new = '';
		$cleanedParamsStr = implode(',', $this->cleanParams($example_values));

		if ($tag_name == '') {
			if (!(int)$hidedefaultexample)
				$result_new .= '`' . $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '`<br/>';
		} else {
			if ($example_values_count > 0)
				$result_new .= '`' . $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '`<br/>';
		}
		return $result . $result_new;
	}

	function renderParamTypeGitHub($param, $param_att, &$value_example): string
	{
		$result = '';
		$value_example = $param_att->example;

		switch ($param_att->type) {
			case 'number':

				//$result .= '&nbsp;&nbsp;&nbsp;&nbsp;* **' . common::translate('COM_CUSTOMTABLES_DEFAULT') . '** - ' . $param_att->default . '<br/>';
				$result .= '<pre>    </pre>* **' . common::translate('COM_CUSTOMTABLES_DEFAULT') . '** - ' . $param_att->default . '<br/>';

				if (!empty($param_att->min))
					$result .= '<pre>    </pre>* **' . common::translate('COM_CUSTOMTABLES_MIN') . '** - ' . $param_att->min . '<br/>';

				if (!empty($param_att->max))
					$result .= '<pre>    </pre>* **' . common::translate('COM_CUSTOMTABLES_MAX') . '** - ' . $param_att->max . '<br/>';

				$value_example = $param_att->min;

				break;

			case 'radio':
				$options = explode(',', $param_att->options);
				$value_example = '';

				foreach ($options as $option) {
					$parts = explode('|', $option);

					if ($parts[0] == '')
						$result .= '<pre>    </pre>* (' . $parts[1] . ' - default)<br/>';
					else
						$result .= '<pre>    </pre>* **' . $parts[0] . '** - (' . $parts[1] . ')<br/>';

					if ($value_example == '' && $parts[0] != '')
						$value_example = $parts[0];
				}
				break;

			case 'list':

				$options = $param->option;
				$value_example = '';

				if (!empty($param_att->example)) {
					$value_example = $param_att->example;
				}

				$result .= '<br/>';
				foreach ($options as $option) {
					$option_att = $option->attributes();


					if ($option_att->value == '')
						$par = '(Default. ';
					else
						$par = '**' . $option_att->value . '** - (';

					//$result .= '&nbsp;&nbsp;&nbsp;&nbsp;* ' . $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description : '') . ')';
					$result .= '<pre>    </pre>* ' . $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description : '') . ')';

					$result .= '<br/>';

					if ($value_example == '' and $option_att->value != '')
						$value_example = $option_att->value;
				}
				break;
		}

		if (!((int)$param_att->examplenoquotes) and $value_example !== null)
			$value_example = $this->prepareExample($value_example);

		return $result;
	}

	function renderParametersGitHub2($params, $opening_char, $tag_name, $postfix, $closing_char, $hidedefaultexample): string
	{
		$example_values = [];
		$example_values_count = 0;

		$result = '';


		$count = 1;

		foreach ($params as $param) {
			$param_att = $param->attributes();

			if (count($param_att) != 0) {
				$result .= $count . '. ' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . '<br/>';

				if (isset($param_att->image)) {
					$result .= '![' . $param_att->label . '](' . $param_att->image . ')<br/><br/>';
				}

				if (!empty($param_att->type)) {
					$value_example = '';
					$result .= $this->renderParamTypeGitHub($param, $param_att, $value_example) . '<br/>';

					if ($value_example != '') {
						$example_values[] = $value_example;
						$example_values_count++;
					}
				}
			}
			$count += 1;
		}

		$result_new = '';
		$cleanedParamsStr = implode(',', $this->cleanParams($example_values));

		if ($tag_name == '') {
			if (!(int)$hidedefaultexample)
				$result_new .= '`' . $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '`<br/>';
		} else {
			if ($example_values_count > 0)
				$result_new .= '`' . $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '`<br/>';
		}
		return $result . $result_new;
	}

	function getLayoutTags(): string
	{
		$xml = JoomlaBasicMisc::getXMLData('tags.xml');

		if (count($xml) == 0)
			return '';

		return $this->renderLayoutTagSets($xml->tagset);
	}

	function renderLayoutTagSets($tagsets): string
	{
		if ($this->internal_use)
			return $this->renderLayoutTagSetsInternal($tagsets);
		else
			return $this->renderLayoutTagSetsGitHub($tagsets);
	}

	function renderLayoutTagSetsInternal($tagSets): string
	{
		$result = '';

		foreach ($tagSets as $tagSet) {
			$tagSetAtt = $tagSet->attributes();
			$is4Pro = (bool)(int)$tagSetAtt->proversion;
			$isDeprecated = (bool)(int)$tagSetAtt->deprecated;

			$active = true;

			/*
			if ($this->onlyWordpress) {
				if (!((string)$type_att->wordpress == 'true'))
					$active = false;
			}
			*/

			if ($this->hideProVersion and $is4Pro) {
				$active = false;
			}

			if ($active and !$isDeprecated) {

				$class = 'ct_doc_tagset_free';
				if ($is4Pro)
					$class = 'ct_doc_tagset_pro';

				$result .= '<div class="' . $class . '">';

				$result .= '<h3>' . $tagSetAtt->label;
				if ($is4Pro)
					$result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h3>';

				$result .= '<p>' . $tagSetAtt->description . '</p>';

				$result .= $this->renderTagsInternal($tagSet->tag, $tagSetAtt->name);

				$result .= '</div>';
			}
		}
		return $result;
	}

	function renderTagsInternal($tags, $tagsetname): string
	{
		$result = '';

		foreach ($tags as $tag) {
			$tag_att = $tag->attributes();

			$is4Pro = (bool)(int)$tag_att->proversion;
			$hidedefaultexample = (bool)(int)$tag_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$tag_att->deprecated;

			$active = true;
			/*
if ($this->onlyWordpress) {
	if (!((string)$type_att->wordpress == 'true'))
		$active = false;
			}
			*/

			if ($this->hideProVersion and $is4Pro) {
				$active = false;
			}

			if ($active and !$isDeprecated) {

				$class = 'ct_doc_free';
				if ($is4Pro)
					$class = 'ct_doc_pro';

				if ($tagsetname == 'plugins') {
					$startchar = '{';
					$endchar = '}';
					$label = $tag_att->label;
				} elseif ($tagsetname == 'filters') {
					$startchar = '{{ ' . $tag_att->examplevalue . ' | ';
					$endchar = ' }}';
					$label = $tag_att->description;
				} else {
					$startchar = '{{ ' . $tag_att->twigclass . '.';
					$endchar = ' }}';
					$label = $tag_att->label;
				}

				$result .= '<div class="' . $class . ' ct_readmoreClosed" id="ctDocTag_' . $tag_att->twigclass . '_' . $tag_att->name . '">';
				$result .= '<a name="' . $tag_att->twigclass . '_' . $tag_att->name . '"></a><h4 onClick="readmoreOpenClose(\'ctDocTag_' . $tag_att->twigclass . '_' . $tag_att->name . '\')">' . $startchar . $tag_att->name . $endchar . ' - <span>' . $label . '</span>';

				if ($is4Pro)
					$result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h4>';

				if ($tagsetname != 'plugins') {
					$result .= '<p>' . $tag_att->description . '</p>';

					if (isset($tag_att->image)) {
						$result .= '<p><img src="' . $tag_att->image . '" alt="' . $tag_att->label . '" /></p>';
					}

					if (!empty($tag->params) and count($tag->params) > 0) {
						$content = $this->renderParametersInternal($tag->params,
							'{{ ',
							'<i>' . $tag_att->twigclass . '.' . $tag_att->name . '</i>',
							'',
							' }}',
							$hidedefaultexample);

						if ($content != '')
							$result .= '<h5>' . common::translate('COM_CUSTOMTABLES_PARAMS') . ':</h5>' . $content;
					}
				}

				if ($tag_att->example !== null) {
					$result .= '<pre>Example: ' . $tag_att->example . '</pre>';
				}

				$result .= '</div>';
			}
		}
		return $result;
	}

	function renderLayoutTagSetsGitHub($tagsets): string
	{
		$result = '';
		foreach ($tagsets as $tagset) {
			$tagset_att = $tagset->attributes();

			if ((int)$tagset_att->deprecated == 0 and $tagset_att->name != 'plugins') {
				$result .= '# ' . $tagset_att->label . '<br/>' . $tagset_att->description . '<br/><br/>';
				$result .= $this->renderTagsGitHub($tagset->tag, $tagset_att->name) . '<br/><br/><br/>';
			}
		}
		return $result;
	}

	function renderTagsGitHub($tags, $tagsetname): string
	{
		$result = '';

		foreach ($tags as $tag) {
			$tag_att = $tag->attributes();
			$hidedefaultexample = (bool)(int)$tag_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$tag_att->deprecated;

			if (!$isDeprecated) {

				if ($tagsetname == 'filters')
					$result .= '## {{ ' . $tag_att->examplevalue . ' | ' . $tag_att->name . ' }}<br/><br/>' . $tag_att->description . '<br/><br/>';
				else
					$result .= '## ' . $tag_att->twigclass . '.' . $tag_att->name . '<br/><br/>' . $tag_att->description . '<br/><br/>';

				if (isset($tag_att->image)) {
					$result .= '![' . $tag_att->label . '](' . $tag_att->image . ')<br/><br/>';
				}

				if ($tagsetname != 'plugins') {

					if (!empty($tag->params) and count($tag->params) > 0) {

						if ($tagsetname == 'filters') {
							$content = $this->renderParametersGitHub($tag->params,
								'{{ ',
								'' . $tag_att->examplevalue . ' | ' . $tag_att->name,
								'',
								' }}',
								true);
						} else {
							$content = $this->renderParametersGitHub($tag->params,
								'{{ ',
								'' . $tag_att->twigclass . '.' . $tag_att->name,
								'',
								' }}',
								$hidedefaultexample);
						}

						if ($content != '')
							$result .= '**' . common::translate('COM_CUSTOMTABLES_PARAMS') . '**<br><br>' . $content;
					}
				}

				if ($tag_att->example !== null) {
					$result .= 'Example: `' . $tag_att->example . '`<br><br>';
				}

				$result .= '<br/>';
			}
		}
		return $result;
	}

	function getMenuItems(): string
	{
		$componentName = 'com_customtables';
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'tmpl';

		$xml = JoomlaBasicMisc::getXMLData('default.xml', $path);

		if (count($xml) == 0)
			return '';

		return $this->renderMenuItemTabs($xml->fields->fieldset);
	}

	function renderMenuItemTabs($fieldset): string
	{
		if ($this->internal_use)
			return $this->renderMenuItemTabsInternal($fieldset);
		else
			return $this->renderMenuItemTabsGitHub($fieldset);
	}

	function renderMenuItemTabsInternal($fieldset): string
	{
		$result = '';

		foreach ($fieldset as $fieldSet) {
			$fieldSetAtt = $fieldSet->attributes();

			if ((int)$fieldSetAtt->deprecated == 0) {
				$is4Pro = (bool)(int)$fieldSetAtt->proversion;
				$class = 'ct_doc_tagset_free';
				if ($is4Pro)
					$class = 'ct_doc_tagset_pro';

				$result .= '<div class="' . $class . '">';

				$result .= '<h3>' . common::translate($fieldSetAtt->label);
				if ($is4Pro)
					$result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h3>';

				$result .= '<p>' . $fieldSetAtt->description . '</p><ul>';

				foreach ($fieldSet->field as $field) {

					$param_att = $field->attributes();

					if (count($param_att) != 0) {
						$result .= '<li><h6>' . $param_att->label . '</h6>' . ($param_att->description != '' ? '<p>' . $param_att->description . '</p>' : '');

						if (!empty($param_att->type)) {
							$result .= $this->renderMenuItemFieldsInternal($field);
						}

						$result .= '</li>';
					}
				}
				$result .= '</ul></div>';
			}
		}
		return $result;
	}

	function renderMenuItemFieldsInternal($field): string
	{
		$result = '';

		$fieldAtt = $field->attributes();
		/*
				switch ($fieldAtt->type) {
					case 'number':

						$result .= '<ul class="ct_doc_param_options">
							<li><b>' . common::translate('COM_CUSTOMTABLES_DEFAULT') . '</b>: ' . $fieldAtt->default . '</li>
		';
						if (!empty($fieldAtt->min))
							$result .= '<li><b>' . common::translate('COM_CUSTOMTABLES_MIN') . '</b>: ' . $fieldAtt->min . '</li>';

						if (!empty($fieldAtt->max))
							$result .= '<li><b>' . common::translate('COM_CUSTOMTABLES_MAX') . '</b>: ' . $fieldAtt->max . '</li>';

						$result .= '</ul>';

						break;

					case 'radio':
						$options = explode(',', $fieldAtt->options);
						$value_example = '';
						//<p>'.common::translate('COM_CUSTOMTABLES_OPTIONS').':</p>
						$result .= '<ul class="ct_doc_param_options">';
						foreach ($options as $option) {
							$parts = explode('|', $option);

							if ($parts[0] == '')
								$result .= '<li>(' . $parts[1] . ' - default)</li>';
							else
								$result .= '<li><b>' . $parts[0] . '</b>: ' . $parts[1] . '</li>';

							if ($value_example == '' && $parts[0] != '')
								$value_example = $parts[0];
						}

						$result .= '</ul>';
						break;
						*/
		/*
					case 'list':

						$options = $fieldAtt->option;
						$value_example = '';

						if (!empty($param_att->example))
							$value_example = $param_att->example;

						$result .= '<p><ul class="ct_doc_param_options">';
						foreach ($options as $option) {
							$option_att = $option->attributes();

							$result .= '<li>';

							if ($option_att->value == '')
								$par = '(Default. ';
							else
								$par = '<b>' . $option_att->value . '</b> - (';

							$result .= $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description . '.' : '') . ')';

							$result .= '</li>';

							if ($value_example == '' and $option_att->value != '')
								$value_example = $option_att->value;
						}

						$result .= '</ul>';
						break;
		*/
		//}

		return $result;
	}

	function renderMenuItemTabsGitHub($fieldset): string
	{
		$result = '';

		foreach ($fieldset as $fieldSet) {
			$fieldSetAtt = $fieldSet->attributes();

			if ((int)$fieldSetAtt->deprecated == 0) {

				$result .= '## ' . common::translate($fieldSetAtt->label) . '<br/>' . $fieldSetAtt->description . '<br/><br/>';
				$count = 1;

				foreach ($fieldSet->field as $field) {

					$param_att = $field->attributes();

					if (count($param_att) != 0) {
						$result .= $count . '. ' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . '<br/>';
						//if (!empty($param_att->type)) {
						//$result .= $this->renderMenuItemFieldsInternal($field);
						//}
					}

					$count += 1;
				}
				$result .= '<br/><br/>';
			}
		}
		return $result;
	}

	function reIndexArray($arrays): array
	{
		$array = array();
		$i = 0;
		foreach ($arrays as $k => $item) {
			$array[$i] = $item;
			unset($arrays[$k]);
			$i++;
		}
		return $array;
	}
}