<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;

$task = common::inputGetCmd('task');
if ($task === null)
	$task = common::inputPostCmd('task');

if ($task !== null and $task !== 'new') {
	require_once CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'controllerHelper.php';
	$result = controllerHelper::doTheTask($task);

	if ($result['link'] !== null) {
		$this->setRedirect($result['link'], $result['message'], !$result['success'] ? 'error' : 'success');
	} else {
		if (isset($result['content']))
			echo $result['content'];

		if (!empty($result['message']))
			common::enqueueMessage($result['message'], $result['success'] ? 'success' : 'error');

		parent::display();
	}
} else {
	parent::display();
}
