<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewChatGPT extends HtmlView
{
	var string $chatGPTAPIKey;

	var array $savedMessages;
	var array $messages;
	var array $tables;

	function display($tpl = null)
	{
		$task = common::inputGetCmd('task');
		if (ob_get_contents())
			ob_clean();

		if ($task == 'chatgpt') {

			//Load messages
			$session = Factory::getSession();
			$sessionId = $session->getId();

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('published', 1);
			$whereClause->addCondition('es_SessionID', $sessionId);

			$this->savedMessages = database::loadAssocList('#__customtables_table_ctchatgpt', ['id', 'es_Role', 'es_Content'], $whereClause);
			$this->convertCTRecordsToMessages($this->savedMessages);

			$joomla_params = ComponentHelper::getParams('com_customtables');
			$this->chatGPTAPIKey = $joomla_params->get('chatgptapikey');

			$module_id = common::inputGetCmd('module');
			$this->getModuleParamsAndTables($module_id);

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
				$sql_query = $this->generateSQLQuery($user_message);

				$isSQLQuery = preg_match('/\b(SELECT)\b/i', $sql_query);
				if ($isSQLQuery) {
					// Execute the generated SQL query securely
					$queryData = $this->fetchFromDatabase($sql_query);
					$query_result = $this->generateAnswer($user_message, $sql_query, $queryData);
				} else {
					$query_result = $sql_query;
				}

				echo json_encode(["message" => $query_result]);
				die;
			}
		}
	}

	protected function convertCTRecordsToMessages($messages)
	{
		$this->messages = [];
		foreach ($messages as $message) {
			$new_message = ['role' => $message['es_Role'], 'content' => $message['es_Content']];
			$this->messages[] = $new_message;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.3.8
	 */
	protected function getModuleParamsAndTables(int $module_id)
	{
		$db = database::getDB();
		$db->setQuery('SELECT params FROM `#__modules` WHERE id=' . $module_id);
		$result = $db->loadAssocList();
		if (count($result) == 0)
			return null;

		$params = json_decode($result[0]['params']);

		if (($params->tablename ?? '') !== '')
			$this->tables[] = ['table' => $params->tablename, 'filter' => $params->filter ?? ''];

		if (($params->tablename2 ?? '') !== '')
			$this->tables[] = ['table' => $params->tablename2, 'filter2' => $params->filter ?? ''];

		if (($params->tablename3 ?? '') !== '')
			$this->tables[] = ['table' => $params->establename3, 'filter3' => $params->filter ?? ''];

		if (($params->establename4 ?? '') !== '')
			$this->tables[] = ['table' => $params->establename4, 'filter4' => $params->filter ?? ''];

		if (($params->tablename5 ?? '') !== '')
			$this->tables[] = ['table' => $params->establename5, 'filter5' => $params->filter ?? ''];

		if (($params->knowledge ?? '') !== '')
			$this->saveMessage(["role" => "system", "content" => $params->knowledge]);
	}

	/**
	 * @throws Exception
	 * @since 3.3.8
	 */
	function saveMessage(array $message, bool $saveToDataBase = true): ?string
	{
		//Check if the message is already exists
		$found = false;
		foreach ($this->messages as $existingMessage) {
			if ($message['role'] !== 'user' and $message['role'] !== 'assistant' and $existingMessage['role'] == $message['role'] and $existingMessage['content'] == $message['content']) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->messages[] = $message;

			if ($saveToDataBase) {

				$session = Factory::getSession();
				$sessionId = $session->getId();
				$data = ['es_SessionID' => $sessionId,
					'es_Role' => $message['role'],
					'es_Content' => $message['content']];

				$listing_id = database::insert('#__customtables_table_ctchatgpt', $data);
				$data['id'] = $listing_id;
				$this->savedMessages[] = $data;

				$this->unpublishOldMessages();

				return $listing_id;
			} else
				return null;
		}
		return null;
	}

	protected function unpublishOldMessages()
	{
		while (count($this->savedMessages) > 10) {

			$removedElement = array_shift($this->savedMessages);
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('id', $removedElement['id']);
			$data = ['published' => 0];
			database::update('#__customtables_table_ctchatgpt', $data, $whereClause);
		}
	}

	/**
	 * @throws Exception
	 * @since 3.3.8
	 */
	public function generateSQLQuery($query)
	{
		// Define the system messages
		$this->saveMessage(["role" => "system", "content" => "You are a helpful assistant who generates SQL queries or gives direct answers if a query is not needed."
			. " Your responses should only include the SQL query or direct answer with no additional explanation."], false);

		foreach ($this->tables as $table) {
			$tableStructure = $this->getTableStructure($table);
			if ($tableStructure !== null) {
				$this->saveMessage([
					"role" => "system",
					"content" => "Table Name: " . $tableStructure['tableName'] . "."
						. " Fields: " . $this->convertFieldNameSet($tableStructure['Fields']) . "
"
						. " Mandatory Where Conditions: " . $tableStructure['Where'] . "
"
						. " Data Example in JSON: " . $tableStructure['Example'] . "
."
				]);
			}
		}

		$this->saveMessage(["role" => "user", "content" => $query]);

		$response = $this->getResponse($this->messages);

		$isSQLQuery = preg_match('/\b(SELECT)\b/i', $response);

		$this->saveMessage(["role" => "assistant", "content" => $response], true);// !$isSQLQuery);
		return $response;
	}

	/**
	 * @throws Exception
	 * @since 3.3.8
	 */
	public function getTableStructure(array $table): ?array
	{
		$ct = new CT([], true);

		$ct->getTable($table['table']);
		if ($ct->Table === null) {
			common::enqueueMessage('Table not found');
			return null;
		}

		$ct->setFilter($table['filter']);

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
			"Example" => json_encode($result),
			"Where" => $ct->Filter->whereClause
		];
	}

	protected function convertFieldNameSet(array $fields): string
	{
		$fieldString = '';
		$i = 1;
		foreach ($fields as $field) {
			$fieldString .= $i . '. ' . $field['FieldName'] . ' (' . $this->formatMySQLType($field['MySQLType']) . ') - ' . $field['Type'] . ';
';

			$i++;
		}

		return $fieldString;
	}

	protected function formatMySQLType($mysqlType): string
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
		$disallowed_keywords = ['update', 'delete', 'insert', 'drop', 'alter', 'create', 'grant'];

		$sql_query_lower = strtolower($sql_query);
		$first_word = strtok($sql_query_lower, ' ');

		if ($first_word !== 'select') {
			return json_encode(['error' => "Only SELECT queries are allowed."]);
		}

		foreach ($disallowed_keywords as $keyword) {
			if (strpos($sql_query_lower, $keyword) !== false) {
				return json_encode(['error' => "Prohibited query."]);
			}
		}

		$db = database::getDB();
		$db->setQuery($sql_query);
		$result = $db->loadAssocList();

		return json_encode($result);

	}

	/**
	 * @throws Exception
	 * @since 3.6.8
	 */
	public function generateAnswer($user_message, $query, $data)
	{
		$this->saveMessage(["role" => "system", "content" => 'You are a helpful assistant who generates answers based on data captured by query (' . $query . ').'], true);
		$this->saveMessage(["role" => "system", "content" => 'The JSON data provided is: ' . $data], true);
		$this->saveMessage(["role" => "user", "content" => $user_message . ' (use the data)'], true);

		$response = $this->getResponse($this->messages);
		$this->saveMessage(["role" => "assistant", "content" => $response]);
		return $response;
	}
}