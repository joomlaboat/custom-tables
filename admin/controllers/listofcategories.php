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

use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfCategories extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFCATEGORIES';

	public function getModel($name = 'Categories', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
}
