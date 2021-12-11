 <?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditPhotos extends JViewLegacy
{
	function display($tpl = null)
	{
		$user = JFactory::getUser();
        $userid = $user->get('id');
		if((int)$userid==0)
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			return false;
		}
		
		$this->Model = $this->getModel();
		$this->images = $this->Model->getPhotoList();
		
		$this->idList=array();

		foreach($this->images as $image)
			$this->idList[]=$image->photoid;
		
		$this->max_file_size=JoomlaBasicMisc::file_upload_max_size();
		
		$this->jinput = JFactory::getApplication()->input;
		
		$this->Listing_Title = $this->Model->Listing_Title;
		
		$this->listing_id = $this->Model->listing_id;
		$this->galleryname = $this->Model->galleryname;

		parent::display($tpl);
	}	
	
	function drawPhotos(&$images)
	{
		if(count($this->images) == )
			return '';

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
			$htmlout.='
				<tr>';

			$imagefile=$this->Model->imagefolderweb.'/'.$this->Model->imagemainprefix.$this->Model->ct->Table->tableid.'_'
				.$this->Model->galleryname.'__esthumb_'.$image->photoid.'.jpg';
				
			$imagefileoriginal = $this->Model->imagefolderweb.'/'.$this->Model->imagemainprefix
				. $this->Model->ct->Table->tableid.'_'.$this->Model->galleryname.'__original_'.$image->photoid.'.'.$image->photo_ext;

			$htmlout.='
					<td valign="top" align="center">
						<input type="checkbox" name="esphoto'.$image->photoid.'" id="esphoto'.$image->photoid.'" align="left" style="vertical-align:top";>
					</td>

					<td'.($c==0 ? ' class="MainImage" ' : '').' width="170" align="center">
						<a href="'.$imagefileoriginal.'" rel="shadowbox"><img src="'.$imagefile.'" border="0" alt="'.$image->title.'" title="'.$image->title.'" width="150" height="150" /></a>
					</td>

					<td valign="top" align="left">
						<table border="0" cellpadding="5" style="margin-left:5px;">
							<tbody>
								<tr>
									<td>'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_TITLE" ).': </td>
									<td><input type="text"  style="width: 150px;" name="esphototitle'.$image->photoid.'" id="esphototitle'.$image->photoid.'" value="'.$image->title.'"></td>
								</tr>
								<tr>
									<td>'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_ORDER" ).': </td>
									<td><input type="text"  style="width: 100px;" name="esphotoorder'.$image->photoid.'" id="esphotoorder'.$image->photoid.'" value="'.$image->ordering.'"></td>
								</tr>
							</tbody>
						</table>
					</td>';

			$c++;

			$htmlout.='
				</tr>
				<tr><td colspan="3"><hr></td></tr>';
		}

		$htmlout.='
			</tbody>
		</table>';

		return $htmlout;
	}
}
