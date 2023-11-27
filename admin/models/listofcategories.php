<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\CT;

use CustomTables\database;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Listofcategories Model
 */
class CustomtablesModelListofcategories extends ListModel
{
	var CT $ct;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'a.id', 'id',
				'a.published', 'published',
				'a.categoryname', 'categoryname'
			);
		}
		parent::__construct($config);
		$this->ct = new CT;
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

		// return items
		return $items;
	}

	/**
	 * Method to autopopulate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		if ($this->ct->Env->version < 4) {
			$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
			$this->setState('filter.search', $search);

			$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
			$this->setState('filter.published', $published);
		}

		$this->setState('params', ComponentHelper::getParams('com_customtables'));

		// List state information.
		parent::populateState($ordering, $direction);

		if ($this->ct->Env->version < 4) {
			$ordering = $this->state->get('list.ordering');
			$direction = strtoupper($this->state->get('list.direction'));
			$app = Factory::getApplication();
			$app->setUserState($this->context . '.list.fullordering', $ordering . ' ' . $direction);
		}
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return    string    An SQL query
	 */
	protected function getListQuery()
	{
		$query = 'SELECT a.* FROM ' . database::quoteName('#__customtables_categories') . ' AS a';
		$where = [];
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
				$search = database::quote('%' . $search . '%');
				$where [] = '(a.categoryname LIKE ' . $search . ')';
			}
		}

		$query .= ' WHERE ' . implode(' AND ', $where);
		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol != '')
			$query .= ' ORDER BY ' . database::quoteName($orderCol) . ' ' . $orderDirn;

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
		$id .= ':' . $this->getState('filter.categoryname');

		return parent::getStoreId($id);
	}
}
