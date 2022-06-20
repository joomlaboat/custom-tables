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
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;
use JoomlaBasicMisc;
use ESTables;
use Joomla\Utilities\ArrayHelper;

class Ordering
{
    var ?Table $Table = null;
    var ?Params $Params = null;
    var ?string $inner = null;
    var ?string $selects = null;
    var ?string $orderby = null;

    var ?string $ordering_processed_string = null;

    function __construct($Table, $Params)
    {
        $this->Table = $Table;
        $this->Params = $Params;
    }

    function parseOrderByString(): bool
    {
        //Order by string examples:
        //name desc
        //_id
        //client.user desc
        //birthdate:%m%d DESC converted to DATE_FORMAT(realfieldname,"%m%d") DESC;

        if (str_contains($this->ordering_processed_string, "DATE_FORMAT")) {
            $this->orderby = $this->ordering_processed_string;
            return true;
        }

        $inners = [];

        $oPair = explode(' ', $this->ordering_processed_string);
        $oPair2 = explode('.', $oPair[0]);
        $orderby_field = $oPair2[0];
        $subtype = '';
        if (isset($oPair2[1]) and $oPair2[1] != '')
            $subtype = $oPair2[1];

        $direction = '';
        if (isset($oPair[1])) {
            $direction = strtolower($oPair[1]);
            $direction = ($direction == 'desc' ? ' DESC' : '');
        }

        if ($orderby_field == '_id') {
            $this->orderby = 'id' . $direction;
            return true;
        } elseif ($orderby_field == '_published') {
            $this->orderby = 'published' . $direction;
            return true;
        }

        $realfieldname = Fields::getRealFieldName($orderby_field, $this->Table->fields, true);

        if ($realfieldname == '')
            return false;

        switch ($subtype) {
            case 'user':
                $inners[] = 'LEFT JOIN #__users ON #__users.id=' . $this->Table->realtablename . '.' . $realfieldname;
                $this->selects = 'name AS t1';
                $this->orderby = '#__users.name' . $direction;
                break;

            case 'customtables':
                $inners[] = 'LEFT JOIN #__customtables_options ON familytreestr=' . $realfieldname;
                $this->selects = '#__customtables_options.title' . $this->Table->Languages->Postfix . ' AS t1';
                $this->orderby = 'title' . $this->Table->Languages->Postfix . $direction;
                break;

            case 'sqljoin':

                if (isset($oPair2[2])) {
                    $typeparams = explode(',', $oPair2[2]);
                    $join_table = $typeparams[0];
                    $join_field = '';
                    if (isset($typeparams[1]))
                        $join_field = $typeparams[1];

                    if ($join_table != '' and $join_field != '') {
                        $real_joined_fieldname = $join_field;

                        $join_table_row = ESTables::getTableRowByName($join_table);

                        $w = $join_table_row->realtablename . '.id=' . $this->Table->realtablename . '.' . $realfieldname;
                        $this->orderby = '(SELECT ' . $join_table_row->realtablename . '.es_' . $real_joined_fieldname . ' FROM ' . $join_table_row->realtablename . ' WHERE ' . $w . ') ' . $direction;
                    }
                }
                break;

            default:
                $this->orderby = $realfieldname . $direction;
                break;
        }

        if (count($inners) > 0)
            $this->inner = implode(' ', $inners);

        return true;
    }

    function parseOrderByParam(): void
    {
        //get sort field (and direction) example "price desc"
        $app = Factory::getApplication();
        $jinput = $app->input;

        $ordering_param_string = '';

        if ($this->Params->blockExternalVars) {
            //module or plugin
            if ($this->Params->forceSortBy != '')
                $ordering_param_string = $this->Params->forceSortBy;
            elseif ($this->Params->sortBy != '')
                $ordering_param_string = $this->Params->sortBy;
        } else {
            if ($this->Params->forceSortBy != '') {
                $ordering_param_string = $this->Params->forceSortBy;
            } elseif ($jinput->get('esordering', '', 'CMD')) {
                $ordering_param_string = $jinput->getString('esordering', '');
                $ordering_param_string = trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $ordering_param_string));
            } else {
                $Itemid = $jinput->getInt('Itemid', 0);
                $ordering_param_string = $app->getUserState('com_customtables.orderby_' . $Itemid, '');

                if ($ordering_param_string == '') {
                    if ($this->Params->sortBy != '')
                        $ordering_param_string = $this->Params->sortBy;
                }
            }
        }

        $this->ordering_processed_string = $this->processOrderingString($ordering_param_string);

        //set state
        if (!$this->Params->blockExternalVars) {
            //component
            $app->setUserState('com_customtables.esorderby', $this->ordering_processed_string);
        }
    }

    protected function processOrderingString($ordering_param_string): ?string
    {
        if ($ordering_param_string == '')
            return null;

        if (is_null($this->Table->fields))
            return null;

        $db = Factory::getDBO();

        $this->ordering_processed_string = '';

        // Check if field exist
        $parts = explode(':', $ordering_param_string);

        $orderingTempArray = explode(' ', $parts[0]);

        $orderingTempArrayPair = explode('.', $orderingTempArray[0]);
        $fieldname = $orderingTempArrayPair[0];
        $desc = '';
        if (isset($orderingTempArray[1]) and $orderingTempArray[1] == 'desc')
            $desc = ' desc';

        if ($fieldname == '_id' or $fieldname == '_published')
            return $fieldname . $desc;

        $order_params = '';
        if (isset($parts[1]))
            $order_params = trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $parts[1]));

        foreach ($this->Table->fields as $row) {
            if ($row['fieldname'] == $fieldname) {
                $fieldType = $row['type'];
                $typeparams = $row['typeparams'];

                if ($fieldType == 'sqljoin')
                    return $fieldname . '.sqljoin.' . $typeparams . $desc;
                elseif ($fieldType == 'customtables')
                    return $fieldname . '.customtables.' . $desc;
                elseif ($fieldType == 'userid' or $fieldType == 'user')
                    return $fieldname . '.user.' . $desc;
                elseif ($fieldType == 'date' or $fieldType == 'creationtime' or $fieldType == 'changetime' or $fieldType == 'lastviewtime') {
                    if ($order_params != '') {
                        return 'DATE_FORMAT(' . $row['realfieldname'] . ', ' . $db->quote($order_params) . ')' . $desc;
                    } else
                        return $fieldname . $desc;
                } elseif ($fieldType !== 'dummy')
                    return $fieldname . $desc;
            }
        }

        return null;
    }

    function getSortByFields(): ?object
    {
        //default sort by fields
        $order_list = [];
        $order_values = [];

        $order_list[] = 'ID ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
        $order_list[] = 'ID ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');

        $order_values[] = '_id';
        $order_values[] = '_id desc';

        $label = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED') . ' ';
        $order_list[] = $label . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
        $order_list[] = $label . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');

        $order_values[] = '_published';
        $order_values[] = '_published desc';

        foreach ($this->Table->fields as $row) {
            if ($row['allowordering'] == 1) {

                /*
                if (!array_key_exists($row['fieldtitle' . $this->Table->Languages->Postfix])) {

                    Factory::getApplication()->enqueueMessage('1:' .
                        JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND'), 'Error');
                    return null;
                }
                */

                $fieldType = $row['type'];
                $fieldname = $row['fieldname'];

                if ($row['fieldtitle' . $this->Table->Languages->Postfix] != '')
                    $fieldtitle = $row['fieldtitle' . $this->Table->Languages->Postfix];
                else
                    $fieldtitle = $row['fieldtitle'];

                $typeparams = $row['typeparams'];

                if ($fieldType == 'string' or $fieldType == 'email' or $fieldType == 'url') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'sqljoin') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeparams;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeparams . ' desc';
                } elseif ($fieldType == 'phponadd' or $fieldType == 'phponchange') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'int' or $fieldType == 'float') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MINMAX');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_MAXMIN');
                    $order_values[] = $fieldname . " desc";
                } elseif ($fieldType == 'changetime' or $fieldType == 'creationtime' or $fieldType == 'date') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NEWOLD');
                    $order_values[] = $fieldname . " desc";
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OLDNEW');
                    $order_values[] = $fieldname;
                } elseif ($fieldType == 'multilangstring') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . $this->Table->Languages->Postfix;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . $this->Table->Languages->Postfix . " desc";
                } elseif ($fieldType == 'customtables') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.customtables';
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.customtables desc';
                } elseif ($fieldType == 'userid' or $fieldType == 'user') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.user';
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.user desc';
                }
            }
        }
        return (object)['titles' => $order_list, 'values' => $order_values];
    }

    public function saveorder(): bool
    {
        // Get the input
        $pks = $this->Table->Env->jinput->post->get('cid', array(), 'array');
        $order = $this->Table->Env->jinput->post->get('order', array(), 'array');

        // Sanitize the input
        $pks = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);

        $realFieldName = '';

        foreach ($this->Table->fields as $field) {
            if ($field['type'] == 'ordering') {
                $realFieldName = $field['realfieldname'];
                break;
            }
        }

        if ($realFieldName == '')
            return false;

        $db = Factory::getDBO();

        for ($i = 0; $i < count($pks); $i++) {
            $query = 'UPDATE ' . $this->Table->realtablename . ' SET ' . $db->quoteName($realFieldName) . '=' . $order[$i] . ' WHERE '
                . $db->quoteName($this->Table->realidfieldname) . '=' . (int)$pks[$i];

            $db->setQuery($query);
            $db->execute();
        }

        return true;
    }
}
