<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

/* All tags already implemented using Twig */

// no direct access
defined('_JEXEC') or die();

use Exception;
use LayoutProcessor;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Layouts
{
    var CT $ct;
    var ?int $tableId;
    var ?int $layoutId;
    var ?int $layoutType;
    var ?string $pageLayoutNameString;
    var ?string $pageLayoutLink;
    var ?string $layoutCode;
    var ?string $layoutCodeCSS;
    var ?string $layoutCodeJS;

    function __construct(&$ct)
    {
        $this->ct = &$ct;
        $this->tableId = null;
        $this->layoutType = null;
        $this->pageLayoutNameString = null;
        $this->pageLayoutLink = null;
        $this->layoutCode = null;
        $this->layoutCodeCSS = null;
        $this->layoutCodeJS = null;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function processLayoutTag(string &$htmlResult): bool
    {
        $options = array();
        $fList = CTMiscHelper::getListToReplace('layout', $options, $htmlResult, '{}');

        if (count($fList) == 0)
            return false;

        $i = 0;
        foreach ($fList as $fItem) {
            $parts = CTMiscHelper::csv_explode(',', $options[$i]);
            $layoutname = $parts[0];

            $ProcessContentPlugins = false;
            if (isset($parts[1]) and $parts[1] == 'process')
                $ProcessContentPlugins = true;

            $layout = $this->getLayout($layoutname);

            if ($ProcessContentPlugins)
                CTMiscHelper::applyContentPlugins($layout);

            $htmlResult = str_replace($fItem, $layout, $htmlResult);
            $i++;
        }

        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function getLayout($layoutNameOrId, bool $processLayoutTag = true, bool $checkLayoutFile = true, bool $addHeaderCode = true): string
    {
        $whereClause = new MySQLWhereClause();

        if (is_int($layoutNameOrId)) {
            if ($layoutNameOrId == 0)
                return '';

            $whereClause->addCondition('id', $layoutNameOrId);
        } else {
            if ($layoutNameOrId == '')
                return '';

            if (self::isLayoutContent($layoutNameOrId)) {
                $this->layoutType = 0;
                return $layoutNameOrId;
            }
            $whereClause->addCondition('layoutname', $layoutNameOrId);
        }

        $selects = [
            'id',
            'tableid',
            'layoutname',
            'layoutcode',
            'layoutmobile',
            'layoutcss',
            'layoutjs',
            'layouttype',
            'MODIFIED_TIMESTAMP'
        ];

        $rows = database::loadAssocList('#__customtables_layouts', $selects, $whereClause, null, null, 1);
        if (count($rows) != 1)
            return '';

        $row = $rows[0];
        $this->tableId = (int)$row['tableid'];
        $this->layoutId = (int)$row['id'];
        $this->layoutType = (int)$row['layouttype'];

        if ($this->ct->Env->isMobile and trim($row['layoutmobile']) != '') {
            $layoutCode = $row['layoutmobile'];
            if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null and is_string($layoutNameOrId)) {
                $content = $this->getLayoutFileContent($row['id'], $layoutNameOrId, $layoutCode, $row['modified_timestamp'], $layoutNameOrId . '_mobile.html', 'layoutmobile');
                if ($content != null)
                    $layoutCode = $content;
            }
        } else {
            $layoutCode = $row['layoutcode'];
            if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null and is_string($layoutNameOrId)) {
                $content = $this->getLayoutFileContent($row['id'], $layoutNameOrId, $layoutCode, $row['modified_timestamp'], $layoutNameOrId . '.html', 'layoutcode');
                if ($content != null)
                    $layoutCode = $content;
            }
        }

        if ($layoutCode === null)
            return '';

        //Get all layouts recursively
        if ($processLayoutTag)
            $this->processLayoutTag($layoutCode);

        if ($addHeaderCode and $this->ct->Env->advancedTagProcessor)
            $this->addCSSandJSIfNeeded($row, $checkLayoutFile);

        $this->pageLayoutNameString = $row['layoutname'];
        $this->pageLayoutLink = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $row['id'];
        $this->layoutCode = $layoutCode;
        return $layoutCode;
    }

    public static function isLayoutContent($layout): bool
    {
        if (str_contains($layout, '[') or str_contains($layout, '{'))
            return true;

        return false;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function getLayoutFileContent(int $layout_id, string $layoutName, string $layoutCode, int $db_layout_ts, string $filename, string $fieldName): ?string
    {
        if (file_exists($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename)) {
            $file_ts = filemtime($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

            if ($db_layout_ts == 0) {
                $whereClause = new MySQLWhereClause();
                $whereClause->addCondition('id', $layout_id);
                $rows = database::loadAssocList('#__customtables_layouts', ['MODIFIED_TIMESTAMP'], $whereClause, null, null, 1);

                if (count($rows) != 0) {
                    $row = $rows[0];
                    $db_layout_ts = $row['modified_timestamp'];
                }
            }

            if ($file_ts > $db_layout_ts) {
                $content = common::getStringFromFile($this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename);

                $data = [
                    $fieldName => addslashes($content),
                    'modified' => common::formatDateFromTimeStamp($file_ts)
                ];

                $whereClauseUpdate = new MySQLWhereClause();
                $whereClauseUpdate->addCondition('id', $layout_id);
                database::update('#__customtables_layouts', $data, $whereClauseUpdate);
                return $content;
            }
        } else {
            $this->storeLayoutAsFile($layout_id, $layoutName, $layoutCode, $filename);
        }
        return null;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function storeLayoutAsFile(int $layout_id, string $layoutName, ?string $layoutCode, string $filename): bool
    {
        $layoutCode = trim($layoutCode ?? '');
        $path = $this->ct->Env->folderToSaveLayouts . DIRECTORY_SEPARATOR . $filename;

        if ($layoutCode == '') {
            if (file_exists($path))
                try {
                    unlink($path);
                } catch (Exception $e) {
                    common::enqueueMessage($path . ': ' . $e->getMessage());
                    return false;
                }

            return true;
        }

        $msg = common::saveString2File($path, $layoutCode);
        if ($msg !== null) {
            common::enqueueMessage($path . ': ' . $msg);
            return false;
        }

        try {
            @$file_ts = filemtime($path);
        } catch (Exception $e) {
            common::enqueueMessage($path . ': ' . $e->getMessage());
            return false;
        }

        if ($file_ts == '') {
            common::enqueueMessage($path . ': No permission -  file not saved');
            return false;
        } else {

            $data = ['modified' => common::formatDateFromTimeStamp($file_ts)];
            $whereClauseUpdate = new MySQLWhereClause();

            if ($layout_id == 0) {
                $whereClauseUpdate->addCondition('layoutname', $layoutName);
            } else {
                $whereClauseUpdate->addCondition('id', $layout_id);
            }
            database::update('#__customtables_layouts', $data, $whereClauseUpdate);
        }
        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function addCSSandJSIfNeeded(array $layoutRow, bool $checkLayoutFile = true): void
    {
        $layoutContent = trim($layoutRow['layoutcss'] ?? '');

        if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
            $content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $layoutContent, $layoutRow['modified_timestamp'], $layoutRow['layoutname'] . '.css', 'layoutcss');
            if ($content != null)
                $layoutContent = $content;
        }

        if ($layoutContent != '') {
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $this->layoutCodeCSS = $twig->process($this->ct->Table->record ?? null);

            if (defined('_JEXEC')) {
                if ($twig->errorMessage !== null)
                    $this->ct->errors[] = $twig->errorMessage;

                $this->ct->document->addCustomTag('<style>' . $this->layoutCodeCSS . '</style>');
            }
        }

        $layoutContent = trim($layoutRow['layoutjs'] ?? '');
        if ($checkLayoutFile and $this->ct->Env->folderToSaveLayouts !== null) {
            $content = $this->getLayoutFileContent($layoutRow['id'], $layoutRow['layoutname'], $layoutContent, $layoutRow['modified_timestamp'], $layoutRow['layoutname'] . '.js', 'layoutjs');
            if ($content != null)
                $layoutContent = $content;
        }

        if ($layoutContent != '') {
            $twig = new TwigProcessor($this->ct, $layoutContent, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
            $this->layoutCodeJS = $twig->process($this->ct->Table->record ?? null);

            if (defined('_JEXEC')) {
                if ($twig->errorMessage !== null)
                    $this->ct->errors[] = $twig->errorMessage;

                $this->ct->document->addCustomTag('<script>' . $this->layoutCodeJS . '</script>');
            }
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
                    common::enqueueMessage($path . ': ' . $e->getMessage());
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
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
        if (defined('_JEXEC')) {
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

        return array(
            1 => 'Catalog',
            //5 => 'Catalog Page',
            //6 => 'Catalog Item',
            2 => 'Edit Form',
            4 => 'Details',
            //3 => 'COM_CUSTOMTABLES_LAYOUTS_DETAILS',
            7 => 'Email Message',
            8 => 'XML File',
            9 => 'CSV File',
            10 => 'JSON File'
        );
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     * @since 3.0.0
     */
    function parseRawLayoutContent(string $content, bool $applyContentPlugins = true): string
    {
        if ($this->ct->Env->legacySupport) {
            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $content;
            $content = $LayoutProc->fillLayout($this->ct->Table->record);
        }

        $twig = new TwigProcessor($this->ct, $content);
        $content = $twig->process($this->ct->Table->record);

        if ($twig->errorMessage !== null)
            $this->ct->errors[] = $twig->errorMessage;

        if ($applyContentPlugins and $this->ct->Params->allowContentPlugins)
            $content = CTMiscHelper::applyContentPlugins($content);

        return $content;
    }

    /**
     * Used in WordPress version
     * @throws Exception
     * @since 3.2.2
     */
    function renderMixedLayout(int $layoutId, int $layoutType = null): array
    {
        if ($this->ct->Table->fields === null)
            return ['html' => 'CustomTable: Table not selected'];

        if ($layoutId !== 0) {
            $this->getLayout($layoutId);
            if ($this->layoutType === null)
                return ['html' => 'CustomTable: Layout "' . $layoutId . '" not found'];

        } else {
            if ($layoutType == 1 or $layoutType == 5)
                $this->layoutCode = $this->createDefaultLayout_SimpleCatalog($this->ct->Table->fields);
            elseif ($layoutType == 2) {
                if (defined('_JEXEC'))
                    $this->layoutCode = $this->createDefaultLayout_Edit($this->ct->Table->fields);
                elseif (defined('WPINC'))
                    $this->layoutCode = $this->createDefaultLayout_Edit_WP($this->ct->Table->fields);
            } elseif ($layoutType == 4 or $layoutType == 6 or $layoutType == 3)
                $this->layoutCode = $this->createDefaultLayout_Details($this->ct->Table->fields);
            elseif ($layoutType == 7)
                $this->layoutCode = $this->createDefaultLayout_Email($this->ct->Table->fields);
            elseif ($layoutType == 9)
                $this->layoutCode = $this->createDefaultLayout_CSV($this->ct->Table->fields);
        }
        /*
         * <option value="1">Simple Catalog</option>
                <option value="5">Catalog Page</option>
                <option value="6">Catalog Item</option>
                <option value="2">Edit form</option>
                <option value="4">Details</option>
                <!--<option value="3">COM_CUSTOMTABLES_LAYOUTS_RECORD_LINK</option>-->
                <option value="7">Email Message</option>
                <option value="8">XML File</option>
                <option value="9">CSV File</option>
                <option value="10">JSON File</option>
         */

        $task = common::inputPostCmd('task', null, 'create-edit-record');
        if ($task === null)
            $task = common::inputGetCmd('task');

        $output = ['style' => $this->layoutCodeCSS, 'script' => $this->layoutCodeJS];

        if (in_array($this->layoutType, [1, 5, 8, 9, 10])) {
            //Simple Catalog or Catalog Page
            if ($task == 'delete') {

                $listing_id = common::inputGetCmd('listing_id', 0);
                if (!empty($listing_id)) {
                    if ($this->ct->deleteSingleRecord($listing_id) === 1) {
                        common::enqueueMessage(common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED_1'), 'notice');
                    } else {
                        common::enqueueMessage(common::translate('COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_DELETED_1'));
                    }
                }
            }

            $output['html'] = $this->renderCatalog();

        } elseif ($this->layoutType == 2) {
            //Edit Form
            if ($task == 'saveandcontinue' or $task == 'save') {
                $record = new record($this->ct);
                $record->editForm->layoutContent = $this->layoutCode;
                $listing_id = common::inputGetCmd('id');
                if ($record->save($listing_id, false)) {

                    if ($this->ct->Env->advancedTagProcessor and !empty($this->ct->Table->tablerow['customphp'])) {

                        try {
                            $action = $record->isItNewRecord ? 'create' : 'update';
                            $customPHP = new CustomPHP($this->ct, $action);
                            $customPHP->executeCustomPHPFile($this->ct->Table->tablerow['customphp'], $record->row_new, $record->row_old);
                        } catch (Exception $e) {
                            $this->ct->errors[] = 'Custom PHP file: ' . $this->ct->Table->tablerow['customphp'] . ' (' . $e->getMessage() . ')';
                        }
                    }

                    common::enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_SAVED'), 'notice');
                } else {
                    common::enqueueMessage(common::translate('COM_CUSTOMTABLES_RECORD_NOT_SAVED'));

                    if ($record->ct->Params !== null and $record->ct->Params->msgItemIsSaved !== null)
                        common::enqueueMessage($record->ct->Params->msgItemIsSaved);
                }

                if ($task == 'save') {

                    $link = common::getReturnToURL();
                    if ($link === null)
                        $link = $this->ct->Params->returnTo;

                    $link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);

                    common::redirect($link);
                }

            } elseif ($task == 'cancel') {
                common::enqueueMessage(common::translate('COM_CUSTOMTABLES_EDIT_CANCELED'), 'notice');
                $link = common::getReturnToURL();
                if ($link === null)
                    $link = $this->ct->Params->returnTo;

                $link = CTMiscHelper::deleteURLQueryOption($link, 'view' . $this->ct->Table->tableid);

                common::redirect($link, common::translate('COM_CUSTOMTABLES_EDIT_CANCELED'));
            }

            $output['html'] = $this->renderEditForm();

            if ($this->ct->LayoutVariables['captcha'])
                $output['captcha'] = true;

            $output['fieldtypes'] = $this->ct->editFieldTypes;

        } elseif ($this->layoutType == 4 or $this->layoutType == 6) {
            //Details or Catalog Item
            if ($this->ct->Table->record === null) {
                $listing_id = common::inputGetCmd('listing_id');
                if ($listing_id !== null)
                    $this->ct->Table->loadRecord($listing_id);
            }
            $output['html'] = $this->renderDetails();
        } else
            $output['html'] = 'CustomTable: Unknown Layout Type';

        $output['scripts'] = $this->ct->LayoutVariables['scripts'] ?? null;
        $output['styles'] = $this->ct->LayoutVariables['styles'] ?? null;
        $output['jslibrary'] = $this->ct->LayoutVariables['jslibrary'] ?? null;
        return $output;
    }

    function createDefaultLayout_SimpleCatalog(array $fields, bool $addToolbar = true): string
    {
        $this->layoutType = 1;

        $result = '<style>' . PHP_EOL . '.datagrid th{text-align:left;}' . PHP_EOL . '.datagrid td{text-align:left;}' . PHP_EOL . '</style>' . PHP_EOL;
        $result .= '<div style="float:right;">{{ html.recordcount }}</div>' . PHP_EOL;

        if ($addToolbar) {
            $result .= '<div style="float:left;">{{ html.add }}</div>' . PHP_EOL;

            if (defined('_JEXEC'))
                $result .= '<div style="text-align:center;">{{ html.print }}</div>' . PHP_EOL;
        }

        $result .= '<div class="datagrid">' . PHP_EOL;

        if ($addToolbar)
            $result .= '<div>{{ html.batch(\'edit\',\'publish\',\'unpublish\',\'refresh\',\'delete\') }}</div>';

        $result .= PHP_EOL;

        $fieldTypes_to_skip = ['log', 'imagegallery', 'filebox', 'dummy'];
        $fieldTypesWithSearch = ['email', 'string', 'multilangstring', 'text', 'multilangtext', 'sqljoin', 'records', 'user', 'userid', 'int', 'checkbox', 'radio'];
        $fieldTypes_allowed_to_orderBy = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime', 'date', 'multilangstring', 'userid', 'user', 'virtual'];

        $result .= PHP_EOL . '<table>' . PHP_EOL;

        $result .= self::renderTableHead($fields, $addToolbar, $fieldTypes_to_skip, $fieldTypesWithSearch, $fieldTypes_allowed_to_orderBy);

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

        $imageGalleryFound = false;
        $fileBoxFound = false;

        foreach ($fields as $field) {

            if ($field['type'] == 'imagegallery') {
                $imageGalleryFound = true;
            } elseif ($field['type'] == 'filebox') {
                $fileBoxFound = true;
            } elseif ($field['type'] != 'ordering' && !in_array($field['type'], $fieldTypes_to_skip)) {

                if ($field['type'] == 'url')
                    $fieldValue = '<a href="{{ ' . $field['fieldname'] . ' }}" target="_blank">{{ ' . $field['fieldname'] . ' }}</a>';
                else
                    $fieldValue = '{{ ' . $field['fieldname'] . ' }}';

                $result .= '<td>' . $fieldValue . '</td>' . PHP_EOL;
            }
        }

        if ($addToolbar) {

            $toolbarButtons = ['edit', 'publish', 'refresh', 'delete'];

            if ($imageGalleryFound)
                $toolbarButtons [] = 'gallery';

            if ($fileBoxFound)
                $toolbarButtons [] = 'filebox';

            $result .= '<td>{{ html.toolbar("' . implode('","', $toolbarButtons) . '") }}</td>' . PHP_EOL;
        }

        $result .= '</tr>';

        $result .= PHP_EOL . '{% endblock %}';
        $result .= PHP_EOL . '</tbody>';
        $result .= PHP_EOL . '</table>' . PHP_EOL;

        $result .= PHP_EOL;
        $result .= '</div>' . PHP_EOL;

        if (defined('_JEXEC')) {
            if ($addToolbar)
                $result .= '<br/><div style="text-align:center;">{{ html.pagination }}</div>' . PHP_EOL;
        }

        return $result;
    }

    protected function renderTableHead(array $fields, bool $addToolbar, array $fieldtypes_to_skip, array $fieldTypesWithSearch, array $fieldtypes_allowed_to_orderby): string
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

    function renderTableColumnHeader(array $field, bool $addToolbar, array $fieldtypes_to_skip, array $fieldtypesWithSearch, array $fieldtypes_allowed_to_orderby): string
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

    function createDefaultLayout_Edit(array $fields, bool $addToolbar = true): string
    {
        $this->layoutType = 2;
        $result = '<legend>{{ table.title }}</legend>{{ html.goback() }}<div class="form-horizontal">';

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
                $result .= '<p><span style="color: #FB1E3D; ">*</span>' . ' {{ ' . $field['fieldname'] . '.title }}</p>';
                break;
            }
        }

        if ($addToolbar)
            $result .= '<div style="text-align:center;">{{ html.button("save") }} {{ html.button("saveandclose") }} {{ html.button("saveascopy") }} {{ html.button("cancel") }}</div>';
        return $result;
    }

    function createDefaultLayout_Edit_WP(array $fields, bool $addToolbar = true, bool $addLegend = true, bool $addGoBack = true): string
    {
        $this->layoutType = 2;

        $result = '';

        if ($addLegend)
            $result .= '<legend>{{ table.title }}</legend>';

        if ($addGoBack)
            $result .= '{{ html.goback() }}';

        $result .= '<table class="form-table" role="presentation">';

        $fieldTypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'imagegallery', 'filebox', 'dummy', 'virtual'];

        foreach ($fields as $field) {

            if (!in_array($field['type'], $fieldTypes_to_skip)) {

                $attribute = 'for="' . $this->ct->Env->field_input_prefix . $field['fieldname'] . '"';
                $label = '<th scope="row">
                            <label ' . $attribute . '>'
                    . '{{ ' . $field['fieldname'] . '.title }}'
                    . ((int)$field['isrequired'] == 1 ? '<span class="description">(' . __('required', 'customtables') . ')</span>' : '')//WP version
                    . '</label>
                        </th>';

                $input = '<td>
                            {{ ' . $field['fieldname'] . '.edit }}
                        </td>';

                $result .= '<tr class="form-field ' . ((int)$field['isrequired'] == 1 ? 'form-required' : 'form') . '">'
                    . $label
                    . $input
                    . '</tr>';
            }
        }

        $result .= '</table>';


        if ($addToolbar)
            $result .= '<div style="text-align:center;">{{ html.button("save") }} {{ html.button("saveandclose") }} {{ html.button("saveascopy") }} {{ html.button("cancel") }}</div>
';
        return $result;
    }

    function createDefaultLayout_Details(array $fields): string
    {
        $this->layoutType = 4;
        $result = '<legend>{{ table.title }}</legend>{{ html.goback() }}<div class="form-horizontal">';

        $fieldTypes_to_skip = ['dummy'];

        foreach ($fields as $field) {
            if (!in_array($field['type'], $fieldTypes_to_skip)) {
                $result .= '<div class="control-group">';

                //if ($field['type'] == 'creationtime' or $field['type'] == 'changetime' or $field['type'] == 'lastviewtime')
                $fieldTag = '{{ ' . $field['fieldname'] . ' }}';

                $result .= '<div class="control-label">{{ ' . $field['fieldname'] . '.title }}</div><div class="controls"> ' . $fieldTag . ' </div>';


                $result .= '</div>';
            }
        }

        $result .= '</div>';

        return $result;
    }

    function createDefaultLayout_Email(array $fields): string
    {
        $this->layoutType = 4;
        $result = 'Dear ...<br/>A new records has been added to {{ table.title }} table.<br/><br/>Details below:<br/>';

        $fieldTypes_to_skip = ['log', 'imagegallery', 'filebox', 'dummy'];

        foreach ($fields as $field) {
            if (!in_array($field['type'], $fieldTypes_to_skip))
                $result .= '{{ ' . $field['fieldname'] . '.title }}: {{ ' . $field['fieldname'] . ' }}<br/>';
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

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function renderCatalog(): string
    {
        if ($this->ct->Env->frmt == 'html')
            common::loadJSAndCSS($this->ct->Params, $this->ct->Env);

        // -------------------- Table

        if ($this->ct->Table === null) {
            $this->ct->getTable($this->ct->Params->tableName);

            if ($this->ct->Table->tablename === null) {
                $this->ct->errors[] = 'Catalog View: Table not selected.';
                return 'Catalog View: Table not selected.';
            }
        }

        // --------------------- Filter
        $this->ct->setFilter($this->ct->Params->filter, $this->ct->Params->showPublished);

        if (!$this->ct->Params->blockExternalVars) {
            if (common::inputGetString('filter', '') and is_string(common::inputGetString('filter', '')))
                $this->ct->Filter->addWhereExpression(common::inputGetString('filter', ''));
        }

        if (!$this->ct->Params->blockExternalVars)
            $this->ct->Filter->addQueryWhereFilter();

        // --------------------- Shopping Cart
        if ($this->ct->Params->showCartItemsOnly) {
            $cookieValue = common::inputCookieGet($this->ct->Params->showCartItemsPrefix . $this->ct->Table->tablename);

            if (isset($cookieValue)) {
                if ($cookieValue == '') {
                    $this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
                } else {
                    $items = explode(';', $cookieValue);

                    foreach ($items as $item) {
                        $pair = explode(',', $item);
                        $this->ct->Filter->whereClause->addOrCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], (int)$pair[0]);
                    }
                }
            } else {
                //Show only shopping cart items. TODO: check the query
                $this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], 0);
            }
        }

        if ($this->ct->Params->listing_id !== null)
            $this->ct->Filter->whereClause->addCondition($this->ct->Table->realtablename . '.' . $this->ct->Table->tablerow['realidfieldname'], $this->ct->Params->listing_id);

        // --------------------- Sorting
        $this->ct->Ordering->parseOrderByParam();

        // --------------------- Limit
        if ($this->ct->Params->listing_id !== null)
            $this->ct->applyLimits(1);
        else
            $this->ct->applyLimits($this->ct->Params->limit ?? 0);

        $this->ct->LayoutVariables['layout_type'] = $this->layoutType;

        // -------------------- Load Records
        if (!$this->ct->getRecords()) {

            if (defined('_JEXEC'))
                $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_FOUND');

            return 'CustomTables: Records not loaded.';
        }

        // -------------------- Parse Layouts
        if ($this->ct->Env->frmt == 'json') {

            $pathViews = CUSTOMTABLES_LIBRARIES_PATH
                . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

            require_once($pathViews . 'json.php');
            $jsonOutput = new ViewJSON($this->ct);
            die($jsonOutput->render($this->layoutCode));
        }

        $twig = new TwigProcessor($this->ct, $this->layoutCode, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);
        $pageLayout = $twig->process();

        if (defined('_JEXEC')) {
            if ($twig->errorMessage !== null)
                $this->ct->errors[] = $twig->errorMessage;

            if ($this->ct->Params->allowContentPlugins)
                $pageLayout = CTMiscHelper::applyContentPlugins($pageLayout);
        }
        return $pageLayout;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function renderEditForm(): string
    {
        $recordRow = null;

        if ($this->ct->Table->tablename !== null) {
            $listing_id = common::inputGetCmd('listing_id');

            if ($listing_id !== null) {
                $recordRow = $this->ct->Table->loadRecord($listing_id);
            }
        }

        $formLink = common::curPageURL();

        $editForm = new Edit($this->ct);
        $editForm->layoutContent = $this->layoutCode;
        return $editForm->render($recordRow, $formLink, 'ctEditForm');
    }

    /**
     * @throws Exception
     * @since 3.2.8
     */
    protected function renderDetails(): string
    {

        if ($this->ct->Table->tablename !== null) {
            $listing_id = common::inputGetCmd('listing_id');

            if ($listing_id !== null) {
                $row = $this->ct->Table->loadRecord($listing_id);
            } else
                return 'Record not loaded (listing_id parameter not specified)';
        } else
            return 'Table not selected';

        if ($this->ct->Table->record === null) {
            return 'Record not loaded!';
        }

        $details = new Details($this->ct);
        $details->layoutDetailsContent = $this->layoutCode;
        $details->pageLayoutNameString = $this->pageLayoutNameString;
        $details->pageLayoutLink = $this->pageLayoutLink;

        if (isset($row)) {
            if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
                $row = ctProHelpers::getSpecificVersionIfSet($this->ct, $row);
        }

        $details->row = $row;

        /*
        if ($details->load()) {
            if ($details->layoutType == 8)
                $this->ct->Env->frmt = 'xml';
            elseif ($details->layoutType == 9)
                $this->ct->Env->frmt = 'csv';
            elseif ($details->layoutType == 10)
                $this->ct->Env->frmt = 'json';
        }*/

        if (!is_null($row)) {
            //Save view log
            $details->SaveViewLogForRecord($row);
            //$this->UpdatePHPOnView();
        }

        return $details->render();
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function getLayoutRowById(int $layoutId): ?array
    {
        $selects = [
            'id',
            'tableid',
            'layoutname',
            'layoutcode',
            'layoutmobile',
            'layoutcss',
            'layoutjs',
            'layouttype',
            'MODIFIED_TIMESTAMP'
        ];

        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('id', $layoutId);

        $rows = database::loadAssocList('#__customtables_layouts', $selects, $whereClause, null, null, 1);
        if (count($rows) != 1)
            return null;

        return $rows[0];
    }
}
