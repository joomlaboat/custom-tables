<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage listoftables.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;

use Joomla\CMS\Component\ComponentHelper;

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Listoftables Model
 */
class CustomtablesModelDatabasecheck extends JModelList
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
     * Method to build an SQL query to load the list data.
     *
     * @return    string    An SQL query
     */
    /*
   protected function getListQuery()
   {
       // Get the user object.
       $user = Factory::getUser();
       // Create a new query object.
       $db = Factory::getDBO();
       $query = $db->getQuery(true);

       // Select some fields

       $categoryname='(SELECT categoryname FROM #__customtables_categories AS categories WHERE categories.id=a.tablecategory LIMIT 1)';
       $fieldcount='(SELECT COUNT(fields.id) FROM #__customtables_fields AS fields WHERE fields.tableid=a.id AND fields.published=1 LIMIT 1)';
       $selects=array();
       $selects[]=ESTables::getTableRowSelects();
       $selects[]=$categoryname.' AS categoryname';
       $selects[]=$fieldcount.' AS fieldcount';


       $query->select(implode(',',$selects));

       // From the customtables_item table
       $query->from($db->quoteName('#__customtables_tables', 'a'));

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
               $query->where('(a.tablename LIKE '.$search.')');
           }
       }

       $search = $this->getState('filter.tablecategory');
       // Filter by Tableid.
       if ($category = $this->getState('filter.tablecategory'))
       {
           $query->where('a.tablecategory = ' . $db->quote((int)$category));
       }


       // Add the list ordering clause.
       $orderCol = $this->state->get('list.ordering', 'a.id');
       $orderDirn = $this->state->get('list.direction', 'asc');
       if ($orderCol != '')
       {
           $query->order($db->escape($orderCol . ' ' . $orderDirn));
       }

       return $query;
   }
   */

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
