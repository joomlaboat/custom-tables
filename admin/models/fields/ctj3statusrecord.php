<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

JFormHelper::loadFieldClass('list');

class JFormFieldctj3statusrecord extends JFormFieldList
{
    public $type = 'ctj3statusrecord';

    public function getOptions()
    {
        $options = [];
        $options[] = JHtml::_('select.option', '', Text::_('JOPTION_SELECT_PUBLISHED'));
        $options[] = JHtml::_('select.option', 1, Text::_('JPUBLISHED'));
        $options[] = JHtml::_('select.option', 0, Text::_('JUNPUBLISHED'));
        //$options[] = JHtml::_('select.option', '*', Text::_('JALL'));

        return $options;
    }
}
