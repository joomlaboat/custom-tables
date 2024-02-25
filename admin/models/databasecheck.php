<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage listoftables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC')) die('Restricted access');

use CustomTables\CT;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Listoftables Model
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

		return $items;
	}

	protected function populateState($ordering = 'a.id', $direction = 'asc')
	{
		if ($this->ct->Env->version < 4) {
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
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.tablecategory');

		return parent::getStoreId($id);
	}
}
