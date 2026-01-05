<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
use Exception;

defined('_JEXEC') or die();

class Twig_Fields_Tags
{
	var CT $ct;
	var bool $isTwig;

	function __construct(CT &$ct, bool $isTwig = true)
	{
		$this->ct = &$ct;
		$this->isTwig = $isTwig;
	}

	function json(): string
	{
		return common::ctJsonEncode(Fields::shortFieldObjects($this->ct->Table->fields));
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function list($param = 'fieldname'): array
	{
		$available_params = ['fieldname', 'title', 'defaultvalue', 'description', 'isrequired', 'isdisabled', 'type', 'typeparams', 'valuerule', 'valuerulecaption'];

		if (!in_array($param, $available_params))
			throw new Exception('{{ fields.array("' . $param . '") }} - Unknown parameter.');

		$fields = Fields::shortFieldObjects($this->ct->Table->fields);
		$list = [];
		foreach ($fields as $field) {
			if ((int)$field['published'] === 1)
				$list[] = $field[$param];
		}

		return $list;
	}

	function count(): int
	{
		return count($this->ct->Table->fields);
	}
}


