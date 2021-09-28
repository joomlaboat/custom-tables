<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Languages;
use CustomTables\Environment;

class CT
{
	var $Languages;
	var $Env;
	var $Table;
	
	function __construct()
	{
		$this->Languages = new Languages;
		$this->Env = new Environment;
	}
	
	function getTable($tablename_or_id, $useridfieldname = null)
	{
		$this->Table = new Table($this->Languages, $this->Env, $tablename_or_id, $useridfieldname);
	}
	
	function setTable(&$tablerow, $useridfieldname = null, $load_fields = true)
	{
		$this->Table = new Table($this->Languages, $this->Env, 0);
		$this->Table->setTable($tablerow, $useridfieldname, $load_fields);
	}
}
