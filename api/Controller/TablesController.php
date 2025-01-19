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

use Joomla\CMS\Factory;

class LoginController
{
	function execute()
	{
		// Get list of tables
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('tablename')
			->from($db->quoteName('#__customtables_tables'));
		$db->setQuery($query);
		$response = $db->loadObjectList();

		CTMiscHelper::fireSuccess(null, $response, 'List of Tables loaded');
	}
}