<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Joomla\CMS\Table\Table;

class CustomtablesTableOptions extends Table
{
	var $id = null;
	var $optionname = null;
	var $title = null;
	var $image = null;
	var $imageparams = null;
	var $ordering = null;
	var $parentid = null;
	var $sublevel = null;
	var $isselectable = true;
	var $optionalcode = null;
	var $link = null;
	var $familytree = null;

	function __construct(&$db)
	{
		parent::__construct('#__customtables_options', 'id', $db);
	}

}
