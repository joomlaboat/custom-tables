<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		default_head.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>
<tr>
	<?php if ($this->canEdit&& $this->canState): ?>
		<th width="1%" class="nowrap center hidden-phone">
			<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
		</th>
		<th width="20" class="nowrap center">
			<?php echo JHtml::_('grid.checkall'); ?>
		</th>
	<?php else: ?>
		<th width="20" class="nowrap center hidden-phone">
			&#9662;
		</th>
		<th width="20" class="nowrap center">
			&#9632;
		</th>
	<?php endif; ?>

	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_FIELDS_FIELDNAME_LABEL'); ?>
	</th>

					<th class="nowrap" >
				<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL', 'fieldtitle', $this->listDirn, $this->listOrder); ?>
					</th>

		<?php
/*
				$morethanonelang=false;
				foreach($this->languages as $lang)
				{
					$id='fieldtitle';
					if($morethanonelang)
						$id.='_'.$lang->sef;

					echo '
					<th class="nowrap" >
					'.JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL', $id, $this->listDirn, $this->listOrder).' ('.$lang->title.')
					</th>
					';

					$morethanonelang=true; //More than one language installed

				}
*/
				?>


	<th class="nowrap hidden-phone" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_TYPE_LABEL', 'type', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_FIELDS_TYPEPARAMS_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_FIELDS_ISREQUIRED_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_FIELDS_TABLEID_LABEL'); ?>
	</th>
	<?php if ($this->canState): ?>
		<th width="10" class="nowrap center" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_STATUS', 'published', $this->listDirn, $this->listOrder); ?>
		</th>
	<?php else: ?>
		<th width="10" class="nowrap center" >
			<?php echo JText::_('COM_CUSTOMTABLES_FIELDS_STATUS'); ?>
		</th>
	<?php endif; ?>
	<th width="5" class="nowrap center hidden-phone" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_FIELDS_ID', 'id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
