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

use CustomTablesImageMethods;
use DateInvalidTimeZoneException;
use Exception;
use InvalidArgumentException;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$log_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR;

if (file_exists($log_path . 'log.php'))
	require_once($log_path . 'log.php');

class Value
{
	var CT $ct;
	var Field $field;
	var ?array $row;

	function __construct(CT &$ct)
	{
		$this->ct = &$ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function renderValue(array $fieldRow, ?array $row, array $option_list, bool $parseParams = true)
	{
		$this->field = new Field($this->ct, $fieldRow, $row, $parseParams);
		$rfn = $this->field->realfieldname;
		$this->row = $row;
		$rowValue = $row[$rfn] ?? null;

		//Try to instantiate a class dynamically
		$aliasMap = [
			'userid' => 'user',
			'sqljoin' => 'tablejoin',
			'records' => 'tablejoinlist'
		];

		$fieldTypeShort = $this->field->type;
		if (key_exists($fieldTypeShort, $aliasMap))
			$fieldTypeShort = $aliasMap[$fieldTypeShort];

		$additionalFile = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
			. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . $fieldTypeShort . '.php';

		if (file_exists($additionalFile)) {
			require_once($additionalFile);
			$className = '\CustomTables\Value_' . $fieldTypeShort;
			$ValueRenderer = new $className($this->ct, $this->field, $rowValue, $option_list);
		}

		switch ($this->field->type) {
			case 'int':
			case 'viewcount':
				$thousand_sep = $option_list[0] ?? (($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? '' : '');
				return number_format((int)$rowValue, 0, '', $thousand_sep);

			case 'float':

				$params_value = (($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] != '') ? (int)$this->field->params[0] : 2);
				$decimals = $params_value;
				$decimals_sep = '.';
				$thousand_sep = '';
				if (count($option_list) > 0) {
					$decimals = (int)($option_list[0] ?? 0);
					$decimals_sep = $option_list[1] ?? '.';
					$thousand_sep = $option_list[2] ?? '';
				}
				return number_format((float)$rowValue, $decimals, $decimals_sep, $thousand_sep);

			case 'ordering':
				return $this->orderingProcess($rowValue);

			case 'id':
			case 'md5':
				//case 'phponadd':
				//case 'phponchange':
				//case 'phponview':
			case 'alias':
			case 'radio':
			case 'server':
			case 'email':
			case 'url':
				return $rowValue;
			case 'googlemapcoordinates':

				if (count($option_list) == 0 or $option_list[0] == 'map') {

					$parts = explode(',', $rowValue ?? '');
					$lat = $parts[0];
					$lng = $parts[1] ?? '';
					if ($lat == '' or $lng == '')
						return '';

					$width = $option_list[1] ?? '320px';
					if (!str_contains($width, '%') and !str_contains($width, 'px'))
						$width .= 'px';

					$height = $option_list[2] ?? '240px';
					if (!str_contains($height, '%') and !str_contains($height, 'px'))
						$height .= 'px';

					$zoom = (count($option_list) > 3 ? (int)$option_list[3] ?? '10' : '10');
					if ($zoom == 0)
						$zoom = 10;

					$boxId = 'ct' . $this->field->fieldname . '_map' . $this->row[$this->ct->Table->realidfieldname];

					return '<div id="' . $boxId . '" style="width:' . $width . ';height:' . $height . '">'
						. '</div>

<script>
            window.addEventListener("load", function() {
				ctValue_googlemapcoordinates("' . $boxId . '", ' . $lat . ',' . $lng . ',' . $zoom . ');
            });
</script>';

				} elseif ($option_list[0] == 'latitude')
					return explode(',', $rowValue)[0];
				elseif ($option_list[0] == 'longitude') {
					$parts = explode(',', $rowValue);
					return ($parts[1] ?? '');
				}
				return $rowValue;

			case 'multilangstring':
			case 'multilangtext':
				return $this->multilingual($option_list);

			case 'text':
			case 'string':
				return BaseValue::TextFunctions($rowValue, $option_list);

			case 'color':
				return $this->colorProcess($rowValue, $option_list);

			case 'blob':
			case 'file':
			case 'imagegallery':
			case 'records':
			case 'user':
			case 'userid':
			case 'sqljoin':
			case 'log':
			case 'article':
			case 'image':
				return $ValueRenderer->render();

			case 'signature':

				if (defined('WPINC'))
					return 'CustomTables for WordPress: "signature" field type is not available yet.';

				$siteName = common::getSiteName();
				$ImageFolderArray = CustomTablesImageMethods::getImageFolder($this->field->params);
				$format = $this->field->params[3] ?? 'png';

				if ($format == 'jpeg')
					$format = 'jpg';

				$imageFileWeb = URI::root() . $ImageFolderArray['web'] . '/' . $rowValue . '.' . $format;
				$imageFile = $ImageFolderArray['path'] . DIRECTORY_SEPARATOR . $rowValue . '.' . $format;

				if (file_exists($imageFile)) {
					$width = (($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] != '') ? $this->field->params[0] ?? '300px' : '300px');
					if (((string)intval($width)) == $width)
						$width .= 'px';

					$height = $this->field->params[1] ?? '150px';
					if (((string)intval($height)) == $height)
						$height .= 'px';

					return '<img src="' . $imageFileWeb . '" alt="' . $siteName . '" title="' . $siteName . '" style="width:' . $width . ';height:' . $height . ';" />';
				}
				return null;

			case 'filebox':

				if (defined('WPINC'))
					return 'CustomTables for WordPress: "filebox" field type is not available yet.';

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
					. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

				$FileBoxRows = InputBox_filebox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $this->row[$this->ct->Table->realidfieldname]);

				if (($option_list[0] ?? '') == '_count')
					return count($FileBoxRows);

				return InputBox_filebox::process($FileBoxRows, $this->field, $this->row[$this->ct->Table->realidfieldname], $option_list);

			case 'usergroup':
				if (defined('_JEXEC'))
					return CTUser::showUserGroup_Joomla((int)$rowValue);
				elseif (defined('WPINC'))
					return $this->ct->Env->user->showUserGroup_WordPress((int)$rowValue);
				else
					return null;

			case 'usergroups':
				return $this->ct->Env->user->showUserGroups($rowValue);

			case 'filelink':
				$processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
					. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php';
				require_once($processor_file);

				if (empty($rowValue))
					return null;

				return Value_file::process($rowValue, $this->field, $option_list, $this->row[$this->ct->Table->realidfieldname], 0);

			case 'language':
				$lang = new Languages();
				foreach ($lang->LanguageList as $language) {
					if ($language->language === $rowValue)
						return $language->caption;
				}
				return '';

			case 'checkbox':
				if ((int)$rowValue)
					return common::translate('COM_CUSTOMTABLES_YES');
				else
					return common::translate('COM_CUSTOMTABLES_NO');

			case 'date':

				return $this->dataProcess($rowValue, $option_list);

			case 'time':

				$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;
				require_once($path . 'time.php');

				$seconds = InputBox_Time::ticks2Seconds($rowValue, $this->field->params);
				return InputBox_Time::seconds2FormattedTime($seconds, $option_list[0] ?? '');

			case 'changetime':
			case 'creationtime':
			case 'lastviewtime':
				return $this->timeProcess($rowValue, $option_list);
			case 'virtual':
				return $this->virtualProcess();
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function orderingProcess($value): string
	{
		if (defined('WPINC')) {
			return 'orderingProcess not yet supported by WordPress version of the Custom Tables.';
		}

		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return $value;

		if ($this->ct->Env->isPlugin)
			return $value;

		if (!in_array($this->ct->LayoutVariables['layout_type'],
			[CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG, CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE, CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM]))//If not Simple Catalog and not Catalog Page and not Catalog Item
			return $value;

		$isEditable = $this->ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT);
		if (!$isEditable)
			return '';

		$orderByPair = explode(' ', $this->ct->Ordering->orderby ?? '');

		if ($orderByPair[0] == $this->field->realfieldname)
			$iconClass = '';
		else
			$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'COM_CUSTOMTABLES_FIELD_ORDERING_DISABLED');

		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$result = '<span class="sortable-handler' . $iconClass . '"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>';
		else
			$result = '<span class="sortable-handler' . $iconClass . '"><i class="ctIconOrdering"></i></span>';

		if ($orderByPair[0] == $this->field->realfieldname) {

			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$result .= '<input type="text" name="order[]" size="5" value="' . htmlspecialchars($value ?? '') . '" class="width-20 text-area-order hidden" />';
			else
				$result .= '<input type="text" style="display:none" name="order[]" size="5" value="' . htmlspecialchars($value ?? '') . '" class="width-20 text-area-order " />';

			$result .= '<input type="checkbox" style="display:none" name="cid[]" value="' . $this->row[$this->ct->Table->realidfieldname] . '" class="width-20 text-area-order " />';

			$this->ct->LayoutVariables['ordering_field_type_found'] = true;
		}
		return $result;
	}

	protected function multilingual(array $option_list): ?string
	{
		$specific_lang = $option_list[4] ?? '';

		$postfix = ''; //first language in the list
		if ($specific_lang != '') {
			$i = 0;
			foreach ($this->ct->Languages->LanguageList as $l) {
				if ($l->sef == $specific_lang) {
					if ($i != 0)
						$postfix = '_' . $specific_lang;

					break;
				}
				$i++;
			}
		} else
			$postfix = $this->ct->Languages->Postfix; //front-end default language

		$fieldname = $this->field->realfieldname . $postfix;
		$rowValue = $this->row[$fieldname] ?? null;

		return BaseValue::TextFunctions($rowValue, $option_list);
	}


	protected function colorProcess($value, array $option_list): string
	{
		if ($value == '')
			$value = '000000';

		if (($option_list[0] ?? '') == "rgba") {
			return CTMiscHelper::colorStringValueToCSSRGB($value);
		} else
			return "#" . $value;
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 *
	 * @since 3.4.5
	 */
	protected function dataProcess($rowValue, array $option_list): string
	{
		if ($rowValue == '' or $rowValue == '0000-00-00' or $rowValue == '0000-00-00 00:00:00')
			return '';

		if (($option_list[0] ?? '') != '') {
			return common::formatDate($rowValue, $option_list[0]);
		} else {

			if ($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] == 'datetime')
				return common::formatDate($rowValue);
			else
				return common::formatDate($rowValue, 'Y-m-d');
		}
	}

	/**
	 * Formats a date/time string or returns a Unix timestamp based on the provided options.
	 *
	 * @param string|null $value The date/time string to be formatted. If null, an empty string is returned.
	 * @param array $option_list An array of options for formatting the date/time.
	 *                        If the first element is 'timestamp', the Unix timestamp will be returned.
	 *                        Otherwise, it should be a valid date/time format string.
	 *
	 * @return string The formatted date/time string or Unix timestamp.
	 *
	 * @throws InvalidArgumentException|DateInvalidTimeZoneException If the options are invalid.
	 *
	 * @since 3.2.9
	 */
	protected function timeProcess(?string $value, array $option_list = []): string
	{
		if ($value === null) {
			return '';
		}

		$format = $option_list[0] ?? 'Y-m-d H:i:s';

		if ($value === '0000-00-00 00:00:00') {
			return '';
		}

		return common::formatDate($value, $format);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function virtualProcess(): string
	{
		if (count($this->field->params) == 0)
			return '';

		$layout = str_replace('****quote****', '"', ($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] != '') ? $this->field->params[0] : '');
		$layout = str_replace('****apos****', '"', $layout);

		try {
			$twig = new TwigProcessor($this->ct, $layout, false, false, true);
			$value = $twig->process($this->row);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return $value;
	}
}

abstract class BaseValue
{
	protected CT $ct;
	protected Field $field;
	protected $rowValue;
	protected array $option_list;

	function __construct(CT &$ct, Field $field, $rowValue, array $option_list = [])
	{
		$this->ct = $ct;
		$this->field = $field;
		$this->rowValue = $rowValue;
		$this->option_list = $option_list;
	}

	public static function TextFunctions(?string $content, array $parameters): ?string
	{
		if ($content === null)
			return null;

		if (count($parameters) == 0)
			return $content;

		switch ($parameters[0]) {
			case "chars" :
			case "words" :

				if (isset($parameters[1]))
					$count = (int)$parameters[1];
				else
					$count = -1;

				if (isset($parameters[2]) and $parameters[2] == 'true')
					$cleanBraces = true;
				else
					$cleanBraces = false;

				if (isset($parameters[3]) and $parameters[3] == 'true')
					$cleanQuotes = true;
				else
					$cleanQuotes = false;

				if ($parameters[0] == "chars")
					return CTMiscHelper::charsTrimText($content, $count, $cleanBraces, $cleanQuotes);
				else
					return CTMiscHelper::wordsTrimText($content, $count, $cleanBraces, $cleanQuotes);

			case "firstimage" :
				return CTMiscHelper::getFirstImage($content);

			default:
				return $content;
		}
	}
}