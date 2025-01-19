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

class EditController
{
	function execute()
	{
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		switch ($_SERVER['REQUEST_METHOD']) {
			case 'POST':
				// Handle POST request
				$this->executePOST();//$postData
				// or use $_POST if data is form-encoded
				break;

			case 'GET':
				// Handle GET request
				$this->executeGET();
				break;

			default:
				CTMiscHelper::fireError(405, 'Method Not Allowed');
		}
	}

	/**
	 * @throws Exception
	 * @since 3.4.8
	 */
	function executePOST()
	{
		$layoutName = common::inputGetCmd('layout');
		$task = common::inputPostCmd('task');

		$ct = new CT([], true);
		$ct->Env->clean = true;

		$layout = new Layouts($ct);
		$layout->getLayout($layoutName);

		$result = null;
		try {
			$result = @$layout->renderMixedLayout($layoutName, null, $task);
		} catch (Throwable $e) {
			CTMiscHelper::fireError(500, $e->getMessage());
		}

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false)
			CTMiscHelper::fireError(500, $result['message'] ?? 'Record not saved');

		CTMiscHelper::fireSuccess($result['id'], $result['data'], $result['message'] ?? 'Done');
	}

	function executeGET()
	{
		$layoutName = Factory::getApplication()->input->get('layout');
		$listing_id = Factory::getApplication()->input->get('id');

		$ct = null;

		try {
			$params['listingid'] = $listing_id;

			$ct = @ new CT($params, false);
			$ct->Env->clean = true;
			$ct->Params->blockExternalVars = false;
			$ct->Params->editLayout = $layoutName;
		} catch (Exception $e) {
			CTMiscHelper::fireError(500, $e->getMessage());
		}

		$editForm = new Edit($ct);
		$editForm->load();

		if (!empty($ct->Params->listing_id) or !empty($ct->Params->filter))
			$ct->getRecord();

		$result = $editForm->processLayout();

		try {
			$j = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		} catch (Exception $e) {
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