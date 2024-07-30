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
use Joomla\CMS\Version;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

if ($version < 4) {

    JFormHelper::loadFieldClass('list');

    class JFormFieldCTTable extends JFormFieldList
    {
        public $type = 'cttable';

        public function getOptions($add_empty_option = true)//$name, $value, &$node, $control_name)
        {
            //$query = 'SELECT id,tabletitle FROM #__customtables_tables WHERE published=1 ORDER BY tabletitle';
            $whereClause = new MySQLWhereClause();
            $whereClause->addCondition('published', 1);
            $records = database::loadObjectList('#__customtables_tables', ['id', 'tabletitle'], $whereClause, 'tabletitle');

            $options = ['' => ' - ' . common::translate('COM_CUSTOMTABLES_SELECT')];

            if ($records) {
                if ($add_empty_option)
                    $options[] = HTMLHelper::_('select.option', '', common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'));

                foreach ($records as $rec)
                    $options[] = HTMLHelper::_('select.option', $rec->id, $rec->tabletitle);
            }
            return $options;
        }
    }

} else {

    class JFormFieldCTTable extends FormField
    {
        public $type = 'cttable';
        protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

        protected function getInput()
        {
            $data = $this->getLayoutData();
            $data['options'] = $this->getOptions();
            return $this->getRenderer($this->layout)->render($data);
        }

        public function getOptions($add_empty_option = true)//$name, $value, &$node, $control_name)
        {
            $whereClause = new MySQLWhereClause();
            $whereClause->addCondition('published', 1);

            $categoryId = common::inputGetInt('categoryid');
            if ($categoryId !== null)
                $whereClause->addCondition('tablecategory', $categoryId);

            $records = database::loadObjectList('#__customtables_tables', ['id', 'tabletitle'], $whereClause, 'tabletitle');

            $options = array();
            if ($records) {
                if ($add_empty_option)
                    $options[] = ['value' => '', 'text' => common::translate('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT')];

                foreach ($records as $rec)
                    $options[] = ['value' => $rec->id, 'text' => $rec->tabletitle];
            }
            return $options;
        }
    }
}