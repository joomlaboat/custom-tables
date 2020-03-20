<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		listoflayouts.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listoflayouts Model
 */
class CustomtablesModelListoflayouts extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.layoutname','layoutname',
				'a.layouttype','layouttype',
				'a.tableid','tableid'
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

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$layoutname = $this->getUserStateFromRequest($this->context . '.filter.layoutname', 'filter_layoutname');
		$this->setState('filter.layoutname', $layoutname);

		$layouttype = $this->getUserStateFromRequest($this->context . '.filter.layouttype', 'filter_layouttype');
		$this->setState('filter.layouttype', $layouttype);

		$tableid = $this->getUserStateFromRequest($this->context . '.filter.tableid', 'filter_tableid');
		$this->setState('filter.tableid', $tableid);

		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

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

		// set selection value to a translatable value
		if (CustomtablesHelper::checkArray($items))
		{

			foreach ($items as $nr => &$item)
			{
				// convert layouttype
				$item->layouttype = $this->selectionTranslation($item->layouttype, 'layouttype');
			}
		}


		// return items
		return $items;
	}

	/**
	 * Method to convert selection values to translatable string.
	 *
	 * @return translatable string
	 */
	public function selectionTranslation($value,$name)
	{
		// Array of layouttype language strings
		if ($name === 'layouttype')
		{
			$layouttypeArray = array(
				1 => 'COM_CUSTOMTABLES_LAYOUTS_SIMPLE_CATALOG',
				5 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_PAGE',
				6 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_ITEM',
				2 => 'COM_CUSTOMTABLES_LAYOUTS_EDIT_FORM',
				4 => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS',
				3 => 'COM_CUSTOMTABLES_LAYOUTS_RECORD_LINK',
				7 => 'COM_CUSTOMTABLES_LAYOUTS_EMAIL_MESSAGE',
				8 => 'COM_CUSTOMTABLES_LAYOUTS_XML',
				9 => 'COM_CUSTOMTABLES_LAYOUTS_CSV',
				10 => 'COM_CUSTOMTABLES_LAYOUTS_JSON'
			);
			// Now check if value is found in this array
			if (isset($layouttypeArray[$value]) && CustomtablesHelper::checkString($layouttypeArray[$value]))
			{
				return $layouttypeArray[$value];
			}
		}
		return $value;
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$tabletitle='(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid LIMIT 1)';
		$modifiedby='(SELECT name FROM #__users AS u WHERE u.id=a.modified_by LIMIT 1)';
		$query->select('a.*, '.$tabletitle.' AS tabletitle, '.$modifiedby.' AS modifiedby');

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_layouts', 'a'));

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
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search_clean = $db->quote('%' . $db->escape($search) . '%');
				$query->where('((a.layoutname LIKE '.$search_clean.') OR INSTR(a.layoutcode,'.$db->quote($search).'))');
			}
		}

		// Filter by Layouttype.
		if ($layouttype = $this->getState('filter.layouttype'))
		{
			$query->where('a.layouttype = ' . $db->quote($db->escape($layouttype)));
		}
		// Filter by Tableid.
		if ($tableid = $this->getState('filter.tableid'))
		{
			$query->where('a.tableid = ' . $db->quote($db->escape($tableid)));
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
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.layoutname');
		$id .= ':' . $this->getState('filter.layouttype');
		$id .= ':' . $this->getState('filter.tableid');

		return parent::getStoreId($id);
	}
}
