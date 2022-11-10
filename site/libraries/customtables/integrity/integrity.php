<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage libraries/_checktable.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\Integrity\IntegrityCoreTables;
use CustomTables\Integrity\IntegrityTables;

class IntegrityChecks
{
    public static function check(CT &$ct, $check_core_tables = true, $check_custom_tables = true): array
    {
        $result = []; //Status array

        if ($check_core_tables)
            IntegrityCoreTables::checkCoreTables($ct);

        if ($check_custom_tables)
            $result = IntegrityTables::checkTables($ct);

        return $result;
    }
}
