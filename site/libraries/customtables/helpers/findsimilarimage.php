<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class FindSimilarImage
{
	static public function find($uploadedfile, $level_identity, $realtablename, $realfieldname, $ImageFolder, $additional_filter = '')
	{
		if ($level_identity < 0)
			$level_identity = 0;

		$ci = new compareImages;
		$photorows = database::loadObjectList('SELECT ' . $realfieldname . ' AS photoid FROM ' . $realtablename . ' WHERE ' . $realfieldname . '>0' . ($additional_filter != '' ? ' AND ' . $additional_filter : ''));

		foreach ($photorows as $photorow) {
			$photoid = $photorow->photoid;

			if ($photoid != 0) {

				$image_file = $ImageFolder . DIRECTORY_SEPARATOR . '_esthumb_' . $photoid . '.jpg';///.$ext;
				if ($image_file != $uploadedfile) {
					if (file_exists($image_file)) {
						$index = $ci->compare($uploadedfile, $image_file);
						if ($index <= $level_identity)
							return $photoid;
					}
				}
			}
		}
		return null;
	}
}
