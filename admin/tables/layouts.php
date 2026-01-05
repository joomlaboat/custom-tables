<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/layouts.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;

/**
 * ListOfLayouts Table class
 */
class CustomtablesTableLayouts extends Table
{
	var $id = null;
	var $changetimestamp = null;

	var $layoutcode = null;
	var $layoutmobile = null;
	var $layoutcss = null;
	var $layoutjs = null;

	var $layoutname = null;
	var $layoutType = null;
	var $tableid = null;
	var $published = null;
	var $created_by = null;
	var $modified_by = null;
	var $created = null;
	var $modified = null;
	var $checked_out = null;
	var $checked_out_time = null;
	var $params = null;

	/**
	 * Constructor
	 *
	 * @param object $db Database connector object
	 *
	 * @since 1.0.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__customtables_layouts', 'id', $db);
	}
}
