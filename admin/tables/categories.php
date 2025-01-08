<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/categories.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;

/**
 * ListOfCategories Table class
 *
 * @since 1.0.0
 */
class CustomtablesTableCategories extends Table
{
	var $id = null;
	var ?string $categoryname = null;
	var ?int $admin_menu = null;

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 *
	 * @since 1.0.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__customtables_categories', 'id', $db);
	}
}
