<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditItem extends BaseDatabaseModel
{
	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	public function copyContent($from, $to)
	{
		//Copy value from one cell to another (drag and drop functionality)
		$from_parts = explode('_', $from);
		$to_parts = explode('_', $to);

		$from_listing_id = $from_parts[0];
		$to_listing_id = $to_parts[0];

		$from_field = $this->ct->Table->getFieldByName($from_parts[1]);
		$to_field = $this->ct->Table->getFieldByName($to_parts[1]);

		if (!isset($from_field['type']))
			die(common::ctJsonEncode(['error' => 'From field not found.']));

		if (!isset($to_field['type']))
			die(common::ctJsonEncode(['error' => 'To field not found.']));

		if (!empty($from_listing_id)) {
			$this->ct->Params->listing_id = $from_listing_id;
			$this->ct->getRecord();
		}

		$from_row = $this->ct->Table->record;

		if (!empty($to_listing_id)) {
			$this->ct->Params->listing_id = $to_listing_id;
			$this->ct->getRecord();
		}

		$to_row = $this->ct->Table->record;

		$f = $from_field['type'];
		$t = $to_field['type'];

		$ok = true;

		if ($f != $t) {
			switch ($t) {
				case 'string':
					if (!($f == 'email' or $f == 'int' or $f == 'float' or $f == 'text'))
						$ok = false;
					break;

				default:
					$ok = false;
			}
		}

		if (!$ok)
			die(common::ctJsonEncode(['error' => 'Target and destination field types do not match.']));

		$new_value = '';

		switch ($to_field['type']) {
			case 'sqljoin':
				if ($to_row[$to_field['realfieldname']] !== '')
					die(common::ctJsonEncode(['error' => 'Target field type is the Table Join. Multiple values not allowed.']));

				break;

			case 'email':

				if ($to_row[$to_field['realfieldname']] !== '')
					die(common::ctJsonEncode(['error' => 'Target field type is an Email. Multiple values not allowed.']));

				break;

			case 'string':

				if (str_contains($to_row[$to_field['realfieldname']], $from_row[$from_field['realfieldname']]))
					die(common::ctJsonEncode(['error' => 'Target field already contains this value.']));

				$new_value = $to_row[$to_field['realfieldname']];
				if ($new_value != '')
					$new_value .= ',';

				$new_value .= $from_row[$from_field['realfieldname']];
				break;

			case 'records':

				$new_items = [''];
				$to_items = explode(',', $to_row[$to_field['realfieldname']]);

				foreach ($to_items as $item) {
					if ($item != '' and !in_array($item, $new_items))
						$new_items[] = $item;
				}

				$from_items = explode(',', $from_row[$from_field['realfieldname']]);

				foreach ($from_items as $item) {
					if ($item != '' and !in_array($item, $new_items))
						$new_items[] = $item;
				}

				$new_items[] = '';

				if (count($new_items) == count($to_items))
					die(common::ctJsonEncode(['error' => 'Target field already contains this value(s).']));

				$new_value = implode(',', $new_items);

				break;
		}

		if ($new_value != '') {

			$data = [
				$to_field['realfieldname'] => $new_value
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition($this->ct->Table->realidfieldname, $to_listing_id);

			try {
				database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
			return true;
		}
		return false;
	}
}
