<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use JoomlaBasicMisc;
use JRegistry;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class Params
{
    var ?string $pageTitle;
    var ?string $showPageHeading;
    var ?string $pageClassSFX;

    var ?string $listing_id;

    var ?string $tableName;

    var ?string $pageLayout;
    var ?string $itemLayout;
    var ?string $detailsLayout;
    var ?string $editLayout;

    var ?string $groupBy;

    var ?string $sortBy;
    var ?string $forceSortBy;

    var ?string $addUserGroups;
    var ?string $editUserGroups;
    var ?string $publishUserGroups;
    var ?string $deleteUserGroups;

    var bool $allowContentPlugins;
    var ?string $userIdField;
    var ?string $filter;

    var int $showPublished;
    var ?int $limit;

    var ?int $publishStatus;
    var ?string $returnTo;
    var ?bool $guestCanAddNew;
    var ?string $requiredLabel;
    var ?string $msgItemIsSaved;
    var ?int $onRecordAddSendEmail;
    var ?string $sendEmailCondition;
    var ?string $onRecordAddSendEmailTo;
    var ?string $onRecordSaveSendEmailTo;
    var ?string $onRecordAddSendEmailLayout;
    var ?string $emailSentStatusField;

    var bool $showCartItemsOnly;
    var ?string $showCartItemsPrefix;
    var ?string $cartReturnTo;
    var ?string $cartMsgItemAdded;
    var ?string $cartMsgItemDeleted;
    var ?string $cartMsgItemUpdated;

    var ?int $ItemId;
    var ?int $ModuleId;
    var ?string $alias;
    var $app;
    var $jinput;

    var ?string $recordsTable;
    var ?string $recordsUserIdField;
    var ?string $recordsField;

    var bool $blockExternalVars;

    function __construct($menu_params = null, $blockExternalVars = false, $ModuleId = null)
    {
        $this->ModuleId = null;
        $this->blockExternalVars = $blockExternalVars;
        $this->app = Factory::getApplication();
        $this->jinput = $this->app->input;
        $this->sortBy = null;

        if (is_null($menu_params)) {

            if (is_null($ModuleId)) {
                $ModuleId = $this->jinput->getInt('ModuleId');
            }

            if (!is_null($ModuleId)) {
                $module = ModuleHelper::getModuleById(strval($ModuleId));
                $menu_params = new JRegistry;
                $menu_params->loadString($module->params);
                $blockExternalVars = false;
                //Do not block external var parameters because this is the edit form or a task
            } elseif (method_exists($this->app, 'getParams')) {
                $menu_params = $this->app->getParams();
            }
        }
        $this->setParams($menu_params, $blockExternalVars, $ModuleId);
    }

    function setParams($menu_params = null, $blockExternalVars = true, $ModuleId = null): void
    {
        $this->blockExternalVars = $blockExternalVars;
        $this->ModuleId = $ModuleId;

        if (is_null($menu_params)) {
            if (method_exists($this->app, 'getParams')) {

                $menu_params = $this->app->getParams();
            } else {
                $this->pageTitle = null;
                $this->showPageHeading = null;
                $this->pageClassSFX = null;
                $this->listing_id = null;
                $this->tableName = null;
                $this->pageLayout = null;
                $this->itemLayout = null;
                $this->detailsLayout = null;
                $this->editLayout = null;
                $this->groupBy = null;
                $this->sortBy = null;
                $this->forceSortBy = null;
                $this->addUserGroups = null;
                $this->editUserGroups = null;
                $this->publishUserGroups = null;
                $this->deleteUserGroups = null;
                $this->allowContentPlugins = false;
                $this->userIdField = null;
                $this->filter = null;
                $this->showPublished = 2;//Show Any
                $this->limit = null;
                $this->publishStatus = null;
                $this->returnTo = null;
                $this->guestCanAddNew = null;
                $this->requiredLabel = null;
                $this->msgItemIsSaved = null;
                $this->onRecordAddSendEmail = null;
                $this->sendEmailCondition = null;
                $this->onRecordAddSendEmailTo = null;
                $this->onRecordSaveSendEmailTo = null;
                $this->onRecordAddSendEmailLayout = null;
                $this->emailSentStatusField = null;
                $this->showCartItemsOnly = false;
                $this->showCartItemsPrefix = null;
                $this->cartReturnTo = null;
                $this->cartMsgItemAdded = null;
                $this->cartMsgItemDeleted = null;
                $this->cartMsgItemUpdated = null;
                $this->ItemId = null;
                $this->alias = null;
                $this->recordsTable = null;
                $this->recordsUserIdField = null;
                $this->recordsField = null;
                return;
            }
        }

        $this->getForceItemId($menu_params);

        if (!$blockExternalVars and $this->jinput->getString('alias', ''))
            $this->alias = JoomlaBasicMisc::slugify($this->jinput->getString('alias'));
        else
            $this->alias = null;

        $this->pageTitle = $menu_params->get('page_title') ?? null;
        $this->showPageHeading = $menu_params->get('show_page_heading', 1);

        if ($menu_params->get('pageclass_sfx') !== null)
            $this->pageClassSFX = strip_tags($menu_params->get('pageclass_sfx'));

        if (!$blockExternalVars and !is_null($this->jinput->getCmd("listing_id")))
            $this->listing_id = $this->jinput->getCmd("listing_id");
        else
            $this->listing_id = $menu_params->get('listingid');

        if ($this->listing_id == 0 or $this->listing_id == '' or $this->listing_id == '0')
            $this->listing_id = null;

        $this->tableName = null;

        if ($this->jinput->getCmd("task") !== null)
            $this->tableName = $this->jinput->getInt("tableid");//TODO: find better way

        if ($this->tableName === null) {
            $this->tableName = $menu_params->get('establename'); //Table name or id not sanitized
            if ($this->tableName === null or $this->tableName === null)
                $this->tableName = $menu_params->get('tableid'); //Used in the back-end
        }

        //Filter
        $this->userIdField = $menu_params->get('useridfield');

        if (!$blockExternalVars and $this->jinput->getString('filter', '')) {

            $filter = $this->jinput->getString('filter', '');
            if (is_array($filter)) {
                $this->filter = $filter['search'];
            } else
                $this->filter = $filter;
        } else
            $this->filter = $menu_params->get('filter');

        $this->showPublished = (int)$menu_params->get('showpublished');

        //Group BY
        $this->groupBy = $menu_params->get('groupby');

        //Sorting
        if (!$blockExternalVars and !is_null($this->jinput->getCmd('sortby')))
            $this->sortBy = strtolower($this->jinput->getCmd('sortby'));
        elseif (!is_null($menu_params->get('sortby')))
            $this->sortBy = strtolower($menu_params->get('sortby'));

        $this->forceSortBy = $menu_params->get('forcesortby');

        //Limit
        $this->limit = $menu_params->get('limit') ?? 20;

        //Layouts
        $this->pageLayout = $menu_params->get('escataloglayout');
        if (is_null($this->pageLayout))
            $this->pageLayout = $menu_params->get('ct_pagelayout');

        $this->itemLayout = $menu_params->get('esitemlayout');
        if (is_null($this->itemLayout))
            $this->itemLayout = $menu_params->get('ct_itemlayout');

        $this->detailsLayout = $menu_params->get('esdetailslayout');
        $this->editLayout = $menu_params->get('eseditlayout');
        $this->onRecordAddSendEmailLayout = $menu_params->get('onrecordaddsendemaillayout');
        $this->allowContentPlugins = $menu_params->get('allowcontentplugins') ?? false;

        //Shopping Cart

        if ($menu_params->get('showcartitemsonly') != '')
            $this->showCartItemsOnly = (bool)(int)$menu_params->get('showcartitemsonly');
        else
            $this->showCartItemsOnly = false;

        $this->showCartItemsPrefix = 'customtables_';
        if ($menu_params->get('showcartitemsprefix') != '')
            $this->showCartItemsPrefix = $menu_params->get('showcartitemsprefix');

        $this->cartReturnTo = $menu_params->get('cart_returnto');
        $this->cartMsgItemAdded = $menu_params->get('cart_msgitemadded');
        $this->cartMsgItemDeleted = $menu_params->get('cart_msgitemdeleted');
        $this->cartMsgItemUpdated = $menu_params->get('cart_msgitemupdated');

        //Permissions

        $this->editUserGroups = $menu_params->get('editusergroups');

        $this->addUserGroups = $menu_params->get('addusergroups');
        if ($this->addUserGroups == 0)
            $this->addUserGroups = $this->editUserGroups;

        $this->publishUserGroups = $menu_params->get('publishusergroups');
        if ($this->publishUserGroups == 0)
            $this->publishUserGroups = $this->editUserGroups;

        $this->deleteUserGroups = $menu_params->get('deleteusergroups');
        if ($this->deleteUserGroups == 0)
            $this->deleteUserGroups = $this->editUserGroups;


        $this->guestCanAddNew = $menu_params->get('guestcanaddnew');
        $this->publishStatus = $menu_params->get('publishstatus');

        if (!$blockExternalVars and is_null($this->publishStatus))
            $this->publishStatus = $this->jinput->getInt('published');
        else
            $this->publishStatus = 1;

        //Emails
        $this->onRecordAddSendEmail = (int)$menu_params->get('onrecordaddsendemail');
        $this->sendEmailCondition = $menu_params->get('sendemailcondition');
        $this->onRecordAddSendEmailTo = $menu_params->get('onrecordaddsendemailto');
        $this->onRecordSaveSendEmailTo = $menu_params->get('onrecordsavesendemailto');
        $this->emailSentStatusField = $menu_params->get('emailsentstatusfield');

        //Form Saved

        if (!$blockExternalVars and $this->jinput->get('returnto', '', 'BASE64'))
            $this->returnTo = base64_decode($this->jinput->get('returnto', '', 'BASE64'));
        else
            $this->returnTo = $menu_params->get('returnto');

        $this->requiredLabel = $menu_params->get('requiredlabel');
        $this->msgItemIsSaved = $menu_params->get('msgitemissaved');

        $this->recordsTable = $menu_params->get('recordstable');
        $this->recordsUserIdField = $menu_params->get('recordsuseridfield');
        $this->recordsField = $menu_params->get('recordsfield');
    }

    protected function getForceItemId($menu_params): void
    {
        $forceItemId = $menu_params->get('forceitemid');
        if (is_null($forceItemId))
            $forceItemId = $menu_params->get('customitemid');

        if (!is_null($forceItemId)) {
            //Find ItemId by alias
            if ((is_numeric($forceItemId))) {
                if ((int)$forceItemId != 0) {
                    $this->ItemId = (int)$forceItemId;
                    return;
                }
            } elseif ($forceItemId != '') {
                $this->ItemId = (int)JoomlaBasicMisc::FindItemidbyAlias($forceItemId);//Accepts menu Itemid and alias
                return;
            }
        }

        $this->ItemId = $this->app->input->getInt('Itemid', 0);
    }
}
