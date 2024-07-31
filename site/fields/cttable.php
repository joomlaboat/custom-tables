<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
    define('CUSTOMTABLES_LIBRARIES_PATH', JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries');

trait JFormFieldCTTableCommon
{
    protected static function getOptionList(): array
    {
        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-common-joomla.php');
        require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
        $whereClause = new MySQLWhereClause();

        $categoryId = common::inputGetInt('categoryid');
        if ($categoryId !== null)
            $whereClause->addCondition('tablecategory', $categoryId);

        $whereClause->addCondition('published', 1);

        $tables = database::loadObjectList('#__customtables_tables',
            ['id', 'tablename'], $whereClause, 'tablename');

        $options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT')];

        if ($tables) {
            foreach ($tables as $table)
                $options[] = HTMLHelper::_('select.option', $table->tablename, $table->tablename);
        }
        return $options;
    }
}

if ($version < 4) {

    JFormHelper::loadFieldClass('list');

    class JFormFieldCTTable extends JFormFieldList
    {
        use JFormFieldCTTableCommon;

        protected $type = 'CTTable';

        protected function getOptions()//$name, $value, &$node, $control_name)
        {
            return self::getOptionList();
        }
    }
} else {
    class JFormFieldCTTable extends FormField
    {
        use JFormFieldCTTableCommon;

        public $type = 'CTTable';
        protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

        protected function getInput()
        {
            $data = $this->getLayoutData();
            $data['options'] = self::getOptionList();
            return $this->getRenderer($this->layout)->render($data);
        }
    }
}