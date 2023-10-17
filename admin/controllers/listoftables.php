<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage controllers/listoffields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.application.component.controlleradmin');

use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use CustomTables\ExportTables;

class CustomtablesControllerListoftables extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFTABLES';

    public function getModel($name = 'Tables', $prefix = 'CustomtablesModel', $config = array())
    {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function export()
    {
        $cIds = common::inputPost('cid', array(), 'array');
        $cIds = ArrayHelper::toInteger($cIds);

        $download_link = ExportTables::export($cIds);

        if ($download_link != '') {
            $msg = 'COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_EXPORTED';

            if (count($cIds) == 1)
                $msg .= '_1';

            $msg = JText::sprintf($msg, count($cIds));

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
