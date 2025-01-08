<?php

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
				/*
								$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

								if (strpos($contentType, 'application/json') !== false) {
									// Handle JSON data
									$postData = json_decode(file_get_contents('php://input'), true);
								} else if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
									// Handle form data
									$postData = $_POST;
								} else {
									// Handle unsupported content type
									http_response_code(415);
									echo json_encode(['error' => 'Unsupported Media Type']);
									exit;
								}
				*/
				// Handle POST request
				$this->executePOST();//$postData
				// or use $_POST if data is form-encoded
				break;

			case 'GET':
				// Handle GET request
				$this->executeGET();
				break;

			default:
				// Handle invalid request method
				$app = Factory::getApplication();
				$app->setHeader('status', 405);
				echo json_encode([
					'success' => false,
					'data' => null,
					'errors' => [
						[
							'code' => 405,
							'title' => 'Method Not Allowed'
						]
					],
					'message' => 'Method Not Allowed'
				]);

		}
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.4.8
	 */
	function executePOST()
	{
		$layoutName = common::inputGetCmd('layout');
		//$listing_id = common::inputPostCmd('id');

		$task = common::inputPostCmd('task');

		$params = null;
		$ct = @ new CT($params, false);
		$ct->Env->clean = true;
		$ct->Params->blockExternalVars = false;

		$layout = new Layouts($ct);
		$layout->getLayout($layoutName);

		$filter = $layout->params['filter'] ?? null;
		if ($filter !== null)
			$ct->setParams($layout->params);

		/*
		$filter = null;
		if ($ct->Params->filter !== null)
			$filter = $ct->Params->filter;

		$ct->setFilter($filter);

		if ($listing_id !== null)
			$ct->Filter->whereClause->addCondition($ct->Table->realidfieldname, $listing_id);

		if ($ct->getRecords(false, 1)) {
			if (count($ct->Records) > 0) {
				$ct->Table->record = $ct->Records[0];
				$ct->Params->listing_id = $ct->Table->record[$ct->Table->realidfieldname];
			}
		}
		*/

		$layout = new Layouts($ct);
		$result = null;
		try {
			$result = @$layout->renderMixedLayout($layoutName, null, 1, true, $task);
		} catch (Throwable $e) {
			CustomTablesAPIHelpers::fireError($e->getMessage());
		}

		if (isset($result['error']) or !isset($result['success']) or $result['success'] === false)
			CustomTablesAPIHelpers::fireError($result['message'] ?? 'Record not saved');

		CustomTablesAPIHelpers::fireSuccess($result['id'], $result['data'], $result['message'] ?? 'Done');
	}

	function executeGET()
	{
		$app = Factory::getApplication();

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
			CustomTablesAPIHelpers::fireError($e->getMessage());
		}

		$editForm = new Edit($ct);
		$editForm->load();

		if (!empty($ct->Params->filter)) {
			$ct->setFilter($ct->Params->filter);

			if (!empty($ct->Params->listing_id))
				$ct->getRecord($ct->Params->listing_id);
			else
				$ct->getRecord();
		} else {
			if (!empty($ct->Params->listing_id))
				$ct->getRecord($ct->Params->listing_id);
		}

		$result = $editForm->processLayout();

		try {
			$j = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		} catch (Exception $e) {
			$j = ['error' => $e->getMessage(), 'result' => $result];
		}

		$app->setHeader('status', 200);
		$app->sendHeaders();

		$listing_id = null;
		if (isset($ct->Table->record) and isset($ct->Table->record[$ct->Table->realidfieldname])) {
			$listing_id = $ct->Table->record[$ct->Table->realidfieldname];
		}

		echo json_encode([
			'success' => true,
			'data' => $j,
			'message' => 'Edit form fields',
			'form_token' => $this->generateFormToken(), // Add token to response
			'input_prefix' => $ct->Table->fieldInputPrefix,
			'id' => $listing_id
		], JSON_PRETTY_PRINT);

		die;
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