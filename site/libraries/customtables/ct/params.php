<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

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
    var ?string $ModuleId;
    var ?string $alias;
    var $app;

    var ?string $recordsTable;
    var ?string $recordsUserIdField;
    var ?string $recordsField;
    var float $version;

    var bool $blockExternalVars;

    function __construct(?array $menu_params = null, $blockExternalVars = false, ?string $ModuleId = null)
    {
        $this->ModuleId = null;
        $this->blockExternalVars = $blockExternalVars;
        $this->sortBy = null;
        $this->allowContentPlugins = false;

        if (defined('_JEXEC'))
            $this->constructJoomlaParams($menu_params, $blockExternalVars, $ModuleId);
        else
            $this->constructWPParams();
    }

    protected function constructJoomlaParams(?array $menu_paramsArray = null, $blockExternalVars = true, ?string $ModuleId = null): void
    {
        $this->app = Factory::getApplication();

        if (is_null($menu_paramsArray)) {

            if (is_null($ModuleId)) {
                $ModuleIdInt = common::inputGetInt('ModuleId');

                if ($ModuleIdInt)
                    $ModuleId = strval($ModuleIdInt);
                else
                    $ModuleId = null;
            }

            if (!is_null($ModuleId)) {
                $module = ModuleHelper::getModuleById($ModuleId);
                $menu_params = new Registry;//Joomla Specific
                $menu_params->loadString($module->params);
                $menu_paramsArray = self::menuParamsRegistry2Array($menu_params);
                $blockExternalVars = false;
                //Do not block external var parameters because this is the edit form or a task
            } elseif (method_exists($this->app, 'getParams')) {
                try {
                    if ($this->app->getLanguage() !== null) {
                        $menu_params_registry = @$this->app->getParams();//Joomla specific
                        $menu_paramsArray = self::menuParamsRegistry2Array($menu_params_registry);
                    } else
                        $menu_paramsArray = null;
                } catch (Exception $e) {
                    $menu_paramsArray = null;
                }
            }
        }
        $this->setParams($menu_paramsArray, $blockExternalVars, $ModuleId);
    }

    public static function menuParamsRegistry2Array(Registry $menu_params_registry): array
    {
        $menu_params = [];
        $menu_params['page_title'] = $menu_params_registry->get('page_title') ?? null;
        $menu_params['show_page_heading'] = $menu_params_registry->get('show_page_heading', 1);
        $menu_params['pageclass_sfx'] = $menu_params_registry->get('pageclass_sfx');
        $menu_params['listingid'] = $menu_params_registry->get('listingid');
        $menu_params['establename'] = $menu_params_registry->get('establename');
        $menu_params['tableid'] = $menu_params_registry->get('tableid');
        $menu_params['useridfield'] = $menu_params_registry->get('useridfield');
        $menu_params['filter'] = $menu_params_registry->get('filter');
        $menu_params['showpublished'] = $menu_params_registry->get('showpublished');
        $menu_params['groupby'] = $menu_params_registry->get('groupby');
        $menu_params['sortby'] = $menu_params_registry->get('sortby');
        $menu_params['forcesortby'] = $menu_params_registry->get('forcesortby');
        $menu_params['limit'] = $menu_params_registry->get('limit');
        $menu_params['escataloglayout'] = $menu_params_registry->get('escataloglayout');
        $menu_params['ct_pagelayout'] = $menu_params_registry->get('ct_pagelayout');
        $menu_params['esitemlayout'] = $menu_params_registry->get('esitemlayout');
        $menu_params['ct_itemlayout'] = $menu_params_registry->get('ct_itemlayout');
        $menu_params['esdetailslayout'] = $menu_params_registry->get('esdetailslayout');
        $menu_params['esdetailslayout'] = $menu_params_registry->get('esdetailslayout');
        $menu_params['eseditlayout'] = $menu_params_registry->get('eseditlayout');
        $menu_params['onrecordaddsendemaillayout'] = $menu_params_registry->get('onrecordaddsendemaillayout');
        $menu_params['allowcontentplugins'] = $menu_params_registry->get('allowcontentplugins');
        $menu_params['showcartitemsonly'] = $menu_params_registry->get('showcartitemsonly');
        $menu_params['showcartitemsprefix'] = $menu_params_registry->get('showcartitemsprefix');
        $menu_params['cart_returnto'] = $menu_params_registry->get('cart_returnto');
        $menu_params['cart_msgitemadded'] = $menu_params_registry->get('cart_msgitemadded');
        $menu_params['cart_msgitemdeleted'] = $menu_params_registry->get('cart_msgitemdeleted');
        $menu_params['cart_msgitemupdated'] = $menu_params_registry->get('cart_msgitemupdated');
        $menu_params['editusergroups'] = $menu_params_registry->get('editusergroups');
        $menu_params['addusergroups'] = $menu_params_registry->get('addusergroups');
        $menu_params['publishusergroups'] = $menu_params_registry->get('publishusergroups');
        $menu_params['deleteusergroups'] = $menu_params_registry->get('deleteusergroups');
        $menu_params['guestcanaddnew'] = $menu_params_registry->get('guestcanaddnew');
        $menu_params['publishstatus'] = $menu_params_registry->get('publishstatus');
        $menu_params['onrecordaddsendemail'] = $menu_params_registry->get('onrecordaddsendemail');
        $menu_params['sendemailcondition'] = $menu_params_registry->get('sendemailcondition');
        $menu_params['onrecordaddsendemailto'] = $menu_params_registry->get('onrecordaddsendemailto');
        $menu_params['onrecordsavesendemailto'] = $menu_params_registry->get('onrecordsavesendemailto');
        $menu_params['emailsentstatusfield'] = $menu_params_registry->get('emailsentstatusfield');
        $menu_params['returnto'] = $menu_params_registry->get('returnto');
        $menu_params['requiredlabel'] = $menu_params_registry->get('requiredlabel');
        $menu_params['msgitemissaved'] = $menu_params_registry->get('msgitemissaved');
        $menu_params['recordstable'] = $menu_params_registry->get('recordstable');
        $menu_params['recordsuseridfield'] = $menu_params_registry->get('recordsuseridfield');
        $menu_params['recordsfield'] = $menu_params_registry->get('recordsfield');

        return $menu_params;
    }

    function setParams(?array $menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
    {
        if (defined('_JEXEC'))
            $this->setJoomlaParams($menu_params, $blockExternalVars, $ModuleId);
        else {
            $this->setDefault();
            $this->setWPParams($menu_params, $blockExternalVars, $ModuleId);
        }
    }

    function setJoomlaParams(?array $menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
    {
        $this->blockExternalVars = $blockExternalVars;
        $this->ModuleId = $ModuleId;

        if (is_null($menu_params)) {
            if (method_exists($this->app, 'getParams')) {

                try {
                    $menu_params_registry = $this->app->getParams();
                    $menu_params = self::menuParamsRegistry2Array($menu_params_registry);
                } catch (Exception $e) {
                    $menu_params = [];
                }

            } else {
                $this->setDefault();
                return;
            }
        }

        $this->getForceItemId($menu_params);

        if (!$blockExternalVars and common::inputGetString('alias', ''))
            $this->alias = CTMiscHelper::slugify(common::inputGetString('alias'));
        else
            $this->alias = null;

        $this->pageTitle = $menu_params['page_title'] ?? null;
        $this->showPageHeading = $menu_params['show_page_heading'] ?? false;

        if (isset($menu_params['pageclass_sfx']))
            $this->pageClassSFX = common::ctStripTags($menu_params['pageclass_sfx'] ?? '');

        if (!$blockExternalVars and common::inputGetCmd('listing_id') !== null)
            $this->listing_id = common::inputGetCmd('listing_id');
        else
            $this->listing_id = $menu_params['listingid'] ?? null;

        if ($this->listing_id == '' or $this->listing_id == '0')
            $this->listing_id = null;

        $this->tableName = null;

        if (common::inputGetInt("ctmodalform", 0) == 1)
            $this->tableName = common::inputGetInt("tableid");//Used in Save Modal form content.

        if ($this->tableName === null) {
            $this->tableName = $menu_params['establename'] ?? null; //Table name or id not sanitized
            if ($this->tableName === null or $this->tableName === null)
                $this->tableName = $menu_params['tableid']; //Used in the back-end
        }

        //Filter
        $this->userIdField = $menu_params['useridfield'] ?? null;

        if (!$blockExternalVars and common::inputGetString('filter')) {

            $filter = common::inputGetString('filter', '');
            if (is_array($filter)) {
                $this->filter = $filter['search'];
            } else
                $this->filter = $filter;
        } else {
            $this->filter = $menu_params['filter'] ?? null;
        }

        $this->showPublished = (int)($menu_params['showpublished'] ?? 1);

        //Group BY
        $this->groupBy = $menu_params['groupby'] ?? null;

        //Sorting
        if (!$blockExternalVars and !is_null(common::inputGetCmd('sortby')))
            $this->sortBy = strtolower(common::inputGetCmd('sortby'));
        elseif (isset($menu_params['sortby']) and !is_null($menu_params['sortby']))
            $this->sortBy = strtolower($menu_params['sortby']);

        $this->forceSortBy = $menu_params['forcesortby'] ?? null;

        //Limit
        $this->limit = common::inputGetInt('limit', (int)($menu_params['limit'] ?? 20));

        //Layouts
        $this->pageLayout = $menu_params['escataloglayout'] ?? null;
        if (is_null($this->pageLayout))
            $this->pageLayout = $menu_params['ct_pagelayout'] ?? null;

        $this->itemLayout = $menu_params['esitemlayout'] ?? null;
        if (is_null($this->itemLayout))
            $this->itemLayout = $menu_params['ct_itemlayout'] ?? null;

        $this->detailsLayout = $menu_params['esdetailslayout'] ?? null;
        $this->editLayout = $menu_params['eseditlayout'] ?? null;
        $this->onRecordAddSendEmailLayout = $menu_params['onrecordaddsendemaillayout'] ?? null;
        $this->allowContentPlugins = $menu_params['allowcontentplugins'] ?? false;

        //Shopping Cart

        if (isset($menu_params['showcartitemsonly']) and $menu_params['showcartitemsonly'] != '')
            $this->showCartItemsOnly = (bool)(int)$menu_params['showcartitemsonly'];
        else
            $this->showCartItemsOnly = false;

        $this->showCartItemsPrefix = 'customtables_';
        if (isset($menu_params['showcartitemsprefix']) and $menu_params['showcartitemsprefix'] != '')
            $this->showCartItemsPrefix = $menu_params['showcartitemsprefix'];

        $this->cartReturnTo = $menu_params['cart_returnto'] ?? null;
        $this->cartMsgItemAdded = $menu_params['cart_msgitemadded'] ?? null;
        $this->cartMsgItemDeleted = $menu_params['cart_msgitemdeleted'] ?? null;
        $this->cartMsgItemUpdated = $menu_params['cart_msgitemupdated'] ?? null;

        //Permissions
        $this->editUserGroups = $menu_params['editusergroups'] ?? null;
        $this->addUserGroups = $menu_params['addusergroups'] ?? 0;
        if ($this->addUserGroups == 0)
            $this->addUserGroups = $this->editUserGroups;

        $this->publishUserGroups = $menu_params['publishusergroups'] ?? 0;
        if ($this->publishUserGroups == 0)
            $this->publishUserGroups = $this->editUserGroups;

        $this->deleteUserGroups = $menu_params['deleteusergroups'] ?? 0;
        if ($this->deleteUserGroups == 0)
            $this->deleteUserGroups = $this->editUserGroups;

        $this->guestCanAddNew = $menu_params['guestcanaddnew'] ?? null;
        $this->publishStatus = $menu_params['publishstatus'] ?? null;

        if ($this->publishStatus === null) {
            if (!$blockExternalVars)
                $this->publishStatus = common::inputGetInt('published');
            else
                $this->publishStatus = 1;
        } else
            $this->publishStatus = (int)$this->publishStatus;

        //Emails
        $this->onRecordAddSendEmail = (int)($menu_params['onrecordaddsendemail'] ?? null);
        $this->sendEmailCondition = $menu_params['sendemailcondition'] ?? null;
        $this->onRecordAddSendEmailTo = $menu_params['onrecordaddsendemailto'] ?? null;
        $this->onRecordSaveSendEmailTo = $menu_params['onrecordsavesendemailto'] ?? null;
        $this->emailSentStatusField = $menu_params['emailsentstatusfield'] ?? null;

        //Form Saved
        if (!$blockExternalVars and common::inputGetCmd('returnto'))
            $this->returnTo = common::getReturnToURL();
        else {
            //$this->returnTo = JRoute::_(Joomla\CMS\Router\Route::_('index.php?Itemid=' . $this->ItemId));
            $version_object = new Version;
            $this->version = (int)$version_object->getShortVersion();
            if ($this->version >= 4)
                $this->returnTo = $menu_params['returnto'] ?? Route::_(sprintf('index.php/?option=com_customtables&Itemid=%d', $this->ItemId));
            else
                $this->returnTo = $menu_params['returnto'] ?? null;
        }
        $this->requiredLabel = $menu_params['requiredlabel'] ?? null;
        $this->msgItemIsSaved = $menu_params['msgitemissaved'] ?? null;

        $this->recordsTable = $menu_params['recordstable'] ?? null;
        $this->recordsUserIdField = $menu_params['recordsuseridfield'] ?? null;
        $this->recordsField = $menu_params['recordsfield'] ?? null;
    }

    protected function setDefault(): void
    {
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
        $this->publishStatus = 1;
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
    }

    //Used by Joomla version of teh Custom Tables
    protected function getForceItemId(array $menu_params): void
    {
        $forceItemId = $menu_params['forceitemid'] ?? null;
        if (is_null($forceItemId))
            $forceItemId = $menu_params['customitemid'] ?? null;

        if (!is_null($forceItemId)) {
            //Find ItemId by alias
            if ((is_numeric($forceItemId))) {
                if ((int)$forceItemId != 0) {
                    $this->ItemId = (int)$forceItemId;
                    return;
                }
            } elseif ($forceItemId != '') {
                $this->ItemId = (int)CTMiscHelper::FindItemidbyAlias($forceItemId);//Accepts menu Itemid and alias
                return;
            }
        }
        $this->ItemId = common::inputGetInt('Itemid', 0);
    }

    function setWPParams(array $menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
    {

    }

    protected function constructWPParams(): void
    {
        $this->setDefault();

        $this->returnTo = common::curPageURL();
        $this->returnTo = CTMiscHelper::deleteURLQueryOption($this->returnTo, 'listing_id');
    }
}
