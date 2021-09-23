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

use CustomTables\CT;
use CustomTables\Fields;

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listofrecords Model
 */
class CustomtablesModelListofRecords extends JModelList
{
	var $ct;
	var $tableid;

	public function __construct($config = array())
	{
		$this->ct = new CT;
		
		if (empty($config['filter_records']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published'
			);
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
		// load parent items
		$items = parent::getItems(); 

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

		$this->ct->getTable($jinput->getCmd('tablename',0), null);
		
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected.', 'error');
			return;
		}
		
		if(!is_array($this->ct->Table->tablerow))
		{
			$this->setError('Table not found.');
			return null;
		}
		
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if($this->ct->Table->published_field_found)
			$query_selects='a.*, a.id as listing_id, a.published AS listing_published';
		else
			$query_selects='a.*, a.id as listing_id, 1 AS listing_published';

		$query->select($query_selects);

		// From the customtables_item table
		$query->from($db->quoteName($this->ct->Table->realtablename, 'a'));
		
		
		$wheres_and = [];
		// Filter by published state
		if($this->ct->Table->published_field_found)
		{
			$published = $this->getState('filter.published');
			
			if (is_numeric($published))
				$wheres_and[] = 'a.published = ' . (int) $published;
			elseif ($published === '')
				$wheres_and[] = '(a.published = 0 OR a.published = 1)';
		}
		
		
		// Filter by search.
		$search = $this->getState('filter.search');
		
		if($search!='')
		{
			$wheres=[];
		
			foreach ($this->ct->Table->fields as $esfield)
			{	
				if($esfield['type'] == 'string')
				{
					$realfieldname = $esfield['realfieldname'];
					$where = $db->quote('%' . $db->escape($search) . '%');
					$wheres[] = ('(a.'.$realfieldname.' LIKE '.$where.')');
				}
			}
			$wheres_and[] = '('.implode(' OR ',$wheres).')';
		}
		
		if(count($wheres_and)>0)
		{
			$where_str = implode(' AND ',$wheres_and);
			$query->where($where_str);
		}
		
		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
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
		$id .= ':' . $this->getState('filter.published');

		return parent::getStoreId($id);
	}
}
