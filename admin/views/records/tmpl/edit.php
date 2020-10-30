<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage views/records/tmpl/edit.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$document = JFactory::getDocument();
$document->addCustomTag('<link src="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

require(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'edititem'.DIRECTORY_SEPARATOR.'tmpl'.DIRECTORY_SEPARATOR.'default.php');