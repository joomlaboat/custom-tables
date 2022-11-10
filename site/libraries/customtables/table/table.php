<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;
use ESTables;

class Table
{
    use Logs;

    var Languages $Languages;
    var Environment $Env;
    var int $tableid;
    var ?array $tablerow;
    var ?string $tablename;
    var bool $published_field_found;
    var ?string $customtablename;
    var string $realtablename;
    var string $realidfieldname;
    var string $tabletitle;
    var ?string $alias_fieldname;
    var ?string $useridfieldname;
    var ?string $useridrealfieldname;
    var ?array $fields;
    var ?array $record;
    var int $recordcount;
    var ?array $recordlist;
    var $db;

    var ?array $imagegalleries;
    var ?array $fileboxes;
    var ?array $selects;

    function __construct($Languages, $Env, $tablename_or_id_not_sanitized, $useridfieldname = null)
    {
        $this->db = Factory::getDBO();

        $this->Languages = $Languages;
        $this->Env = $Env;
        $this->tableid = 0;
        $this->tablerow = null;
        $this->tablename = null;
        $this->published_field_found = false;
        $this->customtablename = null;
        $this->realtablename = '';
        $this->realidfieldname = '';
        $this->tabletitle = '';
        $this->alias_fieldname = null;
        $this->useridfieldname = null;
        $this->useridrealfieldname = null;
        $this->fields = null;
        $this->record = null;
        $this->recordcount = 0;
        $this->recordlist = null;
        $this->imagegalleries = null;
        $this->fileboxes = null;
        $this->selects = null;

        if ($tablename_or_id_not_sanitized === null or $tablename_or_id_not_sanitized == '')
            return;
        elseif (is_numeric($tablename_or_id_not_sanitized)) {
            $this->tablerow = ESTables::getTableRowByIDAssoc((int)$tablename_or_id_not_sanitized);// int sanitizes the input
        } else {
            $tablename_or_id = strtolower(trim(preg_replace('/\W/', '', $tablename_or_id_not_sanitized)));//[^a-zA-Z_\d]
            $this->tablerow = ESTables::getTableRowByNameAssoc($tablename_or_id);
        }

        if (is_null($this->tablerow))
            return;

        if (!isset($this->tablerow['id']))
            return;

        $this->setTable($this->tablerow, $useridfieldname);
    }

    function setTable($tableRow, $useridFieldName = null): void
    {
        $this->tablerow = $tableRow;
        $this->tablename = $this->tablerow['tablename'];
        $this->tableid = $this->tablerow['id'];
        $this->published_field_found = $this->tablerow['published_field_found'];
        $this->customtablename = $this->tablerow['customtablename'];
        $this->realtablename = $this->tablerow['realtablename'];
        $this->realidfieldname = $this->tablerow['realidfieldname'];

        if (isset($this->tablerow['tabletitle' . $this->Languages->Postfix]) and $this->tablerow['tabletitle' . $this->Languages->Postfix] != "")
            $this->tabletitle = $this->tablerow['tabletitle'];

        $this->alias_fieldname = '';
        $this->imagegalleries = array();
        $this->fileboxes = array();
        $this->useridfieldname = '';

        //Fields
        $this->fields = Fields::getFields($this->tableid);

        foreach ($this->fields as $fld) {

            if ($this->published_field_found and $fld['fieldname'] == 'published')
                $this->published_field_found = false;

            switch ($fld['type']) {
                case 'alias':
                    $this->alias_fieldname = $fld['fieldname'];
                    break;
                case 'imagegallery':
                    $this->imagegalleries[] = array($fld['fieldname'], $fld['fieldtitle' . $this->Languages->Postfix]);
                    break;
                case 'filebox':
                    $this->fileboxes[] = array($fld['fieldname'], $fld['fieldtitle' . $this->Languages->Postfix]);
                    break;

                case 'user':
                case 'userid':

                    if ($useridFieldName === null or $useridFieldName == $fld['fieldname']) {
                        $this->useridfieldname = $fld['fieldname'];
                        $this->useridrealfieldname = $fld['realfieldname'];
                    }
                    break;
            }
        }

        //Selects
        $this->selects = [];
        $this->selects[] = $this->realtablename . '.' . $this->realidfieldname;

        if ($this->tablerow['published_field_found']) {

            $this->selects[] = $this->realtablename . '.published AS listing_published';
            //$this->selects[] = $this->realtablename . '.published AS published'; //TODO: not used
        } else
            $this->selects[] = '1 AS listing_published';

        foreach ($this->fields as $field) {
            if ($field['type'] == 'blob') {
                $this->selects[] = 'OCTET_LENGTH(' . $this->realtablename . '.' . $field['realfieldname'] . ') AS ' . $field['realfieldname'];
                $this->selects[] = 'SUBSTRING(' . $this->realtablename . '.' . $field['realfieldname'] . ',1,255) AS ' . $field['realfieldname'] . '_sample';
            } elseif ($field['type'] != 'dummy')
                $this->selects[] = $this->realtablename . '.' . $field['realfieldname'];
        }
    }

    public function getRecordFieldValue($listingid, $resultField)
    {
        $db = Factory::getDBO();
        $query = ' SELECT ' . $resultField . ' FROM ' . $this->realtablename . ' WHERE ' . $this->realidfieldname . '=' . $db->quote($listingid) . ' LIMIT 1';

        $db->setQuery($query);
        $recs = $db->loadAssocList();

        if (count($recs) > 0)
            return $recs[0][$resultField];

        return "";
    }

    function loadRecord($listing_id)
    {
        $query = 'SELECT ' . implode(',', $this->selects) . ' FROM ' . $this->realtablename . ' WHERE ' . $this->realidfieldname . '=' . $this->db->quote($listing_id) . ' LIMIT 1';
        $this->db->setQuery($query);

        $recs = $this->db->loadAssocList();

        if (count($recs) < 1) return $this->record = null;

        $this->record = $recs[0];
        return $recs[0];
    }
}
