<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

// import Joomla table library
jimport('joomla.database.table');

/**
 * Listofrecords Table class
 */
class CustomtablesTableRecords extends JTable
{
	//protected $_jsonEncode = array('params', 'metadata');
    
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		$jinput=JFactory::getApplication()->input;
		$tablename=$jinput->getCmd('tablename');
		//echo $tablename;
		//die;
		
		parent::__construct('#__customtables_table_'.$tablename, 'id', $db); 
	}	
}
