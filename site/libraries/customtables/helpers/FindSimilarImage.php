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

defined('_JEXEC') or die();

class FindSimilarImage
{
	static public function find($uploadedFile, $level_identity, $realtablename, $realfieldname, $ImageFolder, MySQLWhereClause $whereClauseAdditional)
	{
		if ($level_identity < 0)
			$level_identity = 0;

		$ci = new compareImages;

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($realfieldname, 0, '>');

		if ($whereClauseAdditional->hasConditions())
			$whereClause->addNestedCondition($whereClauseAdditional);

		$photoRows = database::loadObjectList($realtablename, [$realfieldname . ' AS photoid'], $whereClause);

		foreach ($photoRows as $photoRow) {
			$photoId = $photoRow->photoid;

			if ($photoId != 0) {

				$image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $photoId . '.jpg';///.$ext;
				if ($image_file != $uploadedFile) {
					if (file_exists($image_file)) {
						$index = $ci->compare($uploadedFile, $image_file);
						if ($index <= $level_identity)
							return $photoId;
					}
				}
			}
		}
		return null;
	}
}
