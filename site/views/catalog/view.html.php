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
	var ?string $content;

	function display($tpl = null)
	{
		$this->ct = new CT(null, false);

		try {
			$this->ct->Params->constructJoomlaParams();
		} catch (Throwable $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return;
		}

		$app = Factory::getApplication();
		$menuParams = $app->getParams();
		$frmt = $menuParams->get('frmt') ?? null;
		if ($frmt !== null) {
			$this->ct->Env->frmt = $frmt;
			$this->ct->Env->clean = 1;
		}

		$key = common::inputGetCmd('key');

		if (defined('_JEXEC') and $key != '') {
			$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR;

			if (file_exists($path . 'tablejoin.php') and file_exists($path . 'tablejoinlist.php')) {
				require_once($path . 'tablejoin.php');
				require_once($path . 'tablejoinlist.php');

				try {
					$result = ProInputBoxTableJoin::renderTableJoinSelectorJSON($this->ct, $key, false);//Inputbox
					CTMiscHelper::fireSuccess(null, $result, 'Lookup Table records loaded');
				} catch (Throwable $e) {
					CTMiscHelper::fireError(500, $e->getMessage());
				}
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
			common::enqueueMessage('"mbstring" PHP extension not installed.<br/>
				You need to install this extension. It depends on of your operating system, here are some examples:<br/><br/>
				sudo apt-get install php-mbstring  # Debian, Ubuntu<br/>
				sudo yum install php-mbstring  # RedHat, Fedora, CentOS<br/><br/>
				Uncomment the following line in php.ini, and restart the Apache server:<br/>
				extension=mbstring<br/><br/>
				Then restart your webs\' server. Example:<br/>service apache2 restart');
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

		$layoutName = common::inputGetCmd('layout');
		if ($layoutName !== null) {
			$this->ct->Params->pageLayout = $layoutName;
		}

		try {
			$this->content = $this->catalog->render($this->ct->Params->pageLayout);
			$code = 200;
		} catch (Throwable $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			$this->content = '';
			$code = 500;
		}

		//Save view log
		$allowed_fields = $this->SaveViewLog_CheckIfNeeded();

		if (count($allowed_fields) > 0 and $this->ct->Records !== null) {
			foreach ($this->ct->Records as $rec)
				$this->SaveViewLogForRecord($rec, $allowed_fields);
		}

		if ($this->ct->Env->frmt === '' or $this->ct->Env->frmt === 'html') {
			parent::display($tpl);
		} else {
			$this->content = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $this->content);
			CTMiscHelper::fireFormattedOutput($this->content, $this->ct->Env->frmt, $this->ct->Params->pageTitle, $code);
		}
		return true;
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
			if ($mFld['type'] == 'lastviewtime' or $mFld['type'] == 'viewcount' or $mFld['type'] == 'server') { //phponview obsolete

				if ($mFld['type'] == 'server') {
					$pair = CTMiscHelper::csv_explode(',', $mFld['typeparams']);
					if (isset($pair[1])) {
						$updateAction = $pair[1];
						if ($updateAction == 'view')
							$allowed_fields[] = $mFld['fieldname'];

						//echo '$allowed_fields:' . implode(',', $allowed_fields) . '.';
					}

				} else {
					if (!empty($mFld['typeparams'])) {
						$pair = CTMiscHelper::csv_explode(',', $mFld['typeparams']);
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
					} else {
						$allowed_fields[] = $mFld['fieldname'];
					}
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

				if ($mFld['type'] == 'server') {

					$fieldParams = ctMiscHelper::csv_explode(',', $mFld['typeparams']);

					if (empty($fieldParams))
						$value = ctMiscHelper::getUserIP(); //Try to get client real IP
					else
						$value = common::inputServer($fieldParams[0], '', 'STRING');

					$data[$mFld['realfieldname']] = $value;
				}
			}
		}

		if (count($data) > 0)
			database::update($this->ct->Table->realtablename, $data, $whereClauseUpdate);
	}
}
