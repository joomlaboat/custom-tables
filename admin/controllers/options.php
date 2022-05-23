<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

class CustomTablesControllerOptions extends JControllerForm
{
    protected $task;

    public function __construct($config = array())
    {
        $this->view_list = 'Listofoptions'; // safeguard for setting the return view listing to the main view.
        parent::__construct($config);
    }

    public function save($key = null, $urlVar = null)
    {
        // get the referal details
        $this->ref = $this->input->get('ref', 0, 'word');
        $this->refid = $this->input->get('refid', 0, 'int');

        if ($this->ref || $this->refid) {
            // to make sure the item is checkedin on redirect
            $this->task = 'save';
        }

        $saved = parent::save($key, $urlVar);

        if ($this->refid && $saved) {
            $redirect = '&view=' . (string)$this->ref . '&layout=default&id=' . (int)$this->refid;

            // Redirect to the item screen.
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . $redirect, false
                )
            );
        } elseif ($this->ref && $saved) {
            $redirect = '&view=' . (string)$this->ref;

            // Redirect to the list screen.
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . $redirect, false
                )
            );
        }
        return $saved;
    }

    public function cancel($key = null)
    {
        // get the referal details
        $this->ref = $this->input->get('ref', 0, 'word');
        $this->refid = $this->input->get('refid', 0, 'int');

        $cancel = parent::cancel($key);

        if ($cancel) {
            if ($this->refid) {
                $redirect = '&view=' . (string)$this->ref . '&layout=edit&id=' . (int)$this->refid;

                // Redirect to the item screen.
                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . $redirect, false
                    )
                );
            } elseif ($this->ref) {
                $redirect = '&view=' . (string)$this->ref;

                // Redirect to the list screen.
                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . $redirect, false
                    )
                );
            }
        } else {
            // Redirect to the items screen.
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list, false
                )
            );
        }
        return $cancel;
    }

    protected function allowAdd($data = array())
    {        // In the absense of better information, revert to the component permissions.
        return parent::allowAdd($data);
    }

    /**
     * Method override to check if you can edit an existing record.
     *
     * @param array $data An array of input data.
     * @param string $key The name of the key for the primary key.
     *
     * @return  boolean
     *
     * @since   1.6
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        // get user object.
        $user = Factory::getUser();
        // get record id.
        $recordId = (int)isset($data[$key]) ? $data[$key] : 0;


        if ($recordId) {
            // The record has been set. Check the record permissions.
            $permission = $user->authorise('core.edit', 'com_customtables.options.' . (int)$recordId);
            if (!$permission) {
                if ($user->authorise('core.edit.own', 'com_customtables.options.' . $recordId)) {
                    // Now test the owner is the user.
                    $ownerId = (int)isset($data['created_by']) ? $data['created_by'] : 0;
                    if (empty($ownerId)) {
                        // Need to do a lookup from the model.
                        $record = $this->getModel()->getItem($recordId);

                        if (empty($record)) {
                            return false;
                        }
                        $ownerId = $record->created_by;
                    }

                    // If the owner matches 'me' then allow.
                    if ($ownerId == $user->id) {
                        if ($user->authorise('core.edit.own', 'com_customtables')) {
                            return true;
                        }
                    }
                }
                return false;
            }
        }
        // Since there is no permission, revert to the component permissions.
        return parent::allowEdit($data, $key);
    }

    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $tmpl = $this->input->get('tmpl');
        $layout = $this->input->get('layout', 'edit', 'string');

        $ref = $this->input->get('ref', 0, 'string');
        $refid = $this->input->get('refid', 0, 'int');

        // Setup redirect info.

        $append = '';

        if ($refid) {
            $append .= '&ref=' . (string)$ref . '&refid=' . (int)$refid;
        } elseif ($ref) {
            $append .= '&ref=' . (string)$ref;
        }

        if ($tmpl) {
            $append .= '&tmpl=' . $tmpl;
        }

        if ($layout) {
            $append .= '&layout=' . $layout;
        }

        if ($recordId) {
            $append .= '&' . $urlVar . '=' . $recordId;
        }

        return $append;
    }

}
