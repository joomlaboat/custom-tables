<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Field
{
	var CT $ct;

	var int $id;
	var ?array $params;
	var ?string $type;
	var int $isrequired;
	var ?string $defaultvalue;

	var string $title;
	var ?string $description;
	var string $fieldname;
	var string $realfieldname;
	var ?string $comesfieldname;
	var ?string $valuerule;
	var ?string $valuerulecaption;

	var array $fieldrow;
	var string $prefix; //part of the table class

	var ?string $layout; //output layout, used in Search Boxes

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function __construct(CT &$ct, array $fieldRow, ?array $row = null, bool $parseParams = true)
	{
		$this->ct = &$ct;

		if (!array_key_exists('id', $fieldRow))
			throw new Exception('FieldRaw: Empty.');

		$this->id = $fieldRow['id'];
		$this->fieldname = $fieldRow['fieldname'];
		$this->realfieldname = $fieldRow['realfieldname'];

		if ($fieldRow['type'] !== null) {
			$this->type = $fieldRow['type'];
			$this->fieldrow = $fieldRow;
			$this->layout = $fieldRow['layout'] ?? null; //rendering layout

			if ($fieldRow['type'] == '_id') {
				$this->title = '#';
			} elseif (!array_key_exists('fieldtitle' . $ct->Languages->Postfix, $fieldRow)) {
				$this->title = 'fieldtitle' . $ct->Languages->Postfix . ' - not found';
			} else {
				$vlu = $fieldRow['fieldtitle' . $ct->Languages->Postfix];
				if ($vlu == '')
					$this->title = $fieldRow['fieldtitle'];
				else
					$this->title = $vlu;
			}

			if (!array_key_exists('description' . $ct->Languages->Postfix, $fieldRow)) {
				$this->description = 'description' . $ct->Languages->Postfix . ' - not found';
			} else {
				$vlu = $fieldRow['description' . $ct->Languages->Postfix];
				if ($vlu == '')
					$this->description = $fieldRow['description'];
				else
					$this->description = $vlu;
			}

			if (isset($fieldRow['isrequired']))
				$this->isrequired = intval($fieldRow['isrequired']);
			else
				$this->isrequired = false;

			if (isset($fieldRow['defaultvalue']))
				$this->defaultvalue = $fieldRow['defaultvalue'];
			else
				$this->defaultvalue = null;

			if (isset($fieldRow['valuerule']))
				$this->valuerule = $fieldRow['valuerule'];
			else
				$this->valuerule = null;

			if (isset($fieldRow['valuerulecaption']))
				$this->valuerulecaption = $fieldRow['valuerulecaption'];
			else
				$this->valuerulecaption = null;

			$this->prefix = $this->ct->Table->fieldInputPrefix;
			$this->comesfieldname = $this->prefix . $this->fieldname;

			if (isset($fieldRow['typeparams']))
				$this->params = CTMiscHelper::csv_explode(',', $fieldRow['typeparams']);
			else
				$this->params = null;

			if ($parseParams and $this->type != 'virtual')
				$this->processParams($row, $this->type);
		} else
			$this->type = null;
	}

	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws LoaderError
	 * @throws Exception
	 * @since 3.0.0
	 */
	function processParams(?array $row, string $type): void
	{
		$new_params = [];

		if ($this->params === null)
			return;

		$index = 0;
		foreach ($this->params as $type_param) {
			if ($type_param !== null) {
				$type_param = str_replace('****quote****', '"', $type_param);
				$type_param = str_replace('****apos****', '"', $type_param);

				if (is_numeric($type_param))
					$new_params[] = $type_param;
				elseif (!str_contains($type_param, '{{') and !str_contains($type_param, '{%'))
					$new_params[] = $type_param;
				else {

					if ($type == 'user' and ($index == 1 or $index == 2)) {
						//Do not parse
						$new_params[] = $type_param;
					} else {
						try {
							$twig = new TwigProcessor($this->ct, $type_param, false, false, false);
							$new_params[] = $twig->process($row);
						} catch (Exception $e) {
							throw new Exception($e->getMessage());
						}
					}
				}
			}
			$index++;
		}
		$this->params = $new_params;
	}
}
