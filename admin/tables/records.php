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
		$tableid=$jinput->getInt('tableid',0);
		
		if($tableid!=0)
		{
			$table=ESTables::getTableRowByID($tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				$this->tableid=0;
				return null;
			}
		}
		
		if($table->customtablename !='')
			$realtablename=$table->customtablename;
		else
			$realtablename = '#__customtables_table_'.$table->tablename;
		
		parent::__construct($realtablename, 'id', $db); 
	}	
	
}
