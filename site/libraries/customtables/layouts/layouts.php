<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

/* All tags already implemented using Twig */

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Exception;
use Joomla\CMS\Factory;
use JoomlaBasicMisc;

class Layouts
{
    var CT $ct;
    var ?int $tableid;
    var ?int $layouttype;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
        $this->tableid = null;
        $this->layouttype = null;
    }

    function processLayoutTag(string &$htmlresult): bool
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('layout', $options, $htmlresult, '{}');

        if (count($fList) == 0)
            return false;

        $i = 0;
        foreach ($fList as $fItem) {
            $parts = JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);
            $layoutname = $parts[0];

            $ProcessContentPlugins = false;
            if (isset($parts[1]) and $parts[1] == 'process')
                $ProcessContentPlugins = true;

            $layout = $this->getLayout($layoutname);

            if ($ProcessContentPlugins)
                JoomlaBasicMisc::applyContentPlugins($layout);

            $htmlresult = str_replace($fItem, $layout, $htmlresult);
            $i++;
        }

        return true;
    }

    function getLayout(string $layoutname, bool $processLayoutTag = true, bool $checkLayoutFile = true, bool $addHeaderCode = true): string
    {
        if ($layoutname == '')
            return '';

        if (self::isLayoutContent($layoutname)) {
            $this->layouttype = 0;
            return $layoutname;
        }

        if ($this->ct->db->serverType == 'postgresql')
            $query = 'SELECT id, tableid, layoutcode, layoutmobile, layoutcss, layoutjs, extract(epoch FROM modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname=' . $this->ct->db->quote($layoutname) . ' LIMIT 1';
        else
            $query = 'SELECT id, tableid, layoutcode, layoutmobile, layoutcss, layoutjs, UNIX_TIMESTAMP(modified) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname=' . $this->ct->db->quote($layoutname) . ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return '';

        $row = $rows[0];
        $this->tableid = (int)$row['tableid'];

        $this->layouttype = (int)$row['layouttype'];

        $content = $this->getLayoutFileContent($row['id'], $row['ts'], $layoutname);
        if ($content != '')
            return $content;

        //Get all layouts recursively
        if ($this->ct->Env->isMobile and trim($row['layoutmobile']) != '')
            $layoutcode = $row['layoutmobile'];
        else
            $layoutcode = $row['layoutcode'];

        if ($processLayoutTag)
            $this->processLayoutTag($layoutcode);

        if ($addHeaderCode)
            $this->addCSSandJSIfNeeded($row);

        return $layoutcode;
    }

    public static function isLayoutContent($layout): bool
    {
        if (str_contains($layout, '[') or str_contains($layout, '{'))
            return true;

        return false;
    }

    protected function getLayoutFileContent(int $layout_id, $db_layout_ts, $layoutname): string
    {
        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'layouts';
        $filename = $layoutname . '.html';

        if (file_exists($path . DIRECTORY_SEPARATOR . $filename)) {
            $file_ts = filemtime($path . DIRECTORY_SEPARATOR . $filename);

            if ($db_layout_ts == 0) {

                $query = 'SELECT UNIX_TIMESTAMP(modified) AS ts FROM #__customtables_layouts WHERE id=' . $layout_id . ' LIMIT 1';
                $this->ct->db->setQuery($query);
                $recs = $this->ct->db->loadAssocList();

                if (count($recs) == 0)
                    $db_layout_ts = 0;
                else {
                    $rec = $recs[0];
                    $db_layout_ts = $rec['ts'];
                }
            }

            if ($file_ts > $db_layout_ts) {

                $content = file_get_contents($path . DIRECTORY_SEPARATOR . $filename);

                $query = 'UPDATE #__customtables_layouts SET layoutcode="' . addslashes($content) . '",modified=FROM_UNIXTIME(' . $file_ts . ') WHERE id=' . $layout_id;

                $this->ct->db->setQuery($query);
                $this->ct->db->execute();

                return $content;
            }
        }
        return '';
    }

    protected function addCSSandJSIfNeeded($layoutRow): void
    {
        if (trim($layoutRow['layoutcss']) != '') {
            $layoutContent = trim($layoutRow['layoutcss']);
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $layoutContent = '<style>' . $twig->process($this->ct->Table->record) . '</style>';
            $this->ct->document->addCustomTag($layoutContent);
        }

        if (trim($layoutRow['layoutjs']) != '') {
            $layoutContent = trim($layoutRow['layoutjs']);
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $layoutContent = $twig->process($this->ct->Table->record);
            $this->ct->document->addCustomTag('<script>' . $layoutContent . '</script>');
        }
    }

    function createDefaultLayout_SimpleCatalog($fields, $addToolbar = true): string
    {
        $this->layouttype = 1;

        $result = '<style>' . PHP_EOL . 'datagrid th{text-align:left;}' . PHP_EOL . '.datagrid td{text-align:left;}' . PHP_EOL . '</style>' . PHP_EOL;
        $result .= '<div style="float:right;">{{ html.recordcount }}</div>' . PHP_EOL;

        if ($addToolbar) {
            $result .= '<div style="float:left;">{{ html.add }}</div>' . PHP_EOL;
            $result .= '<div style="text-align:center;">{{ html.print }}</div>' . PHP_EOL;
        }

        $result .= '<div class="datagrid">' . PHP_EOL;

        if ($addToolbar)
            $result .= '<div>{{ html.batch(\'edit\',\'publish\',\'unpublish\',\'refresh\',\'delete\') }}</div>';

        $result .= PHP_EOL;

        $fieldtypes_to_skip = ['log', 'imagegallery', 'filebox', 'dummy'];
        $fieldTypesWithSearch = ['email', 'string', 'multilangstring', 'text', 'multilangtext', 'sqljoin', 'records', 'user', 'userid', 'int', 'checkbox'];
        $fieldtypes_allowed_to_orderby = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime', 'date', 'multilangstring', 'customtables', 'userid', 'user'];

        $result .= PHP_EOL . '<table>' . PHP_EOL;

        $result .= self::renderTableHead($fields, $addToolbar, $fieldtypes_to_skip, $fieldTypesWithSearch, $fieldtypes_allowed_to_orderby);

        $result .= PHP_EOL . '<tbody>';
        $result .= PHP_EOL . '{% block record %}';
        $result .= PHP_EOL . '<tr>' . PHP_EOL;

        //Look for ordering field type
        if ($addToolbar) {
            foreach ($fields as $field) {
                if ($field['type'] == 'ordering') {
                    $result .= '<td style="text-align:center;">{{ ' . $field['fieldname'] . ' }}</td>' . PHP_EOL;
                }
            }
        }

        if ($addToolbar)
            $result .= '<td style="text-align:center;">{{ html.toolbar("checkbox") }}</td>' . PHP_EOL;

        $result .= '<td style="text-align:center;"><a href="{{ record.link(true) }}">{{ record.id }}</a></td>' . PHP_EOL;

        foreach ($fields as $field) {

            if ($field['type'] != 'ordering' && !in_array($field['type'], $fieldtypes_to_skip))
                $result .= '<td>{{ ' . $field['fieldname'] . ' }}</td>' . PHP_EOL;
        }

        if ($addToolbar)
            $result .= '<td>{{ html.toolbar("edit","publish","refresh","delete") }}</td>' . PHP_EOL;

        $result .= '</tr>';

        $result .= PHP_EOL . '{% endblock %}';
        $result .= PHP_EOL . '</tbody>';
        $result .= PHP_EOL . '</table>' . PHP_EOL;

        $result .= PHP_EOL;
        $result .= '</div>' . PHP_EOL;

        if ($addToolbar)
            $result .= '<br/><div style="text-align:center;">{{ html.pagination }}</div>' . PHP_EOL;

        return $result;
    }

    protected function renderTableHead($fields, $addToolbar, $fieldtypes_to_skip, $fieldTypesWithSearch, $fieldtypes_allowed_to_orderby): string
    {
        $result = '<thead><tr>' . PHP_EOL;

        //Look for ordering field type
        if ($addToolbar) {
            foreach ($fields as $field) {
                if ($field['type'] == 'ordering')
                    $result .= '<th class="short">{{ ' . $field['fieldname'] . '.label(true) }}</th>' . PHP_EOL;
            }
        }

        if ($addToolbar)
            $result .= '<th class="short">{{ html.batch("checkbox") }}</th>' . PHP_EOL;

        if ($addToolbar)
            $result .= '<th class="short">{{ record.label(true) }}</th>' . PHP_EOL;
        else
            $result .= '<th class="short">{{ record.label(false) }}</th>' . PHP_EOL;

        foreach ($fields as $field)
            $result .= self::renderTableColumnHeader($field, $addToolbar, $fieldtypes_to_skip, $fieldTypesWithSearch, $fieldtypes_allowed_to_orderby);

        if ($addToolbar)
            $result .= '<th>Action<br/>{{ html.searchbutton }}</th>' . PHP_EOL;

        $result .= '</tr></thead>' . PHP_EOL . PHP_EOL;

        return $result;
    }

    function renderTableColumnHeader($field, $addToolbar, $fieldtypes_to_skip, $fieldtypes_withsearch, $fieldtypes_allowed_to_orderby): string
    {
        $result = '';

        if ($field['type'] != 'ordering' && !in_array($field['type'], $fieldtypes_to_skip)) {

            $result .= '<th>';

            if ($field['allowordering'] && in_array($field['type'], $fieldtypes_allowed_to_orderby))
                $result .= '{{ ' . $field['fieldname'] . '.label(true) }}';
            else
                $result .= '{{ ' . $field['fieldname'] . '.title }}';

            if ($addToolbar and in_array($field['type'], $fieldtypes_withsearch)) {

                if ($field['type'] == 'checkbox' || $field['type'] == 'sqljoin' || $field['type'] == 'records')
                    $result .= '<br/>{{ html.search("' . $field['fieldname'] . '","","reload") }}';
                else
                    $result .= '<br/>{{ html.search("' . $field['fieldname'] . '") }}';
            }

            $result .= '</th>' . PHP_EOL;
        }

        return $result;
    }

    function createDefaultLayout_CSV($fields): string
    {
        $this->layouttype = 9;

        $result = '';

        $fieldTypes_to_skip = ['log', 'imagegallery', 'filebox', 'dummy', 'ordering'];
        $fieldTypes_to_pureValue = ['image', 'imagegallery', 'filebox', 'file'];

        foreach ($fields as $field) {

            if (!in_array($field['type'], $fieldTypes_to_skip)) {
                if ($result !== '')
                    $result .= ',';

                $result .= '"{{ ' . $field['fieldname'] . '.title }}"';
            }
        }

        $result .= PHP_EOL . "{% block record %}";

        $firstField = true;
        foreach ($fields as $field) {

            if (!in_array($field['type'], $fieldTypes_to_skip)) {

                if (!$firstField)
                    $result .= ',';

                if (!in_array($field['type'], $fieldTypes_to_pureValue))
                    $result .= '"{{ ' . $field['fieldname'] . ' }}"';
                else
                    $result .= '"{{ ' . $field['fieldname'] . '.value }}"';

                $firstField = false;
            }
        }
        return $result . PHP_EOL . "{% endblock %}";
    }

    function createDefaultLayout_Edit($fields, $addToolbar = true): string
    {
        $this->layouttype = 2;

        $result = '<div class="form-horizontal">';

        $fieldTypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'imagegallery', 'filebox', 'dummy'];

        foreach ($fields as $field) {
            if (!in_array($field['type'], $fieldTypes_to_skip)) {
                $result .= '<div class="control-group">';
                $result .= '<div class="control-label">{{ ' . $field['fieldname'] . '.label }}</div><div class="controls">{{ ' . $field['fieldname'] . '.edit }}</div>';
                $result .= '</div>';
            }
        }

        $result .= '</div>';

        foreach ($fields as $field) {
            if ($field['type'] === "dummy") {
                $result .= '<p><span style="color: #FB1E3D; ">*</span> {{ ' . $field['fieldname'] . '.edit }}</p>
';
                break;
            }
        }

        if ($addToolbar)
            $result .= '<div style="text-align:center;">{{ button("save") }} {{ button("saveandclose") }} {{ button("saveascopy") }} {{ button("cancel") }}</div>
';
        return $result;
    }

    public function storeAsFile($data): string
    {
        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'layouts';
        $filename = $data['layoutname'] . '.html';

        try {
            @file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $data['layoutcode']);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        try {
            @$file_ts = filemtime($path . DIRECTORY_SEPARATOR . $filename);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            $file_ts = '';
        }

        if ($file_ts == '') {
            Factory::getApplication()->enqueueMessage('No permission -  file not saved', 'error');
            $file_ts = '';
        } else {

            $layout_id = (int)$data['id'];

            if ($layout_id == 0)
                $query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME(' . $file_ts . ') WHERE layoutname=' . $this->ct->db->quote($data['layoutname']);
            else
                $query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME(' . $file_ts . ') WHERE id=' . $layout_id;

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
        }

        return $file_ts;
    }

    public function layoutTypeTranslation(): array
    {
        return array(
            1 => 'COM_CUSTOMTABLES_LAYOUTS_SIMPLE_CATALOG',
            5 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_PAGE',
            6 => 'COM_CUSTOMTABLES_LAYOUTS_CATALOG_ITEM',
            2 => 'COM_CUSTOMTABLES_LAYOUTS_EDIT_FORM',
            4 => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS',
            3 => 'COM_CUSTOMTABLES_LAYOUTS_RECORD_LINK',
            7 => 'COM_CUSTOMTABLES_LAYOUTS_EMAIL_MESSAGE',
            8 => 'COM_CUSTOMTABLES_LAYOUTS_XML',
            9 => 'COM_CUSTOMTABLES_LAYOUTS_CSV',
            10 => 'COM_CUSTOMTABLES_LAYOUTS_JSON'
        );
    }
}
