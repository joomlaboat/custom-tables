<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listofrecords Model
 */
class CustomtablesModelListofRecords extends JModelList
{
	var $tableid;

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
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
		$jinput=$app->input;
		
		// Adjust the context to support modal layouts.
		if ($layout = $jinput->get('layout'))
			$this->context .= '.' . $layout;
		
		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
		
		$tableid = $this->getUserStateFromRequest($this->context . '.filter.tableid', 'filter_tableid');
		$this->setState('filter.tableid', $tableid);
		
		$jinput->set('tableid',$tableid);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
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
		
		//$app = JFactory::getApplication();
		//$tableid 	= $app->input->get('tableid', 0, 'int');
		//$this->setState('filter.tableid', $tableid);

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
		{
			$this->setError('Table not specified.');
			return null;
		}
		
		$tablerow = ESTables::getTableRowByNameAssoc($this->tablename);
		
		if(!is_array($tablerow))
		{
			$this->setError('Table not found.');
			return null;
		}
		
		$realtablename='';
		
		$published_field_found=true;
		if($tablerow['customtablename']!='')
		{
			$realtablename=$tablerow['customtablename'];
			$realfields=ESFields::getListOfExistingFields($realtablename,false);
			if(!in_array('published',$realfields))
				$published_field_found=false;
		}
		else
			$realtablename='#__customtables_table_'.$this->tablename;
		
		
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if($published_field_found)
			$query_selects='a.*, a.id as listing_id, a.published AS listing_published';
		else
			$query_selects='a.*, a.id as listing_id, 1 AS listing_published';

		$query->select($query_selects);

		// From the customtables_item table
		$query->from($db->quoteName($realtablename, 'a'));
		
		// Filter by published state
		if($published_field_found)
		{
			$published = $this->getState('filter.published');
			if (is_numeric($published))
				$query->where('a.published = ' . (int) $published);
			elseif ($published === '')
				$query->where('(a.published = 0 OR a.published = 1)');
		}
		
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
