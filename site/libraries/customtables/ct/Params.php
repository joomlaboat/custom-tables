<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Throwable;

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

	var array $addUserGroups;
	var array $editUserGroups;
	var array $publishUserGroups;
	var array $deleteUserGroups;

	var bool $allowContentPlugins;
	var ?string $userIdField;
	var ?string $filter;

	var int $showPublished;
	var ?int $limit;

	var ?int $publishStatus;
	var ?string $returnTo;
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

	var bool $blockExternalVars;

	var ?array $params;


	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	function __construct(?array $menu_params, bool $blockExternalVars)
	{
		$this->params = $menu_params;
		$this->blockExternalVars = $blockExternalVars;
		$this->setDefault();
	}

	protected function setDefault(): void
	{
		$this->ModuleId = null;
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
		$this->addUserGroups = [];
		$this->editUserGroups = [];
		$this->publishUserGroups = [];
		$this->deleteUserGroups = [];
		$this->allowContentPlugins = false;
		$this->userIdField = null;
		$this->filter = null;
		$this->showPublished = 2;//Show Any
		$this->limit = null;
		$this->publishStatus = 1;
		$this->returnTo = null;
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

		if (defined('WPINC')) {
			$this->returnTo = common::curPageURL();
			$this->returnTo = CTMiscHelper::deleteURLQueryOption($this->returnTo, 'listing_id');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2
	 */
	public function constructJoomlaParams(): void
	{
		$this->app = Factory::getApplication();

		//This is used for module tasks
		$ModuleId = common::inputGetInt('ModuleId');

		if (!empty($ModuleId)) {

			$this->ModuleId = $ModuleId;
			$module = ModuleHelper::getModuleById((string)$ModuleId);
			$menu_params = new Registry;//Joomla Specific
			$menu_params->loadString($module->params);
			$menu_paramsArray = self::menuParamsRegistry2Array($menu_params);
			$this->blockExternalVars = false;
			$this->setParams($menu_paramsArray);
			return;
		}

		if (method_exists($this->app, 'getParams')) {
			try {
				if ($this->app->getLanguage() !== null) {
					$menu_params_registry = @$this->app->getParams();//Joomla specific
					$menu_paramsArray = self::menuParamsRegistry2Array($menu_params_registry);
					$this->setParams($menu_paramsArray);
				}
			} catch (Throwable $e) {
				throw new Exception($e->getMessage());
			}
		}
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

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public function setParams(?array $menu_params = null): void
	{
		//Merge parameters
		if ($this->params !== null)
			$this->params = array_merge($this->params, $menu_params);
		else
			$this->params = $menu_params;

		if (defined('_JEXEC'))
			$this->setJoomlaParams();
		else {
			$this->setWPParams();
		}
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function setJoomlaParams(): void
	{
		$menu_params = $this->params;

		if (is_null($menu_params)) {
			if (method_exists($this->app, 'getParams')) {

				try {
					$menu_params_registry = $this->app->getParams();
					$menu_params = self::menuParamsRegistry2Array($menu_params_registry);
				} catch (Exception $e) {
					$menu_params = [];
				}
			}
		} else
			$menu_params = $this->getForceItemId($menu_params);

		if (!$this->blockExternalVars and common::inputGetString('alias', ''))
			$this->alias = CTMiscHelper::slugify(common::inputGetString('alias'));
		else
			$this->alias = null;

		$this->pageTitle = $menu_params['page_title'] ?? null;
		$this->showPageHeading = $menu_params['show_page_heading'] ?? false;
		$this->pageClassSFX = common::ctStripTags($menu_params['pageclass_sfx'] ?? '');

		$this->listing_id = $menu_params['listingid'] ?? null;
		if (empty($this->listing_id) or $this->listing_id == '0')
			$this->listing_id = null;

		if (empty($this->listing_id) and !$this->blockExternalVars and !empty(common::inputGetCmd('listing_id')))
			$this->listing_id = common::inputGetCmd('listing_id');

		if (empty($this->listing_id) or $this->listing_id == '0')
			$this->listing_id = null;

		$this->tableName = null;

		if (common::inputGetInt("ctmodalform", 0) == 1)
			$this->tableName = common::inputGetInt("tableid");//Used in Save Modal form content.

		if ($this->tableName === null) {
			$this->tableName = $menu_params['establename'] ?? null; //Table name or id not sanitized
			if ($this->tableName === null)
				$this->tableName = $menu_params['tableid'] ?? null; //Used in the back-end
		}

		//Filter
		$this->userIdField = $menu_params['useridfield'] ?? null;

		if (!empty($menu_params['filter']))
			$this->filter = $menu_params['filter']; //TODO: Test it. Check the security issue here. menu item filter must be on

		$this->showPublished = (int)($menu_params['showpublished'] ?? CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY);

		//Group BY
		$this->groupBy = $menu_params['groupby'] ?? null;

		//Sorting
		if (!$this->blockExternalVars and !is_null(common::inputGetCmd('sortby')))
			$this->sortBy = strtolower(common::inputGetCmd('sortby'));
		elseif (isset($menu_params['sortby']))
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
		if (!empty($menu_params['showcartitemsonly']))
			$this->showCartItemsOnly = (bool)(int)$menu_params['showcartitemsonly'];
		else
			$this->showCartItemsOnly = false;

		$this->showCartItemsPrefix = 'customtables_';
		if (!empty($menu_params['showcartitemsprefix']))
			$this->showCartItemsPrefix = $menu_params['showcartitemsprefix'];

		$this->cartReturnTo = $menu_params['cart_returnto'] ?? null;
		$this->cartMsgItemAdded = $menu_params['cart_msgitemadded'] ?? null;
		$this->cartMsgItemDeleted = $menu_params['cart_msgitemdeleted'] ?? null;
		$this->cartMsgItemUpdated = $menu_params['cart_msgitemupdated'] ?? null;

		//Permissions
		$this->setPermissions($menu_params);

		$this->publishStatus = $menu_params['publishstatus'] ?? 1;

		//Emails
		$this->onRecordAddSendEmail = (int)($menu_params['onrecordaddsendemail'] ?? null);
		$this->sendEmailCondition = $menu_params['sendemailcondition'] ?? null;
		$this->onRecordAddSendEmailTo = $menu_params['onrecordaddsendemailto'] ?? null;
		$this->onRecordSaveSendEmailTo = $menu_params['onrecordsavesendemailto'] ?? null;
		$this->emailSentStatusField = $menu_params['emailsentstatusfield'] ?? null;

		//Form Saved
		if (!$this->blockExternalVars and common::inputGetCmd('returnto'))
			$this->returnTo = common::getReturnToURL();//base 64 decode "returnto" value
		else {

			if (empty($this->ModuleId)) {
				if (CUSTOMTABLES_JOOMLA_MIN_4 and !empty($this->ItemId)) {
					//Check if current ItemId is not the same as set $this->ItemId
					if ($this->ItemId != common::inputGetInt('Itemid'))
						$this->returnTo = $menu_params['returnto'] ?? Route::_(sprintf('index.php/?option=com_customtables&Itemid=%d', $this->ItemId));
				} else
					$this->returnTo = $menu_params['returnto'] ?? null;
			} else {
				$this->returnTo = common::curPageURL();
			}
		}
		$this->requiredLabel = $menu_params['requiredlabel'] ?? null;

		$this->msgItemIsSaved = (empty($menu_params['msgitemissaved']) ? common::translate('COM_CUSTOMTABLES_RECORD_SAVED') : $menu_params['msgitemissaved']);
		if ($this->msgItemIsSaved == '-')
			$this->msgItemIsSaved = null;//Do not show "save message"

		$this->recordsTable = $menu_params['recordstable'] ?? null;
		$this->recordsUserIdField = $menu_params['recordsuseridfield'] ?? null;
		$this->recordsField = $menu_params['recordsfield'] ?? null;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function getForceItemId(array $menu_params): ?array
	{
		$forceItemId = $menu_params['forceitemid'] ?? null;
		if (is_null($forceItemId))
			$forceItemId = $menu_params['customitemid'] ?? null;

		if (!is_null($forceItemId)) {
			//Find ItemId by alias
			if ((is_numeric($forceItemId))) {
				if ((int)$forceItemId != 0) {
					$this->ItemId = (int)$forceItemId;
					return CTMiscHelper::getMenuParams($this->ItemId);
				}
			} elseif ($forceItemId != '') {
				$alias = $forceItemId;

				$menu_Row = CTMiscHelper::FindMenuItemRowByAlias($alias);
				if ($menu_Row === null)
					return null;

				$this->ItemId = (int)$menu_Row['id'];
				return (array)json_decode($menu_Row['params']);
			}
		}
		$this->ItemId = common::inputGetInt('Itemid', 0);
		return $menu_params;
	}

	protected function setPermissions(array $menu_params): void
	{
		if (!empty($menu_params['editusergroups'])) {
			if (is_array($menu_params['editusergroups']))
				$this->editUserGroups = $menu_params['editusergroups'];
			else
				$this->editUserGroups = [$menu_params['editusergroups']];
		}

		if (!empty($menu_params['addusergroups'])) {
			if (is_array($menu_params['addusergroups']))
				$this->addUserGroups = $menu_params['addusergroups'];
			else
				$this->addUserGroups = [$menu_params['addusergroups']];
		}

		if (count($this->addUserGroups) == 0)//If add user group not set then edit user group will be used
			$this->addUserGroups = $this->editUserGroups;

		if (!empty($menu_params['publishusergroups'])) {
			if (is_array($menu_params['publishusergroups']))
				$this->publishUserGroups = $menu_params['publishusergroups'];
			else
				$this->publishUserGroups = [$menu_params['publishusergroups']];
		}

		if (count($this->publishUserGroups) == 0)//If publish user group not set then edit user group will be used
			$this->publishUserGroups = $this->editUserGroups;

		if (!empty($menu_params['deleteusergroups'])) {
			if (is_array($menu_params['deleteusergroups']))
				$this->deleteUserGroups = $menu_params['deleteusergroups'];
			else
				$this->deleteUserGroups = [$menu_params['deleteusergroups']];
		}

		if (count($this->deleteUserGroups) == 0)//If publish user group not set then edit user group will be used
			$this->deleteUserGroups = $this->editUserGroups;
	}

	//Used by Joomla version of the Custom Tables

	protected function setWPParams(): void
	{
		$menu_params = $this->params;

		$this->listing_id = $menu_params['listingid'] ?? null;

		if (empty($this->listing_id) or $this->listing_id == '0')
			$this->listing_id = null;

		if (empty($this->listing_id) and !$this->blockExternalVars and !empty(common::inputGetCmd('listing_id')))
			$this->listing_id = common::inputGetCmd('listing_id');

		if (empty($this->listing_id) or $this->listing_id == '0')
			$this->listing_id = null;

		$this->tableName = null;

		if (!$this->blockExternalVars and common::inputGetInt("ctmodalform", 0) == 1)
			$this->tableName = common::inputGetInt("tableid");//Used in Save Modal form content.

		if ($this->tableName === null) {
			$this->tableName = $menu_params['establename'] ?? null; //Table name or id not sanitized
			if ($this->tableName === null and isset($menu_params['tableid']))
				$this->tableName = $menu_params['tableid']; //Used in the back-end
		}

		//Filter
		$this->userIdField = $menu_params['useridfield'] ?? null;
		$this->filter = $menu_params['filter'] ?? null;

		$this->showPublished = (int)($menu_params['showpublished'] ?? CUSTOMTABLES_SHOWPUBLISHED_PUBLISHED_ONLY);

		//Group BY
		$this->groupBy = $menu_params['groupby'] ?? null;

		//Sorting
		if (!$this->blockExternalVars and !is_null(common::inputGetCmd('sortby')))
			$this->sortBy = strtolower(common::inputGetCmd('sortby'));
		elseif (isset($menu_params['sortby']))
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
		$this->allowContentPlugins = false;

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
		$this->setPermissions($menu_params);

		$this->publishStatus = $menu_params['publishstatus'] ?? 1;

		//Emails
		$this->onRecordAddSendEmail = (int)($menu_params['onrecordaddsendemail'] ?? null);
		$this->sendEmailCondition = $menu_params['sendemailcondition'] ?? null;
		$this->onRecordAddSendEmailTo = $menu_params['onrecordaddsendemailto'] ?? null;
		$this->onRecordSaveSendEmailTo = $menu_params['onrecordsavesendemailto'] ?? null;
		$this->emailSentStatusField = $menu_params['emailsentstatusfield'] ?? null;

		//Form Saved
		if (!$this->blockExternalVars and common::inputGetCmd('returnto'))
			$this->returnTo = common::getReturnToURL();
		else {
			$this->returnTo = $menu_params['returnto'] ?? null;
		}
		$this->requiredLabel = $menu_params['requiredlabel'] ?? null;

		$this->msgItemIsSaved = (empty($menu_params['msgitemissaved']) ? common::translate('COM_CUSTOMTABLES_RECORD_SAVED') : $menu_params['msgitemissaved']);
		if ($this->msgItemIsSaved == '-')
			$this->msgItemIsSaved = null;//Do not show "save message"

		$this->recordsTable = $menu_params['recordstable'] ?? null;
		$this->recordsUserIdField = $menu_params['recordsuseridfield'] ?? null;
		$this->recordsField = $menu_params['recordsfield'] ?? null;
	}

	/**
	 * @throws Exception
	 * @since 3.4.3
	 */
	public function loadParameterUsingMenuAlias(string $Alias_or_ItemId): bool
	{
		//TODO: Check this method CTMiscHelper::getMenuParams($Itemid); maybe they are duplicate
		if ($Alias_or_ItemId == '')
			return false;

		if (defined('_JEXEC')) {

			if (is_numeric($Alias_or_ItemId) and (int)$Alias_or_ItemId > 0)
				$params = CTMiscHelper::getMenuParams($Alias_or_ItemId);
			else
				$params = CTMiscHelper::getMenuParamsByAlias($Alias_or_ItemId);

			if ($params === null)
				return false;

			$this->setParams($params);
			return true;
		} else {
			return false;
		}
	}
}
