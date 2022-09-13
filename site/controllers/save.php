<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\TwigProcessor;
use Joomla\CMS\Factory;

$jinput = Factory::getApplication()->input;

$task = $jinput->getCmd('task');

switch ($task) {
    case 'saveandcontinue':
    case 'saveascopy':
    case 'save' :
        if (CustomTablesSave($task, $this))
            parent::display();

        break;

    case 'cancel':

        $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT_CANCELED');
        $link = $returnto = base64_decode(Factory::getApplication()->input->get('returnto', '', 'BASE64'));
        $this->setRedirect($link, $msg);

        break;

    case 'delete':
        if (CustomTablesDelete($this))
            parent::display();

        break;

    default:
        parent::display();
}

function CustomTablesDelete($this_)
{
    $jinput = Factory::getApplication()->input;

    $ct = new CT;

    $edit_model = $this_->getModel('edititem');
    $edit_model->load($ct);

    $PermissionIndex = 3;//delete

    if (!CTUser::CheckAuthorization($ct, $PermissionIndex)) {
        // not authorized
        if ($ct->Env->clean == 1) {
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
            return false;
        } else {
            $link = $edit_model->ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $ct->Env->encoded_current_url;
            $this_->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
        }
        return true;
    } else {
        $returnto = $jinput->get('returnto', '', 'BASE64');
        $decodedReturnTo = base64_decode($returnto);

        if ($returnto != '') {
            $link = $decodedReturnTo;
            if (!str_contains($link, 'http:') and !str_contains($link, 'https:')) $link .= $edit_model->ct->Env->WebsiteRoot . $link;
        } else
            $link = $ct->Env->WebsiteRoot . 'index.php?Itemid=' . $ct->Params->ItemId;

        if ($edit_model->delete()) {
            if ($ct->Env->clean == 1)
                die('deleted');
            else
                $this_->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'));
        } else {
            if ($ct->Env->clean == 1)
                die('error');
            else
                $this_->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'));
        }
    }
    return true;
}

function CustomTablesSave($task, $this_)
{
    $jinput = Factory::getApplication()->input;
    $returnto = $jinput->get('returnto', '', 'BASE64');
    $link = base64_decode($returnto);

    $jinput->set('task', '');
    $ct = new CT(null, false);
    $model = $this_->getModel('edititem');

    if (!$model->load($ct))
        return false;

    if (!CTUser::CheckAuthorization($ct)) {
        $link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=' . base64_encode(JoomlaBasicMisc::curPageURL());
        $this_->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
    } else {
        $msg_ = '';
        $isOk = true;

        if ($task == 'saveascopy')
            $isOk = $model->copy($msg_, $link);
        else
            $isOk = $model->store($msg_, $link);

        if ($task == 'saveandcontinue') {
            $link = JoomlaBasicMisc::deleteURLQueryOption($link, "listing_id");

            if (!str_contains($link, "?"))
                $link .= '?';
            else
                $link .= '&';

            $link .= 'listing_id=' . $jinput->getInt("listing_id");
            //stay on the same page if "saveandcontinue"
        }

        if ($isOk) {

            if ($ct->Params->msgItemIsSaved == '-')
                $msg = '';
            elseif ($msg_ != '')
                $msg = $msg_;
            elseif ($ct->Params->msgItemIsSaved == '')
                $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED');
            else
                $msg = $ct->Params->msgItemIsSaved;

            if ($ct->Env->legacysupport) {
                $siteLibraryPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
                require_once($siteLibraryPath . 'layout.php');

                $LayoutProc = new LayoutProcessor($ct);
                $LayoutProc->layout = $msg;
                $msg = $LayoutProc->fillLayout(array(), null, '[]', true);
            }

            $twig = new TwigProcessor($ct, $msg);
            $msg = $twig->process();


            if (Factory::getApplication()->input->get('clean', 0, 'INT') == 1) {
                die('saved');
            } elseif ($link != '') {
                $link = str_replace('$get_listing_id', Factory::getApplication()->input->get("listing_id", 0, 'INT'), $link);

                if (!str_contains($link, 'tmpl=component')) {
                    if ($msg != '') {


                        $this_->setRedirect($link, $msg);
                    } else
                        $this_->setRedirect($link);


                } else {
                    $this_->setRedirect($link);
                }

            } else {
                if (Factory::getApplication()->input->get('submitbutton', '', 'CMD') == 'nextprint') {
                    $link = $ct->Env->WebsiteRoot . 'index.php?option=com_customtables&view=details'
                        . '&Itemid=' . Factory::getApplication()->input->get('Itemid', 0, 'INT')
                        . '&listing_id=' . Factory::getApplication()->input->get("listing_id", 0, 'INT')
                        . '&tmpl=component'
                        . '&print=1';

                    $status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

                    echo '<p style="text-align:center;">
						<input type="button" class="button" value="' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT') . '"
					onClick=\'window.open("' . $link . '","win2","' . $status . '"); return false; \'>
					</p>';

                    Factory::getApplication()->input->get('view', 'details');


                    return true;


                } else {
                    $link = $ct->Env->WebsiteRoot . 'index.php?option=com_customtables&view=catalog&Itemid=' . Factory::getApplication()->input->get('Itemid', 0, 'INT');

                    if ($msg != '')
                        $this_->setRedirect($link, $msg);
                    else
                        $this_->setRedirect($link);


                }
            }
        } else {
            if ($ct->Params->msgItemIsSaved == 'COM_CUSTOMTABLES_INCORRECT_CAPTCHA') {
                Factory::getApplication()->enqueueMessage($msg_, 'error');
                echo '
				<script>
setTimeout("history.go(-1)", 3000);
</script>';

            } else {
                if ($link != '') {
                    $msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_NOT_SAVED');
                    $this_->setRedirect($link, $msg, 'error');
                } else
                    Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_NOT_SAVED'), 'error');
            }
        }
    }
    return true;
}
