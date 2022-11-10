<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\TwigProcessor;

trait render_html
{
    protected static function get_CatalogTable_HTML(CT &$ct, $layoutType, $fields, $class, $dragdrop = false)
    {
        //for reload single record functionality
        $listing_id = $ct->Env->jinput->getCmd("listing_id", '');
        $custom_number = $ct->Env->jinput->getInt('number', 0);
        $start = $ct->Env->jinput->getInt('start', 0); //pagination
        // end of for reload single record functionality

        $catalogresult = '';

        $fields = str_replace("\n", '', $fields);
        $fields = str_replace("\r", '', $fields);

        $fieldarray = JoomlaBasicMisc::csv_explode(',', $fields, '"', true);

        //prepare header and record layouts
        $result = '
		<table id="ctTable_' . $ct->Table->tableid . '" ' . ($class != '' ? ' class="' . $class . '" ' : '') . ' style="position: relative;"><thead><tr>';

        $recordLine = '<tr id="ctTable_' . $ct->Table->tableid . '_{{ record.id }}">';

        foreach ($fieldarray as $field) {
            $fieldpair = JoomlaBasicMisc::csv_explode(':', $field, '"', false);

            if (isset($fieldpair[2]) and $fieldpair[2] != '')
                $result .= '<th ' . $fieldpair[2] . '>' . $fieldpair[0] . '</th>';//header
            else
                $result .= '<th>' . $fieldpair[0] . '</th>';//header

            if (!isset($fieldpair[1])) {
                $recordLine .= '<td>Catalog Layout Content field corrupted. Check the Layout.</td>';//content
            } else {
                $attribute = '';
                if ($dragdrop) {
                    $fields_found = tagProcessor_CatalogTableView::checkIfColumnIsASingleField($ct, $fieldpair[1]);

                    if (count($fields_found) == 1)
                        $attribute = ' id="ctTable_' . $ct->Table->tableid . '_{{ record.id }}_' . $fields_found[0] . '" draggable="true" '
                            . 'ondragstart="ctCatalogOnDragStart(event);" ondragover="ctCatalogOnDragOver(event);" ondrop="ctCatalogOnDrop(event);"';
                }

                $recordLine .= '<td' . $attribute . '>' . $fieldpair[1] . '</td>';//content
            }
        }
        $result .= '</tr></thead>';

        $LayoutProc = new LayoutProcessor($ct);

        //Parse Header
        if ($listing_id == '') {
            $LayoutProc->layout = $result;
            $result = $LayoutProc->fillLayout();
            $result = str_replace('&&&&quote&&&&', '"', $result);

            $twig = new TwigProcessor($ct, $result);
            $result = $htmlresult = $twig->process();
        }

        //Complete record layout
        $recordLine .= '</tr>';
        $recordLine = str_replace('|(', '{', $recordLine);//to support old parsing way
        $recordLine = str_replace(')|', '}', $recordLine);//to support old parsing way
        $recordLine = str_replace('&&&&quote&&&&', '"', $recordLine);

        $number = 1 + $ct->LimitStart; //table row number, it maybe uses in the layout as {number}

        $tableContent = '';

        $twig = new TwigProcessor($ct, $recordLine);

        foreach ($ct->Records as $row) {
            $row['_number'] = ($custom_number > 0 ? $custom_number : $number);
            $tableContent .= tagProcessor_Item::RenderResultLine($ct, $layoutType, $twig, $row);//TODO

            $number++;
        }

        if ($listing_id != '')
            die($tableContent);

        $result .= '<tbody>' . $tableContent . '</tbody></table>';
        return $result;
    }

    protected static function checkIfColumnIsASingleField(CT &$ct, $htmlresult)
    {
        $fieldsFound = [];
        foreach ($ct->Table->fields as $field) {
            $options = array();
            $fList = JoomlaBasicMisc::getListToReplace($field['fieldname'], $options, $htmlresult, '[]', ':', '"');
            if (count($fList) > 0)
                $fieldsFound[] = $field['fieldname'];
        }
        return $fieldsFound;
    }
}
