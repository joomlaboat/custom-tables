<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Edit;
use CustomTables\Layouts;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class EditController
{
	/**
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws LoaderError
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	function execute(bool $checkToken = true, string $task = 'save')
	{
		if ($checkToken) {
			$userId = CustomTablesAPIHelpers::checkToken();

			if (!$userId)
				die;
		}

		switch ($_SERVER['REQUEST_METHOD']) {
			case 'POST':
				// Handle POST request
				try {
					$this->executePOST($task);//$postData
				} catch (Throwable $e) {
					CTMiscHelper::fireError(502, $e->getMessage());
				}
				// or use $_POST if data is form-encoded
				break;

			case 'GET':
				// Handle GET request
				try {
					$this->executeGET();
				} catch (Throwable $e) {
					CTMiscHelper::fireError(500, $e->getMessage());
				}

				break;

			default:
				CTMiscHelper::fireError(405, 'Method Not Allowed');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.4.8
	 */
	function executePOST(string $task = 'save')
	{
		try {
			$ct = new CT([], true);
			$ct->Env->clean = true;
		} catch (Exception $e) {
			CTMiscHelper::fireError(501, $e->getMessage());
		}

		$layoutName = common::inputGetCmd('layout');

		if (empty($layoutName)) {
			$Itemid = Factory::getApplication()->input->get('Itemid');
			if ($Itemid > 0) {
				$ct->Params->constructJoomlaParams();
				$ct->getTable($ct->Params->tableName);
			} else {
				CTMiscHelper::fireError(500, common::translate('COM_CUSTOMTABLES_ERROR_LAYOUT_NOT_FOUND'));
			}
		} else {
			$ct->Params->editLayout = $layoutName;
		}

		$layout = new Layouts($ct);

		$result = null;
		try {
			$result = @$layout->renderMixedLayout($layoutName, CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM, $task);
		} catch (Throwable $e) {
			CTMiscHelper::fireError(500, $e->getMessage());
		}

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false)
			CTMiscHelper::fireError(500, $result['message'] ?? 'Record not saved');

		CTMiscHelper::fireSuccess($result['id'], $result['data'], $result['message'] ?? 'Done');
	}

	/**
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws LoaderError
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	function executeGET()
	{
		$ct = null;
		$listing_id = common::inputGetCmd('id');
		$layoutName = common::inputGetCmd('layout');

		$params['listingid'] = $listing_id;

		try {
			$ct = @ new CT($params, false);
			$ct->Env->clean = true;
			$ct->Params->blockExternalVars = false;
		} catch (Exception $e) {
			CTMiscHelper::fireError(500, $e->getMessage());
		}

		$layoutName = common::inputGetCmd('layout');
		echo '$layoutName:' . $layoutName . '<br/>';
		if (empty($layoutName)) {
			$Itemid = Factory::getApplication()->input->get('Itemid');
			$ct->Params->constructJoomlaParams();
			$ct->getTable($ct->Params->tableName);
			print_r($ct->Table->fields);


			//echo 'Itemid:' . $Itemid . '<br/>';


			//echo '$ct->Params->tableName:' . $ct->Params->tableName . '<br/>';
			//echo '$ct->Params->editLayout:' . $ct->Params->editLayout . '<br/>';

			//die;
		} else {
			$ct->Params->editLayout = $layoutName;
		}

		$editForm = new Edit($ct);
		$editForm->load();

		$isEditable = $ct->CheckAuthorization(CUSTOMTABLES_ACTION_EDIT);
		if (!$isEditable) {
			CTMiscHelper::fireError(401, common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
		}

		if (!empty($ct->Params->listing_id) or !empty($ct->Params->filter))
			$ct->getRecord();

		$result = $editForm->processLayout();

		try {
			$j = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			$j = ['error' => $e->getMessage(), 'result' => $result];
		}

		$listing_id = null;
		if (isset($ct->Table->record) and isset($ct->Table->record[$ct->Table->realidfieldname])) {
			$listing_id = $ct->Table->record[$ct->Table->realidfieldname];
		}

		CTMiscHelper::fireSuccess($listing_id, $j, 'Edit form fields',
			['form_token' => $this->generateFormToken(), // Add token to response
				'input_prefix' => $ct->Table->fieldInputPrefix]);
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.5.0
	 */
	protected function generateFormToken(): string
	{
		// Get session object
		$app = Factory::getApplication();
		$sessionId = $app->getSession()->getId();
		$token = Session::getFormToken();

		CTMiscHelper::updateSessionData($sessionId, 'form.token', $token);
		return $token;
	}
}