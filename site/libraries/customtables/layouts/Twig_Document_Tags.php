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
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class Twig_Document_Tags
{
	var CT $ct;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function setmetakeywords($metakeywords): void
	{
		Factory::getApplication()->getDocument()->setMetaData('keywords', $metakeywords);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function setmetadescription($metadescription): void
	{
		Factory::getApplication()->getDocument()->setMetaData('description', $metadescription);
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function setpagetitle($pageTitle): void
	{
		if (defined('_JEXEC'))
			Factory::getApplication()->getDocument()->setTitle(common::translate($pageTitle));
		elseif (defined('WPINC'))
			throw new Exception('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables for WordPress.');
		else
			throw new Exception('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables.');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function setheadtag($tag): void
	{
		if (defined('_JEXEC'))
			Factory::getApplication()->getDocument()->addCustomTag($tag);
		elseif (defined('WPINC'))
			throw new Exception('Warning: The {{ document.setheadtag }} tag is not supported in the current version of the Custom Tables for WordPress.');
		else
			throw new Exception('Warning: The {{ document.setheadtag }} tag is not supported in the current version of the Custom Tables.');
	}

	/**
	 * Processes JavaScript content or URL and adds it to the layout variables.
	 * If the input is a URL to a .js file (with optional query parameters),
	 * it's added to the 'scripts' array.
	 * If it's JavaScript content, it's concatenated to the 'script' variable.
	 *
	 * @param string $linkOrScript Either a URL to a .js file or JavaScript code
	 * @return string Empty string as the content is stored in layout variables
	 *
	 * @example
	 * // Adding a JavaScript file
	 * script('https://example.com/script.js?version=2.3');
	 *
	 * @example
	 * // Adding JavaScript code
	 * script('alert("Hello World");');
	 *
	 * @since 3.5.0
	 */
	function script(string $linkOrScript): string
	{
		//TODO: Consider using defer or async attributes for external scripts when appropriate (currently managed by the browser/CMS defaults)

		// Clean the input string
		$input = trim($linkOrScript);

		// Check if it's a URL
		if (filter_var($input, FILTER_VALIDATE_URL)) {
			// Parse the URL and get the path
			$urlParts = parse_url($input);
			$path = $urlParts['path'];

			// Check if the path ends with .js, ignoring any URL parameters
			if (preg_match('/\.js($|\?)/', $path)) {

				if (!isset($this->ct->LayoutVariables['scripts']))
					$this->ct->LayoutVariables['scripts'] = [];

				$this->ct->LayoutVariables['scripts'][] = $linkOrScript;

				return '';
			}
		}

		if (!isset($this->ct->LayoutVariables['script']))
			$this->ct->LayoutVariables['script'] = $linkOrScript;
		else
			$this->ct->LayoutVariables['script'] .= PHP_EOL . $linkOrScript;
		return '';
	}

	/**
	 * Processes CSS content or URL and adds it to the layout variables.
	 * If the input is a URL to a .css file (with optional query parameters),
	 * it's added to the 'styles' array.
	 * If it's CSS content, it's concatenated to the 'style' variable.
	 *
	 * @param $linkOrStyle
	 * @return string Empty string as the content is stored in layout variables
	 *
	 * @example
	 * // Adding a CSS file
	 * style('https://example.com/styles.css?version=1.2');
	 *
	 * @example
	 * // Adding CSS code
	 * style('.my-class { color: blue; }');
	 *
	 * @since 3.5.0
	 */
	function style($linkOrStyle): string
	{
		// Clean the input string
		$input = trim($linkOrStyle);

		// Check if it's a URL
		if (filter_var($input, FILTER_VALIDATE_URL)) {
			// Parse the URL and get the path
			$urlParts = parse_url($input);
			$path = $urlParts['path'];

			// Check if the path ends with .js, ignoring any URL parameters
			if (preg_match('/\.css($|\?)/', $path)) {

				if (!isset($this->ct->LayoutVariables['styles']))
					$this->ct->LayoutVariables['styles'] = [];

				$this->ct->LayoutVariables['styles'][] = $linkOrStyle;

				return '';
			}
		}

		if (!isset($this->ct->LayoutVariables['style']))
			$this->ct->LayoutVariables['style'] = $linkOrStyle;
		else
			$this->ct->LayoutVariables['style'] .= PHP_EOL . $linkOrStyle;
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	function jslibrary($library): string
	{
		if (defined('_JEXEC')) {

			switch ($library) {
				case 'jquery':
					HTMLHelper::_('jquery.framework');
					break;

				case 'jquery-ui-core':
					// Add jQuery (if not already included)
					HTMLHelper::_('jquery.framework');

					// Add the jQuery UI core library
					$this->ct->LayoutVariables['scripts'][] = 'https://code.jquery.com/ui/1.14.0/jquery-ui.min.js';
					$this->ct->LayoutVariables['styles'][] = 'https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css';

				//$this->ct->document->addScript('https://code.jquery.com/ui/1.14.0/jquery-ui.min.js');

				// Add the jQuery UI CSS
				//$this->ct->document->addStyleSheet('https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css');
			}

			return '';
		} elseif (defined('WPINC')) {
			if (!isset($this->ct->LayoutVariables['jslibrary']))
				$this->ct->LayoutVariables['jslibrary'] = [];

			$this->ct->LayoutVariables['jslibrary'][] = $library;
			return '';
		} else {
			throw new Exception('{{ document.jslibrary() }} not supported in this version of Custom Tables');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.9
	 */
	function layout(string $layoutName = ''): ?string
	{
		//TODO: the use if this tag must be reflected in the dependence tab of the layout used.

		if ($layoutName == '')
			throw new Exception('Warning: The {{ document.layout("layout_name") }} layout name is required.');

		if (!isset($this->ct->Table))
			throw new Exception('{{ document.layout }} - Table not loaded.');

		$layouts = new Layouts($this->ct);
		$layout = $layouts->getLayout($layoutName);

		if (is_null($layouts->tableId))
			throw new Exception('{{ document.layout("' . $layoutName . '") }} - Layout "' . $layoutName . ' not found.');

		if ($layouts->tableId != $this->ct->Table->tableid)
			throw new Exception('{{ document.layout("' . $layoutName . '") }} - Layout Table ID and Current Table ID do not match.');

		if (!empty($layouts->layoutCodeCSS))
			$this->ct->LayoutVariables['style'] = ($this->ct->LayoutVariables['style'] ?? '') . $layouts->layoutCodeCSS;

		if (!empty($layouts->layoutCodeJS))
			$this->ct->LayoutVariables['script'] = ($this->ct->LayoutVariables['script'] ?? '') . $layouts->layoutCodeJS;

		$twig = new TwigProcessor($this->ct, $layout, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
		$number = 1;
		$html_result = '';

		if ($layouts->layoutType == CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM and !is_null($this->ct->Records)) {
			foreach ($this->ct->Records as $row) {
				$row['_number'] = $number;
				$row['_islast'] = $number == count($this->ct->Records);

				try {
					$html_result_layout = $twig->process($row);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}

				$html_result .= $html_result_layout;
				$number++;
			}
		} else {
			try {
				$html_result = $twig->process($this->ct->Table->record);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		return $html_result;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function sitename(): ?string
	{
		return common::getSiteName();
	}

	function languagepostfix(): string
	{
		return $this->ct->Languages->Postfix;
	}

	/**
	 * @throws Exception
	 * @since 3.4.1
	 */
	public function set(string $variable, $value)
	{
		$this->ct->LayoutVariables['globalVariables'][$variable] = $value;
	}

	/**
	 * @throws Exception
	 * @since 3.5.0
	 */
	public function config(string $parameter)
	{
		if (defined('_JEXEC')) {
			if ($parameter == 'googlemapapikey') {
				$joomla_params = ComponentHelper::getParams('com_customtables');
				return $joomla_params->get('googlemapapikey');
			} elseif ($parameter == 'fieldprefix') {

				if ($this->ct->Table !== null)
					return $this->ct->Table->fieldPrefix;

				$prefix = ComponentHelper::getParams('com_customtables');
				if (empty($prefix))
					$prefix = 'ct_';

				if ($prefix == 'NO-PREFIX')
					$prefix = '';

				return $prefix;
			} elseif ($parameter == 'foldertosavelayouts') {
				$joomla_params = ComponentHelper::getParams('com_customtables');
				return $joomla_params->get('folderToSaveLayouts');
			} else {
				throw new Exception('Unknown parameter in document.config(parameter)');
			}
		} elseif (defined('WPINC')) {

			if ($parameter == 'googlemapapikey') {
				return get_option('customtables-googlemapapikey');
			} elseif ($parameter == 'fieldprefix') {
				if ($this->ct->Table !== null)
					return $this->ct->Table->fieldPrefix;

				$prefix = get_option('customtables-fieldprefix');
				if (empty($prefix))
					$prefix = 'ct_';

				if ($prefix == 'NO-PREFIX')
					$prefix = '';

				return $prefix;

			} elseif ($parameter == 'foldertosavelayouts') {
				throw new Exception('WP: "foldertosavelayouts" unsupported parameter in this version of the Custom Tables.');
			} else {
				throw new Exception('Unknown parameter in document.config(parameter)');
			}
		} else {
			throw new Exception('Unknown parameter in document.config()');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.4.1
	 */
	public function get(string $variable)
	{
		return $this->ct->LayoutVariables['globalVariables'][$variable];
	}
}