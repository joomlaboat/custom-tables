<?php

use CustomTables\Catalog;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\Edit;
use CustomTables\record;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

class EditController
{
	function execute()
	{
		$app = Factory::getApplication();
		$userId = CustomTablesAPIHelpers::checkToken();

		if (!$userId)
			die;

		$layoutName = Factory::getApplication()->input->get('layout');
		$listing_id = Factory::getApplication()->input->get('listing_id');

		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

		if (!file_exists($path))
			die('CT Loader not found.');

		require_once($path);

		$loadTwig = true;

		CustomTablesLoader(false, false, null, 'com_customtables', $loadTwig);
		try {
			$params['listingid'] = $listing_id;

			$ct = @ new CT($params, false);
			$ct->Env->clean = true;
			$ct->Params->blockExternalVars = false;
			$ct->Params->editLayout = $layoutName;
		} catch (Exception $e) {
			echo $e->getMessage();
			die;
		}

		$editForm = new Edit($ct);
		$editForm->load();

		if ($ct->Params->listing_id !== null)
			$ct->Table->loadRecord($ct->Params->listing_id);

		$result = $editForm->processLayout();
		echo $result;
		die;
	}
}