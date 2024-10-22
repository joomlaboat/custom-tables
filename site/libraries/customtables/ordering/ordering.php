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
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

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
        $fList = CTMiscHelper::getListToReplace($tag, $options, $result, "<>", ' ');
        $i = 0;
        foreach ($fList as $fItem) {

            $params = CTMiscHelper::getHTMLTagParameters(strtolower($options[$i]));

            foreach ($paramsToAddEdit as $key => $value) {
                $params[$key] = $value;
            }

            $params_str = [];
            foreach ($params as $key => $value)
                $params_str[] = $key . '="' . htmlspecialchars($value ?? '') . '"';

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
        $params['data-url'] = common::UriRoot(true) . '/index.php?option=com_customtables&view=catalog&task=ordering&tableid=' . $tableid . '&tmpl=component&clean=1';
        $params['data-direction'] = 'asc';
        $params['data-nested'] = 'true';
        return self::addEditHTMLTagParams($result, 'tbody', $params);
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
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

    /**
     * @throws Exception
     * @since 3.2.2
     */
    function parseOrderByFieldName(string $fieldName, Table $Table): ?string
    {
        if ($fieldName == '_id')
            return $Table->realidfieldname;
        elseif ($fieldName == '_published' and $Table->published_field_found)
            return 'listing_published';

        $fieldRow = Fields::FieldRowByName($fieldName, $Table->fields);
        if ($fieldRow === null)
            return null;

        $temp_ct = new CT();
        $temp_ct->Table = $Table;
        $field = new Field($temp_ct, $fieldRow);

        if ($field->realfieldname == '')
            return null;

        switch ($field->type) {
            case 'user':
                return '(SELECT #__users.name FROM #__users WHERE #__users.id=' . $Table->realtablename . '.' . $field->realfieldname . ')';

            case 'sqljoin':

                $join_table = $field->params[0];
                $sqljoin_temp_ct = new CT();
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
                return $field->realfieldname;

            default:
                return $field->realfieldname;
        }
    }

    function parseOrderByParam(): void
    {
        if (defined('_JEXEC')) {
            //get sort field (and direction) example "price desc"
            $app = Factory::getApplication();
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
                } elseif (common::inputGetCmd('esordering', '')) {
                    $ordering_param_string = common::inputGetString('esordering', '');
                    $ordering_param_string = trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "", $ordering_param_string));
                } else {
                    $Itemid = common::inputGetInt('Itemid', 0);
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
        } else {
            if ($this->Params->sortBy != '')
                $this->ordering_processed_string = $this->Params->sortBy;
        }
    }

    function getSortByFields(): ?object
    {
        //default sort by fields
        $order_list = [];
        $order_values = [];

        $order_list[] = 'ID ' . common::translate('COM_CUSTOMTABLES_AZ');
        $order_list[] = 'ID ' . common::translate('COM_CUSTOMTABLES_ZA');

        $order_values[] = '_id';
        $order_values[] = '_id desc';

        $label = common::translate('COM_CUSTOMTABLES_PUBLISHED') . ' ';
        $order_list[] = $label . common::translate('COM_CUSTOMTABLES_AZ');
        $order_list[] = $label . common::translate('COM_CUSTOMTABLES_ZA');

        $order_values[] = '_published';
        $order_values[] = '_published desc';

        foreach ($this->Table->fields as $row) {
            if ($row['allowordering'] == 1) {

                $fieldType = $row['type'];
                $fieldname = $row['fieldname'];

                if ($row['fieldtitle' . $this->Table->Languages->Postfix] != '')
                    $fieldtitle = $row['fieldtitle' . $this->Table->Languages->Postfix];
                else
                    $fieldtitle = $row['fieldtitle'];

                $typeParams = $row['typeparams'];

                if ($fieldType == 'string' or $fieldType == 'email' or $fieldType == 'url') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'sqljoin') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeParams;
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.sqljoin.' . $typeParams . ' desc';
                } elseif ($fieldType == 'phponadd' or $fieldType == 'phponchange') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . ' desc';
                } elseif ($fieldType == 'int' or $fieldType == 'float' or $fieldType == 'ordering') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_MINMAX');
                    $order_values[] = $fieldname;
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_MAXMIN');
                    $order_values[] = $fieldname . " desc";
                } elseif ($fieldType == 'changetime' or $fieldType == 'creationtime' or $fieldType == 'date') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_NEWOLD');
                    $order_values[] = $fieldname . " desc";
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_OLDNEW');
                    $order_values[] = $fieldname;
                } elseif ($fieldType == 'multilangstring') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . $this->Table->Languages->Postfix;
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . $this->Table->Languages->Postfix . " desc";
                } elseif ($fieldType == 'userid' or $fieldType == 'user') {
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_AZ');
                    $order_values[] = $fieldname . '.user';
                    $order_list[] = $fieldtitle . ' ' . common::translate('COM_CUSTOMTABLES_ZA');
                    $order_values[] = $fieldname . '.user desc';
                }
            }
        }
        return (object)['titles' => $order_list, 'values' => $order_values];
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public function saveorder(): bool
    {
        // Get the input
        $pks = common::inputPost('cid', array(), 'array');
        $order = common::inputPost('order', array(), 'array');

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

        for ($i = 0; $i < count($pks); $i++) {

            $data = [
                $realFieldName => $order[$i]
            ];
            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition($this->Table->realidfieldname, (int)$pks[$i]);
            database::update($this->Table->realtablename, $data, $whereClauseUpdate);
        }
        return true;
    }
}
