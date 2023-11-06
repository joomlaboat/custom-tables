<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use Joomla\Utilities\ArrayHelper;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$layout = common::inputGet('layout', '', 'CMD');


switch (common::inputGet('task', '', 'CMD')) {
    case 'edit':

        common::inputSet('view', 'listedit');
        common::inputSet('layout', 'form');

        parent::display();

        break;

    case 'save':

        $model = $this->getModel('listedit');

        $link = 'index.php?option=com_customtables&view=list&Itemid=' . common::inputGet('Itemid', 0, 'INT');
        if ($model->store()) {
            $msg = common::translate('COM_CUSTOMTABLES_OPTION_SAVED');
            $this->setRedirect($link, $msg);
        } else {
            $msg = common::translate('COM_CUSTOMTABLES_OPTION_NOT_SAVED');
            $this->setRedirect($link, $msg, 'error');
        }

        break;

    case 'cancel':

        $link = 'index.php?option=com_customtables&view=list&Itemid=' . common::inputGet('Itemid', 0, 'INT');

        $msg = '';

        $this->setRedirect($link, $msg);


        break;

    case 'remove':


        $link = 'index.php?option=com_customtables&view=list&Itemid=' . common::inputGet('Itemid', 0, 'INT');

        // Check for request forgeries
        JSession::checkToken() or jexit('COM_CUSTOMTABLES_INVALID_TOKEN');

        // Get some variables from the request

        $cid = common::inputPost('cid', array(), 'array');
        ArrayHelper::toInteger($cid);

        if (!count($cid)) {
            $this->setRedirect($link, common::translate('COM_CUSTOMTABLES_OPTIONS_NOT_SELECTED'));
            return false;
        }

        $model = $this->getModel('List');
        if ($n = $model->delete($cid)) {
            $msg = common::translate('% COM_CUSTOMTABLES_OPTIONS_DELETED', $n);
        } else {
            $msg = $model->getError();
        }
        $this->setRedirect($link, $msg);

        break;

    default:

        common::inputSet('view', 'list');
        parent::display();

        break;
}
