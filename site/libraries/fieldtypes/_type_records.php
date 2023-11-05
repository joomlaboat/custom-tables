<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\database;
use CustomTables\CT;
use CustomTables\TwigProcessor;

class CT_FieldTypeTag_records
{
    //New function
    public static function resolveRecordTypeValue(&$field, $layoutcode, $rowValue, string $showPublishedString = '', ?string $separatorCharacter = ',')
    {
        if ($separatorCharacter === null)
            $separatorCharacter = ',';

        $ct = new CT;
        $ct->getTable($field->params[0]);

        $selector = $field->params[2];

        if (count($field->params) < 3)
            return 'selector not specified';

        $filter = $field->params[3] ?? '';

        //$showpublished = 0 - show published
        //$showpublished = 1 - show unpublished
        //$showpublished = 2 - show any

        if ($showPublishedString == '' and isset($field->params[6]))
            $showPublishedString = $field->params[6];

        if ($showPublishedString == 'published')
            $showpublished = 0;
        elseif ($showPublishedString == 'unpublished')
            $showpublished = 1;
        else
            $showpublished = 2;

        //this is important because it has been selected somehow.
        $ct->setFilter($filter, $showpublished);
        $ct->Filter->where[] = 'INSTR(' . database::quote($rowValue) . ',' . $ct->Table->realidfieldname . ')';
        $ct->getRecords();

        return CT_FieldTypeTag_records::processRecordRecords($ct, $layoutcode, $rowValue, $ct->Records, $separatorCharacter);
    }

    protected static function processRecordRecords(CT &$ct, $layoutcode, $rowValue, &$records, string $separatorCharacter = ',')
    {
        $valueArray = explode(',', $rowValue);

        $number = 1;

        //To make sure that records belong to the value
        $CleanSearchResult = array();
        foreach ($records as $row) {
            if (in_array($row[$ct->Table->realidfieldname], $valueArray))
                $CleanSearchResult[] = $row;
        }

        $htmlresult = '';

        foreach ($CleanSearchResult as $row) {
            $row['_number'] = $number;
            $row['_islast'] = $number == count($CleanSearchResult);

            $twig = new TwigProcessor($ct, $layoutcode);

            if ($htmlresult != '')
                $htmlresult .= $separatorCharacter;

            $htmlresult .= $twig->process($row);

            if ($twig->errorMessage !== null)
                $ct->errors[] = $twig->errorMessage;

            $number++;
        }

        return $htmlresult;
    }

    //Old function
    public static function resolveRecordType($rowValue, $field, array $options)
    {
        if (count($field->params) < 1)
            return 'table not specified';

        if (count($field->params) < 2)
            return 'field or layout not specified';

        if (count($field->params) < 3)
            return 'selector not specified';

        $esr_table = $field->params[0];

        $sortByField = '';
        if (isset($field->params[5]))
            $sortByField = $field->params[5];

        if (($options[1] ?? '') != '')
            $sortByField = $options[1];

        if (($options[0] ?? '') != '') {
            $esr_field = $options[0];
        } else
            $esr_field = $field->params[1];

        $esr_selector = $field->params[2];

        if (count($field->params) > 3)
            $esr_filter = $field->params[3];
        else
            $esr_filter = '';

        if (($options[2] ?? '') != '')
            $separator = $options[1];
        else
            $separator = '';

        return JHTML::_('ESRecordsView.render', $rowValue, $esr_table, $esr_field, $esr_selector, $esr_filter, $sortByField, $separator);
    }
}
