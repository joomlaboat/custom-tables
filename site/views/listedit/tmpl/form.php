<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;

$max_file_size=JoomlaBasicMisc::file_upload_max_size();
?>


<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		// do field validation
		if (trim(form.optionname.value) == "") {
			alert( "<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PROVIDE_OPTION_NAME', true ); ?>" );
		}
		else {
			submitform( pressbutton );
		}
	}
</script>
<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	
	
	<table width="100%">
		<tr>
			<td align="left">
				<h2>CustomTables - Structure (<?php echo ($this->optionRecord->id!=0 ? 'Edit' : 'New'); ?>)</h2>
			</td>
			<td nowrap="nowrap" align="right">
				<a href="#" onclick="javascript:Joomla.submitbutton('save')" class="toolbar"><img src="<?php JURI::root(true); ?>"/components/com_customtables/libraries/customtables/media/images/icons/save.png" alt="Save" title="Save" /></a>
				<a href="#" onclick="javascript:Joomla.submitbutton('cancel')" class="toolbar"><img src="<?php JURI::root(true); ?>"/components/com_customtables/libraries/customtables/media/images/icons/cancel.png" alt="Cancel" title="Cancel" /></a>
				
			</td>

		</tr>
	</table>
	
	
	
<div>
	
	
	
	<fieldset class="adminform">

		
	
			<table class="admintable" cellspacing="1">
				<tr>
					<td width="150" class="key">
						<label for="optionname">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTION_NAME'); ?>
							
						</label><br/>
						<small>This will be visible if Title not set.</small>
					</td>
					<td>
						<?php
						
							$isReadOnly=false;
							
							if(!$this->isNew)
								$isReadOnly=true;
							
							if(!(strpos($this->optionRecord->optionname,' ')===false))
								$isReadOnly=false;
								
							//$isReadOnly=false;
							
						?>
						<input type="text" name="optionname" id="optionname" class="inputbox" size="40" value="<?php echo $this->optionRecord->optionname; ?>"
																													 
						<?php echo !$isReadOnly ? '' : ' READONLY ' ; ?>																							 
						 />
						<?php echo !$isReadOnly ? '' : ' Readonly' ; ?>																							 
					</td>
				</tr>
				
				
				<?php
				
				$row_array = (array)$this->optionRecord;
			
				$firstlanguage=true;
				foreach($LanguageList as $lang)
				{
					if($firstlanguage)
					{
						$postfix='';
						$firstlanguage=false;
					}
					else
						$postfix='_'.$lang->sef;	
			
					$vlu=$row_array['title'.$postfix];

					?>
								<tr>
					<td width="150" class="key">
						<label for="title<?php echo $postfix; ?>">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_TITLE').$lang->caption; ?>
							
						</label><br/>
					</td>
					<td>
						<input type="text" name="title<?php echo $postfix; ?>" id="title<?php echo $postfix; ?>" class="inputbox" size="40" value="<?php echo $vlu; ?>" />
					</td>
				</tr>
				
				
				
				<?php
				}
			
				?>
				
				

				<tr>
					<td width="150" class="key">
						<label for="parentid">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PARENT_FIELD'); ?>
							
						</label><br/>
					</td>
					<td>
						<?php
							echo JHTML::_('ESOptions.options', $this->row->id, 'parentid',$this->optionRecord->parentid);
						?>

					</td>
				</tr>
				
			
				<tr>
					<td width="150" class="key">
						<label for="isselectable">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IS_SELECTABLE' ); ?>
						</label>
					</td>
					<td>
						<input type="radio" value="1" name="isselectable" id="isselectable" size="40" <?php echo ($this->optionRecord->isselectable ? 'CHECKED' : ''); ?> >
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES');  ?>
							
						<input type="radio" value="0" name="isselectable" id="isselectable" size="40" <?php echo (!$this->optionRecord->isselectable ? 'CHECKED' : ''); ?> >
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');  ?>
							
						
					</td>
				</tr>
				
				
				
				
					
				<tr>
					<td width="150" class="key">
						<label>
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE'); ?>
							
						</label><br/>
						
					</td>
					<td>
						<table border="0" align="center" cellpadding="3" width="100%" class="bigtext">
		
		<tr>
			<td valign="top">
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_file_size; ?>" />
				<input name="imagefile" type="file" />
				<BR><BR>
				<?php echo JoomlaBasicMisc::JTextExtended( "MIN SIZE" ); ?>: 10px x 10px<br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "MAX SIZE" ); ?>: 1000px x 1000px<br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE" ).': '.JoomlaBasicMisc::formatSizeUnits($max_file_size); ?><br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "FORMAT" ); ?>: JPEG, GIF, PNG, WEBP

			</td>
			<td align="center" valign="top">
				<?php
					
					$imagefile_='../images/esoptimages/_esthumb_'.$this->optionRecord->image;
					$imagefile_original_='../images/esoptimages/_original_'.$this->optionRecord->image;
					
					if(file_exists($imagefile_.'.jpg'))
						$imagefile=$imagefile_.'.jpg';
					elseif(file_exists($imagefile_.'.png'))
						$imagefile=$imagefile_.'.png';
					elseif(file_exists($imagefile_.'.webp'))
						$imagefile=$imagefile_.'.webp';
					else
						$imagefile='';
						
					//original
					
					if(file_exists($imagefile_original_.'.jpg'))
						$imagefile_original=$imagefile_original_.'.jpg';
					elseif(file_exists($imagefile_original_.'.png'))
						$imagefile_original=$imagefile_original_.'.png';
					else
						$imagefile_original='';
						
					if($imagefile!='')
					    echo '<a href="'.$imagefile_original.'" target="_blank"><img src="'.$imagefile.'" width="150" border=0 /></a><br/>
						<input type="checkbox" name="image_delete" value="true"> Delete Image';
						
					
				?>
			</td>
			<td valign="top">
				
				<?php
					if($imagefile!='')
					{
						
					$wh=getimagesize($imagefile_original);
					echo '<b>Image Sizes:</b>
					<ul>
							<li>_esthumb (150px x 150px)</li>
							<li>_original ('.$wh[0].'px x '.$wh[1].'px)</li>
					</ul>';
					$imageparams=$this->optionRecord->imageparams;
					
					
					if(strlen($imageparams)==0)
					{
						$pid=$this->optionRecord->parentid;
						$imageparams=Tree::getHeritageInfo($pid, 'imageparams');
					}	
					/*	
					if($imageparams!='')
					{
						echo '<b>Custom Sizes:</b>';	
					
					
						$imagemethods=new CustomTablesImageMethods;
						$cleanOptions=$imagemethods->getCustomImageOptions($imageparams);
						echo '<ul>';
						foreach($cleanOptions as $imagesize)
						{
							echo '<li>'.$imagesize[0].' ('.$imagesize[1].'px x '.$imagesize[2].'px)'.'</li>';
						}
						
						echo '</ul>';
						
					}
					
						*/
					
					}
				?>
				
			</td>
		</tr>
		</table>
					</td>
				</tr>
				
				<tr>
					<td width="150" class="key">
						<label for="imageparams">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_PARAMETERS'); ?>
							
						</label><br/>
					</td>
					<td>
						<input type="text" name="imageparams" id="imageparams" class="inputbox" size="40" value="<?php echo $this->optionRecord->imageparams; ?>" />
						size_name, width, height; ... <i>example: logo, 200,0</i> 0 means unset, so the proportions will be kept.
					</td>
				</tr>
				
				
				
				
				
				
				<tr>
					<td class="key">
						<label for="optionalcode">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTIONAL_CONTENT'); ?>
							<br/><small>Used for Home Page layout.</small>
						</label>
					</td>
					<td>
							<?php
								//get the editor
			 					$editor = JFactory::getEditor();
			 					echo $editor->display( 'optionalcode',$this->optionRecord->optionalcode, '350', '300', '60', '20')
							?>
					</td>
				</tr>
				<tr>
					<td width="150" >

					</td>
				</tr>
				
				<tr>
					<td width="150" class="key">
						<label for="link">
							<?php echo JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTIONAL_LINK'); ?>
							
						</label><br/>
						
					</td>
					<td>
						<input type="text" name="link" id="link" class="inputbox" size="40" value="<?php echo $this->optionRecord->link; ?>" />
					</td>
				</tr>
				
				
				
				
			
				
				
				
				
				
				
				

			</table>
	</fieldset>
</div>
	<input type="hidden" name="option" value="com_customtables" />
	<input type="hidden" name="view" value="list" />
	
	<input type="hidden" name="id" value="<?php echo $this->optionRecord->id; ?>" />
	<input type="hidden" name="task" value="" />
	
	<input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->get('Itemid',0,'INT'); ?>" />


</form>
