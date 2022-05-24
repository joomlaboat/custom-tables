<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

use CustomTablesKeywordSearch;

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
    var $alias_fieldname;
    var int $Limit;
    var int $LimitStart;
    var bool $isEditForm;
    var $app;
    var Document $document;
    var $db;

    var array $LayoutVariables;

    function __construct()
    {
        $this->app = Factory::getApplication();
        $this->document = $this->app->getDocument();
        $this->db = Factory::getDBO();

        $this->Languages = new Languages;
        $this->Env = new Environment;
        $this->Params = new Params();

        $this->GroupBy = '';
        $this->isEditForm = false;
        $this->LayoutVariables = [];

        $this->Limit = 0;
        $this->LimitStart = 0;

        $this->Table = null;
        $this->Records = null;
        $this->Ordering = null;
        $this->Filter = null;
    }

    function setParams($menuParams = null, $blockExternalVars = false): void
    {
        $this->Params->setParams($menuParams, $blockExternalVars);
        $this->Env->ItemId = $this->Params->ItemId;
    }

    function getTable($tablename_or_id, $userIdFieldName = null): void
    {
        $this->Table = new Table($this->Languages, $this->Env, $tablename_or_id, $userIdFieldName);
        $this->Ordering = new Ordering($this->Table, $this->Params);

        $this->prepareSEFLinkBase();
    }

    public function setTable(array &$tablerow, $useridfieldname = null, bool $load_fields = true): void
    {
        $this->Table = new Table($this->Languages, $this->Env, 0);
        $this->Table->setTable($tablerow, $useridfieldname, $load_fields);

        $this->Ordering = new Ordering($this->Table, $this->Params);

        $this->prepareSEFLinkBase();
    }

    protected function prepareSEFLinkBase(): void
    {
        if ($this->Table->fields == null)
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

    function getRecords($all = false): bool
    {
        $db = Factory::getDBO();

        $where = count($this->Filter->where) > 0 ? ' WHERE ' . implode(' AND ', $this->Filter->where) : '';
        $where = str_replace('\\', '', $where); //Just to make sure that there is nothing weird in the query

        $this->getNumberOfRecords($where);
        $query = $this->buildQuery($where);

        if ($this->Table->recordcount > 0) {
            $the_limit = $this->Limit;

            if ($all) {
                if ($the_limit > 0)
                    $db->setQuery($query, 0, 20000); //or we will run out of memory
            } else {
                if ($the_limit > 20000)
                    $the_limit = 20000;

                if ($the_limit == 0)
                    $the_limit = 20000; //or we will run out of memory


                if ($this->Table->recordcount < $this->LimitStart or $this->Table->recordcount < $the_limit)
                    $this->LimitStart = 0;

                $db->setQuery($query, $this->LimitStart, $the_limit);
            }

            $this->Records = $db->loadAssocList();
        } else
            $this->Records = [];

        return true;
    }

    function getNumberOfRecords($where): int
    {
        $db = Factory::getDBO();

        $query_analytical = 'SELECT COUNT(' . $this->Table->tablerow['realidfieldname'] . ') AS count FROM ' . $this->Table->realtablename . ' ' . $where;

        $db->setQuery($query_analytical);
        $rows = $db->loadObjectList();
        if (count($rows) == 0)
            $this->Table->recordcount = 0;
        else
            $this->Table->recordcount = $rows[0]->count;

        return $this->Table->recordcount;
    }

    function buildQuery($where): string
    {
        $ordering = $this->GroupBy != '' ? [$this->GroupBy] : [];

        $selects = [$this->Table->tablerow['query_selects']];

        if ($this->Ordering->ordering_processed_string != null) {
            $this->Ordering->parseOrderByString();
        }

        if ($this->Ordering->orderby != null) {
            if ($this->Ordering->selects != null)
                $selects[] = $this->Ordering->selects;

            $ordering[] = $this->Ordering->orderby;
        }

        $query = 'SELECT ' . implode(',', $selects) . ' FROM ' . $this->Table->realtablename . ' ';

        if ($this->Ordering->inner != null)
            $query .= ' ' . $this->Ordering->inner;

        $query .= $where;

        $query .= ' GROUP BY ' . $this->Table->realtablename . '.' . $this->Table->realidfieldname;

        if (count($ordering) > 0)
            $query .= ' ORDER BY ' . implode(',', $ordering);

        return $query;
    }

    function getRecordsByKeyword(): void
    {
        $moduleid = $this->Env->jinput->get('moduleid', 0, 'INT');
        if ($moduleid != 0) {
            $eskeysearch_ = $this->Env->jinput->get('eskeysearch_' . $moduleid, '', 'STRING');
            if ($eskeysearch_ != '') {
                require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
                    . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'filter' . DIRECTORY_SEPARATOR . 'keywordsearch.php');

                $KeywordSearcher = new CustomTablesKeywordSearch($this);

                $KeywordSearcher->groupby = $this->GroupBy;
                $KeywordSearcher->esordering = $this->Ordering->ordering_processed_string;


                $this->Records = $KeywordSearcher->getRowsByKeywords(
                    $eskeysearch_,
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
        if ($this->Table->recordlist != null)
            return $this->Table->recordlist;

        $recordlist = [];

        foreach ($this->Records as $row)
            $recordlist[] = $row[$this->Table->realidfieldname];

        $this->Table->recordlist = $recordlist;
        return $recordlist;
    }

    function applyLimits(): void
    {
        $limit_var = 'com_customtables.limit_' . $this->Env->ItemId;

        $this->Limit = $this->app->getUserState($limit_var, 0);

        //Grouping
        if ($this->Params->groupBy != '')
            $this->GroupBy = Fields::getRealFieldName($this->Params->groupBy, $this->Table->fields);
        else
            $this->GroupBy = '';

        if ($this->Env->frmt != 'html') {
            //export all records if firmat is csv, xml etc.
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
        } else
            $this->document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/media/system/css/fields/switcher.css">');

        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.uploadfile.min.js"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/jquery.form.js"></script>');

        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');
        $this->document->addScript(URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/base64.js');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/catalog.js" type="text/javascript"></script>');
        $this->document->addScript(URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/edit.js');
        $this->document->addScript(URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/esmulti.js');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/modal.js" type="text/javascript"></script>');
        $this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/uploader.js"></script>');

        $params = ComponentHelper::getParams('com_customtables');
        $googlemapapikey = $params->get('googlemapapikey');

        $this->document->addCustomTag('<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=' . $googlemapapikey . '&sensor=false"></script>');

        $this->document->addScript(URI::root(true) . '/components/com_customtables/libraries/customtables/media/js/combotree.js');

        //Styles
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/style.css" type="text/css" rel="stylesheet" >');
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/modal.css" type="text/css" rel="stylesheet" >');
        $this->document->addCustomTag('<link href="' . URI::root(true) . '/components/com_customtables/libraries/customtables/media/css/uploadfile.css" rel="stylesheet">');
    }
}
