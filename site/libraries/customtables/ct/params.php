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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use JoomlaBasicMisc;

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

	var bool $blockExternalVars;

	function __construct(?Registry $menu_params = null, $blockExternalVars = false, ?string $ModuleId = null)
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

	protected function constructJoomlaParams(?Registry $menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
	{
		$this->app = Factory::getApplication();

		if (is_null($menu_params)) {

			if (is_null($ModuleId)) {
				$ModuleIdInt = common::inputGetInt('ModuleId');

				if ($ModuleIdInt)
					$ModuleId = strval($ModuleIdInt);
				else
					$ModuleId = null;
			}

			if (!is_null($ModuleId)) {
				$module = ModuleHelper::getModuleById($ModuleId);
				$menu_params = new Registry;
				$menu_params->loadString($module->params);
				$blockExternalVars = false;
				//Do not block external var parameters because this is the edit form or a task
			} elseif (method_exists($this->app, 'getParams')) {
				try {
					if ($this->app->getLanguage() !== null) {
						$menu_params = @$this->app->getParams();
					} else
						$menu_params = new Registry;
				} catch (Exception $e) {
					$menu_params = new Registry;
				}
			}
		}
		$this->setParams($menu_params, $blockExternalVars, $ModuleId);
	}

	function setParams($menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
	{
		if (defined('_JEXEC'))
			$this->setJoomlaParams($menu_params, $blockExternalVars, $ModuleId);
		else {
			$this->setDefault();
			$this->setWPParams($menu_params, $blockExternalVars, $ModuleId);
		}
	}

	function setJoomlaParams($menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
	{
		$this->blockExternalVars = $blockExternalVars;
		$this->ModuleId = $ModuleId;

		if (is_null($menu_params)) {
			if (method_exists($this->app, 'getParams')) {

				try {
					$menu_params = $this->app->getParams();
				} catch (Exception $e) {
					$menu_params = new Registry;
				}

			} else {
				$this->setDefault();

				return;
			}
		}

		$this->getForceItemId($menu_params);

		if (!$blockExternalVars and common::inputGetString('alias', ''))
			$this->alias = JoomlaBasicMisc::slugify(common::inputGetString('alias'));
		else
			$this->alias = null;

		$this->pageTitle = $menu_params->get('page_title') ?? null;
		$this->showPageHeading = $menu_params->get('show_page_heading', 1);

		if ($menu_params->get('pageclass_sfx') !== null)
			$this->pageClassSFX = common::ctStripTags($menu_params->get('pageclass_sfx'));

		if (!$blockExternalVars and common::inputGetCmd('listing_id') !== null)
			$this->listing_id = common::inputGetCmd('listing_id');
		else
			$this->listing_id = $menu_params->get('listingid');

		if ($this->listing_id == 0 or $this->listing_id == '' or $this->listing_id == '0')
			$this->listing_id = null;

		$this->tableName = null;

		if (common::inputGetInt("ctmodalform", 0) == 1)
			$this->tableName = common::inputGetInt("tableid");//Used in Save Modal form content.

		if ($this->tableName === null) {
			$this->tableName = $menu_params->get('establename'); //Table name or id not sanitized
			if ($this->tableName === null or $this->tableName === null)
				$this->tableName = $menu_params->get('tableid'); //Used in the back-end
		}

		//Filter
		$this->userIdField = $menu_params->get('useridfield');

		if (!$blockExternalVars and common::inputGetString('filter', '')) {

			$filter = common::inputGetString('filter', '');
			if (is_array($filter)) {
				$this->filter = $filter['search'];
			} else
				$this->filter = $filter;
		} else {
			$this->filter = $menu_params->get('filter');
		}

		$this->showPublished = (int)$menu_params->get('showpublished');

		//Group BY
		$this->groupBy = $menu_params->get('groupby');

		//Sorting
		if (!$blockExternalVars and !is_null(common::inputGetCmd('sortby')))
			$this->sortBy = strtolower(common::inputGetCmd('sortby'));
		elseif (!is_null($menu_params->get('sortby')))
			$this->sortBy = strtolower($menu_params->get('sortby'));

		$this->forceSortBy = $menu_params->get('forcesortby');

		//Limit
		$this->limit = common::inputGetInt('limit', ($menu_params->get('limit') ?? 20));

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

		if ($this->publishStatus === null) {
			if (!$blockExternalVars)
				$this->publishStatus = common::inputGetInt('published');
			else
				$this->publishStatus = 1;
		} else
			$this->publishStatus = (int)$this->publishStatus;

		//Emails
		$this->onRecordAddSendEmail = (int)$menu_params->get('onrecordaddsendemail');
		$this->sendEmailCondition = $menu_params->get('sendemailcondition');
		$this->onRecordAddSendEmailTo = $menu_params->get('onrecordaddsendemailto');
		$this->onRecordSaveSendEmailTo = $menu_params->get('onrecordsavesendemailto');
		$this->emailSentStatusField = $menu_params->get('emailsentstatusfield');

		//Form Saved

		if (!$blockExternalVars and common::inputGetCmd('returnto'))
			$this->returnTo = common::getReturnToURL();
		else
			$this->returnTo = $menu_params->get('returnto');

		$this->requiredLabel = $menu_params->get('requiredlabel');
		$this->msgItemIsSaved = $menu_params->get('msgitemissaved');

		$this->recordsTable = $menu_params->get('recordstable');
		$this->recordsUserIdField = $menu_params->get('recordsuseridfield');
		$this->recordsField = $menu_params->get('recordsfield');
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

		$this->ItemId = common::inputGetInt('Itemid', 0);
	}

	function setWPParams($menu_params = null, $blockExternalVars = true, ?string $ModuleId = null): void
	{
		$this->setDefault();
	}

	protected function constructWPParams(): void
	{
		$this->setDefault();
	}
}
