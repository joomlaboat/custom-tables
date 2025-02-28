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

class Documentation
{
	var bool $internal_use = false;
	var bool $onlyWordpress;
	var bool $hideProVersion;

	function __construct(bool $onlyWordpress = false, bool $hideProVersion = false)
	{
		$this->internal_use = true;
		$this->onlyWordpress = $onlyWordpress;
		$this->hideProVersion = $hideProVersion;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getFieldTypes(): string
	{
		$xml = CTMiscHelper::getXMLData('fieldtypes.xml');
		if (count($xml) == 0 or !isset($xml->type))
			return '';

		return $this->renderFieldTypes($xml->type);
	}

	function renderFieldTypes($types): string
	{
		/*
				if (defined('WPINC')) {
					//temporary
					$root = common::UriRoot();
					if ($root == 'https://ct4.us') {

						$this->renderFieldTypesBetterDocArticles($types);
						return '';
					}
				}
		*/
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
					$result .= '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h4>';

				$result .= '<p>' . $type_att->description . '</p>';

				if (isset($type_att->image)) {
					$result .= '<p><img src="' . $type_att->image . '" alt="' . $type_att->label . '" /></p>';
				}

				if (isset($type_att->link) and isset($type_att->link_title)) {
					$result .= '<p><a href="' . $type_att->link . '" title="' . $type_att->link_title . '" target="_blank">' . $type_att->link_title . '</a></p>';
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

						$result .= $this->renderParametersInternal($params,
							'{{ ',
							'<i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
							'',
							' }}',
							$hideDefaultExample);
						break;
					}
				}

				$result .= '<h5>' . common::translate('COM_CUSTOMTABLES_FIELDTYPE_PUREVALUE') . ':</h5>'
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

					if (isset($editparams_att->link) and isset($editparams_att->link_title)) {
						$result .= '<p><a href="' . $editparams_att->link . '" title="' . $editparams_att->link_title . '" target="_blank">' . $editparams_att->link_title . '</a></p>';
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

			$result .= '<li><code>' . $param_att->label . '</code> (optional) ' . ($param_description != '' ? ' - ' . $param_description : '') . '';

			if (isset($param_att->image)) {
				$result .= '<p><img src="' . $param_att->image . '" alt="' . $param_att->label . '" /></p>';
			}

			if (isset($param_att->link) and isset($param_att->link_title)) {
				$result .= '<p><a href="' . $param_att->link . '" title="' . $param_att->link_title . '" target="_blank">' . $param_att->link_title . '</a></p>';
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
				$result_new .= '<p><b>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . '</b>: <pre class="ct_doc_pre">'
					. $opening_char . $tag_name . $postfix . ($cleanedParamsStr != "" ? '(' . $cleanedParamsStr . ')' : '') . $closing_char . '</pre></p>';
			}
		} else {
			if ($example_values_count > 0) {
				$result_new .= '<p><b>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . '</b>: <pre class="ct_doc_pre">'
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
		//$output = preg_replace('/[^0-9]/', '', $param);
		//if ($output != '')
		//return $param;

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

				if (isset($param_att->link) and isset($param_att->link_title)) {
					$result .= '<p><a href="' . $param_att->link . '" title="' . $param_att->link_title . '" target="_blank">' . $param_att->link_title . '</a></p>';
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

	function renderFieldTypesBetterDocArticles($types)
	{
		foreach ($types as $type) {
			$type_att = $type->attributes();
			$hideDefaultExample = (bool)(int)$type_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$type_att->deprecated;

			$title = $type_att->label;
			$slug = $type_att->ct_name;

			if (!$isDeprecated and ($slug != 'alias' and $slug != 'server' and $slug != 'dummy')) {

				$result = '<h3>Overview</h3>';
				$result .= '<p>' . $type_att->description . '</p>';

				if (isset($type_att->image)) {
					$result .= '<p><img src="' . $type_att->image . '" alt="' . $type_att->label . '" /></p>';
				}

				if (isset($type_att->link) and isset($type_att->link_title)) {
					$result .= '<p><a href="' . $type_att->link . '" title="' . $type_att->link_title . '" target="_blank">' . $type_att->link_title . '</a></p>';
				}

				if (!empty($type->params) and count($type->params) > 0) {
					$content = $this->renderParametersInternal($type->params, '', '', '', '', true);
					if ($content != '')
						$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_FIELDTYPEPARAMS') . '</h4>' . $content;
				}

				$result .= '<hr/>';

				$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_VALUEPARAMS') . ':</h4>'
					. '<p>Basic usage:</p>'
					//. common::translate('COM_CUSTOMTABLES_EXAMPLE')
					. '<pre><code class="language-twig">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i> }}</code></pre>';

				if (!empty($type->valueparams)) {

					$paramList = [];
					foreach ($type->valueparams as $p) {
						foreach ($p->params->param as $param) {
							$param_att = $param->attributes();
							$paramList [] = (string)$param_att->label;
						}
					}

					$result .= '<p>Usage with parameters:</p>'
						. '<pre><code class="language-twig">'
						. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>(' . implode(', ', $paramList
						) . ') }}</code></pre>';

					foreach ($type->valueparams as $p) {
						$params = $p->params;

						$result .= $this->renderParametersInternal($params,
							'{{ ',
							'<i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
							'',
							' }}',
							$hideDefaultExample);
						break;
					}
				}

				$result .= '<hr/>';

				$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_FIELDTYPE_PUREVALUE') . '</h4>'
					. '<p>' . common::translate('COM_CUSTOMTABLES_EXAMPLE') . ':<pre><code class="language-twig">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.value }}'
					. '</code></pre></p>';

				$result .= '<hr/>';

				$result .= '<h4>' . common::translate('COM_CUSTOMTABLES_EDITRECPARAMS') . '</h4>';

				if (!empty($type->editparams)) {

					$editparams_att = $type->editparams->attributes();

					if (isset($editparams_att->image)) {
						$result .= '<p><img src="' . $editparams_att->image . '" alt="' . $editparams_att->label . '" /></p>';
					}

					if (isset($editparams_att->description)) {
						$result .= '<p>' . $editparams_att->description . '</p>';
					}

					if (isset($editparams_att->link) and isset($editparams_att->link_title)) {
						$result .= '<p><a href="' . $editparams_att->link . '" title="' . $editparams_att->link_title . '" target="_blank">' . $editparams_att->link_title . '</a></p>';
					}
				}

				$result .= '<p>Basic usage:</p>'
					. '<pre><code class="language-twig">'
					. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.edit }}</code></pre>';


				if (!empty($type->editparams)) {

					$paramList = [];
					foreach ($type->editparams as $p) {
						foreach ($p->params->param as $param) {
							$param_att = $param->attributes();
							$paramList [] = (string)$param_att->label;
						}
					}

					$result .= '<p>Usage with parameters:</p>'
						. '<pre><code class="language-twig">'
						. '{{ <i>' . str_replace(' ', '', common::translate('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.edit(' . implode(', ', $paramList
						) . ') }}</code></pre>';

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
						$result .= '<h4>' . $params_att->label . ':</h4>';
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
				$this->create_betterdocs_article('field-types', null, $slug, $title, $result);
			}
		}
	}

	function create_betterdocs_article($parentCategorySlug, $childCategorySlug, $slug, $title, $content)
	{
		// Check if post exists by slug
		$existing_post = get_page_by_path($slug, OBJECT, 'docs');

		if (!empty($existing_post)) {
			echo "Article with slug '{$slug}' already exists with ID: {$existing_post->ID}<br/>";
			return false;

		}

		// Get the category by slug
		$parent_category = get_term_by('slug', $parentCategorySlug, 'doc_category');
		if (!$parent_category) {
			die("Category '" . $parentCategorySlug . "' not found");
		}

		if ($childCategorySlug !== null) {
			$child_category = get_term_by('slug', $childCategorySlug, 'doc_category');
			if (!$child_category) {
				die("Category '" . $childCategorySlug . "' not found");
			}
		}

		// Prepare post data
		$post_data = array(
			'post_title' => $title,
			'post_content' => $content,
			'post_status' => 'publish',
			'post_type' => 'docs',
			'post_name' => $slug,  // Set the slug
			'post_author' => 1, // Default admin user ID
		);

		// Insert the post
		$post_id = wp_insert_post($post_data);

		if (is_wp_error($post_id)) {
			die("Error creating article: " . $post_id->get_error_message());
		}

		if ($childCategorySlug !== null) {
			// Set both parent and child categories
			wp_set_object_terms($post_id, array($parent_category->term_id, $child_category->term_id), 'doc_category');
		} else {
			// Set the category
			wp_set_object_terms($post_id, $parent_category->term_id, 'doc_category');
		}

		echo "Article created successfully with ID: " . $post_id . '<br/>';
		return true;
	}

	function getLayoutTags(): string
	{
		$xml = CTMiscHelper::getXMLData('tags.xml');

		if (count($xml) == 0)
			return '';

		return $this->renderLayoutTagSets($xml->tagset);
	}

	function renderLayoutTagSets($tagsets): string
	{
		/*
				if (defined('WPINC')) {
					//temporary
					$root = common::UriRoot();
					if ($root == 'https://ct4.us') {

						$this->renderLayoutTagBetterDocArticles($tagsets);
						return '';
					}
				}
		*/
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
					$result .= '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

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
					$result .= '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

				$result .= '</h4>';

				if ($tagsetname != 'plugins') {
					$result .= '<p>' . $tag_att->description . '</p>';

					if (isset($tag_att->image)) {
						$result .= '<p><img src="' . $tag_att->image . '" alt="' . $tag_att->label . '" /></p>';
					}

					if (isset($type_att->link) and isset($type_att->link_title)) {
						$result .= '<p><a href="' . $type_att->link . '" title="' . $type_att->link_title . '" target="_blank">' . $type_att->link_title . '</a></p>';
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

	function renderLayoutTagBetterDocArticles($tagSets)
	{
		foreach ($tagSets as $tagSet) {
			$tagSetAtt = $tagSet->attributes();
			$isDeprecated = (bool)(int)$tagSetAtt->deprecated;

			if (!$isDeprecated)
				$this->renderTagsBetterDocArticles($tagSet->tag, $tagSetAtt->name);
		}
	}

	function renderTagsBetterDocArticles($tags, $tagsetname)
	{
		$count = 1;
		foreach ($tags as $tag) {
			$tag_att = $tag->attributes();
			$result = '';
			$hidedefaultexample = (bool)(int)$tag_att->hidedefaultexample;
			$isDeprecated = (bool)(int)$tag_att->deprecated;

			if (!$isDeprecated) {// and $tag_att->name == 'goback'

				if ($tagsetname != 'plugins') {

					$result .= '<p>' . $tag_att->description . '</p>';

					if (isset($tag_att->image)) {
						$result .= '<p><img src="' . $tag_att->image . '" alt="' . $tag_att->label . '" /></p>';
					}

					if (isset($type_att->link) and isset($type_att->link_title)) {
						$result .= '<p><a href="' . $type_att->link . '" title="' . $type_att->link_title . '" target="_blank">' . $type_att->link_title . '</a></p>';
					}

					$result .= '<p><b>Basic usage:</b></p>'
						. '<pre><code class="language-twig">'
						. '{{ ' . $tag_att->twigclass . '.' . $tag_att->name . ' }}</code></pre>';


					if (!empty($tag->params) and count($tag->params) > 0) {

						$paramList = [];

						foreach ($tag->params->param as $param) {
							$param_att = $param->attributes();
							$paramList [] = (string)$param_att->label;
						}

						$result .= '<p><b>Usage with parameters:</b></p>'
							. '<pre><code class="language-twig">'
							. '{{ ' . $tag_att->twigclass . '.' . $tag_att->name . '(' . implode(', ', $paramList) . ') }}</code></pre>';


						$content = $this->renderParametersInternal($tag->params,
							'{{ ',
							'<i>' . $tag_att->twigclass . '.' . $tag_att->name . '</i>',
							'',
							' }}',
							$hidedefaultexample);

						if ($content != '')
							$result .= '<p><b>' . common::translate('COM_CUSTOMTABLES_PARAMS') . ':</b></p>' . $content;
					}
				}

				if ($tag_att->example !== null) {

					$result .= '<p><b>Example:</b></p>'
						. '<pre><code class="language-twig">' . $tag_att->example . '</code></pre>';
				}

				$label = '{{ ' . $tag_att->twigclass . '.' . $tag_att->name . ' }} - ' . $tag_att->label;

				$this->create_betterdocs_article($tag_att->twigclass, null, $tag_att->twigclass . '-' . $tag_att->name, $label, $result);
			}
		}
	}

	function getMenuItems(): string
	{
		$componentName = 'com_customtables';
		$path = CUSTOMTABLES_ABSPATH . 'components' . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'tmpl';

		$xml = CTMiscHelper::getXMLData('default.xml', $path);

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

				if (defined('_JEXEC'))
					$result .= '<h3>' . common::translate($fieldSetAtt->label);
				else
					$result .= '<h3>' . $fieldSetAtt->label;

				if ($is4Pro)
					$result .= '<div class="ct_doc_pro_label"><a href="https://ct4.us/product/custom-tables-pro-for-joomla/" target="_blank">' . common::translate('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

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

				if (defined('_JEXEC'))
					$result .= '## ' . common::translate($fieldSetAtt->label) . '<br/>' . $fieldSetAtt->description . '<br/><br/>';
				else
					$result .= '## ' . $fieldSetAtt->label . '<br/>' . $fieldSetAtt->description . '<br/><br/>';

				$count = 1;

				foreach ($fieldSet->field as $field) {

					$param_att = $field->attributes();

					if (count($param_att) != 0) {
						$result .= $count . '. ' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . '<br/>';
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