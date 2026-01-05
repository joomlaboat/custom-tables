<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use CustomTables\CustomPHP;
use CustomTables\Layouts;
use CustomTables\record;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Records Controller
 *
 * @since 3.0.0
 */
class CustomtablesControllerRecords extends FormController
{
	/**
	 * Current or most recently performed task.
	 *
	 * @var    string
	 * @since  12.2
	 * @note   Replaces _task.
	 */
	protected $task;

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public function __construct($config = array())
	{
		$this->view_list = 'Listofrecords'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param string $key The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @throws Exception
	 * @since   12.2
	 */
	public function cancel($key = null): bool
	{
		// get the referral details
		$tableId = common::inputGet('tableid', 0, 'int');

		$cancel = parent::cancel($key);

		// Redirect to the items screen.
		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=listofrecords&tableid=' . (int)$tableId, false)
		);
		return $cancel;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public function save($key = null, $urlVar = null): bool
	{
		$ct = new CT([], true);
		$tableId = common::inputGet('tableid', 0, 'int');
		$ct->getTable($tableId);

		$record = new record($ct);
		$Layouts = new Layouts($ct);
		$record->editForm->layoutContent = $Layouts->createDefaultLayout_Edit($ct->Table->fields, false);

		$listing_id = common::inputGetCmd('id');

		$saved = $record->save($listing_id, false);

		if ($saved) {
			$listing_id = $record->listing_id;

			if ($ct->Env->advancedTagProcessor and !empty($ct->Table->tablerow['customphp'])) {
				try {
					$action = $record->isItNewRecord ? 'create' : 'update';
					$customPHP = new CustomPHP($ct, $action);
					$customPHP->executeCustomPHPFile($ct->Table->tablerow['customphp'], $record->row_new, $record->row_old);
				} catch (Exception $e) {
					throw new Exception('Custom PHP file: ' . $ct->Table->tablerow['customphp'] . ' (' . $e->getMessage() . ')');
				}
			}
		} else {
			return false;
		}

		$redirect = 'index.php?option=' . $this->option;

		if ($this->task == 'apply') {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_SAVED'), 'success');
			$redirect .= '&view=records&layout=edit&id=' . $listing_id . '&tableid=' . (int)$tableId;
		} elseif ($this->task == 'save2copy') {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORDS_COPIED'), 'success');
			$redirect .= '&view=records&task=records.edit&tableid=' . (int)$tableId . '&id=' . $ct->Params->listing_id;
		} elseif ($this->task == 'save2new') {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_SAVED'), 'success');
			$redirect .= '&view=records&task=records.edit&tableid=' . (int)$tableId;
		} else {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_SAVED'), 'success');
			$redirect .= '&view=listofrecords&tableid=' . (int)$tableId;
		}

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_($redirect, false)
		);

		return true;
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param array $data An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array()): bool
	{
		// In the absence of better information, revert to the component permissions.
		return parent::allowAdd($data);
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	protected function allowEdit($data = array(), $key = 'id'): bool
	{
		//To support char type record id
		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;

		if ($recordId) {
			$user = new CTUser();

			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.records.' . $recordId);

			if (!$permission) {
				if ($user->authorise('core.edit.own', 'com_customtables.records.' . $recordId)) {
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
		return true;
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param null $recordId
	 * @param string $urlVar The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @throws Exception
	 * @since   12.2
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
	{
		$tmpl = common::inputGetCmd('tmpl');
		$layout = common::inputGetString('layout', 'edit');

		$ref = common::inputGet('ref', 0, 'string');
		$refId = common::inputGetCmd('refid', 0);

		//To support char type record id
		$listing_id = common::inputGetCmd('id');
		if ($listing_id === null) {
			$cid = common::inputPostArray('cid', []);
			$cid = ArrayHelper::toInteger($cid);
			$listing_id = $cid[0] ?? null;
		}

		$tableid = common::inputGetInt('tableid', 0);
		// Setup redirect info.

		$append = '';

		if ($refId) {
			$append .= '&ref=' . $ref . '&refid=' . $refId;
		} elseif ($ref) {
			$append .= '&ref=' . $ref;
		}

		if ($tmpl) {
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout) {
			$append .= '&layout=' . $layout;
		}

		$append .= '&tableid=' . $tableid;

		if ($listing_id) {
			$append .= '&' . $urlVar . '=' . $listing_id;
		}

		//This is to overwrite Joomla current record ID state value. Joomla converts ID to integer, but we want to support both int and cmd (A-Za-z\d_-)
		$values = (array)Factory::getApplication()->getUserState('com_customtables.edit.records.id');
		$values[] = $listing_id;
		Factory::getApplication()->setUserState('com_customtables.edit.records.id', $values);

		return $append;
	}
}
