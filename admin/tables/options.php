<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.8.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		view.html.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/


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

?>
