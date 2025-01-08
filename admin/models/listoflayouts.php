<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage listoflayouts.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

jimport('joomla.application.component.modellist');

use CustomTables\CT;
use CustomTables\ListOfLayouts;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Listoflayouts Model
 *
 * @since 1.0.0
 */
class CustomtablesModelListOfLayouts extends ListModel
{
	var CT $ct;
	var ListOfLayouts $helperListOfLayout;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'a.id', 'id',
				'a.published', 'published',
				'a.layoutname', 'layoutname',
				't.tablename', 'tablename',
				'a.layouttype', 'layouttype',
				'a.tableid', 'tableid'
			);
		}

		parent::__construct($config);

		$this->ct = new CT;
		$this->ct->setParams();

		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoflayouts.php');
		$this->helperListOfLayout = new listOfLayouts($this->ct);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  array  An array of data items on success, false on failure.
	 *
	 * @since 3.0.0
	 */
	public function getItems(): array
	{
		$items = parent::getItems();

		if (is_array($items))
			return $this->helperListOfLayout->translateLayoutTypes($items);
		else
			return [];
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.3.6
	 */
	protected function getListQuery()
	{
		$published = $this->getState('filter.published');
		$search = $this->getState('filter.search');
		$layoutType = $this->getState('filter.layouttype');
		$tableid = $this->getState('filter.tableid');
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirection = $this->state->get('list.direction', 'asc');
		return $this->helperListOfLayout->getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, null, null, true);
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4) {
			$layoutType = $this->getUserStateFromRequest($this->context . '.filter.layouttype', 'filter_layouttype');
			$this->setState('filter.layouttype', $layoutType);

			$tableid = $this->getUserStateFromRequest($this->context . '.filter.tableid', 'filter_tableid');
			$this->setState('filter.tableid', $tableid);

			$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
			$this->setState('filter.search', $search);

			$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
			$this->setState('filter.published', $published);
		}

		$this->setState('params', ComponentHelper::getParams('com_customtables'));

		// List state information.
		parent::populateState($ordering, $direction);
		if (!CUSTOMTABLES_JOOMLA_MIN_4) {
			$ordering = $this->state->get('list.ordering');
			$direction = strtoupper($this->state->get('list.direction'));
			$app = Factory::getApplication();
			$app->setUserState($this->context . '.list.fullordering', $ordering . ' ' . $direction);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 * @since 3.0.0
	 */
	protected function getStoreId($id = ''): string
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.layoutname');
		$id .= ':' . $this->getState('filter.layouttype');
		$id .= ':' . $this->getState('filter.tableid');

		return parent::getStoreId($id);
	}
}
