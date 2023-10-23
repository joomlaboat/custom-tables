<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldESCatalogLayout extends JFormFieldList
{
    protected $type = 'escataloglayout';

    protected function getOptions(): array
    {
        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
        require_once($path . 'loader.php');
        CTLoader();

        $query = 'SELECT id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename'
            . ' FROM #__customtables_layouts WHERE published=1 AND (layouttype=1 OR layouttype=5 OR layouttype=8 OR layouttype=9 OR layouttype=10)'
            . ' ORDER BY tablename,layoutname';

        $messages = database::loadObjectList($query);
        $options = array();

        $options[] = JHtml::_('select.option', '', '- ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT'));

        if ($messages) {
            foreach ($messages as $message)
                $options[] = JHtml::_('select.option', $message->layoutname, $message->tablename . ': ' . $message->layoutname);
        }
        return $options;
    }
}
