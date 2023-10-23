<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\ListOfFields;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use CustomTables\DataTypes;
use Joomla\CMS\Version;

/**
 * Listoffields Model
 */
class CustomtablesModelListoffields extends JModelList
{
    var $tableid;
    var CT $ct;
    var $helperListOfFields;

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

        $this->ct = new CT;
        $this->ct->setParams();

        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoftables.php');
        $this->helperListOfFields = new listOfFields($this->ct);
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
        $published = $this->getState('filter.published');
        $search = $this->getState('filter.search');
        $type = $this->getState('filter.type');
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirection = $this->state->get('list.direction', 'asc');
        //$limit = $this->state->get('list.limit', 20);
        //$start = $this->state->get('list.start', 0);
        $tableId = common::inputGetInt('tableid');
        return $this->helperListOfFields->getListQuery($tableId, $published, $search, $type, $orderCol, $orderDirection);//, $limit, $start);
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
