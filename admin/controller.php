<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/controller
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * General Controller of Customtables component
 */
class CustomtablesController extends BaseController//JControllerLegacy
{
	public function __construct($config = array())
	{
		// set the default view
		$config['default_view'] = 'customtables';
		parent::__construct($config);
	}

	function display($cachable = false, $urlparams = false)
	{
		// set default view if not set
		$view = common::inputGetCmd('view', 'customtables');
		$data = $this->getViewRelation($view);
		$layout = common::inputGet('layout', null, 'WORD');
		$id = common::inputGetCmd('id');

		// Check for edit form.
		if (CustomtablesHelper::checkArray($data)) {
			if ($data['edit'] && $layout == 'edit' && !$this->checkEditId('com_customtables.edit.' . $data['view'], $id)) {
				// Somehow the person just went to the form - we don't allow that.

				Factory::getApplication()->enqueueMessage(common::translate('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');

				// check if item was opened from other than its own list view
				$ref = common::inputGetCmd('ref', 0);
				$refid = common::inputGetInt('refid', 0);
				// set redirect
				if ($refid > 0 && CustomtablesHelper::checkString($ref)) {
					// redirect to item of ref
					if ($ref == 'records') {
						$refid = common::inputGetCmd('refid', 0);
						$this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . $ref . '&layout=edit&id=' . $refid, false));
					} else
						$this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . $ref . '&layout=edit&id=' . $refid, false));
				} elseif (CustomtablesHelper::checkString($ref)) {

					// redirect to ref
					$this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . $ref, false));
				} else {
					// normal redirect back to the list view
					$this->setRedirect(JRoute::_('index.php?option=com_customtables&view=' . $data['views'], false));
				}

				return false;
			}
		}

		return parent::display($cachable, $urlparams);
	}

	protected function getViewRelation($view)
	{
		if (CustomtablesHelper::checkString($view)) {
			// the view relationships
			$views = array(
				'categories' => 'listofcategories',
				'tables' => 'listoftables',
				'layouts' => 'listoflayouts',
				'fields' => 'listoffields',
				'records' => 'listofrecords',
				'documentation' => 'documentation',
				'databasecheck' => 'databasecheck'
			);
			// check if this is a list view
			if (in_array($view, $views)) {
				// this is a list view
				return array('edit' => false, 'view' => array_search($view, $views), 'views' => $view);
			} // check if it is an edit view
			elseif (array_key_exists($view, $views)) {
				// this is an edit view
				return array('edit' => true, 'view' => $view, 'views' => $views[$view]);
			}
		}
		return false;
	}

	protected function checkEditId($context, $id): bool
	{
		if ($id) {
			$values = (array)Factory::getApplication()->getUserState($context . '.id');

			//To support both int and cmd IDs
			return \in_array($id, $values);
		}

		// No id for a new item.
		return true;
	}
}
