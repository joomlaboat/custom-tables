<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage edit.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');
$componentParams = JComponentHelper::getParams('com_customtables');


require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
$LangMisc	= new ESLanguages;
$languages=$LangMisc->getLanguageList();

$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';

if(file_exists($phptagprocessor))
{
	$phptagprocessor=true;
}
else
	$phptagprocessor=false;

?>
<script type="text/javascript">
	// waiting spinner
	var outerDiv = jQuery('body');
	jQuery('<div id="loading"></div>')
		.css("background", "rgba(255, 255, 255, .8) url('components/com_customtables/assets/images/import.gif') 50% 15% no-repeat")
		.css("top", outerDiv.position().top - jQuery(window).scrollTop())
		.css("left", outerDiv.position().left - jQuery(window).scrollLeft())
		.css("width", outerDiv.width())
		.css("height", outerDiv.height())
		.css("position", "fixed")
		.css("opacity", "0.80")
		.css("-ms-filter", "progid:DXImageTransform.Microsoft.Alpha(Opacity = 80)")
		.css("filter", "alpha(opacity = 80)")
		.css("display", "none")
		.appendTo(outerDiv);
	jQuery('#loading').show();
	// when page is ready remove and show
	jQuery(window).load(function() {
		jQuery('#customtables_loader').fadeIn('fast');
		jQuery('#loading').hide();
		
		
		
	});
</script>
<div id="customtables_loader" style="display: none;">
<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int) $this->item->id.$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
<div id="jform_title"></div>
<div class="form-horizontal">

	<?php echo JHtml::_('bootstrap.startTabSet', 'tablesTab', array('active' => 'details')); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'tablesTab', 'details', JText::_('COM_CUSTOMTABLES_TABLES_DETAILS', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tablename'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tablename'); ?></div>
				</div>

				<hr/>

				<?php

				$morethanonelang=false;
				foreach($languages as $lang)
				{
					$id='tabletitle';
					if($morethanonelang)
					{
						$id.='_'.$lang->sef;

						$cssclass='text_area';
						$att='';
					}
					else
					{
						$cssclass='text_area required';
						$att=' required aria-required="true"';
					}

					$item_array=(array)$this->item;
					$vlu='';

					if(isset($item_array[$id]))
						$vlu=$item_array[$id];

					echo '
					<div class="control-group">
						<div class="control-label">'.$this->form->getLabel('tabletitle').'</div>
						<div class="controls">
							<input type="text" name="jform['.$id.']" id="jform_'.$id.'"  value="'.$vlu.'" class="'.$cssclass.'"     placeholder="Table Title"   maxlength="255" '.$att.' />
							<b>'.$lang->title.'</b>
						</div>

					</div>
					';

					$morethanonelang=true; //More than one language installed
				}


				?>


				<hr/>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tablecategory'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tablecategory'); ?></div>
				</div>


				<?php // echo JLayoutHelper::render('tables.details_left', $this); ?>
			</div>
		</div>

	<?php echo JHtml::_('bootstrap.endTab'); ?>


	<?php



		$morethanonelang=false;
		foreach($languages as $lang)
		{
			$id='description';
			if($morethanonelang)
				$id.='_'.$lang->sef;

			JHtml::_('bootstrap.addTab', 'tablesTab', $id, JText::_('COM_CUSTOMTABLES_TABLES_DESCRIPTION', true).' <b>'.$lang->title.'</b>');
			echo '
			<div id="'.$id.'" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">';

			$editor = JFactory::getEditor();

			$item_array=(array)$this->item;
			$vlu='';

			if(isset($item_array[$id]))
				$vlu=$item_array[$id];

			echo $editor->display('jform['.$id.']',$vlu, '100%', '300', '60', '5');

			echo '
					</div>
				</div>
			</div>';
			$morethanonelang=true; //More than one language installed
		}

	?>

	<?php
	if($phptagprocessor):
	echo JHtml::_('bootstrap.addTab', 'tablesTab', 'advanced', JText::_('COM_CUSTOMTABLES_TABLES_ADVANCED', true)); ?>

	<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('customphp'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('customphp'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('allowimportcontent'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('allowimportcontent'); ?></div>
				</div>
			</div>
	</div>


	<?php echo JHtml::_('bootstrap.endTab');
	endif;
	?>

	<?php
	echo JHtml::_('bootstrap.addTab', 'tablesTab', 'dependencies', JText::_('COM_CUSTOMTABLES_TABLES_DEPENDENCIES', true));
	require_once('_dependencies.php');
	?>

	<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<?php
				echo renderDependencies($this->item->id,$this->item->tablename);
				?>

			</div>
	</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>

	<?php if ($this->canDo->get('core.admin')) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'tablesTab', 'permissions', JText::_('COM_CUSTOMTABLES_TABLES_PERMISSION', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<fieldset class="adminform">
					<div class="adminformlist">
					
					
					<?php 
					
					foreach ($this->form->getFieldset('accesscontrol') as $field): ?>
						<div>
							<?php echo $field->label; echo $field->input;?>
						</div>
						<div class="clearfix"></div>
					<?php endforeach; ?>
					</div>
				</fieldset>
			</div>
		</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>

	<?php echo '<!-- end tab set -->'.JHtml::_('bootstrap.endTabSet').'<!-- end of the end of the tab set ;-) -->'; ?>

	<div>
		<input type="hidden" name="task" value="tables.edit" />
		<input type="hidden" name="originaltableid" value="<?php echo $this->item->id; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</div>

<div class="clearfix"></div>
<?php echo JLayoutHelper::render('tables.details_under', $this); ?>
</form>
</div>
