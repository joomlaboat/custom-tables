<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;
use CustomTables\database;

defined('_JEXEC') or die('Restricted Access');

class JHTMLCTArticle
{
	static public function render($control_name, $value, $cssclass, $params, $attribute = '')
	{
		$catid = (int)$params[0];
		$query = 'SELECT id, title FROM #__content';

		if ($catid != 0)
			$query .= ' WHERE catid=' . $catid;

		$query .= ' ORDER BY title';
		$options = database::loadObjectList($query);
		$options = array_merge(array(array(
			'id' => '',
			'data-type' => 'article',
			'title' => '- ' . common::translate('COM_CUSTOMTABLES_SELECT'))), $options);

		return JHTML::_('select.genericlist', $options, $control_name, 'class="' . $cssclass . '" ' . $attribute . ' ', 'id', 'title', $value, $control_name);
	}
}
