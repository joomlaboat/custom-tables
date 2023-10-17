<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage listoftables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

jimport('joomla.application.component.modellist');

/**
 * Listoftables Model
 */
class CustomtablesModelListOfTables extends JModelList
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'a.id', 'id',
                'a.published', 'published',
                'a.ordering', 'ordering',
                //'a.created_by','created_by',
                //'a.modified_by','modified_by',
                'a.tablecategory', 'tablecategory',
                'a.tablename', 'tablename'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     */
    public function getItems()
    {
        // load parent items
        return parent::getItems();
    }

    /**
     * Method to autopopulate the model state.
     *
     * @return  void
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
     */
    protected function getListQuery()
    {
        $categoryname = '(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
        $fieldcount = '(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';
        $selects = array();
        $selects[] = ESTables::getTableRowSelects();
        $selects[] = $categoryname . ' AS categoryname';
        $selects[] = $fieldcount . ' AS fieldcount';

        $query = 'SELECT ' . implode(',', $selects) . ' FROM ' . database::quoteName('#__customtables_tables') . ' AS a';
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
                $where [] = '(a.tablename LIKE ' . $search . ')';
            }
        }

        // Filter by Tableid.
        if ($category = $this->getState('filter.tablecategory')) {
            $where [] = 'a.tablecategory = ' . database::quote((int)$category);
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
        $id .= ':' . $this->getState('filter.tablename');
        $id .= ':' . $this->getState('filter.tablecategory');

        return parent::getStoreId($id);
    }
}
