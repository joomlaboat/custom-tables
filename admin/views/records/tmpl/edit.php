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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

if($this->ct->Env->version >= 4)
{
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('behavior.keepalive');
}

$document = Factory::getDocument();
$document->addStyleSheet(JURI::root(true)."/components/com_customtables/libraries/customtables/media/css/style.css");

CTViewEdit($this->ct, $this->row, $this->pagelayout, $this->formLink,'adminForm');
