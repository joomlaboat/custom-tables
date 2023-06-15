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
    var ?int $tableId;
    var ?int $layoutId;
    var ?int $layoutType;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
        $this->tableId = null;
        $this->layoutType = null;
    }

    function getLayoutRowById(int $layoutId): ?array
    {
        if ($this->ct->db->serverType == 'postgresql')
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'CASE WHEN modified IS NULL THEN extract(epoch FROM created) '
                . 'ELSE extract(epoch FROM modified) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE id=' . $layoutId . ' LIMIT 1';
        else
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'IF(modified IS NULL,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(modified)) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE id=' . $layoutId . ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return null;

        return $rows[0];
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

    function getLayout(string $layoutName, bool $processLayoutTag = true, bool $checkLayoutFile = true, bool $addHeaderCode = true): string
    {
        if ($layoutName == '')
            return '';

        if (self::isLayoutContent($layoutName)) {
            $this->layoutType = 0;
            return $layoutName;
        }

        if ($this->ct->db->serverType == 'postgresql')
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'CASE WHEN modified IS NULL THEN extract(epoch FROM created) '
                . 'ELSE extract(epoch FROM modified) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE layoutname=' . $this->ct->db->quote($layoutName) . ' LIMIT 1';
        else
            $query = 'SELECT id, tableid, layoutname, layoutcode, layoutmobile, layoutcss, layoutjs, '
                . 'IF(modified IS NULL,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(modified)) AS ts, '
                . 'layouttype '
                . 'FROM #__customtables_layouts WHERE layoutname=' . $this->ct->db->quote($layoutName) . ' LIMIT 1';

        $this->ct->db->setQuery($query);
        $rows = $this->ct->db->loadAssocList();
        if (count($rows) != 1)
            return '';

        $row = $rows[0];
        $this->tableId = (int)$row['tableid'];
        $this->layoutId = (int)$row['id'];
        $this->layoutType = (int)$row['layouttype'];

        if ($this->ct->Env->isMobile and trim($row['layoutmobile']) != '') {

            $layoutCode = $row['layoutmobile'];
            if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
                $content = $this->getLayoutFileContent($row['id'], $layoutName, $layoutCode, $row['ts'], $layoutName . '_mobile.html', 'layoutmobile');
                if ($content != null)
                    $layoutCode = $content;
            }

        } else {

            $layoutCode = $row['layoutcode'];
            if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
                $content = $this->getLayoutFileContent($row['id'], $layoutName, $layoutCode, $row['ts'], $layoutName . '.html', 'layoutcode');
                if ($content != null)
                    $layoutCode = $content;
            }
        }

        //Get all layouts recursively
        if ($processLayoutTag)
            $this->processLayoutTag($layoutCode);

        if ($addHeaderCode)
            $this->addCSSandJSIfNeeded($row, $checkLayoutFile);

        return $layoutCode;
    }

    public static function isLayoutContent($layout): bool
    {
        if (str_contains($layout, '[') or str_contains($layout, '{'))
            return true;

        return false;
    }

    public function getLayoutFileContent(int $layout_id, string $layoutName, string $layoutCode, int $db_layout_ts, string $filename, string $fieldName): ?string
    {
        if (file_exists($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename)) {
            $file_ts = filemtime($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

            if ($db_layout_ts == 0) {

                $query = 'SELECT UNIX_TIMESTAMP(modified) AS ts FROM #__customtables_layouts WHERE id=' . $layout_id . ' LIMIT 1';
                $this->ct->db->setQuery($query);
                $recs = $this->ct->db->loadAssocList();

                if (count($recs) != 0) {
                    $rec = $recs[0];
                    $db_layout_ts = $rec['ts'];
                }
            }

            if ($file_ts > $db_layout_ts) {
                $content = file_get_contents($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);
                $query = 'UPDATE #__customtables_layouts SET ' . $fieldName . '="' . addslashes($content) . '",modified=FROM_UNIXTIME(' . $file_ts . ') WHERE id=' . $layout_id;
                $this->ct->db->setQuery($query);
                $this->ct->db->execute();
                return $content;
            }
        } else {
            $this->storeLayoutAsFile($layout_id, $layoutName, $layoutCode, $filename);
        }
        return null;
    }

    public function storeLayoutAsFile(int $layout_id, string $layoutName, ?string $layoutCode, string $filename): bool
    {
        $layoutCode = trim($layoutCode ?? '');
        $path = $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename;

        if ($layoutCode == '') {
            if (file_exists($path))
                try {
                    unlink($path);
                } catch (Exception $e) {
                    Factory::getApplication()->enqueueMessage($path . '<br/>' . $e->getMessage(), 'error');
                    return false;
                }

            return true;
        }

        try {
            @file_put_contents($path, $layoutCode);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($path . '<br/>' . $e->getMessage(), 'error');
            return false;
        }

        try {
            @$file_ts = filemtime($path);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($path . '<br/>' . $e->getMessage(), 'error');
            return false;
        }

        if ($file_ts == '') {
            Factory::getApplication()->enqueueMessage($path . '<br/>No permission -  file not saved', 'error');
            return false;
        } else {

            if ($layout_id == 0)
                $query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME(' . $file_ts . ') WHERE layoutname=' . $this->ct->db->quote($layoutName);
            else
                $query = 'UPDATE #__customtables_layouts SET modified=FROM_UNIXTIME(' . $file_ts . ') WHERE id=' . $layout_id;

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
        }
        return true;
    }

    protected function addCSSandJSIfNeeded(array $layoutRow, bool $checkLayoutFile = true): void
    {
        $layoutContent = trim($layoutRow['layoutcss']);

        if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
            $content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $layoutContent, $layoutRow['ts'], $layoutRow['layoutname'] . '.css', 'layoutcss');
            if ($content != null)
                $layoutContent = $content;
        }

        if ($layoutContent != '') {
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $layoutContent = '<style>' . $twig->process($this->ct->Table->record ?? null) . '</style>';

            if ($twig->errorMessage !== null)
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

            $this->ct->document->addCustomTag($layoutContent);
        }

        $layoutContent = trim($layoutRow['layoutjs']);
        if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
            $content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $layoutContent, $layoutRow['ts'], $layoutRow['layoutname'] . '.js', 'layoutjs');
            if ($content != null)
                $layoutContent = $content;
        }

        if ($layoutContent != '') {
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $layoutContent = $twig->process($this->ct->Table->record);

            if ($twig->errorMessage !== null)
                $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

            $this->ct->document->addCustomTag('<script>' . $layoutContent . '</script>');
        }
    }

    public function deleteLayoutFiles(string $layoutName): bool
    {
        if ($this->ct->Env->folderToSaveLayouts === null)
            return false;

        $fileNames = ['.html', '_mobile.html', '.css', '.js'];
        foreach ($fileNames as $fileName) {
            $path = $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $layoutName . $fileName;
            if (file_exists($path)) {
                try {
                    @unlink($path);
                } catch (Exception $e) {
                    Factory::getApplication()->enqueueMessage($path . '<br/>' . $e->getMessage(), 'error');
                    return false;
                }
            }
        }
        return true;
    }

    function createDefaultLayout_SimpleCatalog($fields, $addToolbar = true): string
    {
        $this->layoutType = 1;

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
        $fieldtypes_allowed_to_orderby = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime', 'date', 'multilangstring', 'customtables', 'userid', 'user', 'virtual'];

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

    function renderTableColumnHeader($field, $addToolbar, $fieldtypes_to_skip, $fieldtypesWithSearch, $fieldtypes_allowed_to_orderby): string
    {
        $result = '';

        if ($field['type'] != 'ordering' && !in_array($field['type'], $fieldtypes_to_skip)) {

            $result .= '<th>';

            if ($field['allowordering'] && in_array($field['type'], $fieldtypes_allowed_to_orderby))

                if (Fields::isVirtualField($field))
                    $result .= '{{ ' . $field['fieldname'] . '.title }}';
                else
                    $result .= '{{ ' . $field['fieldname'] . '.label(true) }}';

            else
                $result .= '{{ ' . $field['fieldname'] . '.title }}';

            if ($addToolbar and in_array($field['type'], $fieldtypesWithSearch)) {

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
        $this->layoutType = 9;

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
        $this->layoutType = 2;
        $result = '<div class="form-horizontal">';

        $fieldTypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'imagegallery', 'filebox', 'dummy', 'virtual'];

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
                $result .= '<p><span style="color: #FB1E3D; ">*</span> {{ ' . $field['fieldname'] . ' }}</p>
';
                break;
            }
        }

        if ($addToolbar)
            $result .= '<div style="text-align:center;">{{ button("save") }} {{ button("saveandclose") }} {{ button("saveascopy") }} {{ button("cancel") }}</div>
';
        return $result;
    }

    public function storeAsFile($data): void
    {
        if ($this->ct->Env->folderToSaveLayouts !== null) {
            $this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutcode'], $data['layoutname'] . '.html');
            $this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutmobile'], $data['layoutname'] . '_mobile.html');
            $this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutcss'], $data['layoutname'] . '.css');
            $this->storeLayoutAsFile((int)$data['id'], $data['layoutname'], $data['layoutjs'], $data['layoutname'] . '.js');
        }
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
