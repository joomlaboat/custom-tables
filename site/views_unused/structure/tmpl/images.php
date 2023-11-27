<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\DataTypes\Tree;
use Joomla\CMS\Uri\Uri;

JHTML::stylesheet("default.css", Uri::root(true) . "/components/com_customtables/views/catalog/tmpl/");

$catalogResult = '<table style="width:100%;text-align:center">';

$tr = 0;
$number_of_columns = 3;
$content_width = 100;
$column_width = floor($content_width / $number_of_columns);

$imagemethods = new CustomTablesImageMethods;

$image_prefix = '_esthumb';
$imageparams = '';
if ($this->image_prefix == '_original') {
	$image_prefix = '_original';
} else {
	if (count($this->rows) > 0) {
		$row = $this->rows[0];
		$imageparams = Tree::getHeritageInfo($row['parentid'], 'imageparams');
		$type_params = JoomlaBasicMisc::csv_explode(',', $imageparams, '"', false);
		$cleanOptions = $imagemethods->getCustomImageOptions($type_params[0]);

		if (count($cleanOptions) > 0) {
			foreach ($cleanOptions as $imgSize) {
				if ($this->image_prefix == $imgSize[0])
					$image_prefix = $imgSize[0];
			}
		}
	}
}

foreach ($this->rows as $row) {
	if ($tr == 0)
		$catalogResult .= '<tr>';

	$imagefile_ = 'images/esoptimages/' . $image_prefix . '_' . $row['image'];

	if (file_exists($imagefile_ . '.jpg'))
		$imageFile = $imagefile_ . '.jpg';
	elseif (file_exists($imagefile_ . '.png'))
		$imageFile = $imagefile_ . '.png';
	elseif (file_exists($imagefile_ . '.webp'))
		$imageFile = $imagefile_ . '.webp';
	else
		$imageFile = '';

	if ($imageFile != '') {
		$catalogResult .= '<td style="width:' . $column_width . '%;vertical-align:top;text-align:center">';

		if ($this->esfieldname != '') {
			$aLink = 'index.php?option=com_customtables&view=catalog&';

			if ($this->ct->Params->pageLayout != '')
				$aLink .= 'pagelayout=' . $this->ct->Params->pageLayout . '&';

			if ($this->ct->Params->ItemId != '')
				$aLink .= 'Itemid=' . $this->ct->Params->ItemId . '&';
			else
				$aLink .= 'Itemid=' . common::inputGetInt('Itemid', 0) . '&';

			$aLink .= '&establename=' . $this->ct->Table->tablename;
			$aLink .= '&filter=' . $this->esfieldname . urlencode('=') . $this->optionname;

			if ($row['optionname'] != '')
				$aLink .= '.' . $row['optionname'];

			$catalogResult .= '<a href="' . $aLink . '"><img src="' . $imageFile . '" border="0" /></a>';
		} else
			$catalogResult .= '<img src="' . $imageFile . '" border="0" />';

		$catalogResult .= '</td>';

		$tr++;

		if ($tr == $number_of_columns) {
			$catalogResult .= '</tr>';

			if ($this->row_break)
				$catalogResult .= '<tr><td colspan="' . $number_of_columns . '"><hr /></td></tr>';

			$tr = 0;
		}
	}
}

$catalogResult .= '</tbody>
</table>';

if ((int)$this->ct->Params->allowContentPlugins == 1)
	$catalogResult = JoomlaBasicMisc::applyContentPlugins($catalogResult);

echo $catalogResult;
 