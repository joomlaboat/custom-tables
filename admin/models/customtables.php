<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Customtables Model
 *
 * @since 1.0.0
 */
class CustomtablesModelCustomtables extends ListModel
{
	var CT $ct;

	public function getIcons()
	{
		$this->ct = new CT;

		// load user for access menus
		$user = new CTUser();
		// reset icon array
		$icons = array();
		// view groups array

		if (!$this->ct->Env->advancedTagProcessor) {
			$viewGroups = array(
				'main' => array('png.listoftables', 'png.listoflayouts', 'png.importtables', 'png.documentation')
			);
		} else {
			$viewGroups = array(
				'main' => array('png.listoftables', 'png.listoflayouts', 'png.listofcategories', 'png.importtables', 'png.documentation')
			);
		}

		// view access array
		$viewAccess = array(
			'listofcategories.submenu' => 'categories.submenu',
			'listofcategories.dashboard_list' => 'categories.dashboard_list',
			'listoftables.submenu' => 'tables.submenu',
			'listoftables.dashboard_list' => 'tables.dashboard_list',
			'listoflayouts.submenu' => 'layouts.submenu',
			'listoflayouts.dashboard_list' => 'layouts.dashboard_list',
			'documentation.dashboard_list' => 'documentation.dashboard_list');
		// loop over the $views
		foreach ($viewGroups as $group => $views) {
			$i = 0;
			$url = '';
			if (CustomtablesHelper::checkArray($views)) {

				foreach ($views as $view) {
					$action = true;
					$add = false;
					$name = '';

					// external views (links)
					if (strpos($view, '||') !== false) {
						$dwd = explode('||', $view);
						if (count($dwd) == 3) {
							list($type, $name, $url) = $dwd;
							$viewName = $name;
							$alt = $name;
							$image = $name . '.' . $type ?? 'png';
							$name = 'COM_CUSTOMTABLES_DASHBOARD_' . CustomtablesHelper::safeString($name, 'U');
						}
					} // internal views
					elseif (strpos($view, '.') !== false) {
						$dwd = explode('.', $view);
						if (count($dwd) == 3) {
							list($type, $name, $action) = $dwd;
						} elseif (count($dwd) == 2) {
							list($type, $name) = $dwd;
							$action = false;
						}

						$viewName = $name;
						if ($action) {
							switch ($action) {
								case 'add':
									$url = 'index.php?option=com_customtables&view=' . $name . '&layout=edit';
									$image = $name . '_' . $action . '.' . $type ?? 'png';
									$alt = $name . '&nbsp;' . $action;
									$name = 'COM_CUSTOMTABLES_DASHBOARD_' . CustomtablesHelper::safeString($name, 'U') . '_ADD';
									$add = true;
									break;
								default:
									$url = 'index.php?option=com_categories&view=categories&extension=com_customtables.' . $name;
									$image = $name . '_' . $action . '.' . $type ?? 'png';
									$alt = $name . '&nbsp;' . $action;
									$name = 'COM_CUSTOMTABLES_DASHBOARD_' . CustomtablesHelper::safeString($name, 'U') . '_' . CustomtablesHelper::safeString($action, 'U');
									break;
							}
						} else {
							$alt = $name;
							$url = 'index.php?option=com_customtables&view=' . $name;
							$image = $name . '.' . $type;
							$name = 'COM_CUSTOMTABLES_DASHBOARD_' . CustomtablesHelper::safeString($name, 'U');
							$hover = false;
						}
					} else {
						$viewName = $view;
						$alt = $view;
						$url = 'index.php?option=com_customtables&view=' . $view;
						$image = $view . '.png';
						$name = ucwords($view) . '<br /><br />';
						$hover = false;
					}
					// first make sure the view access is set
					if (CustomtablesHelper::checkArray($viewAccess)) {
						// setup some defaults
						$dashboard_add = false;
						$dashboard_list = false;
						$accessTo = '';
						$accessAdd = '';
						// access checking start
						$accessCreate = (isset($viewAccess[$viewName . '.create'])) ? common::checkString($viewAccess[$viewName . '.create']) : false;
						$accessAccess = (isset($viewAccess[$viewName . '.access'])) ? common::checkString($viewAccess[$viewName . '.access']) : false;
						// set main controllers
						$accessDashboard_add = (isset($viewAccess[$viewName . '.dashboard_add'])) ? common::checkString($viewAccess[$viewName . '.dashboard_add']) : false;
						$accessDashboard_list = (isset($viewAccess[$viewName . '.dashboard_list'])) ? common::checkString($viewAccess[$viewName . '.dashboard_list']) : false;
						// check for adding access
						if ($add && $accessCreate) {
							$accessAdd = $viewAccess[$viewName . '.create'];
						} elseif ($add) {
							$accessAdd = 'core.create';
						}
						// check if access to view is set
						if ($accessAccess) {
							$accessTo = $viewAccess[$viewName . '.access'];
						}
						// set main access controllers
						if ($accessDashboard_add) {
							$dashboard_add = $user->authorise($viewAccess[$viewName . '.dashboard_add'], 'com_customtables');
						}
						if ($accessDashboard_list) {
							$dashboard_list = $user->authorise($viewAccess[$viewName . '.dashboard_list'], 'com_customtables');
						}
						if (common::checkString($accessAdd) && common::checkString($accessTo)) {
							// check access
							if ($user->authorise($accessAdd, 'com_customtables') && $user->authorise($accessTo, 'com_customtables') && $dashboard_add) {
								$icons[$group][$i] = new StdClass;
								$icons[$group][$i]->url = $url;
								$icons[$group][$i]->name = $name;
								$icons[$group][$i]->image = $image;
								$icons[$group][$i]->alt = $alt;
							}
						} elseif (common::checkString($accessTo)) {
							// check access
							if ($user->authorise($accessTo, 'com_customtables') && $dashboard_list) {
								$icons[$group][$i] = new StdClass;
								$icons[$group][$i]->url = $url;
								$icons[$group][$i]->name = $name;
								$icons[$group][$i]->image = $image;
								$icons[$group][$i]->alt = $alt;
							}
						} elseif (common::checkString($accessAdd)) {
							// check access
							if ($user->authorise($accessAdd, 'com_customtables') && $dashboard_add) {
								$icons[$group][$i] = new StdClass;
								$icons[$group][$i]->url = $url;
								$icons[$group][$i]->name = $name;
								$icons[$group][$i]->image = $image;
								$icons[$group][$i]->alt = $alt;
							}
						} else {
							$icons[$group][$i] = new StdClass;
							$icons[$group][$i]->url = $url;
							$icons[$group][$i]->name = $name;
							$icons[$group][$i]->image = $image;
							$icons[$group][$i]->alt = $alt;
						}
					} else {
						$icons[$group][$i] = new StdClass;
						$icons[$group][$i]->url = $url;
						$icons[$group][$i]->name = $name;
						$icons[$group][$i]->image = $image;
						$icons[$group][$i]->alt = $alt;
					}
					$i++;
				}
			} else {
				$icons[$group][$i] = false;
			}
		}
		return $icons;
	}
}
