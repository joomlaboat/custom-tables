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

class CustomTablesRouter implements JComponentRouterInterface
{
	public function build(&$query)
	{
		$segments = [];
		if (isset($query['alias'])) {
			$segments[] = $query['alias'];
			unset($query['alias']);
		}
		return $segments;
	}

	public function parse(&$segments)
	{
		$vars = [];

		//Check if it's a file to download

		$libraryPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries';
		if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
			define('CUSTOMTABLES_LIBRARIES_PATH', $libraryPath);

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php');
		if (CT_FieldTypeTag_file::CheckIfFile2download($segments, $vars)) {
			//rerouted
			$vars['option'] = 'com_customtables';
			$segments[0] = null;
			return $vars;
		}

		if (isset($segments[0])) {

			$vars['option'] = 'com_customtables';
			$vars['view'] = 'details';
			$vars['alias'] = $segments[0];
			$segments[0] = null;
		}
		return $vars;
	}

	public function preprocess($query)
	{
		return $query;
	}
}
