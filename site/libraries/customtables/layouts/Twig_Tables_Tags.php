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
use LayoutProcessor;

class Twig_Tables_Tags
{
	var CT $ct;

	function __construct(&$ct)
	{
		$this->ct = &$ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getvalue($table = '', $fieldname = '', $record_id_or_filter = '', $orderby = '')
	{
		$tag = 'tables.getvalue';
		if ($table == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '",value_field_name) }} - Table not specified.';
			return '';
		}

		if ($fieldname == '') {
			$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '",field_name) }} - Value field not specified.';
			return '';
		}

		$join_ct = new CT;
		$join_ct->getTable($table);
		$join_table_fields = $join_ct->Table->fields;

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			try {
				$row = $join_ct->Table->loadRecord($record_id_or_filter);
				if ($row === null)
					return '';
			} catch (Exception $e) {
				$join_ct->errors[] = $e->getMessage();
				return '';
			}
		} else {
			try {
				$join_ct->setFilter($record_id_or_filter, 2);
				if ($join_ct->getRecords(false, 1, $orderby)) {
					if (count($join_ct->Records) > 0) {
						$row = $join_ct->Records[0];
					} else
						return '';
				} else
					return '';
			} catch (Exception $e) {
				$join_ct->errors[] = $e->getMessage();
				return '';
			}
		}

		if (Layouts::isLayoutContent($fieldname)) {

			$twig = new TwigProcessor($join_ct, $fieldname);
			$value = $twig->process($row);

			if ($twig->errorMessage !== null)
				$join_ct->errors[] = $twig->errorMessage;

			return $value;

		} else {
			$value_realfieldname = '';
			if ($fieldname == '_id')
				$value_realfieldname = $join_ct->Table->realidfieldname;
			elseif ($fieldname == '_published')
				if ($join_ct->Table->published_field_found) {
					$value_realfieldname = 'listing_published';
				} else {
					$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '","published") }} - "published" does not exist in the table.';
					return '';
				}
			else {
				foreach ($join_table_fields as $join_table_field) {
					if ($join_table_field['fieldname'] == $fieldname) {
						$value_realfieldname = $join_table_field['realfieldname'];
						break;
					}
				}
			}

			if ($value_realfieldname == '') {
				$this->ct->errors[] = '{{ ' . $tag . '("' . $table . '","' . $fieldname . '") }} - Value field "' . $fieldname . '" not found.';
				return '';
			}
			return $row[$value_realfieldname];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecords($layoutname = '', $filter = '', $orderby = '', $limit = 0, $groupby = ''): string
	{
		//Example {{ html.records("InvoicesPage","firstname=john","lastname",10,"country") }}

		if ($layoutname == '') {
			$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.';
			return '';
		}

		$join_ct = new CT;
		$layouts = new Layouts($join_ct);
		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
		if ($layouts->tableId === null) {
			$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.';
			return '';
		}

		$join_ct->getTable($layouts->tableId);
		if ($join_ct->Table === null) {
			$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Table "' . $layouts->tableId . ' not found.';
			return '';
		}

		try {
			$join_ct->setFilter($filter, 2);
			if ($join_ct->getRecords(false, $limit, $orderby, $groupby)) {

				if ($join_ct->Env->legacySupport) {
					$LayoutProc = new LayoutProcessor($join_ct);
					$LayoutProc->layout = $pageLayout;
					$pageLayout = $LayoutProc->fillLayout();
				}

				$twig = new TwigProcessor($join_ct, $pageLayout);

				$value = $twig->process();

				if ($twig->errorMessage !== null)
					$join_ct->errors[] = $twig->errorMessage;

				return $value;
			}
		} catch (Exception $e) {
			return 'Error: ' . $e->getMessage();
		}

		$this->ct->errors[] = '{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Could not load records.';
		return '';
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecord($layoutname = '', $record_id_or_filter = '', $orderby = ''): string
	{
		if ($layoutname == '') {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout name not specified.';
			return '';
		}

		if ($record_id_or_filter == '') {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Record id or filter not set.';
			return '';
		}

		$join_ct = new CT;
		$layouts = new Layouts($join_ct);

		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table

		if ($layouts->tableId === null) {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.';
			return '';
		}

		$join_ct->getTable($layouts->tableId);
		if ($join_ct->Table === null) {
			$this->ct->errors[] = '{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Table "' . $layouts->tableId . ' not found.';
			return '';
		}

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			$row = $join_ct->Table->loadRecord($record_id_or_filter);

			if ($row === null)
				return '';
		} else {
			$join_ct->setFilter($record_id_or_filter, 2);
			if ($join_ct->getRecords(false, 1, $orderby)) {
				if (count($join_ct->Records) > 0)
					$row = $join_ct->Records[0];
				else
					return '';
			} else
				return '';
		}

		$twig = new TwigProcessor($join_ct, $pageLayout);

		$value = $twig->process($row);
		if ($twig->errorMessage !== null)
			$join_ct->errors[] = $twig->errorMessage;

		return $value;
	}
}