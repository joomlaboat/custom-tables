<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;

class updateFileBox
{
    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function process(int $tableId): array
    {
        $stepSize = common::inputGetInt('stepsize', 10);
        $startIndex = common::inputGetInt('startindex', 0);

        $old_typeparams = base64_decode(common::inputGetBase64('old_typeparams', ''));
        if ($old_typeparams == '')
            return array('error' => 'old_typeparams not set');

        $old_params = CTMiscHelper::csv_explode(',', $old_typeparams);

        $new_typeparams = base64_decode(common::inputGetBase64('new_typeparams', ''));
        if ($new_typeparams == '')
            return array('error' => 'new_typeparams not set');

        $new_params = CTMiscHelper::csv_explode(',', $new_typeparams);

        $fieldid = common::inputGetInt('fieldid', 0);
        if ($fieldid == 0)
            return array('error' => 'fieldid not set');

        $ct = new CT;
        $ct->getTable($tableId);
        $fieldRow = $ct->Table->getFieldById($fieldid);
        if ($fieldRow === null) {
            return array('error' => 'field id set but field not found');
        } else {
            $count = 0;
            if ($startIndex == 0)
                $count = updateImages::countImages($ct->Table->realtablename);

            $status = updateImages::processImages($ct, $fieldRow, $old_params, $new_params);
            return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startIndex, 'stepsize' => $stepSize, 'error' => $status);
        }
    }
}
