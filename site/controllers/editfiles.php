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
use CustomTables\CT;
use CustomTables\CTUser;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$ct = new CT;

$model = $this->getModel('edititem');
$model->load($ct);

$model->params = Factory::getApplication()->getParams();


$model->listing_id = common::inputGetCmd("listing_id");

if (!CTUser::CheckAuthorization($ct, 5)) {
    //not authorized
    Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');


    $link = JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL()));
    $this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
    return;
} else {
    switch (common::inputGetCmd('task')) {

        case 'add' :

            $model = $this->getModel('editfiles');

            if ($model->add()) {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ADDED');
            } else {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_ADDED');
            }

            $fileboxname = common::inputGetCmd('fileboxname');
            $listing_id = common::inputGet("listing_id", 0, 'INT');
            $returnto = common::inputGet('returnto', '', 'BASE64');
            $Itemid = common::inputGet('Itemid', 0, 'INT');

            $link = 'index.php?option=com_customtables&view=editfiles'

                . '&fileboxname=' . $fileboxname
                . '&listing_id=' . $listing_id
                . '&returnto=' . $returnto
                . '&Itemid=' . $Itemid;

            $this->setRedirect($link, $msg);

            break;

        case 'delete' :

            $model = $this->getModel('editfiles');

            if ($model->delete()) {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_DELETED');
            } else {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_DELETED');
            }
            //$establename=common::inputGetCmd( 'establename');
            $fileboxname = common::inputGetCmd('fileboxname');
            $listing_id = common::inputGet("listing_id", 0, 'INT');
            $returnto = common::inputGet('returnto', '', 'BASE64');
            $Itemid = common::inputGet('Itemid', 0, 'INT');

            $link = 'index.php?option=com_customtables&view=editfiles'

                . '&fileboxname=' . $fileboxname
                . '&listing_id=' . $listing_id
                . '&returnto=' . $returnto
                . '&Itemid=' . $Itemid;

            $this->setRedirect($link, $msg);

            break;

        case 'saveorder' :

            $model = $this->getModel('editfiles');


            if ($model->reorder()) {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ORDER_SAVED');
            } else {
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ORDER_NOT_SAVED');
            }
            $returnto = common::inputGet('returnto', '', 'BASE64');

            $link = $returnto = base64_decode(common::inputGet('returnto', '', 'BASE64'));


            $this->setRedirect($link, $msg);

            break;

        case 'cancel' :

            $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT_CANCELED');
            $link = $returnto = base64_decode(common::inputGet('returnto', '', 'BASE64'));

            $this->setRedirect($link, $msg);

            break;
        default:

            parent::display();
    }
}