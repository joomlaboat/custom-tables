<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access
defined('_JEXEC') or die();

use CustomTables\common;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_FIELDS_USER', 'u.username', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_FIELDS_TIME', 'a.datetime', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col" class="text-left d-none d-md-table-cell">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_LAYOUTS_TABLEID_LABEL', 't.tabletitle', $this->listDirn, $this->listOrder); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_ACTIONS_ACTION'); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_RECORDS_ID'); ?>
	</th>

	<th scope="col">
		<?php echo common::translate('COM_CUSTOMTABLES_ACTIONS_ITEMID'); ?>
	</th>

</tr>
