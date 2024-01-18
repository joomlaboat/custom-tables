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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\CatalogExportCSV;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\Catalog;
use CustomTables\database;
use CustomTables\Inputbox;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewCatalog extends HtmlView
{
	var CT $ct;
	var string $listing_id;
	var Catalog $catalog;
	var string $catalogTableCode;

	function display($tpl = null)
	{
		/*
		$menu = Factory::getApplication()->getMenu();
		$activeMenuItem = $menu->getActive();


		if ($activeMenuItem) {
			$menuParams = $activeMenuItem->getParams();

			$filter = $menuParams->get('filter');

			// Now $menuParams contains the parameters of the active menu item
		} else {
			$menuParams = null;
		}
*/
		$this->ct = new CT(null, false);
		$key = common::inputGetCmd('key');

		if ($key != '')
			Inputbox::renderTableJoinSelectorJSON($this->ct, $key);
		else
			$this->renderCatalog($tpl);
	}

	function renderCatalog($tpl): bool
	{
		$this->catalog = new Catalog($this->ct);

		if ($this->ct->Env->frmt == 'csv') {

			if (ob_get_contents())
				ob_end_clean();

			$pathViews = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
			require_once($pathViews . 'catalog-csv.php');
			$catalogCSV = new CatalogExportCSV($this->ct, $this->catalog);
			if (!$catalogCSV->error) {
				$filename = JoomlaBasicMisc::makeNewFileName($this->ct->Params->pageTitle, 'csv');
				header('Content-Disposition: attachment; filename="' . $filename . '"');
				header('Content-Type: text/csv; charset=utf-16');
				header("Pragma: no-cache");
				header("Expires: 0");

				//layoutType: 9 CSV
				$layout = common::inputGetCmd('layout');
				echo $catalogCSV->render($layout);
				die;
			} else
				return false;
		} else {
			parent::display($tpl);
		}

		//Save view log
		$allowed_fields = $this->SaveViewLog_CheckIfNeeded();
		if (count($allowed_fields) > 0 and $this->ct->Records !== null) {
			foreach ($this->ct->Records as $rec)
				$this->SaveViewLogForRecord($rec, $allowed_fields);
		}
		return true;
	}

	function SaveViewLog_CheckIfNeeded(): array
	{
		$user_groups = $this->ct->Env->user->groups;
		$allowed_fields = array();

		if ($this->ct->Table === null)
			return [];

		if ($this->ct->Table->fields === null)
			return [];

		foreach ($this->ct->Table->fields as $mFld) {
			if ($mFld['type'] == 'lastviewtime' or $mFld['type'] == 'viewcount' or $mFld['type'] == 'phponview') {
				$pair = explode(',', $mFld['typeparams']);
				$user_group = '';

				if (isset($pair[1])) {
					if ($pair[1] == 'catalog')
						$user_group = $pair[0];
				} else
					$user_group = $pair[0];

				$group_id = JoomlaBasicMisc::getGroupIdByTitle($user_group);

				if ($user_group != '') {
					if (in_array($group_id, $user_groups))
						$allowed_fields[] = $mFld['fieldname'];
				}
			}
		}
		return $allowed_fields;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function SaveViewLogForRecord($rec, $allowedFields)
	{
		//$update_fields = array();

		$data = [];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition('id', $rec[$this->ct->Table->realidfieldname]);

		foreach ($this->ct->Table->fields as $mFld) {
			if (in_array($mFld['fieldname'], $allowedFields)) {
				if ($mFld['type'] == 'lastviewtime')
					$data[$mFld['realfieldname']] = gmdate('Y-m-d H:i:s');
				//$update_fields[] = $mFld['realfieldname'] . '="' . gmdate('Y-m-d H:i:s') . '"';

				if ($mFld['type'] == 'viewcount')
					$data[$mFld['realfieldname']] = ((int)($rec[$this->ct->Env->field_prefix . $mFld['fieldname']]) + 1);
				//$update_fields[] = $mFld['realfieldname'] . '="' . ((int)($rec[$this->ct->Env->field_prefix . $mFld['fieldname']]) + 1) . '"';
			}
		}

		if (count($data) > 0) {
			database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
			//$query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET ' . implode(', ', $update_fields) . ' WHERE id=' . $rec[$this->ct->Table->realidfieldname];
		}
	}
}
