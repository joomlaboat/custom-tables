<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;

/**
 * ListOfTables Table class
 *
 * @since 1.0.0
 */
class CustomtablesTableTables extends Table
{
	var $id = null;
	var $customphp = null;
	var $description = null;
	var $tablecategory = null;
	var $tablename = null;
	var $tabletitle = null;
	var $published = null;
	var $created_by = null;
	var $modified_by = null;
	var $created = null;
	var $modified = null;
	var $checked_out = null;
	var $checked_out_time = null;
	var $allowimportcontent = null;
	var $customtablename = null;
	var $customidfield = null;
	var $customidfieldtype = null;
	var $primarykeypattern = null;
	var $customfieldprefix = null;

	/**
	 * Constructor
	 *
	 * @param object $db Database connector object
	 *
	 * @since 1.0.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__customtables_tables', 'id', $db);
	}

}
