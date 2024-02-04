<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

echo '<div class="ct_howitworks">' . common::translate('COM_CUSTOMTABLES_HOW_IT_WORKS_DESC') . '</div>';

