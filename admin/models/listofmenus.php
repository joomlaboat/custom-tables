<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\CT;

use CustomTables\database;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * ListOfMenus Model
 *
 * @since 3.6.7
 */
class CustomtablesModelListOfMenus extends ListModel
{
	var CT $ct;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'a.id', 'id',
				'a.published', 'published',
				'a.title', 'title'
			);
		}
		parent::__construct($config);
		$this->ct = new CT([], true);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getItems()
	{
		// load parent items
		return parent::getItems();
	}

	/**
	 * Method to auto populate the model state.
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$this->setState('params', ComponentHelper::getParams('com_customtables'));

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return    string    An SQL query
	 *
	 * @since 1.0.0
	 */
	protected function getListQuery()
	{
		$db = database::getDB();

		$query = 'SELECT a.* FROM ' . $db->quoteName('#__menu') . ' AS a';
		$where = [];
		$where  [] = 'INSTR(link,"index.php?option=com_customtables&view=listofrecords&Itemid=")';
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
			$where [] = 'a.published = ' . (int)$published;
		elseif (is_null($published) or $published === '')
			$where [] = '(a.published = 0 OR a.published = 1)';

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$where [] = 'a.id = ' . (int)substr($search, 3);
			} else {
				$search = $db->quote('%' . $search . '%');
				$where [] = '(a.title LIKE ' . $search . ')';
			}
		}

		$query .= ' WHERE ' . implode(' AND ', $where);
		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol != '')
			$query .= ' ORDER BY ' . $db->quoteName($orderCol) . ' ' . $orderDirn;

		return $query;
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 * @since 1.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.title');

		return parent::getStoreId($id);
	}
}
