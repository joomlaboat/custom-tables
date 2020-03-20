<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com 
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		listofrecords.php
	@author			Ivan Komlev <https://joomlaboat.com>	
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listofrecords Model
 */
class CustomtablesModelListofRecords extends JModelList
{
	var $tableid;
	var $tablename;
	
	public function __construct($config = array())
	{
		if (empty($config['filter_records']))
        {
			$config['filter_records'] = array();
		}
		
		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		//$this->tablename=$tablename;
		// load parent items
		$items = parent::getItems(); 

		// return items
		return $items;
	}

	/**
	 * Method to convert selection values to translatable string.
	 *
	 * @return translatable string
	 */
	
	protected function getListQuery()
	{
		$jinput=JFactory::getApplication()->input;
		$this->tablename=$jinput->getCmd('tablename',0);
		
		if($this->tablename=="")
			return null;
				
		
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('a.*, a.id AS listing_id');

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_table_'.$this->tablename, 'a'));

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}
		// Filter by search.
		/*
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search) . '%');
				$query->where('(a.fieldtitle LIKE '.$search.')');
			}
		}
*/
		// Filter by Type.
		/*
		if ($type = $this->getState('filter.type'))
		{
			$query->where('a.type = ' . $db->quote($db->escape($type)));
		}
*/
		
		/*
		if ($this->tableid!=0)
		{
			$query->where('a.tableid = ' . $db->quote($db->escape($this->tableid)));
		}
		*/
		////$app = JFactory::getApplication();
		//$this->tableid=$app->input->getint('tableid',0);
		
		// Add the list ordering clause.
		//$orderCol = $this->state->get('list.ordering', 'a.id');
		//$orderDirn = $this->state->get('list.direction', 'asc');	
		//if ($orderCol != '')
		//{
			//$query->order($db->escape($orderCol . ' ' . $orderDirn));
		//}

		return $query;
	} 
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		//$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		//$id .= ':' . $this->getState('filter.ordering');
		//$id .= ':' . $this->getState('filter.created_by');
		//$id .= ':' . $this->getState('filter.modified_by');
		//$id .= ':' . $this->getState('filter.fieldtitle');
		//$id .= ':' . $this->getState('filter.type');

		return parent::getStoreId($id);
	}
}
