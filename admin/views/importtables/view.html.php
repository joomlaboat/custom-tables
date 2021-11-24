<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Import Joomla! libraries
jimport( 'joomla.application.component.view');

use Joomla\CMS\Version;

class CustomTablesViewImportTables extends JViewLegacy
{
    var $catalogview;

    function display($tpl = null)
    {
		$version = new Version;
		$this->version = (int)$version->getShortVersion();

		JToolBarHelper::title(   JText::_( 'Custom Tables - Import Tables', 'generic.png' ));//

		parent::display($tpl);
	}

	function generateRandomString($length = 32)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++)
		    $randomString .= $characters[rand(0, $charactersLength - 1)];

		return $randomString;
	}
}
