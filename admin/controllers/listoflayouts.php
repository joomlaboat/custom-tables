<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

/**
 * Listoflayouts Controller
 */
class CustomtablesControllerListoflayouts extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFLAYOUTS';
}
