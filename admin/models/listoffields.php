<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access
defined('_JEXEC') or die();

jimport('joomla.application.component.modellist');

use CustomTables\common;
use CustomTables\CT;
use CustomTables\ListOfFields;
use CustomTables\DataTypes;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * ListOfFields Model
 *
 * @since 1.0.0
 */
class CustomtablesModelListOfFields extends ListModel
{
	var int $tableid;
	var CT $ct;
	var ListOfFields $helperListOfFields;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'a.id', 'id',
				'a.published', 'published',
				'a.tableid', 'tableid',
				'a.ordering', 'ordering',
				'a.fieldname', 'fieldname',
				'a.type', 'type'
			);
		}
		parent::__construct($config);

		$this->ct = new CT([], true);
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoftables.php');
		$this->helperListOfFields = new listOfFields($this->ct);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 * @since 1.0.0
	 */
	public function getItems()
	{
		// load parent items
		$items = parent::getItems();

		$translations = DataTypes::fieldTypeTranslation();
		$isrequiredTranslation = DataTypes::isrequiredTranslation();

		// set selection value to a translatable value
		if (CustomtablesHelper::checkArray($items)) {
			foreach ($items as $item) {
				// convert type
				if (isset($translations[$item->type])) {
					$item->typeLabel = $translations[$item->type];
				} else {
					if ($item->type == '')
						$item->typeLabel = '<span style="color:red;">NOT SELECTED</span>';
					else
						$item->typeLabel = '<span style="color:red;">UNKNOWN "' . $item->type . '" TYPE</span>';
				}

				// convert isrequired
				if (isset($isrequiredTranslation[$item->isrequired])) {
					$item->isrequired = $isrequiredTranslation[$item->isrequired];
				}
			}
		}
		return $items;
	}

	/**
	 * Method to autopopulate the model state.
	 *
	 * @return  void
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function populateState($ordering = 'a.id', $direction = 'asc')
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4) {
			$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
			$this->setState('filter.search', $search);

			$type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
			$this->setState('filter.type', $type);

			$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
			$this->setState('filter.published', $published);
		}

		$this->setState('params', ComponentHelper::getParams('com_customtables'));

		parent::populateState($ordering, $direction);

		if (!CUSTOMTABLES_JOOMLA_MIN_4) {
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
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function getListQuery(): string
	{
		$published = $this->getState('filter.published');
		$search = $this->getState('filter.search');
		$type = $this->getState('filter.type');
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirection = $this->state->get('list.direction', 'asc');
		$tableId = common::inputGetInt('tableid');
		return $this->helperListOfFields->getListQuery($tableId, $published, $search, $type, $orderCol, $orderDirection, null, null, true);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 * @since 1.0.0
	 */
	protected function getStoreId($id = ''): string
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
