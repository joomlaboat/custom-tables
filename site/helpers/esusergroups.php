<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
use CustomTables\database;
use Joomla\CMS\Factory;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLESUserGroups
{
    static public function render($control_name, $value, array $typeParamsArray): string
    {
        $selector = $typeParamsArray[0];
        $availableUserGroups = $typeParamsArray[1] ?? '';
        $availableUserGroupList = (trim($availableUserGroups) == '' ? [] : explode(',', trim($availableUserGroups)));
        $htmlresult = '';

        $db = Factory::getDBO();

        $query = $db->getQuery(true);
        $query->select('#__usergroups.id AS id, #__usergroups.title AS name');
        $query->from('#__usergroups');

        if (count($availableUserGroupList) == 0) {
            $query->where('#__usergroups.title!=' . $db->quote('Super Users'));
        } else {
            $where = [];
            foreach ($availableUserGroupList as $availableUserGroup) {
                if ($availableUserGroup != '')
                    $where[] = '#__usergroups.title=' . $db->quote($availableUserGroup);
            }
            $query->where(implode(' OR ', $where));
        }

        $query->order('#__usergroups.title');
        $records = database::loadObjectList((string)$query);
        $valueArray = explode(',', $value);

        switch ($selector) {
            case 'single' :
                $htmlresult = JHTMLESUserGroups::getSingle($records, $control_name, $valueArray);
                break;

            case 'multi' :
                $htmlresult .= '<SELECT name="' . $control_name . '[]" id="' . $control_name . '" data-type="usergroups" MULTIPLE >';
                foreach ($records as $row) {
                    $htmlresult .= '<option value="' . $row->id . '" '
                        . ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' SELECTED ' : '')
                        . '>' . htmlspecialchars($row->name ?? '') . '</option>';
                }

                $htmlresult .= '</SELECT>';
                break;

            case 'radio' :
                $htmlresult .= '<table style="border:none;" id="usergroups_table_' . $control_name . '">';
                $i = 0;
                foreach ($records as $row) {
                    $htmlresult .= '<tr><td style="vertical-align: middle">'
                        . '<input type="radio" '
                        . 'name="' . $control_name . '" '
                        . 'id="' . $control_name . '_' . $i . '" '
                        . 'value="' . $row->id . '" '
                        . 'data-type="usergroups" '
                        . ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
                        . ' /></td>'
                        . '<td style="vertical-align: middle">'
                        . '<label for="' . $control_name . '_' . $i . '">' . $row->name . '</label>'
                        . '</td></tr>';
                    $i++;
                }
                $htmlresult .= '</table>';
                break;

            case 'checkbox' :
                $htmlresult .= '<table style="border:none;">';
                $i = 0;
                foreach ($records as $row) {
                    $htmlresult .= '<tr><td style="vertical-align: middle">'
                        . '<input type="checkbox" '
                        . 'name="' . $control_name . '[]" '
                        . 'id="' . $control_name . '_' . $i . '" '
                        . 'value="' . $row->id . '" '
                        . 'data-type="usergroups" '
                        . ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' checked="checked" ' : '')
                        . ' /></td>'
                        . '<td style="vertical-align: middle">'
                        . '<label for="' . $control_name . '_' . $i . '">' . $row->name . '</label>'
                        . '</td></tr>';
                    $i++;
                }
                $htmlresult .= '</table>';
                break;

            case 'multibox' :
                $htmlresult .= JHTMLESUserGroups::getMultibox($records, $valueArray, $control_name);
                break;

            default:
                return '<p>Incorrect selector</p>';
        }
        return $htmlresult;
    }

    static protected function getSingle($records, $control_name, $valueArray): string
    {
        $htmlresult = '<SELECT name="' . $control_name . '[]" id="' . $control_name . '" data-type="usergroups">';

        foreach ($records as $row) {
            $htmlresult .= '<option value="' . $row->id . '" '
                . ((in_array($row->id, $valueArray) and count($valueArray) > 0) ? ' SELECTED ' : '')
                . '>' . htmlspecialchars($row->name ?? '') . '</option>';
        }

        $htmlresult .= '</SELECT>';
        return $htmlresult;
    }

    static protected function getMultibox($records, $valueArray, $control_name): string
    {
        $ctInputBoxRecords_r = [];
        $ctInputBoxRecords_v = [];
        $ctInputBoxRecords_p = [];

        foreach ((array)$records as $rec) {
            $row = (array)$rec;
            if (in_array($row['id'], $valueArray) and count($valueArray) > 0) {
                $ctInputBoxRecords_r[] = $row['id'];
                $ctInputBoxRecords_v[] = $row['name'];
                $ctInputBoxRecords_p[] = 1;
            }
        }

        $htmlresult = '
		<script>
			ctInputBoxRecords_r["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_r) . ';
			ctInputBoxRecords_v["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_v) . ';
			ctInputBoxRecords_p["' . $control_name . '"] = ' . json_encode($ctInputBoxRecords_p) . ';
		</script>
		';

        $single_box = JHTMLESUserGroups::getSingle($records, $control_name . '_selector', $valueArray);

        $htmlresult .= '<div style="padding-bottom:20px;"><div style="width:90%;" id="' . $control_name . '_box"></div>'
            . '<div style="height:30px;">'
            . '<div id="' . $control_name . '_addButton" style="visibility:visible;"><img src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="Add" title="Add" style="cursor: pointer;" '
            . 'onClick="ctInputBoxRecords_addItem(\'' . $control_name . '\',\'_selector\')" /></div>'
            . '<div id="' . $control_name . '_addBox" style="visibility:hidden;">'
            . '<div style="float:left;">' . $single_box . '</div>'
            . '<img src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/plus.png" alt="Add" title="Add" '
            . 'style="cursor: pointer;float:left;margin-top:8px;margin-left:3px;" onClick="ctInputBoxRecords_DoAddItem(\'' . $control_name . '\',\'_selector\')" />'
            . '<img src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/images/icons/cancel.png" alt="Cancel" title="Cancel" style="cursor: pointer;float:left;margin-top:6px;margin-left:10px;" '
            . 'onClick="ctInputBoxRecords_cancel(\'' . $control_name . '\')" />'

            . '</div>'
            . '</div>'
            . '<div style="display:none;"><select name="' . $control_name . '[]" id="' . $control_name . '" MULTIPLE ></select></div>'
            . '</div>
		
		<script>
			ctInputBoxRecords_showMultibox("' . $control_name . '","");
		</script>
		';
        return $htmlresult;
    }
}
