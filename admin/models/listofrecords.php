<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage listofrecords.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

use Joomla\CMS\Component\ComponentHelper;

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listofrecords Model
 */
class CustomtablesModelListofRecords extends JModelList
{
	var $ct;
	//var $tableid;

	public function __construct($config = array())
	{
		$this->ct = new CT;
		
		if (empty($config['filter_records']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				//'a.tableid','tableid'
			);
		}
		parent::__construct($config);
		
		
		$jinput=JFactory::getApplication()->input;

		$this->ct->getTable($jinput->getInt('tableid',0), null);
		
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected.', 'error');
			return;
		}
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		if($this->ct->Env->version < 4)
		{
			$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
			$this->setState('filter.search', $search);
		
			$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
			$this->setState('filter.published', $published);
		}
		
		$this->setState('params', ComponentHelper::getParams('com_customtables'));

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
		//echo 'sssssssss';
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
		//$query->select($this->ct->Table->tablerow['query_selects']);

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
		//$id .= ':' . $this->getState('filter.tableid');

		return parent::getStoreId($id);
	}
}
