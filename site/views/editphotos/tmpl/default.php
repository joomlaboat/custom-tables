<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

 $document = JFactory::getDocument();
 $document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/imagegallery.js"></script>');

$max_file_size=JoomlaBasicMisc::file_upload_max_size();

 $jinput = JFactory::getApplication()->input;

	$user = JFactory::getUser();
 $userid = $user->get('id');

 if((int)$userid==0)
			die();

	$images=$this->Model->getPhotoList();

	$idList=array();

	foreach($images as $image)
 		$idList[]=$image->photoid;

?>
<style>
.MainImage{
 border-color: #ff0000;
 border-style: solid;

}
</style>
<script>
var idList = [<?php echo implode(',',$idList) ?>];
</script>

<h3><?php echo $this->Model->Listing_Title; ?></h3>
<!--<h2><?php echo $this->Model->GalleryTitle; ?></h2>   -->

<form action="index.php" method="POST" name="eseditphotos" id="eseditphotos" enctype="multipart/form-data">
<?php

	$toolbar='
	<div style="height:40px;">
	<div style="float:left;">
	<!--<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ADD_PHOTO').'" onClick=\'ShowAddPhoto()\'>-->
	<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SAVE_ORDER').'" onClick=\'SaveOrder()\'>
	<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FINISH').'" onClick=\'this.form.task.value="cancel";this.form.submit()\'>
	</div>
	<div style="float:right;">
	<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE').'" onClick=\'DeletePhotos()\'>
	</div>
	</div>
	';


?>


	<fieldset class="adminform" >
		<legend><?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PHOTO_MANAGER" ); ?></legend>




	<div name="addphotoblock" id="addphotoblock" style="display: block;">
		<h4><?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_ADD_NEW_PHOTO" ); ?></h4>
		<table border="0" align="center" cellpadding="3" width="100%" class="bigtext">
   <tbody>
		<tr>
			<td valign="top"><?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_UPLOAD_PHOTO" ); ?>:<br/></td>
			<td valign="top">
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_file_size; ?>" />
				<input name="uploadedfile" type="file" /><input type="button" class="button" value="<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_UPLOAD_PHOTO" ); ?>" onClick='this.form.task.value="add";this.form.submit()'>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_MIN_SIZE" ); ?>: 90px x 90px<br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_MAX_SIZE" ); ?>: 1000px x 1000px<br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE" ).': '.JoomlaBasicMisc::formatSizeUnits($max_file_size); ?><br/>
				<?php echo JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_FORMAT" ); ?>: JPEG, GIF, PNG, WEBP
			</td>
		</tr>
  </tbody>
	</table><br/>
	</div>

<?php
	if(count($images)>0)
  drawPhotos($images, $this->Model);

?>




	<input type="hidden" name="option" value="com_customtables" />
	<input type="hidden" name="view" value="editphotos" />
	<input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->get('Itemid',0,'INT'); ?>" />
	<input type="hidden" name="returnto" value="<?php echo JFactory::getApplication()->input->get('returnto','','BASE64');; ?>" />

	<input type="hidden" name="vlu" id="vlu" value="" />
	<input type="hidden" name="task" id="photoedit_task" value="" />
	<input type="hidden" name="photoids" id="photoids" value="" />
	<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $this->Model->listing_id; ?>" />
	<input type="hidden" name="establename" id="establename" value="<?php echo $this->Model->establename; ?>" />
	<input type="hidden" name="galleryname" id="galleryname" value="<?php echo $this->Model->galleryname; ?>" />

	</fieldset>
	<br/>
	<?php
	echo $toolbar;
	?>

</FORM>

<?php

	function drawPhotos(&$images, &$Model)
	{

		$htmlout='

		<h2>'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_LIST_OF_FOTOS' ).'</h2>
		<table width="100%" border="0" cellpadding="5" cellspacing="5">
			<thead>
			<tr>
			<td valign="top" align="center"><input type="checkbox" name="SelectAllBox" id="SelectAllBox" onClick=SelectAll(this.checked) align="left" style="vertical-align:top";></td>
			<td valign="top" align="center"></td>
			<td valign="top" align="center"></td>
			</tr>
			<tr><td colspan="3"><hr></td></tr>
			</thead>
			<tbody>
		';



		$i=0;
		$c=0;
		foreach($images as $image)
		{

			$htmlout.='<tr>';

			$imagefile=$Model->imagefolderweb.'/'.$Model->imagemainprefix.$Model->estableid.'_'.$Model->galleryname.'__esthumb_'.$image->photoid.'.jpg';
			$imagefileoriginal=$Model->imagefolderweb.'/'.$Model->imagemainprefix.$Model->estableid.'_'.$Model->galleryname.'__original_'.$image->photoid.'.'.$image->photo_ext;


			$htmlout.='
			<td valign="top" align="center">
			<input type="checkbox" name="esphoto'.$image->photoid.'" id="esphoto'.$image->photoid.'" align="left" style="vertical-align:top";>
			</td>

			<td'.($c==0 ? ' class="MainImage" ' : '').' width="170" align="center">
			<a href="'.$imagefileoriginal.'" rel="shadowbox"><img src="'.$imagefile.'" border="0" alt="'.$image->title.'" title="'.$image->title.'" width="150" height="150" /></a>
			</td>

			<td valign="top" align="left">

			<table border="0" cellpadding="5" style="margin-left:5px;">';

			$htmlout.='<tr>
			<td>'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_TITLE" ).': </td><td><input type="text"  style="width: 150px;" name="esphototitle'.$image->photoid.'" id="esphototitle'.$image->photoid.'" value="'.$image->title.'"></td>
			</tr>
			<tr>
			<td>'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_ORDER" ).': </td><td><input type="text"  style="width: 100px;" name="esphotoorder'.$image->photoid.'" id="esphotoorder'.$image->photoid.'" value="'.$image->ordering.'"></td>
			</tr>
			</table>


			</td>';

			$c++;

			$htmlout.='</tr><tr><td colspan="3"><hr></td></tr>';

		}


		$htmlout.='

		</tbody>
		</table><br/><br/>


		';

echo $htmlout;

	}


?>
