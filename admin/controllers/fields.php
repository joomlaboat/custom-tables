<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTUser;
use CustomTables\Fields;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

class CustomtablesControllerFields extends FormController
{
	var string $ref;
	var int $refid;
	protected $task;

	public function __construct($config = array())
	{
		$this->view_list = 'listoffields'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}

	public function batch($model = null)
	{
		JSession::checkToken() or jexit(common::translate('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Fields', '', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_customtables&view=listoffields' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	/**
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function cancel($key = null)
	{
		$tableid = common::inputGet('tableid', 0, 'int');
		$cancel = parent::cancel($key);
		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=listoffields&tableid=' . (int)$tableid, false
			)
		);
		return $cancel;
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function edit($key = NULL, $urlVar = NULL)
	{
		parent::edit($key, $urlVar);

		$redirect = 'index.php?option=' . $this->option;

		$tableid = common::inputGet('tableid', 0, 'int');
		$fieldid = common::inputGetInt('fieldid', 0);
		$id = common::inputGet('id', 0, 'int');

		$extratask = common::inputGetCmd('extratask', '');

		//Postpone extra task
		if ($extratask != '') {
			$redirect .= '&extratask=' . common::inputGetCmd('extratask', '');
			$redirect .= '&old_typeparams=' . common::inputGet('old_typeparams', '', 'BASE64');
			$redirect .= '&new_typeparams=' . common::inputGet('new_typeparams', '', 'BASE64');
			$redirect .= '&fieldid=' . $fieldid;

			if (common::inputGetInt('stepsize', 10) != 10)
				$redirect .= '&stepsize=' . common::inputGetInt('stepsize', 10);
		}
		$redirect .= '&view=fields&layout=edit&tableid=' . (int)$tableid . '&id=' . (int)$id;

		$context = 'com_customtables.edit.fields';
		Factory::getApplication()->setUserState($context . '.id', $id);

		// Redirect to the item screen.
		$application = Factory::getApplication();
		$application->redirect(Route::_($redirect, false));
		$application->close();
		exit(0);
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function save($key = null, $urlVar = null): bool
	{
		$tableId = common::inputGetInt('tableid');
		$fieldId = common::inputGetInt('id');
		if ($fieldId == 0)
			$fieldId = null;

		// get the referral details
		$this->ref = common::inputGet('ref', 0, 'word');
		$this->refid = common::inputGet('refid', 0, 'int');

		try {
			$fieldId = Fields::saveField($tableId, $fieldId);
		} catch (Exception $e) {
			$redirect = 'index.php?option=' . $this->option;
			$redirect .= '&view=listoffields&tableid=' . (int)$tableId;
			$this->setRedirect(
				Route::_($redirect, false), $e->getMessage(), 'error'
			);
			return false;
		}

		if ($fieldId == null) {
			$redirect = 'index.php?option=' . $this->option;
			$redirect .= '&view=listoffields&tableid=' . (int)$tableId;
			$this->setRedirect(
				Route::_($redirect, false), 'Could not save the field.', 'error'
			);
			return false;
		}

		$redirect = 'index.php?option=' . $this->option;
		$extraTask = common::inputGetCmd('extratask', '');

		//Postpone extra task
		if ($extraTask != '') {
			$redirect .= '&extratask=' . $extraTask;
			$redirect .= '&old_typeparams=' . common::inputGet('old_typeparams', '', 'BASE64');
			$redirect .= '&new_typeparams=' . common::inputGet('new_typeparams', '', 'BASE64');
			$redirect .= '&fieldid=' . $fieldId;

			if (common::inputGetInt('stepsize', 10) != 10)
				$redirect .= '&stepsize=' . common::inputGetInt('stepsize', 10);
		}

		if ($extraTask != '' or $this->task == 'apply' or $this->task == 'save2copy')
			$redirect .= '&view=listoffields&tableid=' . (int)$tableId . '&task=fields.edit&id=' . (int)$fieldId;
		elseif ($this->task == 'save2new')
			$redirect .= '&view=listoffields&tableid=' . (int)$tableId . '&task=fields.edit';
		else
			$redirect .= '&view=listoffields&tableid=' . (int)$tableId;

		if ($fieldId != null) {
			// Redirect to the item screen.
			$this->setRedirect(
				Route::_($redirect, false)
			);
			return true;
		}

		return false;
	}

	protected function allowAdd($data = array()): bool
	{        // In the absence of better information, revert to the component permissions.
		return parent::allowAdd($data);
	}

	protected function allowEdit($data = array(), $key = 'id'): bool
	{
		// get user object.
		$user = new CTUser();
		// get record id.
		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;

		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.fields.' . (int)$recordId);
			if (!$permission) {
				if ($user->authorise('core.edit.own', 'com_customtables.fields.' . $recordId)) {
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
		$tmpl = common::inputGetCmd('tmpl');
		$layout = common::inputGetString('layout', 'edit');

		$ref = common::inputGet('ref', 0, 'string');
		$refId = common::inputGet('refid', 0, 'int');

		$tableid = common::inputGetInt('tableid', 0);

		// Setup redirect info.
		$append = '';

		if ($refId) {
			$append .= '&ref=' . (string)$ref . '&refid=' . (int)$refId;
		} elseif ($ref) {
			$append .= '&ref=' . (string)$ref;
		}

		if ($tmpl) {
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout) {
			$append .= '&layout=' . $layout;
		}

		$append .= '&tableid=' . $tableid;

		if ($recordId) {
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		return $append;
	}

	protected function postSaveHook(Joomla\CMS\MVC\Model\BaseDatabaseModel $model, $validData = [])
	{
	}
}
