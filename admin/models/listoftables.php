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
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\ListOfTables;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Version;

/**
 * Listoftables Model
 */
class CustomtablesModelListOfTables extends ListModel
{
    var CT $ct;
    var $helperListOfTables;

    public function __construct($config = array())
    {


        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'a.id', 'id',
                'a.published', 'published',
                'a.ordering', 'ordering',
                'a.tablecategory', 'tablecategory',
                'a.tablename', 'tablename'
            );
        }

        parent::__construct($config);

        $this->ct = new CT;
        $this->ct->setParams();

        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoftables.php');
        $this->helperListOfTables = new listOfTables($this->ct);
    }

    /**
     * Method to get an array of data items.
     *
     * @return  array  An array of data items on success, false on failure.
     *
     * @since 1.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (is_array($items))
            return $items;
        else
            return [];
    }

    /**
     * Method to autopopulate the model state.
     *
     * @return  void
     *
     * @throws Exception
     * @since 1.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $version_object = new Version;
        $version = (int)$version_object->getShortVersion();

        if ($version < 4) {
            $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
            $this->setState('filter.search', $search);

            $category = $this->getUserStateFromRequest($this->context . '.filter.tablecategory', 'filter_tablecategory');
            $this->setState('filter.tablecategory', $category);

            $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
            $this->setState('filter.published', $published);
        }

        $category_id = common::inputGetInt('category');

        if ($category_id !== null) {

            //$this->state->set('filter.tablecategory', $category_id);
            //$this->state->set('filter[tablecategory]', $category_id);
            //$this->setState('filter.tablecategory', $category_id);
            $this->setState('.filter.tablecategory', $category_id);
            //$this->setState('filter[tablecategory]', 3);
        }

        // Load the parameters.
        $this->setState('params', ComponentHelper::getParams('com_customtables'));

        // List state information.
        parent::populateState($ordering, $direction);

        if ($version < 4) {
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
     *
     * @throws Exception
     * @since 1.0.0
     */
    protected function getListQuery(): string
    {
        $published = $this->getState('filter.published');
        $search = $this->getState('filter.search');

        $category_id = common::inputGetInt('category');
        if ($category_id !== null) {
            $this->state->set('filter.tablecategory', $category_id);
            $this->setState('filter.tablecategory', $category_id);
        }

        $category = $this->getState('filter.tablecategory');
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirection = $this->state->get('list.direction', 'asc');

        return $this->helperListOfTables->getListQuery($published, $search, $category, $orderCol, $orderDirection, null, null, true);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @return  string  A store id.
     *
     * @since 1.0.0
     */
    protected function getStoreId($id = ''): string
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.id');
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.tablename');
        $id .= ':' . $this->getState('filter.tablecategory');

        return parent::getStoreId($id);
    }
}
