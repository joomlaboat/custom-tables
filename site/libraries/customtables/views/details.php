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

// no direct access
defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Uri\Uri;
use LayoutProcessor;
use tagProcessor_PHP;
use CustomTables\ctProHelpers;

class Details
{
    var CT $ct;
    var string $layoutDetailsContent;
    var ?array $row;
    var int $layoutType;
    var ?string $pageLayoutNameString;
    var ?string $pageLayoutLink;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
        $this->layoutType = 0;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function load($layoutDetailsContent = null): bool
    {
        if (!$this->loadRecord())
            return false;

        $this->pageLayoutNameString = null;
        $this->pageLayoutLink = null;

        if (is_null($layoutDetailsContent)) {
            $this->layoutDetailsContent = '';

            if ($this->ct->Params->detailsLayout != '') {
                $Layouts = new Layouts($this->ct);
                $this->layoutDetailsContent = $Layouts->getLayout($this->ct->Params->detailsLayout);
                $this->pageLayoutNameString = $this->ct->Params->detailsLayout;
                $this->pageLayoutLink = common::UriRoot(true) . '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

                if ($Layouts->layoutType === null) {
                    $this->ct->errors[] = 'Layout "' . $this->ct->Params->detailsLayout . '" not found or the type is not set.';
                    return false;
                }

                $this->layoutType = $Layouts->layoutType;
            } else {
                $Layouts = new Layouts($this->ct);
                $this->layoutDetailsContent = $Layouts->createDefaultLayout_Details($this->ct->Table->fields);
                $this->pageLayoutNameString = 'Default Details Layout';
                $this->pageLayoutLink = null;
            }
        } else $this->layoutDetailsContent = $layoutDetailsContent;

        $this->ct->LayoutVariables['layout_type'] = $this->layoutType;

        if (!is_null($this->row)) {
            //Save view log
            $this->SaveViewLogForRecord($this->row);
            $this->UpdatePHPOnView();
        }
        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function loadRecord(): bool
    {
        $filter = '';

        if ($this->ct->Params->listing_id === null and $this->ct->Params->filter != '' and $this->ct->Params->alias == '') {

            $twig = new TwigProcessor($this->ct, $this->ct->Params->filter);
            $filter = $twig->process();

            if ($twig->errorMessage !== null) {
                $this->ct->errors[] = $twig->errorMessage;
                return false;
            }
        }

        if (!is_null($this->ct->Params->recordsTable) and !is_null($this->ct->Params->recordsUserIdField) and !is_null($this->ct->Params->recordsField)) {
            if (!$this->checkRecordUserJoin($this->ct->Params->recordsTable, $this->ct->Params->recordsUserIdField, $this->ct->Params->recordsField, $this->ct->Params->listing_id)) {
                //YOU ARE NOT AUTHORIZED TO ACCESS THIS SOURCE;
                $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED');
                return false;
            }
        }

        $this->ct->getTable($this->ct->Params->tableName, $this->ct->Params->userIdField);

        if ($this->ct->Table->tablename === null)
            return false;

        if (!is_null($this->ct->Params->alias) and $this->ct->Table->alias_fieldname != '')
            $filter = $this->ct->Table->alias_fieldname . '="' . $this->ct->Params->alias . '"';

        if ($filter != '') {
            if ($this->ct->Params->alias == '') {
                //Parse using layout
                if ($this->ct->Env->legacySupport) {
                    require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');
                    $LayoutProc = new LayoutProcessor($this->ct);
                    $LayoutProc->layout = $filter;
                    $filter = $LayoutProc->fillLayout(null, null, '[]', true);
                }

                $twig = new TwigProcessor($this->ct, $filter);
                $filter = $twig->process();

                if ($twig->errorMessage !== null) {
                    $this->ct->errors[] = $twig->errorMessage;
                    return false;
                }
            }

            $this->row = $this->getDataByFilter($filter);
        } else
            $this->row = $this->getDataById($this->ct->Params->listing_id);

        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function checkRecordUserJoin($recordsTable, $recordsUserIdField, $recordsField, $listing_id): bool
    {
        //TODO: avoid es_
        //$query = 'SELECT COUNT(*) AS count FROM #__customtables_table_' . $recordsTable . ' WHERE es_' . $recordsUserIdField . '='
        //. $this->ct->Env->user->id . ' AND INSTR(es_' . $recordsField . ',",' . $listing_id . ',") LIMIT 1';

        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('es_' . $recordsUserIdField, $this->ct->Env->user->id);
        $whereClause->addCondition('es_' . $recordsField, ',' . $listing_id . ',', 'INSTR');

        $rows = database::loadAssocList('#__customtables_table_' . $recordsTable, ['COUNT_ROWS'], $whereClause, null, null, 1);
        $num_rows = $rows[0]['record_count'];

        if ($num_rows == 0)
            return false;

        return true;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function getDataByFilter($filter)
    {
        if ($filter != '') {
            $this->ct->setFilter($filter, 2); //2 = Show any - published and unpublished
        } else {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_NOFILTER');
            return null;
        }

        $this->ct->Ordering->orderby = $this->ct->Table->realidfieldname . ' DESC';
        if ($this->ct->Table->published_field_found)
            $this->ct->Ordering->orderby .= ',published DESC';

        $rows = $this->buildQuery($this->ct->Filter->whereClause, 1);

        if (count($rows) < 1)
            return null;

        $row = $rows[0];

        if (isset($row)) {
            if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
                $row = ctProHelpers::getSpecificVersionIfSet($this->ct, $row);
        }
        return $row;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function buildQuery(MySQLWhereClause $whereClause, ?int $limit): array
    {
        $ordering = $this->ct->GroupBy != '' ? [$this->ct->GroupBy] : [];

        if (is_null($this->ct->Table) or is_null($this->ct->Table->tablerow)) {
            return [];
        }

        if ($this->ct->Ordering->ordering_processed_string !== null) {
            $this->ct->Ordering->parseOrderByString();
        }

        $selects = $this->ct->Table->selects;

        if ($this->ct->Ordering->orderby !== null) {
            if ($this->ct->Ordering->selects !== null)
                $selects[] = $this->ct->Ordering->selects;

            $ordering[] = $this->ct->Ordering->orderby;
        }
        return database::loadAssocList($this->ct->Table->realtablename, $selects, $whereClause,
            (count($ordering) > 0 ? implode(',', $ordering) : null), null,
            $limit, null, $this->ct->Table->realtablename . '.' . $this->ct->Table->realidfieldname);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected function getDataById($listing_id)
    {
        if (is_numeric($listing_id) and intval($listing_id) == 0) {
            $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_NOFILTER');
            return null;
        }

        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition('id', $listing_id);

        $rows = $this->buildQuery($whereClause, 1);

        if (count($rows) < 1)
            return null;

        $row = $rows[0];

        if (isset($row)) {
            if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers'))
                $row = ctProHelpers::getSpecificVersionIfSet($this->ct, $row);
        }
        return $row;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function SaveViewLogForRecord($rec): void
    {
        $updateFields = [];

        foreach ($this->ct->Table->fields as $field) {
            if ($field['type'] == 'lastviewtime')
                $updateFields[$field['realfieldname']] = common::currentDate();
            elseif ($field['type'] == 'viewcount')
                $updateFields[$field['realfieldname']] = ((int)($rec[$field['realfieldname']]) + 1);
        }

        if (count($updateFields) > 0) {

            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $rec[$this->ct->Table->realidfieldname]);

            database::update($this->ct->Table->realtablename, $updateFields, $whereClauseUpdate);
        }
    }

    protected function UpdatePHPOnView(): bool
    {
        if (!isset($row[$this->ct->Table->realidfieldname]))
            return false;

        foreach ($this->ct->Table->fields as $field) {
            if ($field['type'] == 'phponview') {
                $fieldname = $field['fieldname'];
                tagProcessor_PHP::processTempValue($this->ct, $this->row, $fieldname, $field->params);
            }
        }
        return true;
    }

    public function render(): string
    {
        $layoutDetailsContent = $this->layoutDetailsContent;

        if ($this->ct->Env->legacySupport) {
            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct);
            $LayoutProc->layout = $layoutDetailsContent;
            $layoutDetailsContent = $LayoutProc->fillLayout($this->row);
        }

        $twig = new TwigProcessor($this->ct, $layoutDetailsContent, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);
        $layoutDetailsContent = $twig->process($this->row);

        if ($twig->errorMessage !== null)
            $this->ct->errors[] = $twig->errorMessage;

        if ($this->ct->Params->allowContentPlugins)
            $layoutDetailsContent = CTMiscHelper::applyContentPlugins($layoutDetailsContent);

        return $layoutDetailsContent;
    }
}
