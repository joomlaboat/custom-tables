<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage libraries/_checktable.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
namespace CustomTables;

defined('_JEXEC') or die('Restricted access');

use CustomTables\Integrity\IntegrityCoreTables;
use CustomTables\Integrity\IntegrityTables;
use CustomTables\Integrity\IntegrityFields;
use CustomTables\Integrity\IntegrityOptions;

class IntegrityChecks
{
	public static function check(&$ct,$check_core_tables = true, $check_custom_tables = true)
	{
		$result = []; //Status array
		
		if($check_core_tables)
			IntegrityCoreTables::checkCoreTables($ct);

		if($check_custom_tables)
			$result = IntegrityTables::checkTables($ct);
		
		return $result;
	}
}
