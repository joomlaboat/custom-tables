<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

use CustomTables\CT;
use CustomTables\TwigProcessor;

class CT_FieldTypeTag_records
{
    //New function
    public static function resolveRecordTypeValue(&$field, $layoutcode, $rowValue, array $options)
    {
        $db = Factory::getDBO();

        $ct = new CT;
        $ct->getTable($field->params[0]);

        $selector = $field->params[2];

        if (count($field->params) < 3)
            return 'selector not specified';

        $sortbyfield = '';
        if ($options[0] != '')
            $sortbyfield = $options[0];
        elseif (isset($field->params[5]))
            $sortbyfield = $field->params[5];

        $filter = $field->params[3] ?? '';

        //$showpublished = 0 - show published
        //$showpublished = 1 - show unpublished
        //$showpublished = 2 - show any
        $showpublished = (($field->params[6] ?? '') == '' ? 2 : ((int)($field->params[6] ?? 0) == 1 ? 0 : 1));

        //this is important because it has been selected somehow.
        $ct->setFilter($filter, $showpublished);

        $ct->Filter->where[] = 'INSTR(' . $db->quote($rowValue) . ',' . $ct->Table->realidfieldname . ')';
        $ct->getRecords();

        return CT_FieldTypeTag_records::processRecordRecords($ct, $layoutcode, $rowValue, $ct->Records);
    }

    protected static function processRecordRecords(CT &$ct, $layoutcode, $rowValue, &$records)
    {
        $valuearray = explode(',', $rowValue);

        $number = 1;

        //To make sure that records belong to the value
        $CleanSearchResult = array();
        foreach ($records as $row) {
            if (in_array($row[$ct->Table->realidfieldname], $valuearray))
                $CleanSearchResult[] = $row;
        }

        $result_count = count($CleanSearchResult);

        $htmlresult = '';

        foreach ($CleanSearchResult as $row) {
            $row['_number'] = $number;

            $twig = new TwigProcessor($ct, '{% autoescape false %}' . $layoutcode . '{% endautoescape %}');
            $htmlresult .= $twig->process($row);

            $number++;
        }

        return $htmlresult;
    }

    //Old function
    public static function resolveRecordType(CT &$ct, $rowValue, $field, array $options)
    {
        $sortbyfield = '';
        $result = '';

        if (count($field->params) < 1)
            $result .= 'table not specified';

        if (count($field->params) < 2)
            $result .= 'field or layout not specified';

        if (count($field->params) < 3)
            $result .= 'selector not specified';

        $esr_table = $field->params[0];

        if ($options[0] != '') {
            $esr_field = $options[0];

            if (isset($options[1])) {
                $sortbyfield = $options[1];
            }

        } else
            $esr_field = $field->params[1];

        $esr_selector = $field->params[2];

        if (count($field->params) > 3)
            $esr_filter = $field->params[3];
        else
            $esr_filter = '';

        if ($sortbyfield == '' and isset($field->params[5]))
            $sortbyfield = $field->params[5];

        //this is important because it has been selected somehow.
        $esr_filter = '';

        $result = JHTML::_('ESRecordsView.render', $rowValue, $esr_table, $esr_field, $esr_selector, $esr_filter, $sortbyfield);

        return $result;
    }
}
