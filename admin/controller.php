<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/controller
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;

// import Joomla controller library
jimport('joomla.application.component.controller');

/**
 * General Controller of Customtables component
 */
class CustomtablesController extends JControllerLegacy
{
    public function __construct($config = array())
    {
        // set the default view
        $config['default_view'] = 'customtables';
        parent::__construct($config);
    }

    function display($cachable = false, $urlparams = false)
    {
        // set default view if not set
        $view = $this->input->getCmd('view', 'customtables');
        $data = $this->getViewRelation($view);
        $layout = $this->input->get('layout', null, 'WORD');
        $id = $this->input->getCmd('id');

        // Check for edit form.
        if (CustomtablesHelper::checkArray($data)) {
            if ($data['edit'] && $layout == 'edit' && !$this->checkEditId('com_customtables.edit.' . $data['view'], $id)) {
                // Somehow the person just went to the form - we don't allow that.

                Factory::getApplication()->enqueueMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');

                // check if item was opened from other then its own list view
                $ref = $this->input->getCmd('ref', 0);
                $refid = $this->input->getInt('refid', 0);
                // set redirect
                if ($refid > 0 && CustomtablesHelper::checkString($ref)) {
                    // redirect to item of ref
                    if ($ref == 'records') {
                        $refid = $this->input->getCmd('refid', 0);
                        $this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . (string)$ref . '&layout=edit&id=' . $refid, false));
                    } else
                        $this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . (string)$ref . '&layout=edit&id=' . (int)$refid, false));
                } elseif (CustomtablesHelper::checkString($ref)) {

                    // redirect to ref
                    $this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . (string)$ref, false));
                } else {
                    // normal redirect back to the list view
                    $this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . $data['views'], false));
                }

                return false;
            }
        }

        return parent::display($cachable, $urlparams);
    }

    protected function checkEditId($context, $id): bool
    {
        if ($id) {
            $values = (array)Factory::getApplication()->getUserState($context . '.id');

            $result = \in_array($id, $values); //To support both int and cmd IDs

            if (\defined('JDEBUG') && JDEBUG) {
                Factory::getApplication()->getLogger()->info(
                    sprintf(
                        'Checking edit ID %s.%s: %d %s',
                        $context,
                        $id,
                        (int)$result,
                        str_replace("\n", ' ', print_r($values, 1))
                    ),
                    array('category' => 'controller')
                );
            }

            return $result;
        }

        // No id for a new item.
        return true;
    }

    protected function getViewRelation($view)
    {
        if (CustomtablesHelper::checkString($view)) {
            // the view relationships
            $views = array(
                'categories' => 'listofcategories',
                'tables' => 'listoftables',
                'layouts' => 'listoflayouts',
                'fields' => 'listoffields',
                'records' => 'listofrecords',
                'documentation' => 'documentation',
                'databasecheck' => 'databasecheck'
            );
            // check if this is a list view
            if (in_array($view, $views)) {
                // this is a list view
                return array('edit' => false, 'view' => array_search($view, $views), 'views' => $view);
            } // check if it is an edit view
            elseif (array_key_exists($view, $views)) {
                // this is an edit view
                return array('edit' => true, 'view' => $view, 'views' => $views[$view]);
            }
        }
        return false;
    }
}
