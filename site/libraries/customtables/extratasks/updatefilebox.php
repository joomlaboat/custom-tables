<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
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
use CustomTables\CT;
use CustomTables\Fields;

class updateFileBox
{
	public static function process(): array
	{
		$ct = new CT;

		$old_typeparams = base64_decode(common::inputGetBase64('old_typeparams', ''));
		if ($old_typeparams == '')
			return array('error' => 'old_typeparams not set');

		$old_params = JoomlaBasicMisc::csv_explode(',', $old_typeparams);

		$new_typeparams = base64_decode(common::inputGetBase64('new_typeparams', ''));
		if ($new_typeparams == '')
			return array('error' => 'new_typeparams not set');

		$new_params = JoomlaBasicMisc::csv_explode(',', $new_typeparams);

		$fieldid = common::inputGetInt('fieldid', 0);
		if ($fieldid == 0)
			return array('error' => 'fieldid not set');


		$fieldRow = Fields::getFieldRow($fieldid);

		$ct->getTable($fieldRow->tableid);

		$stepsize = common::inputGetInt('stepsize', 10);
		$startindex = common::inputGetInt('startindex', 0);

		$count = 0;
		if ($startindex == 0) {
			$count = updateImages::countImages($ct->Table->realtablename, $fieldRow->realfieldname, $ct->Table->realidfieldname);
		}

		$status = updateImages::processImages($ct, $fieldRow, $old_params, $new_params, $startindex, $stepsize);

		return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startindex, 'stepsize' => $stepsize, 'error' => $status);
	}
}
