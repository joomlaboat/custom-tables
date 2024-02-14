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
use Exception;

if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

class ListOfLayouts
{
	var CT $ct;

	function __construct(CT $ct)
	{
		$this->ct = $ct;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getLayouts()
	{
		//$query = 'SELECT id,layoutname,tableid,layouttype FROM #__customtables_layouts WHERE published=1 ORDER BY layoutname';
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);
		return database::loadObjectList('#__customtables_layouts', ['id', 'layoutname', 'tableid', 'layouttype'], $whereClause, 'layoutname');
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getItems($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit, $start): array
	{
		$items = $this->getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit, $start);
		return $this->translateLayoutTypes($items);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getListQuery($published, $search, $layoutType, $tableid, $orderCol, $orderDirection, $limit = 0, $start = 0, bool $returnQueryString = false)
	{
		$whereClause = new MySQLWhereClause();

		$selects = [
			'a.*',
			'TABLE_TITLE',
			'MODIFIED_BY',
			'LAYOUT_SIZE'
		];

		$whereClausePublished = new MySQLWhereClause();

		// Filter by published state
		if (is_numeric($published)) {
			$whereClausePublished->addCondition('a.published', (int)$published);
		} elseif (is_null($published) or $published === '') {
			$whereClausePublished->addOrCondition('a.published', 0);
			$whereClausePublished->addOrCondition('a.published', 1);
		}

		if ($whereClausePublished->hasConditions())
			$whereClause->addNestedCondition($whereClausePublished);

		// Filter by search.
		if (!empty($search)) {

			$whereClauseSearch = new MySQLWhereClause();

			if (stripos($search, 'id:') === 0) {
				$whereClauseSearch->addCondition('a.id', intval(substr($search, 3)));
			} else {
				$whereClauseSearch->addOrCondition('a.layoutname', '%' . $search . '%', 'LIKE');
				$whereClauseSearch->addOrCondition('a.layoutcode', '%' . $search . '%', 'LIKE');
				$whereClauseSearch->addOrCondition('a.layoutmobile', '%' . $search . '%', 'LIKE');
				$whereClauseSearch->addOrCondition('a.layoutcss', '%' . $search . '%', 'LIKE');
				$whereClauseSearch->addOrCondition('a.layoutjs', '%' . $search . '%', 'LIKE');
			}
			if ($whereClauseSearch->hasConditions())
				$whereClause->addNestedCondition($whereClauseSearch);
		}

		// Filter by Layouttype.
		if ($layoutType)
			$whereClause->addCondition('a.layouttype', $layoutType);

		// Filter by Tableid.
		if ($tableid)
			$whereClause->addCondition('a.tableid', $tableid);

		return database::loadAssocList('#__customtables_layouts AS a', $selects, $whereClause, $orderCol, $orderDirection, $limit, $start, null, $returnQueryString);
	}

	function translateLayoutTypes(array $items): array
	{
		$Layouts = new Layouts($this->ct);
		$translations = $Layouts->layoutTypeTranslation();

		foreach ($items as $item) {
			// convert layoutType
			if (isset($translations[$item->layouttype])) {
				$item->layouttype = $translations[$item->layouttype];
			} else {
				$item->layouttype = '<span style="color:red;">NOT SELECTED</span>';
			}
		}
		return $items;
	}

	function save(?int $layoutId): bool
	{
		// Check if running in WordPress context
		if (defined('WPINC')) {
			check_admin_referer('create-edit-layout');

			// Check user capabilities
			if (!current_user_can('install_plugins')) {
				wp_die(
					'<h1>' . __('You need a higher level of permission.') . '</h1>' .
					'<p>' . __('Sorry, you are not allowed to create layouts.') . '</p>',
					403
				);
			}
		}

		// Process layout name
		if (function_exists("transliterator_transliterate"))
			$newLayoutName = transliterator_transliterate("Any-Latin; Latin-ASCII; Lower()", common::inputPostString('layoutname', null, 'create-edit-layout'));
		else
			$newLayoutName = common::inputPostString('layoutname', null, 'create-edit-layout');

		$newLayoutName = str_replace(" ", "_", $newLayoutName);
		$newLayoutName = trim(preg_replace("/[^a-z A-Z_\d]/", "", $newLayoutName));
		$data['layoutname'] = $newLayoutName;//$sets[] = 'layoutname=' . database::quote($newLayoutName);
		$data['modified_by'] = (int)$this->ct->Env->user->id;//$sets[] = 'modified_by=' . (int)$this->ct->Env->user->id;
		$data['modified'] = current_time('mysql', 1); // This will use the current date and time in MySQL format;//$sets[] = 'modified=NOW()';
		$data['layouttype'] = common::inputPostString('layouttype', null, 'create-edit-layout');//$sets[] = 'layouttype=' . database::quote(common::inputPostString('layouttype'));
		$data['tableid'] = common::inputPostInt('table', null, 'create-edit-layout');//$sets[] = 'tableid=' . common::inputGetInt('table');
		$data['layoutcode'] = common::inputPostRow('layoutcode', null, 'create-edit-layout');//$sets[] = 'layoutcode=' . database::quote(common::inputGetRow('layoutcode'), true);
		$data['layoutmobile'] = common::inputPostRow('layoutmobile', null, 'create-edit-layout');//$sets[] = 'layoutmobile=' . database::quote(common::inputGetRow('layoutmobile'), true);
		$data['layoutcss'] = common::inputPostRow('layoutcss', null, 'create-edit-layout');//$sets[] = 'layoutcss=' . database::quote(common::inputGetRow('layoutcss'), true);
		$data['layoutjs'] = common::inputPostRow('layoutjs', null, 'create-edit-layout');//$sets[] = 'layoutjs=' . database::quote(common::inputGetRow('layoutjs'), true);

		try {
			if ($layoutId !== null) {
				$whereClauseUpdate = new MySQLWhereClause();
				$whereClauseUpdate->addCondition('id', $layoutId);

				database::update('#__customtables_layouts', $data, $whereClauseUpdate);
			} else
				database::insert('#__customtables_layouts', $data);
		} catch (Exception $e) {
			return false;
		}
		return true;
	}
}