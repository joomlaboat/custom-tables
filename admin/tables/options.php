<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Include library dependencies
jimport('joomla.filter.input');

class CustomtablesTableOptions extends JTable
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

	//function TableListEdit(& $db)
 function __construct(&$db)
	{
		parent::__construct('#__customtables_options', 'id', $db);
	}

}
