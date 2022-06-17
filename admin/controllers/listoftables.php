<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage controllers/listoffields.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use CustomTables\ExportTables;

/**
 * Listoftables Controller
 */
class CustomtablesControllerListoftables extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFTABLES';

    /**
     * Proxy for getModel.
     * @since    2.5
     */
    public function getModel($name = 'Tables', $prefix = 'CustomtablesModel', $config = array())
    {
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));

        return $model;
    }

    public function export()
    {
        $cids = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cids = ArrayHelper::toInteger($cids);

        $download_link = ExportTables::export($cids);

        if ($download_link != '') {
            $msg = 'COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_EXPORTED';

            if (count($cids) == 1)
                $msg .= '_1';

            $msg = JText::sprintf($msg, count($cids));

            $msg .= '&nbsp;&nbsp;<a href="' . $download_link . '" target="_blank">Download (Click Save Link As...)</a>';
        } else {
            $msg = Text::_('COM_CUSTOMTABLES_TABLES_UNABLETOEXPORT');
        }

        Factory::getApplication()->enqueueMessage($msg, 'success');

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoftables';

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }
}
