<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @subpackage listoflayouts.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

use CustomTables\CT;
use CustomTables\ListOfLayouts;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * Listoflayouts Model
 */
class CustomtablesModelListOfLayouts extends JModelList
{
    var CT $ct;
    var $helperListOfLayout;

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
        $this->ct->setParams();

        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-listoflayouts.php');
        $this->helperListOfLayout = new listOfLayouts($this->ct);
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (is_array($items))
            return $this->helperListOfLayout->translateLayoutTypes($items);
        else
            return [];
    }

    protected function getListQuery()
    {
        $published = $this->getState('filter.published');
        $search = $this->getState('filter.search');
        $layoutType = $this->getState('filter.layouttype');
        $tableid = $this->getState('filter.tableid');
        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirection = $this->state->get('list.direction', 'asc');
        //$limit = $this->state->get('list.limit', 20);
        //$start = $this->state->get('list.start', 0);

        return $this->helperListOfLayout->getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection);//, $limit, $start);
    }

    protected function populateState($ordering = null, $direction = null)
    {
        if ($this->ct->Env->version < 4) {
            $layoutType = $this->getUserStateFromRequest($this->context . '.filter.layouttype', 'filter_layouttype');
            $this->setState('filter.layouttype', $layoutType);

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
        if ($this->ct->Env->version < 4) {
            $ordering = $this->state->get('list.ordering');
            $direction = strtoupper($this->state->get('list.direction'));
            $app = Factory::getApplication();
            $app->setUserState($this->context . '.list.fullordering', $ordering . ' ' . $direction);
        }
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
