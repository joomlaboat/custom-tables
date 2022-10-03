<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage listoflayouts.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

use CustomTables\CT;
use CustomTables\Layouts;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * Listoflayouts Model
 */
class CustomtablesModelListOfLayouts extends JModelList
{
    var CT $ct;

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'a.id', 'id',
                'a.published', 'published',
                'a.layoutname', 'layoutname',
                'a.layouttype', 'layouttype',
                'a.tableid', 'tableid'
            );
        }

        parent::__construct($config);

        $this->ct = new CT;
        $this->ct->setParams();//$params
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

        // set selection value to a translatable value
        if (CustomtablesHelper::checkArray($items)) {
            $Layouts = new Layouts($this->ct);
            $translations = $Layouts->layoutTypeTranslation();

            foreach ($items as $nr => &$item) {
                // convert layouttype
                if (isset($translations[$item->layouttype])) {
                    $item->layouttype = $translations[$item->layouttype];
                } else {
                    $item->layouttype = '<span style="color:red;">NOT SELECTED</span>';
                }
            }
        }


        // return items
        return $items;
    }

    protected function populateState($ordering = null, $direction = null)
    {
        if ($this->ct->Env->version < 4) {
            $layouttype = $this->getUserStateFromRequest($this->context . '.filter.layouttype', 'filter_layouttype');
            $this->setState('filter.layouttype', $layouttype);

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
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return    string    An SQL query
     */
    protected function getListQuery(): string
    {
        // Create a new query object.
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Select some fields
        $tabletitle = '(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid LIMIT 1)';
        $modifiedby = '(SELECT name FROM #__users AS u WHERE u.id=a.modified_by LIMIT 1)';
        $query->select('a.*, ' . $tabletitle . ' AS tabletitle, ' . $modifiedby . ' AS modifiedby');

        // From the customtables_item table
        $query->from($db->quoteName('#__customtables_layouts', 'a'));

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published))
            $query->where('a.published = ' . (int)$published);
        elseif (is_null($published) or $published === '')
            $query->where('(a.published = 0 OR a.published = 1)');

        // Filter by search.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int)substr($search, 3));
            } else {
                $search_clean = $db->quote('%' . $db->escape($search) . '%');
                $query->where('(
					(a.layoutname LIKE ' . $search_clean . ') OR 
					INSTR(a.layoutcode,' . $db->quote($search) . ') OR 
					INSTR(a.layoutmobile,' . $db->quote($search) . ') OR 
					INSTR(a.layoutcss,' . $db->quote($search) . ') OR
					INSTR(a.layoutjs,' . $db->quote($search) . ')
					)');
            }
        }

        // Filter by Layouttype.
        if ($layouttype = $this->getState('filter.layouttype')) {
            $query->where('a.layouttype = ' . $db->quote($db->escape($layouttype)));
        }
        // Filter by Tableid.
        if ($tableid = $this->getState('filter.tableid')) {
            $query->where('a.tableid = ' . $db->quote($db->escape($tableid)));
        }
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'asc');
        if ($orderCol != '') {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

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
        $id .= ':' . $this->getState('filter.layoutname');
        $id .= ':' . $this->getState('filter.layouttype');
        $id .= ':' . $this->getState('filter.tableid');

        return parent::getStoreId($id);
    }
}
