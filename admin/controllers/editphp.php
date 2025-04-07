<?php
// administrator/components/com_customtables/controllers/editphp.php

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

class CustomTablesControllerEditphp extends BaseController
{
	public function display($cachable = false, $urlparams = array())
	{
		parent::display();
	}

	public function save()
	{
		Session::checkToken() or jexit('Invalid Token');

		$input = Factory::getApplication()->input;
		$encodedPath = $input->get('file', '', 'BASE64');
		$code = $input->getRaw('code');

		$baseFolder = JPATH_SITE . '/components/com_customtables/customphp/';

		if (empty($encodedPath)) {
			$filename = basename($input->get('filename', '', 'CMD'));
			if (!preg_match('/\\.php$/', $filename)) {
				throw new Exception('Invalid file extension');
			}
			$fullPath = realpath($baseFolder) . '/' . $filename;

			// Prevent overwriting existing file
			if (file_exists($fullPath)) {
				throw new Exception('File already exists');
			}

			$encodedPath = base64_encode('/' . $filename);
		} else {
			$relPath = base64_decode($encodedPath);
			$fullPath = realpath($baseFolder . $relPath);
		}

		// Safety check: only allow writing inside customphp
		if (strpos($fullPath, realpath($baseFolder)) !== 0) {
			throw new Exception('Invalid file path.');
		}

		// If existing file, ensure it's writable
		if (file_exists($fullPath) && !is_writable($fullPath)) {
			throw new Exception('File is not writable.');
		}

		file_put_contents($fullPath, $code);

		$this->setRedirect('index.php?option=com_customtables&view=editphp&file=' . $encodedPath, 'File saved successfully');
	}
}
