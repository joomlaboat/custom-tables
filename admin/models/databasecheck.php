<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage listoftables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\CT;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * ListOfTables Model
 * @since 1.0.0
 */
class CustomtablesModelDatabasecheck extends ListModel
{
	var CT $ct;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'a.tablecategory', 'tablecategory'
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

	protected function populateState($ordering = 'a.id', $direction = 'asc')
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4) {
			$category = $this->getUserStateFromRequest($this->context . '.filter.tablecategory', 'filter_tablecategory');
			$this->setState('filter.tablecategory', $category);
		}

		// Load the parameters.
		$this->setState('params', ComponentHelper::getParams('com_customtables'));

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 * @since 3.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.tablecategory');

		return parent::getStoreId($id);
	}
}
