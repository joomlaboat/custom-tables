<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/categories.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;

/**
 * ListOfFields Table class
 */
class CustomtablesTableFields extends Table
{
	var $id = null;
	var $tableid = null;
	var $allowordering = null;
	var $defaultvalue = null;
	var $fieldname = null;
	var $customfieldname = null;
	var $fieldtitle = null;
	var $description = null;
	var $isrequired = null;
	var $isdisabled = null;
	var $savevalue = null;
	var $alwaysupdatevalue = null;
	var $type = null;
	var $typeParams = null;
	var ?string $valuerule = null;
	var ?string $valuerulecaption = null;
	var $published = null;
	var $parentid = null;
	var $created_by = null;
	var $modified_by = null;
	var $created = null;
	var $modified = null;
	var $checked_out = null;
	var $checked_out_time = null;
	var $ordering = null;

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db)
	{
		parent::__construct('#__customtables_fields', 'id', $db);
	}
}
