<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use CustomTables\CT;
use CustomTables\DataTypes;
use Joomla\CMS\Version;

/**
 * Listoffields Model
 */
class CustomtablesModelListoffields extends JModelList
{
    var $tableid;

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

        $translations = DataTypes::fieldTypeTranslation();
        $isrequiredTranslation = DataTypes::isrequiredTranslation();

        // set selection value to a translatable value
        if (CustomtablesHelper::checkArray($items)) {
            foreach ($items as $nr => &$item) {
                // convert type
                if (isset($translations[$item->type])) {
                    $item->typeLabel = $translations[$item->type];
                } else {
                    $item->typeLabel = '<span style="color:red;">NOT SELECTED</span>';
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
     */

    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $version_object = new Version;
        $version = (int)$version_object->getShortVersion();

        if ($version < 4) {
            $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
            $this->setState('filter.search', $search);

            $type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
            $this->setState('filter.type', $type);

            $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
            $this->setState('filter.published', $published);
        }

        $this->setState('params', ComponentHelper::getParams('com_customtables'));

        parent::populateState($ordering, $direction);

        if ($version < 4) {
            $ordering = $this->state->get('list.ordering');
            $direction = $this->state->get('list.direction');
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
        $jinput = Factory::getApplication()->input;
        $this->tableid = $jinput->getInt('tableid', 0);

        // Create a new query object.
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Select some fields
        $tabletitle = '(SELECT tabletitle FROM #__customtables_tables AS tables WHERE tables.id=a.tableid)';
        if ($db->serverType == 'postgresql')
            $realfieldname_query = 'CASE WHEN customfieldname!=\'\' THEN customfieldname ELSE CONCAT(\'es_\',fieldname) END AS realfieldname';
        else
            $realfieldname_query = 'IF(customfieldname!=\'\', customfieldname, CONCAT(\'es_\',fieldname)) AS realfieldname';

        $query->select('a.*, ' . $tabletitle . ' AS tabletitle, ' . $realfieldname_query);

        // From the customtables_item table
        $query->from($db->quoteName('#__customtables_fields', 'a'));

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
                $search = $db->quote('%' . $db->escape($search) . '%');
                $query->where('(a.fieldname LIKE ' . $search . ' OR a.fieldtitle LIKE ' . $search . ')');
            }
        }

        // Filter by Type.
        if ($type = $this->getState('filter.type')) {
            $query->where('a.type = ' . $db->quote($db->escape($type)));
        }

        if ($this->tableid != 0) {
            $query->where('a.tableid = ' . $db->quote($db->escape($this->tableid)));
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.ordering');
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
        $id .= ':' . $this->getState('filter.fieldname');
        $id .= ':' . $this->getState('filter.type');

        return parent::getStoreId($id);
    }
}
