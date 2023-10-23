<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldESTable extends JFormFieldList
{

    protected $type = 'estable';

    protected function getOptions()//$name, $value, &$node, $control_name)
    {
        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
        require_once($path . 'loader.php');
        CTLoader();

        $query = 'SELECT id,tablename FROM #__customtables_tables WHERE published=1 ORDER BY tablename';
        $messages = database::loadObjectList($query);
        $options = array();
        if ($messages) {
            foreach ($messages as $message)
                $options[] = JHtml::_('select.option', $message->tablename, $message->tablename);
        }
        return $options;
    }
}
