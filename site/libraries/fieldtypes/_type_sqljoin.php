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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\TwigProcessor;

class CT_FieldTypeTag_sqljoin
{
    //New function
    public static function resolveSQLJoinTypeValue(&$field, $layoutcode, $listing_id, array $options): string
    {
        $ct = new CT;
        $ct->getTable($field->params[0]);

        //TODO: add selector to the output box
        $selector = $field->params[6] ?? 'dropdown';

        $row = $ct->Table->loadRecord($listing_id);

        $twig = new TwigProcessor($ct, $layoutcode);
        return $twig->process($row);
    }

    //Old function
    public static function resolveSQLJoinType($listing_id, $typeparams, $option_list): string
    {
        if ($listing_id == '')
            return '';

        if (count($typeparams) < 1)
            return 'table not specified';

        if (count($typeparams) < 2)
            return 'field or layout not specified';

        $esr_table = $typeparams[0];

        if (isset($option_list[0]) and $option_list[0] != '')
            $esr_field = $option_list[0];
        else
            $esr_field = $typeparams[1];

        //this is important because it has been selected somehow.
        //$esr_filter='';

        if (count($typeparams) > 2)
            $esr_filter = $typeparams[2];
        else
            $esr_filter = '';

        //Old method - slow
        $result = JHTML::_('ESSQLJoinView.render', $listing_id, $esr_table, $esr_field, $esr_filter);

        //New method - fast and secure
        $join_ct = new CT;
        $join_ct->getTable($typeparams[0]);

        $row = $join_ct->Table->loadRecord($listing_id);

        $twig = new TwigProcessor($join_ct, $result);
        return $twig->process($row);
    }
}
