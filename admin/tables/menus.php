<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/tables/menu.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;

/**
 * ListOfMenus Table class
 *
 * @since 1.0.0
 */
class CustomtablesTableMenus extends Table
{
	var ?int $id = null;
	var ?string $title = null;
	var ?string $alias = null;
	var ?string $link = null;

	/**
	 * Constructor
	 *
	 * @param object $db Database connector object
	 *
	 * @since 1.0.0
	 */
	function __construct($db)
	{
		parent::__construct('#__menu', 'id', $db);
	}
}
