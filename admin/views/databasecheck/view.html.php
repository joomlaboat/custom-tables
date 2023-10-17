<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

// import Joomla view library
jimport('joomla.application.component.view');

use CustomTables\CT;
use CustomTables\database;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Tables View class
 */
class CustomtablesViewDataBaseCheck extends JViewLegacy
{
    /**
     * display method of View
     * @return void
     */
    var CT $ct;

    var $tables = false;

    public $activeFilters;

    public function display($tpl = null)
    {
        $version = new Version;
        $this->version = (int)$version->getShortVersion();

        $this->ct = new CT;

        $this->state = $this->get('State');

        if ($this->ct->Env->version >= 4) {
            $this->filterForm = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');
        }

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('databasecheck');
            //$this->addToolBar();
            if ($this->version < 4) {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            } else
                $this->addToolbar_4();
        }

        if ($this->version < 4)
            $this->AllTables = $this->getAllTables($this->state->get('filter.tablecategory'));
        else
            $this->AllTables = $this->getAllTables($this->state->get('list.tablecategory'));

        $this->AllFields = $this->getAllFields();

        if ($this->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');

        // Set the document
        $document = Factory::getDocument();
        $this->setDocument($document);
    }

    protected function addToolBar_3()
    {
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_DATABASECHECK'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=databasecheck');
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        $CTCategory = JFormHelper::loadFieldType('CTCategory', false);
        $CTCategoryOptions = $CTCategory->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
            Text::_('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'),
            'filter_tablecategory',
            JHtml::_('select.options', $CTCategoryOptions, 'value', 'text', $this->state->get('filter.tablecategory'))
        );
    }

    protected function addToolbar_4()
    {
        //$user  = Factory::getUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_DATABASECHECK'), 'joomla');

    }

    protected function getAllTables($categoryid)
    {
        $query = 'SELECT * FROM #__customtables_tables WHERE published = 1'
            . ($categoryid != 0 ? ' AND tablecategory=' . $categoryid : '')
            . ' ORDER BY tablename';

        return database::loadAssocList($query);
    }

    protected function getAllFields()
    {
        $query = 'SELECT * FROM #__customtables_fields WHERE published = 1 ORDER BY fieldname';
        return database::loadAssocList($query);
    }

    public function setDocument(Joomla\CMS\Document\Document $document): void
    {
        $document->setTitle(Text::_('COM_CUSTOMTABLES_DATABASECHECK'));
        $document->addStyleSheet(JURI::root(true) . "/components/com_customtables/libraries/customtables/media/css/fieldtypes.css");
    }

    protected function prepareTables()
    {
        $this->getColors();

        $tables = [];

        $color_index = 0;
        foreach ($this->AllTables as $table) {
            $joincount = 0;
            $fields = $this->getTableFields($table['id']);

            $field_names = [];
            foreach ($fields as $field) {
                $attr = ["name" => $field['fieldname'], "type" => $field['type']];

                if ($field['type'] == 'sqljoin' or $field['type'] == 'records') {
                    $params = JoomlaBasicMisc::csv_explode(',', $field['typeparams'], '"', false);
                    $jointable = $params[0];
                    $attr["join"] = $jointable;
                    $attr["joincolor"] = '';
                    $joincount++;
                }

                $field_names[] = $attr;
            }

            $tables[] = ['name' => $table['tablename'], 'columns' => $field_names, 'joincount' => $joincount, 'dependencies' => 0
                , "color" => $this->colors[$color_index], "text_color" => $this->text_colors[$color_index]];

            $color_index++;
            if ($color_index >= count($this->colors))
                $color_index = 0;
        }

        for ($i = 0; $i < count($tables); $i++) {
            $dependencies = 0;
            foreach ($tables as $table) {
                foreach ($table['columns'] as $column) {
                    if (isset($column['join']) and $column['join'] == $tables[$i]['name'])
                        $dependencies++;
                }
            }

            $tables[$i]['dependencies'] = $dependencies;


            //Get join table color
            for ($c = 0; $c < count($tables[$i]['columns']); $c++) {
                $column = $tables[$i]['columns'][$c];


                if ($column['type'] == 'sqljoin' or $column['type'] == 'records') {
                    foreach ($tables as $table) {
                        if ($table['name'] == $column['join']) {
                            $color = $table['color'];
                            $tables[$i]['columns'][$c]['joincolor'] = $table['color'];
                            break;
                        }
                    }

                }


            }
        }

        //Reorganize the list
        $tables_with_join = [];
        $tables_without_join = [];
        foreach ($tables as $table) {
            if ($table['joincount'] > 0)
                $tables_with_join[] = $table;
            else
                $tables_without_join[] = $table;
        }

        return array_merge($tables_with_join, $tables_without_join);
    }

    protected function getColors()
    {
        //Colors
        $colors = [];
        $colors[] = '#FF0000';
        $colors[] = '#00FFFF';
        $colors[] = '#C0C0C0';
        $colors[] = '#0000FF';
        $colors[] = '#808080';
        $colors[] = '#00008B';
        //$colors[]='#000000';
        $colors[] = '#ADD8E6';
        $colors[] = '#FFA500';
        $colors[] = '#800080';
        $colors[] = '#A52A2A';
        $colors[] = '#FFFF00';
        $colors[] = '#800000';
        $colors[] = '#00FF00';
        $colors[] = '#008000';
        $colors[] = '#FF00FF';
        $colors[] = '#808000';
        $colors[] = '#FFC0CB';
        $colors[] = '#7fffd4';

        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        //$text_colors[]='#FFFFFF';//black
        $text_colors[] = '#000000';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#FFFFFF';
        $text_colors[] = '#000000';
        $text_colors[] = '#000000';

        $this->colors = $colors;
        $this->text_colors = $text_colors;
    }

    protected function getTableFields($tableid)
    {
        $fields = [];

        foreach ($this->AllFields as $field) {
            if ($field['tableid'] == $tableid)
                $fields[] = $field;
        }

        return $fields;
    }


}
