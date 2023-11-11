<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
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

use CustomTables\CT;
use CustomTables\DataTypes\Tree;

class CT_FieldTypeTag_ct
{
	public static function ResolveStructure(CT &$ct, string &$htmlresult): void
	{
		$options = array();
		$fList = JoomlaBasicMisc::getListToReplace('resolve', $options, $htmlresult, '{}');
		$i = 0;
		foreach ($fList as $fItem) {
			$value = $options[$i];
			$vlu = implode(',', Tree::getMultyValueTitles($value, $ct->Languages->Postfix, 1, ' - '));
			$htmlresult = str_replace($fItem, $vlu, $htmlresult);
			$i++;
		}
	}

	public static function groupCustomTablesParents(CT &$ct, $valueString, $rootParent): array
	{
		$GroupList = explode(',', $valueString);
		$GroupNames = array();
		$Result = array();
		foreach ($GroupList as $GroupItem) {
			if (strlen($GroupItem) > 0) {
				$TriName = explode('.', $GroupItem);

				if (count($TriName) >= 3) {
					if (!in_array($TriName[1], $GroupNames)) {
						$GroupNames[] = $TriName[1];
						$Result[$TriName[1]][] = Tree::getOptionTitleFull($rootParent . '.' . $TriName[1] . '.', $ct->Languages->Postfix);
					}
					$Result[$TriName[1]][] = Tree::getOptionTitleFull($rootParent . '.' . $TriName[1] . '.' . $TriName[2] . '.', $ct->Languages->Postfix);
				}
			}
		}
		return array_values($Result);
	}
}
