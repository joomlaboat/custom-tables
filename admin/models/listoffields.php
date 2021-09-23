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

use CustomTables\CT;

/**
 * Listoffields Model
 */
class CustomtablesModelListoffields extends JModelList
{
	var $ct;
	
	var $tableid;
	
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				//'a.ordering','ordering',
				//'a.created_by','created_by',
				//'a.modified_by','modified_by',
				//'a.fieldtitle','fieldtitle',
				'a.type','type'
			);
		}

		parent::__construct($config);
		
		$this->ct = new CT;
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
		//$fieldtitle = $this->getUserStateFromRequest($this->context . '.filter.fieldtitle', 'filter_fieldtitle');
		//$this->setState('filter.fieldtitle', $fieldtitle);

		//$type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
		//$this->setState('filter.type', $type);
		
		//$tableid = $this->getUserStateFromRequest($this->context . '.filter.tableid', 'filter_tableid');
		//$this->setState('filter.tableid', $tableid);
        
		//$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		//$this->setState('filter.sorting', $sorting);
        
		//$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		//$this->setState('filter.access', $access);
        
		//$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		//$this->setState('filter.search', $search);

		//$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		//$this->setState('filter.published', $published);
        
		//$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		//$this->setState('filter.created_by', $created_by);

		//$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		//$this->setState('filter.created', $created);

		//$jinput->set('tableid',$tableid);
		
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
				// convert type
				$item->type = $this->selectionTranslation($item->type, 'type');
				// convert isrequired
				$item->isrequired = $this->selectionTranslation($item->isrequired, 'isrequired');
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
		// Array of type language strings
		if ($name === 'type')
		{
			$typeArray = array(
				'string' => 'COM_CUSTOMTABLES_FIELDS_STRING',
				'multilangstring' => 'COM_CUSTOMTABLES_FIELDS_MULTILANGSTRING',
				'textText' => 'COM_CUSTOMTABLES_FIELDS_TEXTTEXT',
				'multilangtext' => 'COM_CUSTOMTABLES_FIELDS_MULTILANGTEXT',
				'int' => 'COM_CUSTOMTABLES_FIELDS_INTEGER',
				'float' => 'COM_CUSTOMTABLES_FIELDS_FLOAT',
				'customtables' => 'COM_CUSTOMTABLES_FIELDS_EXTRA_SEARCH',
				'records' => 'COM_CUSTOMTABLES_FIELDS_MULTI_SQL_JOIN',
				'checkbox' => 'COM_CUSTOMTABLES_FIELDS_CHECKBOX',
				'radio' => 'COM_CUSTOMTABLES_FIELDS_RADIO_BUTTONS',
				'email' => 'COM_CUSTOMTABLES_FIELDS_EMAIL',
				'url' => 'COM_CUSTOMTABLES_FIELDS_URL',
				'date' => 'COM_CUSTOMTABLES_FIELDS_DATE',
				'time' => 'COM_CUSTOMTABLES_FIELDS_TIME',
				'image' => 'COM_CUSTOMTABLES_FIELDS_IMAGE',
				'imagegallery' => 'COM_CUSTOMTABLES_FIELDS_IMAGE_GALLERY',
				'filebox' => 'COM_CUSTOMTABLES_FIELDS_FILE_BOX',
				'file' => 'COM_CUSTOMTABLES_FIELDS_FILE',
				'filelink' => 'COM_CUSTOMTABLES_FIELDS_FILE_LINK',
				'creationtime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_CREATION_DATE_TIME',
				'changetime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_CHANGE_DATE_TIME',
				'lastviewtime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_LAST_VIEW_DATE_TIME',
				'viewcount' => 'COM_CUSTOMTABLES_FIELDS_AUTO_VIEW_COUNT',
				'userid' => 'COM_CUSTOMTABLES_FIELDS_AUTO_AUTHOR_USER_ID',
				'user' => 'COM_CUSTOMTABLES_FIELDS_USER',
				'server' => 'COM_CUSTOMTABLES_FIELDS_SERVER',
				'alias' => 'COM_CUSTOMTABLES_FIELDS_ALIAS',
				'color' => 'COM_CUSTOMTABLES_FIELDS_COLOR',
				'id' => 'COM_CUSTOMTABLES_FIELDS_AUTO_ID',
				'phponadd' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONADD_SCRIPT',
				'phponchange' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONCHANGE_SCRIPT',
				'phponview' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONVIEW_SCRIPT',
				'sqljoin' => 'COM_CUSTOMTABLES_FIELDS_SQL_JOIN',
				'googlemapcoordinates' => 'COM_CUSTOMTABLES_FIELDS_GOOGLE_MAP_COORDINATES',
				'dummy' => 'COM_CUSTOMTABLES_FIELDS_DUMMY_USED_FOR_TRANSLATION',
				'article' => 'COM_CUSTOMTABLES_FIELDS_ARTICLE_LINK',
				'multilangarticle' => 'COM_CUSTOMTABLES_FIELDS_MULTILINGUAL_ARTICLE',
				'md5' => 'COM_CUSTOMTABLES_FIELDS_MDFIVE_HASH',
				'log' => 'COM_CUSTOMTABLES_FIELDS_MODIFICATION_LOG',
				'usergroup' => 'COM_CUSTOMTABLES_FIELDS_USER_GROUP',
				'usergroups' => 'COM_CUSTOMTABLES_FIELDS_USER_GROUPS'
			);
			// Now check if value is found in this array
			if (isset($typeArray[$value]) && CustomtablesHelper::checkString($typeArray[$value]))
			{
				return $typeArray[$value];
			}
		}
		// Array of isrequired language strings
		if ($name === 'isrequired')
		{
			$isrequiredArray = array(
				1 => 'COM_CUSTOMTABLES_FIELDS_YES',
				0 => 'COM_CUSTOMTABLES_FIELDS_NO'
			);
			// Now check if value is found in this array
			if (isset($isrequiredArray[$value]) && CustomtablesHelper::checkString($isrequiredArray[$value]))
			{
				return $isrequiredArray[$value];
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
		$jinput = JFactory::getApplication()->input;
		$this->tableid=$jinput->getInt('tableid',0);
		
		$state_tableid=(int)$this->getState('filter.tableid');
	
		if($state_tableid!=0)// and $tableid==0)
		{
			$this->tableid=$state_tableid;
			$jinput->set('tableid',$this->tableid);
			$this->tableid=$jinput->getInt('tableid',0);
		}
		elseif($state_tableid==0 and $this->tableid!=0)
		{
			$this->setState('filter.tableid',$this->tableid);
		}
		else
		{
			$this->tableid=0;
		}
		
		//-0---------------------------------------------
		
		
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		
		$tabletitle='(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid)';
		$query->select('a.*, '.$tabletitle.' AS tabletitle');
		
		$query->select('a.*');

		// From the customtables_item table
		$query->from($db->quoteName('#__customtables_fields', 'a'));

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
				$search = $db->quote('%' . $db->escape($search) . '%');
				$query->where('(a.fieldtitle LIKE '.$search.')');
			}
		}

		// Filter by Type.
		if ($type = $this->getState('filter.type'))
		{
			$query->where('a.type = ' . $db->quote($db->escape($type)));
		}

		
		
		if ($this->tableid!=0)
		{
			$query->where('a.tableid = ' . $db->quote($db->escape($this->tableid)));
		}
		$app = JFactory::getApplication();
		$this->tableid=$app->input->getint('tableid',0);
		
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

		return parent::getStoreId($id);
	}
}
