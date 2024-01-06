<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/layouts.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

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

	//protected $_jsonEncode = array('params', 'metadata');

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db)
	{
		parent::__construct('#__customtables_layouts', 'id', $db);
	}
}
