<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage views/fields/tmpl/edit.php
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

$document = JFactory::getDocument();
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/ajax.js"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/typeparams.js"></script>');

$input	= JFactory::getApplication()->input;

if($input->getCmd('extratask','')=='updateimages')
{
	$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'extratasks'.DIRECTORY_SEPARATOR;
	require_once($path.'extratasks.php');
	extraTasks::prepareJS();
}


$componentParams = JComponentHelper::getParams('com_customtables');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'types.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
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
<div id="fieldsData" style="display:none;"><?php echo json_encode(ESFields::getFields($this->tableid,true)); ?></div>
<script type="text/javascript">
	//websiteroot="<?php echo JURI::root(true); ?>";
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

	<?php

	if($phptagprocessor)
	{
		echo '
		proversion=true;
';
	}
	;
	
	echo 'all_tables='.$this->getAllTables().';';
	?>

	var json=JSON.parse(document.getElementById("fieldsData").innerHTML);
	wizardFields=Array.from(json);

</script>
<div id="customtables_loader" style="display: none;">

<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int)($this->item->id).$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

<div class="form-horizontal">

	<?php echo JHtml::_('bootstrap.startTabSet', 'fieldsTab', array('active' => 'general')); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'fieldsTab', 'general', JText::_('COM_CUSTOMTABLES_FIELDS_GENERAL', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tableid',null,$this->tableid); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('fieldname'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('fieldname'); ?></div>
				</div>

				<?php if($this->table_row->customtablename!=''): ?>
				<hr/>
				<p><?php echo JText::_('COM_CUSTOMTABLES_FIELDS_THIS_IS_THIRDPARTY_FIELD', true).': "'.$this->table_row->customtablename.'"';?></p>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('customfieldname'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('customfieldname'); ?></div>
				</div>
				
				<?php endif; ?>
				
				<hr/>

				<?php
				
				$morethanonelang=false;
				foreach($languages as $lang)
				{
					$id='fieldtitle';
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
						<div class="control-label">'.$this->form->getLabel('fieldtitle').'</div>
						<div class="controls">
							<input type="text" name="jform['.$id.']" id="jform_'.$id.'"  value="'.$vlu.'" class="'.$cssclass.'"     placeholder="Field Title"   maxlength="255" '.$att.'  />
							<b>'.$lang->title.'</b>
						</div>

					</div>
					';

					$morethanonelang=true; //More than one language installed
				}
				?>

				<hr/>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('typeparams'); ?></div>
					<div class="controls"><div class="typeparams_box" id="typeparams_box"></div></div>
				</div>

				<div class="control-group">
					<div class="control-label"></div>
					<div class="controls"><?php echo $this->form->getInput('typeparams'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('parent'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('parent'); ?></div>
				</div>
			</div>
		</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'fieldsTab', 'optional', JText::_('COM_CUSTOMTABLES_FIELDS_OPTIONAL', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php echo JLayoutHelper::render('fields.optional_left', $this); ?>
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

			JHtml::_('bootstrap.addTab', 'fieldsTab', $id, JText::_('COM_CUSTOMTABLES_FIELDS_DESCRIPTION', true).' <b>'.$lang->title.'</b>');
			echo '
			<div id="'.$id.'" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">';

			$editor = JFactory::getEditor();

			$item_array=(array)$this->item;
			$vlu='';

			if(isset($item_array[$id]))
				$vlu=$item_array[$id];

				echo '<textarea rows="10" cols="20" name="jform['.$id.']" id="jform_'.$id.'" style="width:100%;height:100%;"
				class="text_area"  placeholder="Field Description" >'.$vlu.'</textarea>';

			echo '
					</div>
				</div>
			</div>';
			$morethanonelang=true; //More than one language installed
		}

	echo JHtml::_('bootstrap.endTabSet'); ?>

	<div>
		<input type="hidden" name="task" value="fields.edit" />
		<input type="hidden" name="tableid" value="<?php echo $this->tableid; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>

	<script>
		updateTypeParams("jform_type","jform_typeparams","typeparams_box");
	</script>

</div>

<div id="ct_fieldtypeeditor_box" style="display: none;"><?php
	$attributes=array('name'=>'ct_fieldtypeeditor','id'=>'ct_fieldtypeeditor','directory'=>'images','recursive'=>true, 'label'=>'Select Folder','readonly'=>false);
	echo CTTypes::getField('folderlist', $attributes,null)->input;
?></div>

</form>
</div>

  <!-- Modal content -->
  <div id="ctModal" class="ctModal">
  <div id="ctModal_box" class="ctModal_content">
    <span id="ctModal_close" class="ctModal_close">&times;</span>
	<div id="ctModal_content"></div>
  </div>
	</div>
  <!-- end of the modal -->