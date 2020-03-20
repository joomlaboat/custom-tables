<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		customtables.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

defined('_JEXEC') or die('Restricted access');

JHTML::addIncludePath(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');
$isNew = $this->item->id == 0;
?>

<?php 


/*
<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton)
	{
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		// do field validation
		if (trim(form.optionname.value) == "") {
			alert( "<?php echo JText::_( 'You Must Provide a Option Name.', true ); ?>" );
		}
		else {
			submitform( pressbutton );
		}
	}

</script>
*/ ?>
<?php // echo JRoute::_('index.php?option=com_customtables'); ?>

<form id="adminForm" action="index.php" method="post" class="form-inline" enctype="multipart/form-data">
<?php /* <form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">*/ ?>

		<legend><?php echo JText::_( 'Custom Tables - Option Details' ); ?></legend>

		<div class="form-horizontal">
			<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('optionname'); ?></div>
					<div class="controls"><?php echo ($isNew ? $this->form->getInput('optionname') : $this->item->optionname); ?></div>
			</div>

				<?php
				$row_lang=(array)$this->item;
				$morethanonelang=false;
				foreach($this->LanguageList as $lang)
				{
					$id='title';
					if($morethanonelang)
						$id.='_'.$lang->sef;
					else
						$morethanonelang=true; //More than one language installed

					?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('title').' ('.$lang->caption.')'; ?></div>
						<div class="controls"><input type="text" name="jform[<?php echo $id; ?>]" id="jform_<?php echo $id; ?>" class="inputbox" size="40" value="<?php echo $row_lang[$id]; ?>" /></div>
					</div>

				<?php
				}

				?>

			<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('parentid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('parentid'); ?></div>
			</div>

			<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('isselectable'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('isselectable'); ?></div>
			</div>

			<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('optionalcode'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('optionalcode'); ?></div>
			</div>

			<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('link'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('link'); ?></div>
			</div>

		</div>


			<input type="hidden" name="option" value="com_customtables" />
			<?php echo JHtml::_('form.token'); ?>
<?php

	?>

	<input type="hidden" name="task" value="options.edit" />
	<input type="hidden" name="id" value="<?php echo $this->item->id; ?>" />


</form>

