<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\TableHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView;


class CustomTablesViewChatGPT extends HtmlView
{
    var $chatGPTAPIKey;

    function display($tpl = null)
    {
        $task = common::inputGetCmd('task');
        if (ob_get_contents())
            ob_clean();

        if ($task == 'chatgpt') {

            $joomla_params = ComponentHelper::getParams('com_customtables');
            $this->chatGPTAPIKey = $joomla_params->get('chatgptapikey');
            //$user_message = 'what is the most expensive color';

            $module_id = common::inputGetCmd('module');
            $tableName = $this->getModuleParamsTableName($module_id);

            $user_message = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get the raw POST data
                $rawData = file_get_contents('php://input');

                // Decode the JSON payload
                $data = json_decode($rawData, true);

                // Check if the message key exists in the decoded data
                if (isset($data['message']) and $data['message'] != '') {
                    $user_message = $data['message'];
                }
            } else {
                $user_message = common::inputGetString('message');
            }

            // Handle user request
            if ($user_message !== null) {


                // Generate the SQL query dynamically
                $sql_query = $this->generateSQLQuery($tableName, $user_message);


                // Execute the generated SQL query securely
                $data = $this->fetchFromDatabase($sql_query);

                $query_result = $this->generateAnswer($user_message, $sql_query, $data);

                echo json_encode(["message" => $query_result]);
                die;
            }
        }
    }

    protected function getModuleParamsTableName(int $module_id)
    {

        $example_query = 'SELECT params FROM `#__modules` WHERE id=' . $module_id;

        $db = database::getDB();
        $db->setQuery('SELECT params FROM `#__modules` WHERE id=' . $module_id);
        $result = $db->loadAssocList();
        if (count($result) == 0)
            return null;

        $params = json_decode($result[0]['params']);
        return $params->establename;
    }

    public function generateSQLQuery($tableName, $query)
    {
        $tableStructure = $this->getTableStructure($tableName);

        $fieldString = '';
        $i = 1;
        foreach ($tableStructure['Fields'] as $field) {
            $fieldString .= $i . '. ' . $field['FieldName'] . ' (' . $this->formatMySQLType($field['MySQLType']) . ') - ' . $field['Type'] . ';
';

            $i++;
        }

        // Format the table structure as part of the system message
        $systemContent = "You are a helpful assistant who generates SQL queries. Your responses should only include the SQL query with no additional explanation or text. The table structure is as follows: " .

            "Table Name: " . $tableStructure['tableName'] . ". Fields: 
" . $fieldString . "

Data Example in JSON: " . $tableStructure['Example'];
        $messages = [
            ["role" => "system", "content" => $systemContent],
            ["role" => "user", "content" => $query]
        ];

        return $this->getResponse($messages);
    }

    public function getTableStructure(string $tableName): array
    {
        $ct = new CT();

        $tableRow = TableHelper::getTableRowByNameAssoc($tableName);
        if (!is_array($tableRow) and $tableRow == 0) {
            common::enqueueMessage('Table not found', 'error');
        } else {
            $ct->setTable($tableRow, null, false);
        }

        $fields = [];
        $fields[] = ["FieldName" => 'id', "Type" => 'PRIMARY KEY', "MySQLType" => Fields::getProjectedFieldType('_id', null)];


        foreach ($ct->Table->fields as $field) {
            $mySQLType = Fields::getProjectedFieldType($field['type'], $field['typeparams']);
            $fields[] = ["FieldName" => $field['realfieldname'], "Type" => $field['type'], "MySQLType" => $mySQLType];
        }

        $example_query = 'SELECT * FROM `' . $ct->Table->realtablename . '` LIMIT 3';

        $db = database::getDB();
        $db->setQuery($example_query);
        $result = $db->loadAssocList();

        return [
            "tableName" => $ct->Table->realtablename,
            "Fields" => $fields,
            "Example" => json_encode($result)
        ];
    }

    protected function formatMySQLType($mysqlType)
    {
        $type = $mysqlType['data_type'];
        $length = isset($mysqlType['length']) ? "length " . $mysqlType['length'] : "";
        $nullable = isset($mysqlType['is_nullable']) && $mysqlType['is_nullable'] ? "nullable" : "";
        $unsigned = isset($mysqlType['is_unsigned']) && $mysqlType['is_unsigned'] ? "unsigned" : "";

        // Build the formatted string
        $parts = array_filter([$type, $length, $nullable, $unsigned]);
        return implode(", ", $parts);
    }

    public function getResponse($messages)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4',
            'messages' => $messages,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->chatGPTAPIKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    protected function fetchFromDatabase($sql_query)
    {
        // Ensure the query starts with "SELECT" and does not contain any harmful keywords
        $allowed_keywords = ['select', 'from', 'where', 'join', 'order by', 'group by', 'having', 'limit', 'offset'];
        $disallowed_keywords = ['update', 'delete', 'insert', 'drop', 'alter'];

        $sql_query_lower = strtolower($sql_query);
        $first_word = strtok($sql_query_lower, ' ');

        if ($first_word !== 'select') {
            return "Only SELECT queries are allowed.";
        }

        foreach ($disallowed_keywords as $keyword) {
            if (strpos($sql_query_lower, $keyword) !== false) {
                return "Invalid query.";
            }
        }

        $db = database::getDB();
        $db->setQuery($sql_query);
        $result = $db->loadAssocList();

        return json_encode($result);

    }

    public function generateAnswer($user_message, $query, $data)
    {
        // Your responses should only include the answer "
        //    . "with no additional explanation or text.
        // Format the table structure as part of the system message
        $systemContent = "
        You are a helpful assistant who generates answers based on JSON data captured by query (" . $query . "). The JSON data provided is: " . $data;

        $messages = [
            ["role" => "system", "content" => $systemContent],
            ["role" => "user", "content" => $user_message]
        ];

        return $this->getResponse($messages);
    }
}