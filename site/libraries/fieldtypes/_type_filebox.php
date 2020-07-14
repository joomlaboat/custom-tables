<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_filebox
{
	public static function getFileBoxRows($establename,$fileboxname, $listing_id)
	{
		$db = JFactory::getDBO();
		$fileboxtablename='#__customtables_filebox_'.$establename.'_'.$fileboxname;

		$query = 'SELECT fileid, file_ext FROM '.$fileboxtablename.' WHERE listingid='.(int)$listing_id.' ORDER BY fileid';
		$db->setQuery($query);
		if (!$db->query())    die('getFileBoxRows: '. $db->stderr());
		$filerows=$db->loadObjectList();
		
		return $filerows;
	}

	public static function getFileBoxSRC(&$Model,$FileBoxRows, $id,$fileboxname,$typeparams,&$filesrclist,&$filetaglist)
	{

		$pair=explode(',',$typeparams);
		$filefolder='/images/'.$pair[1];

		//the returnedl list should be separated by ;
		$filesrclistarray=array();
		$filetaglistarray=array();


		foreach($FileBoxRows as $filerow)
		{
			$shortname=$Model->estableid.'_'.$fileboxname.'_'.$filerow->fileid.'.'.$filerow->file_ext;
			$filename=$filefolder.'/'.$shortname;

			$filesrclistarray[]=$filename;
			$target='_blank';
			$iconsize='32';
			
			
			$parts=explode('.',$filename);
            $fileextension=end($parts);
            $icon='/components/com_customtables/images/fileformats/'.$iconsize.'px/'.$fileextension.'.png';
                
            $filetaglistarray[]='<li><a href="'.$filename.'"'.$target.'><img src="'.$icon.'" alt="'.$shortname.'" title="'.$shortname.'" /><span>'.$shortname.'</span></a></li>';

		}

		$filesrclist=implode(';',$filesrclistarray);
		$filetaglist='<ul>'.implode('',$filetaglistarray).'</ul?';
		return true;
	}

}
