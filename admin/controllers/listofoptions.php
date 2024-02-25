<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\AdminController;

class CustomTablesControllerListOfOptions extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFOPTIONS';

	public function getModel($name = 'Options', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
}

