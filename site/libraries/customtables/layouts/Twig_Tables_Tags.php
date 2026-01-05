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
defined('_JEXEC') or die();

use Exception;

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
		if ($table == '')
			throw new Exception('{{ ' . $tag . '("' . $table . '",value_field_name) }} - Table not specified.');

		if ($fieldname == '')
			throw new Exception('{{ ' . $tag . '("' . $table . '",field_name) }} - Value field not specified.');

		$join_ct = new CT([], true);
		$join_ct->getTable($table);
		$join_ct->Params->forceSortBy = $orderby;
		$join_table_fields = $join_ct->Table->fields;

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			try {
				$join_ct->Params->listing_id = $record_id_or_filter;
				if (!$join_ct->getRecord())
					return '';

			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		} else {
			try {
				$join_ct->Params->filter = $record_id_or_filter;
				if (!$join_ct->getRecord())
					return '';

			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		if (Layouts::isLayoutContent($fieldname)) {

			try {
				$twig = new TwigProcessor($join_ct, $fieldname);
				$value = $twig->process($join_ct->Table->record);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			return $value;

		} else {
			$value_realfieldname = '';
			if ($fieldname == '_id')
				$value_realfieldname = $join_ct->Table->realidfieldname;
			elseif ($fieldname == '_published')
				if ($join_ct->Table->published_field_found) {
					$value_realfieldname = 'listing_published';
				} else {
					throw new Exception('{{ ' . $tag . '("' . $table . '","published") }} - "published" does not exist in the table.');
				}
			else {
				foreach ($join_table_fields as $join_table_field) {
					if ($join_table_field['fieldname'] == $fieldname) {
						$value_realfieldname = $join_table_field['realfieldname'];
						break;
					}
				}
			}

			if ($value_realfieldname == '')
				throw new Exception('{{ ' . $tag . '("' . $table . '","' . $fieldname . '") }} - Value field "' . $fieldname . '" not found.');

			return $join_ct->Table->record[$value_realfieldname];
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecord($layoutname = '', $record_id_or_filter = '', $orderby = ''): string
	{
		if ($layoutname == '')
			throw new Exception('{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout name not specified.');

		$join_ct = new CT([], true);
		$layouts = new Layouts($join_ct);

		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table

		if ($layouts->tableId === null)
			throw new Exception('{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.');

		if ($join_ct->Table === null)
			throw new Exception('{{ tables.getrecord("' . $layoutname . '","' . $record_id_or_filter . '","' . $orderby . '") }} - Table "' . $layouts->tableId . ' not found.');

		$join_ct->Params->forceSortBy = $orderby;

		if (is_numeric($record_id_or_filter) and (int)$record_id_or_filter > 0) {
			$join_ct->Params->listing_id = $record_id_or_filter;
			if (!$join_ct->getRecord())
				return '';
		} else {
			$join_ct->Params->filter = $record_id_or_filter;
			if (!$join_ct->getRecord())
				return '';
		}

		try {
			$twig = new TwigProcessor($join_ct, $pageLayout);
			$value = $twig->process($join_ct->Table->record);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $value;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getrecords($layoutname = '', $filter = '', $orderby = '', $limit = 0, $groupby = ''): string
	{
		//Example {{ html.records("InvoicesPage","firstname=john","lastname",10,"country") }}

		if ($layoutname == '')
			throw new Exception('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout name not specified.');

		$join_ct = new CT([], true);
		$layouts = new Layouts($join_ct);
		$pageLayout = $layouts->getLayout($layoutname, false);//It is safer to process layout after rendering the table
		if ($layouts->tableId === null)
			throw new Exception('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Layout "' . $layoutname . ' not found.');

		$join_ct->getTable($layouts->tableId);
		if ($join_ct->Table === null)
			throw new Exception('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Table "' . $layouts->tableId . ' not found.');

		try {
			$join_ct->setFilter($filter, CUSTOMTABLES_SHOWPUBLISHED_ANY);
			if ($join_ct->getRecords(false, $limit, $orderby, $groupby)) {

				$twig = new TwigProcessor($join_ct, $pageLayout);
				return $twig->process();
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		throw new Exception('{{ tables.getrecords("' . $layoutname . '","' . $filter . '","' . $orderby . '") }} - Could not load records.');
	}
}