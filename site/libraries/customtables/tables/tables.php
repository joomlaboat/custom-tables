<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

class Tables
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function loadRecords($tableid,$filter = '', $orderby = '', $limit = 0)
	{
		if($tableid == 0)
			return false;

		$this->ct->getTable($tableid, null);
		
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not found.', 'error');
			return false;
		}
		
		$this->ct->Table->recordcount = 0;
		
		$this->ct->setFilter($filter, 2);
		
		$this->ct->Ordering->ordering_processed_string = $orderby;
		$this->ct->Ordering->parseOrderByString();
		
		$this->ct->Limit=$limit;
		$this->ct->LimitStart=0;
		
		$this->ct->getRecords();

		return true;
	}
}
