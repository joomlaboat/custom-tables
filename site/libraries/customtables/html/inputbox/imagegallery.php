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
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use CT_FieldTypeTag_imagegallery;

class InputBox_imagegallery extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(): string
	{
		if (!$this->ct->isRecordNull($this->row)) {
			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_gallery.php');

			$result = '';
			$listing_id = $this->row[$this->ct->Table->realidfieldname] ?? null;
			$getGalleryRows = CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $listing_id);
			$image_prefix = '';

			if (isset($pair[1]) and (int)$pair[1] < 250)
				$img_width = (int)$pair[1];
			else
				$img_width = 250;

			$imageSRCList = CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $image_prefix, $this->field->fieldname,
				$this->field->params, $this->ct->Table->tableid);

			if (count($imageSRCList) > 0) {

				$result .= '<div style="width:100%;overflow:scroll;border:1px dotted grey;background-image: url(\'' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/bg.png\');">

		<table><tbody><tr>';

				foreach ($imageSRCList as $img) {
					$result .= '<td>';
					$result .= '<a href="' . $img . '" target="_blank"><img src="' . $img . '" style="width:' . $img_width . 'px;" />';
					$result .= '</td>';
				}

				$result .= '</tr></tbody></table>
		</div>';

			} else
				return 'No Images';

			return $result;
		}
		return '';
	}
}