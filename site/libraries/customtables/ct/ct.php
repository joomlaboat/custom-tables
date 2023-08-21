<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTablesImageMethods;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use CustomTablesKeywordSearch;
use mysql_xdevapi\Exception;
use CustomTables\CustomPHP\CleanExecute;

class CT
{
    var Languages $Languages;
    var Environment $Env;
    var ?Params $Params;
    var ?Table $Table;
    var ?array $Records;
    var string $GroupBy;
    var ?Ordering $Ordering;
    var ?Filtering $Filter;
    var ?string $alias_fieldname;
    var int $Limit;
    var int $LimitStart;
    var bool $isEditForm;
    var $app;
    var Document $document;
    var $db;
    var array $editFields;
    var array $LayoutVariables;

    function __construct($menuParams = null, $blockExternalVars = true, ?string $ModuleId = null)
    {
        $this->app = Factory::getApplication();
        $this->document = $this->app->getDocument();
        $this->db = Factory::getDBO();

        $this->Languages = new Languages;
        $this->Env = new Environment;
        $this->Params = new Params($menuParams, $blockExternalVars, $ModuleId);

        $this->GroupBy = '';
        $this->isEditForm = false;
        $this->LayoutVariables = [];
        $this->editFields = [];

        $this->Limit = 0;
        $this->LimitStart = 0;

        $this->Table = null;
        $this->Records = null;
        $this->Ordering = null;
        $this->Filter = null;
    }

    function isRecordNull(?array $row): bool
    {
        if (is_null($row))
            return true;

        if (!is_array($row))
            return true;

        if (count($row) == 0)
            return true;

        if (!isset($row[$this->Table->realidfieldname]))
            return true;

        $id = $row[$this->Table->realidfieldname];

        if (is_null($id))
            return true;

        if ($id == '')
            return true;

        if (is_numeric($id) and intval($id) == 0)
            return true;

        return false;
    }

    function setParams($menuParams = null, $blockExternalVars = true, ?string $ModuleId = null): void
    {
        $this->Params->setParams($menuParams, $blockExternalVars, $ModuleId);
    }

    function getTable($tableNameOrID, $userIdFieldName = null): void
    {
        $this->Table = new Table($this->Languages, $this->Env, $tableNameOrID, $userIdFieldName);
        $this->Ordering = new Ordering($this->Table, $this->Params);
        $this->prepareSEFLinkBase();
    }

    public function setTable(array $tableRow, $userIdFieldName = null): void
    {
        $this->Table = new Table($this->Languages, $this->Env, 0);
        $this->Table->setTable($tableRow, $userIdFieldName);

        $this->Ordering = new Ordering($this->Table, $this->Params);

        $this->prepareSEFLinkBase();
    }

    protected function prepareSEFLinkBase(): void
    {
        if (is_null($this->Table))
            return;

        if (is_null($this->Table->fields))
            return;

        if (!str_contains($this->Env->current_url, 'option=com_customtables')) {
            foreach ($this->Table->fields as $fld) {
                if ($fld['type'] == 'alias') {
                    $this->alias_fieldname = $fld['fieldname'];
                    return;
                }
            }
        }
        $this->alias_fieldname = null;
    }

    function setFilter($filter_string = '', $showpublished = 0): void
    {
        $this->Filter = new Filtering($this, $showpublished);
        if ($filter_string != '')
            $this->Filter->addWhereExpression($filter_string);
    }

    function getRecords($all = false, $limit = 0): bool
    {
        $where = count($this->Filter->where) > 0 ? ' WHERE ' . implode(' AND ', $this->Filter->where) : '';
        $where = str_replace('\\', '', $where); //Just to make sure that there is nothing weird in the query

        if ($this->getNumberOfRecords($where) == -1)
            return false;

        $query = $this->buildQuery($where);

        if ($this->Table->recordcount > 0) {

            if ($limit > 0) {
                $this->db->setQuery($query, 0, $limit);
                $this->Limit = $limit;
            } else {
                $the_limit = $this->Limit;

                if ($all) {
                    if ($the_limit > 0)
                        $this->db->setQuery($query, 0, 20000); //or we will run out of memory
                } else {
                    if ($the_limit > 20000)
                        $the_limit = 20000;

                    if ($the_limit == 0)
                        $the_limit = 20000; //or we will run out of memory

                    if ($this->Table->recordcount < $this->LimitStart or $this->Table->recordcount < $the_limit)
                        $this->LimitStart = 0;

                    $this->db->setQuery($query, $this->LimitStart, $the_limit);
                }
            }
            $this->Records = $this->db->loadAssocList();
        } else
            $this->Records = [];

        if ($this->Limit == 0)
            $this->Limit = 20000;

        return true;
    }

    function getNumberOfRecords(string $where = ''): int
    {
        $query_check_table = 'SHOW TABLES LIKE ' . $this->db->quote(str_replace('#__', $this->db->getPrefix(), $this->Table->realtablename));
        $this->db->setQuery($query_check_table);
        $rows = $this->db->loadObjectList();
        if (count($rows) == 0)
            return -1;

        $query_analytical = 'SELECT COUNT(' . $this->Table->tablerow['realidfieldname'] . ') AS count FROM ' . $this->Table->realtablename . ' ' . $where;

        try {

            $this->db->setQuery($query_analytical);
            $rows = $this->db->loadObjectList();

        } catch (Exception $e) {
            echo 'Database error happened';
            echo $e->getMessage();
            return 0;
        }

        if (count($rows) == 0)
            $this->Table->recordcount = -1;
        else
            $this->Table->recordcount = intval($rows[0]->count);

        return $this->Table->recordcount;
    }

    function buildQuery($where): ?string
    {
        $ordering = $this->GroupBy != '' ? [$this->GroupBy] : [];

        if (is_null($this->Table) or is_null($this->Table->tablerow)) {
            $this->app->enqueueMessage('Table not set.', 'error');
            return null;
        }

        if ($this->Ordering->ordering_processed_string !== null) {
            $this->Ordering->parseOrderByString();
        }

        $selects = $this->Table->selects;

        if ($this->Ordering->orderby !== null) {
            if ($this->Ordering->selects !== null)
                $selects[] = $this->Ordering->selects;

            $ordering[] = $this->Ordering->orderby;
        }

        $query = 'SELECT ' . implode(',', $selects) . ' FROM ' . $this->Table->realtablename . ' ';
        $query .= $where;
        $query .= ' GROUP BY ' . $this->Table->realtablename . '.' . $this->Table->realidfieldname;

        if (count($ordering) > 0)
            $query .= ' ORDER BY ' . implode(',', $ordering);

        return $query;
    }

    function getRecordsByKeyword(): void
    {
        $moduleId = $this->Env->jinput->get('moduleid', 0, 'INT');
        if ($moduleId != 0) {
            $keywordSearch = $this->Env->jinput->get('eskeysearch_' . $moduleId, '', 'STRING');
            if ($keywordSearch != '') {
                require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
                    . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'filter' . DIRECTORY_SEPARATOR . 'keywordsearch.php');

                $KeywordSearcher = new CustomTablesKeywordSearch($this);

                $KeywordSearcher->groupby = $this->GroupBy;
                $KeywordSearcher->esordering = $this->Ordering->ordering_processed_string;

                $this->Records = $KeywordSearcher->getRowsByKeywords(
                    $keywordSearch,
                    $this->Table->recordcount,
                    (int)$this->app->getState('limit'),
                    $this->LimitStart
                );

                if ($this->Table->recordcount < $this->LimitStart)
                    $this->LimitStart = 0;
            }
        }
    }

    function getRecordList(): array
    {
        if ($this->Table->recordlist !== null)
            return $this->Table->recordlist;

        $recordList = [];

        foreach ($this->Records as $row)
            $recordList[] = $row[$this->Table->realidfieldname];

        $this->Table->recordlist = $recordList;
        return $recordList;
    }

    function applyLimits($limit = 0): void
    {
        if ($limit != 0) {
            $this->Limit = $limit;
            $this->LimitStart = 0;
            return;
        }

        $limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
        $this->Limit = $this->app->getUserState($limit_var, 0);

        //Grouping
        if ($this->Params->groupBy != '')
            $this->GroupBy = Fields::getRealFieldName($this->Params->groupBy, $this->Table->fields);
        else
            $this->GroupBy = '';

        if ($this->Env->frmt != 'html') {
            //export all records if format is csv, xml etc.
            $this->Limit = 0;
            $this->LimitStart = 0;
            return;
        }

        if ($this->Params->blockExternalVars) {
            if ((int)$this->Params->limit > 0) {
                $this->Limit = (int)$this->Params->limit;
                $this->LimitStart = $this->Env->jinput->getInt('start', 0);
                $this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
            } else {
                $this->Limit = 0;
                $this->LimitStart = 0;
            }
        } else {
            $this->LimitStart = $this->Env->jinput->getInt('start', 0);
            $this->Limit = $this->app->getUserState($limit_var, 0);

            if ($this->Limit == 0 and (int)$this->Params->limit > 0) {
                $this->Limit = (int)$this->Params->limit;
            }

            // In case limit has been changed, adjust it
            $this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
        }
    }

    function loadJSAndCSS(): void
    {
        //JQuery and Bootstrap
        if ($this->Env->version < 4) {
            $this->document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/jquery.min.js"></script>');
            $this->document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/bootstrap.min.js"></script>');
        } else {

            HTMLHelper::_('jquery.framework');
            $this->document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/media/system/css/fields/switcher.css">');
        }

        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.uploadfile.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.form.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/base64.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/catalog.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/edit.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/esmulti.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/modal.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/uploader.js"></script>');

        $params = ComponentHelper::getParams('com_customtables');
        $googleMapAPIKey = $params->get('googlemapapikey');

        if ($googleMapAPIKey !== null and $googleMapAPIKey != '')
            $this->document->addCustomTag('<script src="https://maps.google.com/maps/api/js?key=' . $googleMapAPIKey . '&sensor=false"></script>');

        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/combotree.js"></script>');
        $this->document->addCustomTag('<script>let ctWebsiteRoot = "' . $this->Env->WebsiteRoot . '";</script>');

        if ($this->Params->ModuleId == null)
            $this->document->addCustomTag('<script>ctItemId = "' . $this->Params->ItemId . '";</script>');

        //Styles
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/style.css" type="text/css" rel="stylesheet" >');
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/modal.css" type="text/css" rel="stylesheet" >');
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/uploadfile.css" rel="stylesheet">');

        $this->document->addCustomTag('<link href="' . URI::root(true) . '/media/system/css/fields/calendar.min.css" rel="stylesheet" />');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar-locales/date/gregorian/date-helper.min.js" defer></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar.min.js" defer></script>');

        Text::script('COM_CUSTOMTABLES_JS_SELECT_RECORDS');
        Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE1');
        Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE');
        Text::script('COM_CUSTOMTABLES_JS_NOTHING_TO_SAVE');
        Text::script('COM_CUSTOMTABLES_JS_SESSION_EXPIRED');
        Text::script('COM_CUSTOMTABLES_SELECT');
        Text::script('COM_CUSTOMTABLES_SELECT_NOTHING');
        Text::script('COM_CUSTOMTABLES_ADD');
    }

    public function deleteSingleRecord($listing_id): int
    {
        //delete images if exist
        $imageMethods = new CustomTablesImageMethods;

        $query = 'SELECT * FROM ' . $this->Table->realtablename . ' WHERE ' . $this->Table->realidfieldname . '=' . $this->db->quote($listing_id);

        $this->db->setQuery($query);
        $rows = $this->db->loadAssocList();

        if (count($rows) == 0)
            return -1;

        $row = $rows[0];

        foreach ($this->Table->fields as $fieldrow) {
            $field = new Field($this, $fieldrow, $row);

            if ($field->type == 'image') {
                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

                //delete single image
                if ($row[$field->realfieldname] !== null) {
                    $imageMethods->DeleteExistingSingleImage(
                        $row[$field->realfieldname],
                        $ImageFolder_,
                        $field->params[0],
                        $this->Table->realtablename,
                        $field->realfieldname,
                        $this->Table->realidfieldname
                    );
                }
            } elseif ($field->type == 'imagegallery') {
                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

                //delete gallery images if exist
                $galleryName = $field->fieldname;
                $photoTableName = '#__customtables_gallery_' . $this->Table->tablename . '_' . $galleryName;

                $query = 'SELECT photoid FROM ' . $photoTableName . ' WHERE listingid=' . $this->db->quote($listing_id);
                $this->db->setQuery($query);

                $photoRows = $this->db->loadObjectList();

                $imageGalleryPrefix = 'g';

                foreach ($photoRows as $photoRow) {
                    $imageMethods->DeleteExistingGalleryImage(
                        $ImageFolder_,
                        $imageGalleryPrefix,
                        $this->Table->tableid,
                        $galleryName,
                        $photoRow->photoid,
                        $field->params[0],
                        true
                    );
                }
            }
        }

        $query = 'DELETE FROM ' . $this->Table->realtablename . ' WHERE ' . $this->Table->realidfieldname . '=' . $this->db->quote($listing_id);
        $this->db->setQuery($query);
        $this->db->execute();

        $this->Table->saveLog($listing_id, 5);

        $new_row = array();

        if ($this->Env->advancedTagProcessor)
            CleanExecute::executeCustomPHPfile($this->Table->tablerow['customphp'], $new_row, $row);

        return 1;
    }

    public function setPublishStatusSingleRecord($listing_id, $status): int
    {
        if (!$this->Table->published_field_found)
            return -1;

        $query = 'UPDATE ' . $this->Table->realtablename . ' SET published=' . (int)$status . ' WHERE ' . $this->Table->realidfieldname . '=' . $this->db->quote($listing_id);

        $this->db->setQuery($query);
        $this->db->execute();

        if ($status == 1)
            $this->Table->saveLog($listing_id, 3);
        else
            $this->Table->saveLog($listing_id, 4);

        $this->RefreshSingleRecord($listing_id, 0);
        return 1;
    }

    public function RefreshSingleRecord($listing_id, $save_log): int
    {
        $query = 'SELECT ' . implode(',', $this->Table->selects) . ' FROM ' . $this->Table->realtablename
            . ' WHERE ' . $this->Table->realidfieldname . '=' . $this->db->quote($listing_id) . ' LIMIT 1';

        $this->db->setQuery($query);
        $rows = $this->db->loadAssocList();

        if (count($rows) == 0)
            return -1;

        $row = $rows[0];

        $saveField = new SaveFieldQuerySet($this, $row, false);
        $this->Env->jinput->set("listing_id", $listing_id);

        if ($this->Env->advancedTagProcessor)
            CleanExecute::doPHPonChange($this, $row);

        //update MD5s
        $this->updateMD5($saveField, $listing_id);

        if ($save_log == 1)
            $this->Table->saveLog($listing_id, 10);

        //TODO use $saveField->saveField
        //$this->updateDefaultValues($row);

        if ($this->Env->advancedTagProcessor)
            CleanExecute::executeCustomPHPfile($this->Table->tablerow['customphp'], $row, $row);

        //Send email note if applicable
        if ($this->Params->onRecordAddSendEmail == 3 and ($this->Params->onRecordSaveSendEmailTo != '' or $this->Params->onRecordAddSendEmailTo != '')) {
            //check conditions

            if ($saveField->checkSendEmailConditions($listing_id, $this->Params->sendEmailCondition)) {
                //Send email conditions met
                $saveField->sendEmailIfAddressSet($listing_id, $row);//,$new_username,$new_password);
            }
        }
        return 1;
    }

    protected function updateMD5(SaveFieldQuerySet $saveField, string $listing_id)
    {
        //TODO: Use savefield
        $saveMD5Query = array();
        foreach ($this->Table->fields as $fieldrow) {
            if ($fieldrow['type'] == 'md5') {
                $fieldsToCount = explode(',', str_replace('"', '', $fieldrow['typeparams']));//only field names, nothing else

                $fields = array();
                foreach ($fieldsToCount as $f) {
                    //to make sure that field exists
                    foreach ($this->Table->fields as $fieldrow2) {
                        if ($fieldrow2['fieldname'] == $f and $fieldrow['fieldname'] != $f)
                            $fields[] = 'COALESCE(' . $fieldrow2['realfieldname'] . ')';
                    }
                }

                if (count($fields) > 1)
                    $saveMD5Query[] = $fieldrow['realfieldname'] . '=md5(CONCAT_WS(' . implode(',', $fields) . '))';
            }
        }
        $saveField->runUpdateQuery($saveMD5Query, $listing_id);
    }
}
