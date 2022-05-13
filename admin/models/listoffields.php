<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

use CustomTables\CT;
use CustomTables\DataTypes;

use Joomla\CMS\Component\ComponentHelper;

/**
 * Listoffields Model
 */
class CustomtablesModelListoffields extends JModelList
{
	var $ct;
	var $tableid;
	
	public function __construct($config = array())
	{
		$this->ct = new CT;
		
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.tableid','tableid',
				'a.ordering', 'ordering',
				'a.fieldname','fieldname',
				'a.type','type'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'a.ordering', $direction = 'asc')
	{
		if($this->ct->Env->version < 4)
		{
			$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
			$this->setState('filter.search', $search);
			
			$type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
			$this->setState('filter.type', $type);
			
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
		
		$translations = DataTypes::fieldTypeTranslation();
		$isrequiredTranslation = DataTypes::isrequiredTranslation();

		// set selection value to a translatable value
		if (CustomtablesHelper::checkArray($items))
		{
			foreach ($items as $nr => &$item)
			{
				// convert type
				if(isset($translations[$item->type]))
				{
					$item->type = $translations[$item->type];
				}
				else
				{
					$item->type = '<span style="color:red;">NOT SELECTED</span>';
				}
				
				// convert isrequired
				if(isset($isrequiredTranslation[$item->isrequired]))
				{
					$item->isrequired = $isrequiredTranslation[$item->isrequired];
				}
			}
		}
		return $items;
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		$jinput = JFactory::getApplication()->input;
		$this->tableid = $jinput->getInt('tableid',0);

		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$tabletitle='(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid)';
		$query->select('a.*, '.$tabletitle.' AS tabletitle');

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_fields', 'a'));

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
			$query->where('a.published = ' . (int) $published);
		elseif (is_null($published) or  $published == '')
			$query->where('a.published = 1');

        // Filter by search.
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

		// Filter by Type.
		if ($type = $this->getState('filter.type'))
		{
			$query->where('a.type = ' . $db->quote($db->escape($type)));
		}
		
		if ($this->tableid != 0)
		{
			$query->where('a.tableid = ' . $db->quote($db->escape($this->tableid)));
		}
		
		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.ordering');
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
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.fieldname');
		$id .= ':' . $this->getState('filter.type');
		
		return parent::getStoreId($id);
	}
}
