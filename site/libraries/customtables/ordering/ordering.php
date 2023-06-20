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

use Joomla\CMS\Factory;
use JoomlaBasicMisc;
use Joomla\Utilities\ArrayHelper;
use JRegistry;

class Ordering
{
    var ?Table $Table = null;
    var ?Params $Params = null;
    var ?string $selects = null;
    var ?string $orderby = null;
    var ?string $ordering_processed_string = null;
    private int $index;
    private ?array $fieldList;

    function __construct($Table, $Params)
    {
        $this->Table = $Table;
        $this->Params = $Params;
        $this->index = -1;
        $this->fieldList = null;
    }

    public static function addTableTagID(string $result, int $tableid): string
    {
        $params = array();
        $params['id'] = 'ctTable_' . $tableid;

        return self::addEditHTMLTagParams($result, 'table', $params);
    }

    public static function addEditHTMLTagParams(string $result, string $tag, array $paramsToAddEdit): string
    {
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace($tag, $options, $result, "<>", ' ', '"');
        $i = 0;
        foreach ($fList as $fItem) {

            $params = JoomlaBasicMisc::getHTMLTagParameters(strtolower($options[$i]));

            foreach ($paramsToAddEdit as $key => $value) {
                $params[$key] = $value;
            }

            $params_str = [];
            foreach ($params as $key => $value)
                $params_str[] = $key . '="' . htmlspecialchars($value) . '"';

            $val = '<' . $tag . ' ' . implode(' ', $params_str) . '>';
            $result = str_replace($fItem, $val, $result);

            $i++;
        }
        return $result;
    }

    public static function addTableBodyTagParams(string $result, int $tableid): string
    {
        $params = array();
        $params['class'] = 'js-draggable';
        $params['data-url'] = '/index.php?option=com_customtables&view=catalog&task=ordering&tableid=' . $tableid . '&tmpl=component&clean=1';
        $params['data-direction'] = 'asc';
        $params['data-nested'] = 'true';

        return self::addEditHTMLTagParams($result, 'tbody', $params);
    }

    function parseOrderByString(): bool
    {
        if ($this->ordering_processed_string === null or $this->ordering_processed_string == '')
            return false;

        $orderingStringPair = explode(' ', $this->ordering_processed_string);

        $direction = '';
        if (isset($orderingStringPair[1])) {
            $direction = (strtolower($orderingStringPair[1]) == 'desc' ? ' DESC' : '');
        }

        $this->fieldList = explode('.', $orderingStringPair[0]);

        $this->index = 0;

        $orderbyQuery = self::parseOrderByFieldName($this->fieldList[$this->index], $this->Table);

        if ($orderbyQuery === null)
            return false;

        $this->orderby = $orderbyQuery . $direction;

        return true;
    }

    function parseOrderByFieldName(string $fieldName, Table $Table): ?string
    {
        if ($fieldName == '_id')
            return $Table->realidfieldname;
        elseif ($fieldName == '_published' and $Table->published_field_found)
            return 'published';

        $fieldRow = Fields::FieldRowByName($fieldName, $Table->fields);
        if ($fieldRow === null)
            return null;

        $params = new JRegistry;
        $params->loadArray([]);
        $temp_ct = new CT($params, true);
        $temp_ct->Table = $Table;
        $field = new Field($temp_ct, $fieldRow);

        if ($field->realfieldname == '')
            return null;

        switch ($field->type) {
            case 'user':
                return '(SELECT #__users.name FROM #__users WHERE #__users.id=' . $Table->realtablename . '.' . $field->realfieldname . ')';

            case 'customtables':
                return '(SELECT #__customtables_options.title FROM #__customtables_options WHERE #__customtables_options.familytreestr=' . $field->realfieldname . ')';

            case 'sqljoin':

                $join_table = $field->params[0];

                $params = new JRegistry;
                $params->loadArray([]);
                $sqljoin_temp_ct = new CT($params, true);
                $sqljoin_temp_ct->getTable($join_table);

                if ($this->index == count($this->fieldList) - 1) {

                    $join_field = '';
                    if (isset($field->params[1]))
                        $join_field = $field->params[1];

                    $select = self::parseOrderByFieldName($join_field, $sqljoin_temp_ct->Table);
                    return '(SELECT ' . $select . ' FROM ' . $sqljoin_temp_ct->Table->realtablename
                        . ' WHERE ' . $sqljoin_temp_ct->Table->realtablename . '.' . $sqljoin_temp_ct->Table->realidfieldname . '=' . $Table->realtablename . '.' . $field->realfieldname . ')';
                } else {
                    $join_field = $this->fieldList[$this->index + 1];
                    $this->index += 1;
                }

                $select = self::parseOrderByFieldName($join_field, $sqljoin_temp_ct->Table);
                return '(SELECT ' . $select . ' FROM ' . $sqljoin_temp_ct->Table->realtablename
                    . ' WHERE ' . $sqljoin_temp_ct->Table->realtablename . '.' . $sqljoin_temp_ct->Table->realidfieldname . '=' . $Table->realtablename . '.' . $field->realfieldname . ')';

            case 'date':
            case 'creationtime':
            case 'changetime':
            case 'lastviewtime':

                if (count($field->params) > 0 and $field->params[0] != '') {
                    return 'DATE_FORMAT(' . $field->realfieldname . ', ' . $Table->db->quote($field->params[0]) . ')';
                } else
                    return $field->realfieldname;

            default:
                return $field->realfieldname;
        }
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
                    if ($this->Params->sortBy !== null and $this->Params->sortBy != '')
                        $ordering_param_string = $this->Params->sortBy;
                }
            }
        }

        $this->ordering_processed_string = $ordering_param_string;

        //set state
        if (!$this->Params->blockExternalVars)
            $app->setUserState('com_customtables.esorderby', $this->ordering_processed_string);
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

                $typeParams = $row['typeparams'];

                if ($fieldType == 'string' or $fieldType == 'email' or $fieldType == 'url') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'sqljoin') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeParams;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeParams . ' desc';
                } elseif ($fieldType == 'phponadd' or $fieldType == 'phponchange') {
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'int' or $fieldType == 'float' or $fieldType == 'ordering') {
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
