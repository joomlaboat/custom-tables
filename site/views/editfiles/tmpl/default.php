<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

	$max_file_size=JoomlaBasicMisc::file_upload_max_size(); 
	$user = JFactory::getUser();
	$userid = $user->get('id');
		if((int)$userid==0)
			die();
	
	$files=$this->Model->getFileList();
	$idList=array();

	foreach($files as $file)
	{
		$idList[]=$file->fileid;
	}
	//<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('Save').'" onClick=\'SaveOrder()\'>
?>
<h2><?php echo $this->Model->FileBoxTitle; ?></h2>   

<form action="index.php?Itemid=<?php echo JFactory::getApplication()->input->get('Itemid',0,'INT'); ?>" method="POST" name="eseditfiles" id="eseditfiles" enctype="multipart/form-data">
<?php
//<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('Add File').'" onClick=\'ShowAddFile()\'>
	$toolbar='
	<div style="height:40px;">
	<div style="float:left;">
	
	
	<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('Finish').'" onClick=\'this.form.task.value="cancel";this.form.submit()\'>
	</div>
	<div style="float:right;">
	<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('Delete').'" onClick=\'DeleteFiles()\'>
	</div>
	</div>
	';
	
	echo $toolbar;
	
	
?>


	<fieldset class="adminform" >
		<legend><?php echo JoomlaBasicMisc::JTextExtended( "File Manager" ); ?></legend>
		

<script>

var idList = [];

<?php

	$i=0;
	foreach($idList as $h)
	{
	echo 'idList['.$i.']='.$h.';
	';
		$i++;
	}
	
?>


function DeleteFiles(fileid){
	var count=0;
	var fileids="";
	
	for(var i=0;i<idList.length;i++)
	{
		if(document.getElementById("esfile"+idList[i]).checked)
		{
			count++;
			fileids+="*"+idList[i];
		}
	}
	if(count==0)
	{
		alert("<?php echo JoomlaBasicMisc::JTextExtended( "Select Files First" ); ?>");
		return false;
	}
	
	if( confirm("<?php echo JoomlaBasicMisc::JTextExtended( "Are you sure to delete?" ); ?> "+count+" <?php echo JoomlaBasicMisc::JTextExtended( "file(s)" ); ?>?")){
		
		document.getElementById("fileedit_task").value="delete";
		document.getElementById("fileids").value=fileids;
		document.getElementById("eseditfiles").submit();
	}
	
	return true;
}
function SelectAll(s)
{
	
	
	for(var i=0;i<idList.length;i++)
	{
		document.getElementById("esfile"+idList[i]).checked=s;
		
	}
}
function SaveOrder(){
	
	document.getElementById("fileedit_task").value="saveorder";
	document.getElementById("eseditfiles").submit()
	
}
function ShowAddFile()
{
	var obj=document.getElementById("addfileblock");
	if(obj.style.display=="block")
		obj.style.display="none";
	else
		obj.style.display="block";
}

//<div name="addfileblock" id="addfileblock" style="display: <?php echo (count($files)>0 ? 'none' : 'block')?> ;">
//-->
</SCRIPT>
	<div name="addfileblock" id="addfileblock" style="display:block;">
		<h2><?php echo JoomlaBasicMisc::JTextExtended( "Add New File" ); ?></h2>
		<table border="0" align="center" cellpadding="3" width="100%" class="bigtext">
		<tr>
			<td valign="top"><?php echo JoomlaBasicMisc::JTextExtended( "Upload File" ); ?>:<br/></td>
			<td valign="top">
				<?php //<input type="hidden" name="max File Size" value="<?php echo $max_file_size;  ?>
				<input name="uploadedfile" type="file" /><input type="button" class="button" value="Upload" onClick='this.form.task.value="add";this.form.submit()'>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE" ).': '.JoomlaBasicMisc::formatSizeUnits($max_file_size); ?><br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "File Formats" ); ?>: <b><?php echo str_replace(' ',', ',$this->Model->allowedExtensions); ?></b>
			</td>
		</tr>
	</table><br/>
	</div>

<?php
	if(count($files)>0)	
		drawFiles($files, $this->Model);
	
?>


	
	<input type="hidden" name="option" value="com_customtables" />
	<input type="hidden" name="view" value="editfiles" />
	<input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->get('Itemid',0,'INT'); ?>" />
	<input type="hidden" name="returnto" value="<?php echo JFactory::getApplication()->input->get('returnto','','BASE64');; ?>" />
	
	<input type="hidden" name="vlu" id="vlu" value="" />
	<input type="hidden" name="task" id="fileedit_task" value="" />
	<input type="hidden" name="fileids" id="fileids" value="" />
	<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $this->Model->listing_id; ?>" />
	
	
	<input type="hidden" name="fileboxname" id="fileboxname" value="<?php echo $this->Model->fileboxname; ?>" />

	</fieldset>
	<br/>
	<?php
	//echo $toolbar;
	?>
	
</FORM>

<?php

	
	function drawFiles(&$files, &$Model)
	{
		$mainframe = JFactory::getApplication('site');
		
		$htmlout='
		
		<h2>'.JoomlaBasicMisc::JTextExtended( "List of Files" ).'</h2>
		<table width="100%" border="0">
			<thead>
			<tr>
			<th valign="top" align="center" style="width:40px;"><input type="checkbox" name="SelectAllBox" id="SelectAllBox" onClick=SelectAll(this.checked) align="left" style="vertical-align:top";> Select All</th>
			<th valign="top" align="center"></th>
			
			</tr>
			
			</thead>
			<tbody>
		';
		    
        
		
		$i=0;
		$c=0;
		foreach($files as $file)
		{
			$htmlout.='<tr>';

			$filename=$Model->estableid.'_'.$Model->fileboxname.'_'.$file->fileid.'.'.$file->file_ext;
			$filepath=$Model->fileboxfolderweb.'/'.$filename;
			
			
			
			$htmlout.='
			<td valign="top" align="center">
			<input type="checkbox" name="esfile'.$file->fileid.'" id="esfile'.$file->fileid.'" align="left" style="vertical-align:top">
			</td>
			
			<td align="left"><a href="'.$filepath.'">'.$filename.'</a></td>
			';

			$c++;
				$htmlout.='</tr>';				
			
		}

		
		$htmlout.='
		
		</tbody>
		</table><br/><br/>
		

		';
		        
        
		echo $htmlout;
        
            
	}


?>
