<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

class LoginController
{
	function execute()
	{
		// Get list of layouts
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('layoutname')
			->from($db->quoteName('#__customtables_layouts'));
		$db->setQuery($query);
		$response = $db->loadObjectList();

		// Send the response
		CTMiscHelper::fireSuccess(null, $response, 'List of Layouts loaded');
	}
}