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
    var ?array $imagegalleries;
    var ?array $fileboxes;
    var ?array $selects;

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function __construct($Languages, $Env, $tablename_or_id_not_sanitized, $useridfieldname = null)
    {
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
            $this->tablerow = TableHelper::getTableRowByIDAssoc((int)$tablename_or_id_not_sanitized);// int sanitizes the input
        } else {
            $tablename_or_id = strtolower(trim(preg_replace('/\W/', '', $tablename_or_id_not_sanitized)));//[^a-zA-Z_\d]
            $this->tablerow = TableHelper::getTableRowByNameAssoc($tablename_or_id);
        }

        if (is_null($this->tablerow))
            return;

        if (!isset($this->tablerow['id']))
            return;

        $this->setTable($this->tablerow, $useridfieldname);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
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
            $this->tabletitle = $this->tablerow['tabletitle' . $this->Languages->Postfix];

        $this->alias_fieldname = '';
        $this->imagegalleries = array();
        $this->fileboxes = array();
        $this->useridfieldname = '';

        //Fields
        $this->fields = Fields::getFields($this->tableid);

        foreach ($this->fields as $fld) {

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

            $this->selects[] = 'LISTING_PUBLISHED';
        } else
            $this->selects[] = 'LISTING_PUBLISHED_1';

        foreach ($this->fields as $field) {
            if ($field['type'] == 'blob') {
                $this->selects[] = ['OCTET_LENGTH', $this->realtablename, $field['realfieldname'], $field['realfieldname']];
                $this->selects[] = ['SUBSTRING_255', $this->realtablename, $field['realfieldname'], $field['realfieldname'] . '_sample'];
            } elseif ($field['type'] == 'multilangstring' or $field['type'] == 'multilangtext') {// or $field['type'] == 'multilangarticle') {

                $firstLanguage = true;
                foreach ($this->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $this->selects[] = $this->realtablename . '.' . $field['realfieldname'] . $postfix;
                }

            } elseif ($field['type'] != 'dummy' and !Fields::isVirtualField($field))
                $this->selects[] = $this->realtablename . '.' . $field['realfieldname'];
        }
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function getRecordFieldValue($listingId, $resultField)
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($this->realidfieldname, $listingId);

        $rows = database::loadAssocList($this->realtablename, [$resultField], $whereClause, null, null, 1);

        if (count($rows) > 0)
            return $rows[0][$resultField];

        return "";
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function loadRecord(?string $listing_id): ?array
    {
        if ($this->selects === null)
            throw new Exception('Table::loadRecord - Table not set.');

        $whereClause = new MySQLWhereClause();
        if (empty($listing_id)) {
            $this->record = null;
            return null;
        }

        $whereClause->addCondition($this->realidfieldname, $listing_id);
        $rows = database::loadAssocList($this->realtablename, $this->selects, $whereClause, null, null, 1);

        if (count($rows) < 1) return $this->record = null;

        $this->record = $rows[0];
        return $rows[0];
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function isRecordExists($listing_id): bool
    {
        if ($listing_id === null or $listing_id === '' or (is_numeric($listing_id) and $listing_id === 0))
            return false;

        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($this->realidfieldname, $listing_id);
        $col = database::loadColumn($this->realtablename, ['COUNT_ROWS'], $whereClause, null, null, 1);
        if (count($col) == 0)
            return false;

        return $col[0] == 1;
    }

    function isRecordNull(): bool
    {
        if (is_null($this->record))
            return true;

        if (!is_array($this->record))
            return true;

        if (count($this->record) == 0)
            return true;

        if (!isset($this->record[$this->realidfieldname]))
            return true;

        $id = $this->record[$this->realidfieldname];

        if (is_null($id))
            return true;

        if ($id == '')
            return true;

        if (is_numeric($id) and intval($id) == 0)
            return true;

        return false;
    }
}
