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

use CustomTablesImageMethods;
use Exception;
use Joomla\CMS\HTML\HTMLHelper;

use CT_FieldTypeTag_file;
use CT_FieldTypeTag_image;
use CT_FieldTypeTag_imagegallery;
use CT_FieldTypeTag_log;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$types_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR;

if (file_exists($types_path . '_type_file.php')) {
	require_once($types_path . '_type_file.php');
	require_once($types_path . '_type_gallery.php');
	require_once($types_path . '_type_image.php');
	require_once($types_path . '_type_log.php');
}

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
	function renderValue(array $fieldrow, ?array $row, array $option_list, bool $parseParams = true)
	{
		$this->field = new Field($this->ct, $fieldrow, $row, $parseParams);
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
						. '</div><script>ctValue_googlemapcoordinates("' . $boxId . '", ' . $lat . ',' . $lng . ',' . $zoom . ')</script>';

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
				return $this->TextFunctions($rowValue, $option_list);

			case 'blob':
				return $this->blobProcess($rowValue, $option_list);

			case 'color':
				return $this->colorProcess($rowValue, $option_list);

			case 'file':

				return CT_FieldTypeTag_file::process($rowValue, $this->field, $option_list, $row[$this->ct->Table->realidfieldname], false);

			case 'image':
				$imageSRC = '';
				$imagetag = '';

				CT_FieldTypeTag_image::getImageSRCLayoutView($option_list, $rowValue, $this->field->params, $imageSRC, $imagetag);

				return $imagetag;

			case 'signature':

				$imageSRC = '';
				$imagetag = '';
				CT_FieldTypeTag_image::getImageSRCLayoutView($option_list, $rowValue, $this->field->params, $imageSRC, $imagetag);

				$conf = Factory::getConfig();
				$sitename = $conf->get('config.sitename');

				$ImageFolder_ = CustomTablesImageMethods::getImageFolder($this->field->params);

				$ImageFolderWeb = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder_);
				$ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);

				$imageSRC = '';
				$imagetag = '';

				$format = $this->field->params[3] ?? 'png';

				if ($format == 'jpeg')
					$format = 'jpg';

				$imageFileWeb = URI::root() . $ImageFolderWeb . '/' . $rowValue . '.' . $format;
				$imageFile = $ImageFolder . DIRECTORY_SEPARATOR . $rowValue . '.' . $format;

				if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imageFile)) {
					$width = (($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] != '') ? $this->field->params[0] ?? '300px' : '300px');
					if (((string)intval($width)) == $width)
						$width .= 'px';

					$height = $this->field->params[1] ?? '150px';
					if (((string)intval($height)) == $height)
						$height .= 'px';

					return '<img src="' . $imageFileWeb . '" alt="' . $sitename . '" title="' . $sitename . '" style="width:' . $width . ';height:' . $height . ';" />';
				}
				return null;

			case 'article':
				//case 'multilangarticle':
				return $this->articleProcess($rowValue, $option_list);

			case 'imagegallery':

				$getGalleryRows = CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $this->row[$this->ct->Table->realidfieldname]);

				if ($option_list[0] ?? '' == '_count')
					return count($getGalleryRows);

				$imageSRCList = CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $option_list[0] ?? '', $this->field->fieldname, $this->field->params, $this->ct->Table->tableid, true);
				$imageTagList = CT_FieldTypeTag_imagegallery::getImageGalleryTagList($imageSRCList);
				return implode('', $imageTagList);

			case 'filebox':

				require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
					. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

				$FileBoxRows = InputBox_filebox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $this->row[$this->ct->Table->realidfieldname]);

				if (($option_list[0] ?? '') == '_count')
					return count($FileBoxRows);

				return InputBox_filebox::process($FileBoxRows, $this->field, $this->row[$this->ct->Table->realidfieldname], $option_list);

			case 'sqljoin':
			case 'userid':
			case 'user':
			case 'records':
				return $ValueRenderer->render();

			case 'usergroup':
				return CTUser::showUserGroup((int)$rowValue);

			case 'usergroups':
				return CTUser::showUserGroups($rowValue);

			case 'filelink':
				$processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
				require_once($processor_file);
				return CT_FieldTypeTag_file::process($rowValue, $this->field, $option_list, $this->row[$this->ct->Table->realidfieldname], 0);

			case 'language':
				$lang = new Languages();
				foreach ($lang->LanguageList as $language) {
					if ($language->language === $rowValue)
						return $language->caption;
				}
				return '';

			case 'log':
				return CT_FieldTypeTag_log::getLogVersionLinks($this->ct, $rowValue, $this->row);

			case 'checkbox':
				if ((int)$rowValue)
					return common::translate('COM_CUSTOMTABLES_YES');
				else
					return common::translate('COM_CUSTOMTABLES_NO');

			case 'date':
			case 'lastviewtime':
				return $this->dataProcess($rowValue, $option_list);

			case 'time':

				$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;
				require_once($path . 'time.php');

				$seconds = InputBox_Time::ticks2Seconds($rowValue, $this->field->params);
				return InputBox_Time::seconds2FormattedTime($seconds, $option_list[0] ?? '');

			case 'changetime':
			case 'creationtime':
				return $this->timeProcess($rowValue, $option_list);
			case 'virtual':
				return $this->virtualProcess();
		}
		return null;
	}

	protected function orderingProcess($value): string
	{
		if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
			return $value;

		if ($this->ct->Env->isPlugin)
			return $value;

		if (!in_array($this->ct->LayoutVariables['layout_type'], [1, 5, 6]))//If not Simple Catalog and not Catalog Page and not Catalog Item
			return $value;

		$edit_userGroup = (int)$this->ct->Params->editUserGroups;
		$isEditable = CTUser::checkIfRecordBelongsToUser($this->ct, $edit_userGroup);
		if (!$isEditable)
			return '';

		$edit_userGroup = (int)$this->ct->Params->editUserGroups;
		$isEditable = CTUser::checkIfRecordBelongsToUser($this->ct, $edit_userGroup);

		$orderby_pair = explode(' ', $this->ct->Ordering->orderby ?? '');

		if ($orderby_pair[0] == $this->field->realfieldname and $isEditable)
			$iconClass = '';
		else
			$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'COM_CUSTOMTABLES_FIELD_ORDERING_DISABLED');

		if ($this->ct->Env->version < 4)
			$result = '<span class="sortable-handler' . $iconClass . '"><i class="ctIconOrdering"></i></span>';
		else
			$result = '<span class="sortable-handler' . $iconClass . '"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>';

		if ($orderby_pair[0] == $this->field->realfieldname) {

			if ($this->ct->Env->version < 4)
				$result .= '<input type="text" style="display:none" name="order[]" size="5" value="' . htmlspecialchars($value ?? '') . '" class="width-20 text-area-order " />';
			else
				$result .= '<input type="text" name="order[]" size="5" value="' . htmlspecialchars($value ?? '') . '" class="width-20 text-area-order hidden" />';

			$result .= '<input type="checkbox" style="display:none" name="cid[]" value="' . $this->row[$this->ct->Table->realidfieldname] . '" class="width-20 text-area-order " />';

			$this->ct->LayoutVariables['ordering_field_type_found'] = true;
		}
		return $result;
	}

	protected function multilingual(array $option_list)
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

		return $this->TextFunctions($rowValue, $option_list);
	}

	public function TextFunctions($content, $parameters)
	{
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

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function blobProcess(?string $value, array $option_list): ?string
	{
		if ((int)$value == 0)
			return null;

		if ($this->field->type != 'blob' and $this->field->type != 'tinyblob' and $this->field->type != 'mediumblob' and $this->field->type != 'longblob')
			return self::TextFunctions($value, $option_list);

		$filename = CT_FieldTypeTag_file::getBlobFileName($this->field, $value, $this->row, $this->ct->Table->fields);

		return CT_FieldTypeTag_file::process($filename, $this->field, $option_list, $this->row[$this->ct->Table->realidfieldname], false, intval($value));
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
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function articleProcess($rowValue, array $option_list)
	{
		if (isset($option_list[0]) and $option_list[0] != '')
			$article_field = $option_list[0];
		else
			$article_field = 'title';

		$article = $this->getArticle((int)$rowValue, $article_field);

		if (isset($option_list[1])) {
			$opts = str_replace(':', ',', $option_list[1]);
			return $this->TextFunctions($article, explode(',', $opts));
		} else
			return $article;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function getArticle($articleId, $field)
	{
		//$query = 'SELECT ' . $field . ' FROM #__content WHERE id=' . (int)$articleId . ' LIMIT 1';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('id', (int)$articleId);

		$rows = database::loadAssocList('#__content', [$field], $whereClause, null, null, 1);

		if (count($rows) != 1)
			return ""; //return nothing if article not found

		return $rows[0][$field];
	}

	protected function dataProcess($rowValue, array $option_list): string
	{
		if ($rowValue == '' or $rowValue == '0000-00-00' or $rowValue == '0000-00-00 00:00:00')
			return '';

		$PHPDate = strtotime($rowValue);

		if (($option_list[0] ?? '') != '') {
			if ($option_list[0] == 'timestamp')
				return $PHPDate;

			return gmdate($option_list[0], $PHPDate);
		} else
			return HTMLHelper::date($PHPDate);
	}

	protected function timeProcess(?string $value, array $option_list): string
	{
		if ($value === null)
			return '';

		$PHPDate = strtotime($value);
		if (isset($option_list[0]) and $option_list[0] != '') {
			if ($option_list[0] == 'timestamp')
				return $PHPDate;

			return gmdate($option_list[0], $PHPDate);
		} else {
			if ($value == '0000-00-00 00:00:00')
				return '';

			return HTMLHelper::date($PHPDate);
		}
	}

	protected function virtualProcess(): string
	{
		if (count($this->field->params) == 0)
			return '';

		$layout = str_replace('****quote****', '"', ($this->field->params !== null and count($this->field->params) > 0 and $this->field->params[0] != '') ? $this->field->params[0] : '');
		$layout = str_replace('****apos****', '"', $layout);

		try {
			$twig = new TwigProcessor($this->ct, $layout, false, false, true);
			$value = @$twig->process($this->row);

			if ($twig->errorMessage !== null)
				return 'virtualProcess Error:' . $twig->errorMessage;
		} catch (Exception $e) {
			return 'virtualProcess Error:' . $e->getMessage();
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
}