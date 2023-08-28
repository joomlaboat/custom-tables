<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted Access');

class JHTMLCTArticle
{
    static public function render($control_name, $value, $cssclass, $params, $attribute = '')
    {
        $catid = (int)$params[0];

        $db = Factory::getDBO();

        $query = $db->getQuery(true);
        $query->select('id, title');

        if ($catid != 0)
            $query->where('catid=' . $catid);

        $query->from('#__content');
        $query->order('title');
        $db->setQuery($query);
        $options = $db->loadObjectList();
        $options = array_merge(array(array(
            'id' => '',
            'data-type' => 'article',
            'title' => '- ' . Text::_('COM_CUSTOMTABLES_SELECT'))), $options);

        return JHTML::_('select.genericlist', $options, $control_name, 'class="' . $cssclass . '" ' . $attribute . ' ', 'id', 'title', $value, $control_name);
    }
}
