<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage listofrecords.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\Fields;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listofrecords Model
 */
class CustomtablesModelListOfRecords extends JModelList
{
    var CT $ct;
    var $ordering_realfieldname;

    public function __construct($config = array())
    {
        $this->ct = new CT;
        $this->ct->getTable(common::inputGetInt('tableid', 0), null);

        if ($this->ct->Table->tablename === null) {
            Factory::getApplication()->enqueueMessage('Table not selected.', 'error');
            return;
        }

        //Check if ordering type field exists
        $this->ordering_realfieldname = '';
        foreach ($this->ct->Table->fields as $field) {
            if ($field['type'] == 'ordering') {
                $this->ordering_realfieldname = $field['realfieldname'];
                break;
            }
        }

        //Ordering
        if (empty($config['filter_records']))
            $config['filter_fields'] = array('id', 'published', 'custom');

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
        $items = parent::getItems();
        return $items;
    }

    /**
     * Method to autopopulate the model state.
     *
     * @return  void
     */
    protected function populateState($ordering = null, $direction = 'asc')
    {
        if ($this->ordering_realfieldname != '' and $ordering === null)
            $ordering = $this->ct->Table->realtablename . '.' . $this->ordering_realfieldname;

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

    protected function getListQuery()
    {
        // Get the user object.
        $user = Factory::getUser();

        // Create a new query object.
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        $query->select(implode(',', $this->ct->Table->selects));

        // From the customtables_item table
        $query->from($db->quoteName($this->ct->Table->realtablename, $this->ct->Table->realtablename));

        $wheres_and = [];
        // Filter by published state
        if ($this->ct->Table->published_field_found) {
            $published = $this->getState('filter.published');

            if (is_numeric($published))
                $wheres_and[] = $this->ct->Table->realtablename . '.published = ' . (int)$published;
            elseif (is_null($published) or $published === '')
                $wheres_and[] = '(' . $this->ct->Table->realtablename . '.published = 0 OR ' . $this->ct->Table->realtablename . '.published = 1)';
        }

        // Filter by search.
        $search = $this->getState('filter.search');

        if ($search != '') {
            $wheres = [];

            foreach ($this->ct->Table->fields as $fieldRow) {
                if ($fieldRow['type'] == 'string') {
                    $realfieldname = $fieldRow['realfieldname'];
                    $where = $db->quote('%' . $db->escape($search) . '%');
                    $wheres[] = ('(' . $this->ct->Table->realtablename . '.' . $realfieldname . ' LIKE ' . $where . ')');
                }
            }
            $wheres_and[] = '(' . implode(' OR ', $wheres) . ')';
        }

        if (count($wheres_and) > 0) {
            $where_str = implode(' AND ', $wheres_and);
            $query->where($where_str);
        }

        // Add the list ordering clause.
        $order_by_Col = $this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname;
        $orderDirn = $this->state->get('list.direction', 'asc');

        if ($this->ct->Env->version < 4) {
            if ($this->ordering_realfieldname != '')
                $order_by_Col = $this->ct->Table->realtablename . '.' . $this->ordering_realfieldname;
        } else {
            $orderCol = $this->state->get('list.ordering', ($this->ordering_realfieldname != '' ? 'custom' : 'id'));

            if ($orderCol == 'id')
                $order_by_Col = $this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname;
            elseif ($orderCol == 'published')
                $order_by_Col = $this->ct->Table->realtablename . '.published';
            elseif ($orderCol == 'custom' and $this->ordering_realfieldname != '')
                $order_by_Col = $this->ct->Table->realtablename . '.' . $this->ordering_realfieldname;
        }

        $query->order($db->escape($order_by_Col . ' ' . $orderDirn));

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
        $id .= ':' . $this->getState('filter.published');
        return parent::getStoreId($id);
    }
}
