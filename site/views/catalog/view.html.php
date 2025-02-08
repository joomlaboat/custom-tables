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
use CustomTables\Catalog;
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\ProInputBoxTableJoin;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class CustomTablesViewCatalog extends HtmlView
{
	var CT $ct;
	var string $listing_id;
	var Catalog $catalog;

	function display($tpl = null)
	{
		$this->ct = new CT(null, false);
		$this->ct->Params->constructJoomlaParams();

		$app = Factory::getApplication();
		$menuParams = $app->getParams();
		$frmt = $menuParams->get('frmt') ?? null;
		if ($frmt !== null)
			$this->ct->Env->frmt = $frmt;

		$key = common::inputGetCmd('key');

		if (defined('_JEXEC') and $key != '') {
			$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

			if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
				require_once($path . 'tablejoin.php');
				require_once($path . 'tablejoinlist.php');

				ProInputBoxTableJoin::renderTableJoinSelectorJSON($this->ct, $key);//Inputbox
			}
		} else
			$this->renderCatalog($tpl);
	}

	/**
	 * @throws Exception
	 * @since 3.2.
	 */
	function renderCatalog($tpl): bool
	{
		if (!function_exists('mb_convert_encoding')) {
			$msg = '"mbstring" PHP extension not installed.<br/>
				You need to install this extension. It depends on of your operating system, here are some examples:<br/><br/>
				sudo apt-get install php-mbstring  # Debian, Ubuntu<br/>
				sudo yum install php-mbstring  # RedHat, Fedora, CentOS<br/><br/>
				Uncomment the following line in php.ini, and restart the Apache server:<br/>
				extension=mbstring<br/><br/>
				Then restart your webs\' server. Example:<br/>service apache2 restart';

			common::enqueueMessage($msg);
			return false;
		}

		/*

		FUTURE USE

		if (!empty($this->ct->Params->tableName))
			$this->ct->getTable($this->ct->Params->tableName);

		if ($this->ct->Table === null) {
			common::enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_TABLE_NOT_SPECIFIED'));
			return false;
		}

		$layout = new Layouts($this->ct);
		$layout->getLayout($this->ct->Params->editLayout);

		$result = $layout->renderMixedLayout($this->ct->Params->editLayout);
		*/

		$this->catalog = new Catalog($this->ct);

		if ($this->ct->Env->frmt === '' or $this->ct->Env->frmt === 'html') {
			//Save view log
			$allowed_fields = $this->SaveViewLog_CheckIfNeeded();
			if (count($allowed_fields) > 0 and $this->ct->Records !== null) {
				foreach ($this->ct->Records as $rec)
					$this->SaveViewLogForRecord($rec, $allowed_fields);
			}

			parent::display($tpl);
			return true;
		}

		// Ensure no previous output interferes
		//while (ob_get_level() > 0) ob_end_clean();

		if (!$this->ct->Env->frmt == 'rawhtml') {

			$fileExtension = 'html';
			if ($this->ct->Env->frmt == 'text/html')
				$fileExtension = 'html';
			elseif ($this->ct->Env->frmt == 'txt')
				$fileExtension = 'txt';
			elseif ($this->ct->Env->frmt == 'json')
				$fileExtension = 'json';
			elseif ($this->ct->Env->frmt == 'xml')
				$fileExtension = 'xml';

			$filename = CTMiscHelper::makeNewFileName($this->ct->Params->pageTitle, $fileExtension);
			if (is_null($filename))
				$filename = 'ct';

			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		if ($this->ct->Env->frmt == 'text/html')
			header('Content-Type: text/html; charset=utf-8');
		elseif ($this->ct->Env->frmt == 'txt')
			header('Content-Type: text/plain; charset=utf-8');
		elseif ($this->ct->Env->frmt == 'json')
			header('Content-Type: application/json; charset=utf-8');
		elseif ($this->ct->Env->frmt == 'xml')
			header('Content-Type: application/xml; charset=utf-8');

		header("Pragma: no-cache");
		header("Expires: 0");

		try {
			$content = $this->catalog->render($this->ct->Params->pageLayout);
			echo preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $content);
		} catch (Exception $e) {
			echo 'Error during the Catalog rendering: ' . $e->getMessage();
		}

		exit;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function SaveViewLog_CheckIfNeeded(): array
	{
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

				$group_id = CTMiscHelper::getGroupIdByTitle($user_group);

				if ($user_group != '') {
					if (in_array($group_id, $this->ct->Env->user->groups))
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
		$data = [];
		$whereClauseUpdate = new MySQLWhereClause();
		$whereClauseUpdate->addCondition('id', $rec[$this->ct->Table->realidfieldname]);

		foreach ($this->ct->Table->fields as $mFld) {
			if (in_array($mFld['fieldname'], $allowedFields)) {
				if ($mFld['type'] == 'lastviewtime')
					$data[$mFld['realfieldname']] = common::currentDate();

				if ($mFld['type'] == 'viewcount')
					$data[$mFld['realfieldname']] = ((int)($rec[$this->ct->Table->fieldPrefix . $mFld['fieldname']]) + 1);
			}
		}

		if (count($data) > 0)
			database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
	}
}
