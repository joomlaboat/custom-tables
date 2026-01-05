<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// If this file is called directly, abort.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

// Define str_starts_with if it doesn't exist
if (!function_exists('str_starts_with')) {
	function str_starts_with(string $haystack, string $needle): bool
	{
		return substr($haystack, 0, strlen($needle)) === $needle;
	}
}

// Define str_contains if it doesn't exist
if (!function_exists('str_contains')) {
	function str_contains(string $haystack, string $needle): bool
	{
		return $needle === '' || strpos($haystack, $needle) !== false;
	}
}

function CustomTablesLoader($include_utilities = false, $include_html = false, $PLUGIN_NAME_DIR = null, $componentName = 'com_customtables', bool $loadTwig = true): void
{
	if (!defined('CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY'))
		define('CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY', 0);

	if (!defined('CUSTOMTABLES_SHOWPUBLISHED_UNPUBLISHED_ONLY'))
		define('CUSTOMTABLES_SHOWPUBLISHED_UNPUBLISHED_ONLY', 1);

	if (!defined('CUSTOMTABLES_SHOWPUBLISHED_ANY'))
		define('CUSTOMTABLES_SHOWPUBLISHED_ANY', 2);

	if (!defined('CUSTOMTABLES_SHOW_NOT_TRASHED'))
		define('CUSTOMTABLES_SHOW_NOT_TRASHED', -1);

	if (!defined('CUSTOMTABLES_SHOW_TRASHED'))
		define('CUSTOMTABLES_SHOW_TRASHED', -2);

	if (!defined('CUSTOMTABLES_ACTION_EDIT'))
		define('CUSTOMTABLES_ACTION_EDIT', 1);

	if (!defined('CUSTOMTABLES_ACTION_PUBLISH'))
		define('CUSTOMTABLES_ACTION_PUBLISH', 2);

	if (!defined('CUSTOMTABLES_ACTION_DELETE'))
		define('CUSTOMTABLES_ACTION_DELETE', 3);

	if (!defined('CUSTOMTABLES_ACTION_ADD'))
		define('CUSTOMTABLES_ACTION_ADD', 4);

	if (!defined('CUSTOMTABLES_ACTION_FORCE_EDIT'))
		define('CUSTOMTABLES_ACTION_FORCE_EDIT', 5);

	if (!defined('CUSTOMTABLES_ACTION_COPY'))
		define('CUSTOMTABLES_ACTION_COPY', 6);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG'))
		define('CUSTOMTABLES_LAYOUT_TYPE_SIMPLE_CATALOG', 1);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM'))
		define('CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM', 2);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_DETAILS'))
		define('CUSTOMTABLES_LAYOUT_TYPE_DETAILS', 4);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE'))
		define('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_PAGE', 5);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM'))
		define('CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM', 6);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_EMAIL'))
		define('CUSTOMTABLES_LAYOUT_TYPE_EMAIL', 7);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_XML'))
		define('CUSTOMTABLES_LAYOUT_TYPE_XML', 8);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_CSV'))
		define('CUSTOMTABLES_LAYOUT_TYPE_CSV', 9);

	if (!defined('CUSTOMTABLES_LAYOUT_TYPE_JSON'))
		define('CUSTOMTABLES_LAYOUT_TYPE_JSON', 10);

	if (defined('CUSTOMTABLES_MEDIA_WEBPATH'))
		return;

	$libraryPath = null;

	if (defined('_JEXEC')) {

		if (!defined('CUSTOMTABLES_JOOMLA_VERSION')) {
			// Get Joomla version
			$version = new Version();
			define('CUSTOMTABLES_JOOMLA_VERSION', $version->getShortVersion());

			if (!defined('CUSTOMTABLES_JOOMLA_MIN_4')) {
				if (version_compare(CUSTOMTABLES_JOOMLA_VERSION, '4.0', '>='))
					define('CUSTOMTABLES_JOOMLA_MIN_4', true);
				else
					define('CUSTOMTABLES_JOOMLA_MIN_4', false);
			}
		}

		if ($componentName == 'com_extensiontranslator')
			$libraryPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . 'libraries';
		else
			$libraryPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . 'libraries';

		if (!defined('CUSTOMTABLES_ABSPATH'))
			define('CUSTOMTABLES_ABSPATH', JPATH_SITE . DIRECTORY_SEPARATOR);

		if (!defined('CUSTOMTABLES_IMAGES_PATH'))
			define('CUSTOMTABLES_IMAGES_PATH', JPATH_SITE . DIRECTORY_SEPARATOR . 'images');

		if (!defined('CUSTOMTABLES_PRO_PATH'))
			define('CUSTOMTABLES_PRO_PATH', JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR);

		if (!defined('CUSTOMTABLES_TEMP_PATH')) {

			// Check if version is 4.0 or higher
			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$config = Factory::getContainer()->get('config');
			else
				$config = Factory::getConfig();

			$tmpPath = $config->get('tmp_path');

			define('CUSTOMTABLES_TEMP_PATH', $tmpPath . DIRECTORY_SEPARATOR);
		}

	} elseif (defined('WPINC')) {


		if (!defined('CUSTOMTABLES_JOOMLA_VERSION')) {
			define('CUSTOMTABLES_JOOMLA_VERSION', 0);

			if (!defined('CUSTOMTABLES_JOOMLA_MIN_4'))
				define('CUSTOMTABLES_JOOMLA_MIN_4', false);
		}


		$libraryPath = $PLUGIN_NAME_DIR . 'libraries';

		if (!defined('CUSTOMTABLES_ABSPATH'))
			define('CUSTOMTABLES_ABSPATH', ABSPATH);

		if (!defined('CUSTOMTABLES_IMAGES_PATH'))
			define('CUSTOMTABLES_IMAGES_PATH', ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'uploads');

		if (!defined('CUSTOMTABLES_PRO_PATH'))
			define('CUSTOMTABLES_PRO_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'customtablespro' . DIRECTORY_SEPARATOR);

		if (!defined('CUSTOMTABLES_TEMP_PATH')) {
			define('CUSTOMTABLES_TEMP_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR);
		}
	}

	if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
		define('CUSTOMTABLES_LIBRARIES_PATH', $libraryPath);

	if (defined('_JEXEC')) {
		define('CUSTOMTABLES_MEDIA_WEBPATH', URI::root(false) . 'components/com_customtables/libraries/customtables/media/');
		define('CUSTOMTABLES_LIBRARIES_WEBPATH', URI::root(false) . 'components/com_customtables/libraries/');

		define('CUSTOMTABLES_PLUGIN_WEBPATH', URI::root(false) . 'plugins/content/customtables/');

		$url = URI::root(false);
		if (strlen($url) > 0 and $url[strlen($url) - 1] == '/')
			$url = substr($url, 0, strlen($url) - 1);

		define('CUSTOMTABLES_MEDIA_HOME_URL', $url);
	} elseif (defined('WPINC')) {
		define('CUSTOMTABLES_MEDIA_WEBPATH', home_url() . '/wp-content/plugins/customtables/libraries/customtables/media/');
		define('CUSTOMTABLES_MEDIA_HOME_URL', home_url());
	}

	if ((!defined('_JEXEC') or ($loadTwig === null or $loadTwig)) and !class_exists('Twig')) {

		if ($componentName == 'com_customtables' or $componentName == 'com_extensiontranslator') {
			$twig_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'twig' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
			require_once($twig_file);
		}
	}

	$path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
	$pathIntegrity = $path . 'integrity' . DIRECTORY_SEPARATOR;

	require_once($pathIntegrity . 'integrity.php');
	require_once($pathIntegrity . 'fields.php');
	require_once($pathIntegrity . 'coretables.php');
	require_once($pathIntegrity . 'tables.php');

	$path_helpers = $path . 'helpers' . DIRECTORY_SEPARATOR;

	require_once($path_helpers . 'CustomTablesImageMethods.php');
	require_once($path_helpers . 'CTUser.php');
	require_once($path_helpers . 'CTMiscHelper.php');
	require_once($path_helpers . 'Fields.php');
	require_once($path_helpers . 'Icons.php');
	require_once($path_helpers . 'Pagination.php');
	require_once($path_helpers . 'FindSimilarImage.php');
	require_once($path_helpers . 'TableHelper.php');
	require_once($path_helpers . 'compareimages.php');
	require_once($path_helpers . 'DataTypes.php');
	require_once($path_helpers . 'FileMethods.php');

	if (defined('_JEXEC')) {
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-common-joomla.php');
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		if (file_exists(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'pagination.php'))
			require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'pagination.php');
	} elseif (defined('WPINC')) {
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-common-wp.php');
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-database-wp.php');
	}

	if ($include_utilities) {
		$path_utilities = $path . 'utilities' . DIRECTORY_SEPARATOR;
		require_once($path_utilities . 'ImportTables.php');
		require_once($path_utilities . 'ExportTables.php');
	}

	$pathDataTypes = $path . 'ct' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'CT.php');
	require_once($pathDataTypes . 'Environment.php');
	require_once($pathDataTypes . 'Logs.php');
	require_once($pathDataTypes . 'Params.php');
	require_once($pathDataTypes . 'Field.php');
	require_once($pathDataTypes . 'Table.php');

	$pathDataTypes = $path . 'layouts' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'layouts.php');

	require_once($pathDataTypes . 'twig.php');
	require_once($pathDataTypes . 'Twig_Fields_Tags.php');

	require_once($pathDataTypes . 'Twig_Record_Tags.php');
	require_once($pathDataTypes . 'Twig_Table_Tags.php');

	require_once($pathDataTypes . 'Twig_Tables_Tags.php');
	require_once($pathDataTypes . 'Twig_HTML_Tags.php');
	require_once($pathDataTypes . 'Twig_User_Tags.php');
	require_once($pathDataTypes . 'Twig_Document_Tags.php');
	require_once($pathDataTypes . 'Twig_URL_Tags.php');

	$pathDataTypes = $path . 'ordering' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'ordering.php');

	$pathDataTypes = $path . 'records' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'savefieldqueryset.php');
	require_once($pathDataTypes . 'record.php');

	$pathDataTypes = $path . 'html' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'toolbar.php');
	require_once($pathDataTypes . 'forms.php');
	require_once($pathDataTypes . 'inputbox.php');
	require_once($pathDataTypes . 'value.php');

	$pathDataTypes = $path . 'languages' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'languages.php');

	$pathDataTypes = $path . 'filter' . DIRECTORY_SEPARATOR;
	require_once($pathDataTypes . 'filtering.php');

	$pathViews = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

	require_once($pathViews . 'edit.php');
	require_once($pathViews . 'catalog.php');
	//details.php removed
}
