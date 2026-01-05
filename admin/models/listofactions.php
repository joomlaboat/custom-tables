<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage listofrecords.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;

/**
 * ListOfRecords Model
 *
 * @since 1.0.0
 */
class CustomtablesModelListOfActions extends ListModel
{
	var CT $ct;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				//'a.id', 'id',
				'u.username', 'username',
				'a.datetime', 'datetime',
				'a.tableid', 'tableid',
				't.tabletitle', 'tabletitle',
			);
		}

		parent::__construct($config);

		$this->ct = new CT([], true);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  array  An array of data items on success, false on failure.
	 *
	 * @since 3.0.0
	 */

	protected function getListQuery(): string
	{
		if (!CUSTOMTABLES_JOOMLA_MIN_4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get(DatabaseInterface::class);

		$search = $this->getState('filter.search');
		$tableid = $this->getState('filter.tableid');

		$wheres = [];

		if (!empty($tableid) and $tableid !== 0)
			$wheres [] = 'a.tableid=' . (int)$tableid;

		if (!empty($search) and $search !== 0)
			$wheres [] = 'u.username LIKE ' . $db->quote('%' . $search . '%');

		$orderCol = $this->state->get('list.ordering', 'a.datetime');
		$orderDirection = $this->state->get('list.direction', 'desc');
		if (strtolower($orderDirection) !== 'desc')
			$orderDirection = 'asc';

		$selects = ['*'];
		$selects[] = 'u.username AS USER_NAME';// (SELECT username FROM #__users WHERE #__users.id=a.userid LIMIT 1) AS USER_NAME';
		$selects[] = 't.tabletitle AS TABLE_TITLE';//(SELECT tabletitle FROM #__customtables_tables WHERE #__customtables_tables.id=a.tableid LIMIT 1) AS TABLE_TITLE';
		$selects[] = 'CASE action
        WHEN 1 THEN "' . common::translate('COM_CUSTOMTABLES_NEW') . '"
        WHEN 2 THEN "' . common::translate('COM_CUSTOMTABLES_EDIT') . '"
        WHEN 3 THEN "' . common::translate('COM_CUSTOMTABLES_PUBLISH') . '"
        WHEN 4 THEN "' . common::translate('COM_CUSTOMTABLES_UNPUBLISH') . '"
        WHEN 5 THEN "' . common::translate('COM_CUSTOMTABLES_DELETE') . '"
        WHEN 6 THEN "' . common::translate('COM_CUSTOMTABLES_UPLOAD_PHOTO') . '"
        WHEN 7 THEN "' . common::translate('COM_CUSTOMTABLES_IMAGE_DELETED') . '"
        WHEN 8 THEN "' . common::translate('COM_CUSTOMTABLES_UPLOAD_FILE') . '"
        WHEN 9 THEN "' . common::translate('COM_CUSTOMTABLES_FILE_DELETED') . '"
        WHEN 10 THEN "' . common::translate('COM_CUSTOMTABLES_REFRESH') . '"
        ELSE "UNKNOWN"
    	END AS ACTION_LABEL';
		$selects[] = '(SELECT title FROM #__menu WHERE #__menu.id=a.Itemid LIMIT 1) AS MENU_TITLE';

		$join = [];
		$join[] = 'LEFT JOIN `#__users` AS u ON a.userid = u.id';
		$join[] = 'LEFT JOIN `#__customtables_tables` AS t ON a.tableid=t.id';

		return 'SELECT ' . implode(',', $selects) . ' FROM #__customtables_log AS a ' . implode(' ', $join)
			. (count($wheres) > 0 ? ' WHERE ' . implode(' AND ', $wheres) : '')
			. ' ORDER BY ' . $db->quoteName($orderCol) . ' ' . $orderDirection;
	}
}