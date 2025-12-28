<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

$document = Factory::getApplication()->getDocument();
$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/imagegallery.js"></script>');

?>
<style>
	.MainImage {
		border-color: #ff0000;
		border-style: solid;
	}
</style>
<script>
	var idList = [<?php echo implode(',', $this->idList) ?>];
</script>

<h3><?php echo $this->Listing_Title; ?></h3>

<form action="index.php?option=com_customtables&view=editphotos&Itemid=<?php echo common::inputGetInt('Itemid', 0); ?>"
	  method="POST" name="eseditphotos" id="eseditphotos" enctype="multipart/form-data">
	<?php

	$toolbar = '
	<div style="height:40px;">
	<div style="float:left;">
	<!--<input type="button" class="button" value="' . common::translate('COM_CUSTOMTABLES_ADD_PHOTO') . '" onClick=\'ShowAddPhoto()\'>-->
	<input type="button" class="button" value="' . common::translate('COM_CUSTOMTABLES_SAVE_ORDER') . '" onClick=\'SaveOrder()\'>
	<input type="button" class="button" value="' . common::translate('COM_CUSTOMTABLES_FINISH') . '" onClick=\'this.form.task.value="cancel";this.form.submit()\'>
	</div>
	<div style="float:right;">
	<input type="button" class="button" value="' . common::translate('COM_CUSTOMTABLES_DELETE') . '" onClick=\'DeletePhotos()\'>
	</div>
	</div>
	';


	?>

	<fieldset class="adminform">
		<legend><?php echo common::translate('COM_CUSTOMTABLES_PHOTO_MANAGER'); ?></legend>

		<div name="addphotoblock" id="addphotoblock" style="display: block;">
			<h4><?php echo common::translate('COM_CUSTOMTABLES_ADD_NEW_PHOTO'); ?></h4>
			<table style="width:100%" class="bigtext">
				<tbody>
				<tr>
					<td><?php echo common::translate('COM_CUSTOMTABLES_UPLOAD_PHOTO'); ?>
						:<br/></td>
					<td>
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $this->max_file_size; ?>"/>
						<input name="uploadedfile" type="file"/><input type="button" class="button"
																	   value="<?php echo common::translate('COM_CUSTOMTABLES_UPLOAD_PHOTO'); ?>"
																	   onClick='this.form.task.value="add";this.form.submit()'>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php echo common::translate('COM_CUSTOMTABLES_PERMITTED_MAX_FILE_SIZE') . ': ' . CTMiscHelper::formatSizeUnits($this->max_file_size); ?>
						<br/>
						<?php echo common::translate('COM_CUSTOMTABLES_FORMAT'); ?>: JPEG, GIF, PNG, WEBP
					</td>
				</tr>
				</tbody>
			</table>
			<br/>
		</div>

		<?php echo $this->drawPhotos(); ?>

		<input type="hidden" name="option" value="com_customtables"/>
		<input type="hidden" name="view" value="editphotos"/>
		<input type="hidden" name="Itemid" value="<?php echo common::inputGet('Itemid', 0, 'INT'); ?>"/>
		<input type="hidden" name="returnto" value="<?php echo common::inputGet('returnto', '', 'BASE64');; ?>"/>

		<input type="hidden" name="vlu" id="vlu" value=""/>
		<input type="hidden" name="task" id="photoedit_task" value=""/>
		<input type="hidden" name="photoids" id="photoids" value=""/>
		<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $this->listing_id; ?>"/>
		<input type="hidden" name="galleryname" id="galleryname" value="<?php echo $this->galleryname; ?>"/>

	</fieldset>
	<br/>
	<?php
	echo $toolbar;
	?>
</form>
