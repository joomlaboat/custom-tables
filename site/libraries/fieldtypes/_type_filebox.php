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
	public static function process($FileBoxRows, &$field, $listing_id, array $option_list)
    {
		$filesrclistarray=array();

		foreach($FileBoxRows as $filerow)
		{
			$filename=$field->ct->Table->tableid.'_'.$field->fieldname.'_'.$filerow->fileid.'.'.$filerow->file_ext;
			$filesrclistarray[]=CT_FieldTypeTag_file::process($filename, $field, $option_list, $listing_id);
		}
		
		$listformat = '';
		if(isset($option_list[4]))
			$listformat = $option_list[4];
		
		switch($listformat)
		{
			case 'ul':
			
				$filetaglistarray=array();

				foreach($filesrclistarray as $filename)
					$filetaglistarray[]='<li>'.$filename.'</li>';

				return '<ul>'.implode('',$filetaglistarray).'</ul>';
				break;
				
			case ',':
				return implode(',',$filesrclistarray);
				break;
			
			case ';':
				return implode(';',$filesrclistarray);
				break;
			
			default:
				//INCLUDING OL
				$filetaglistarray=array();

				foreach($filesrclistarray as $filename)
					$filetaglistarray[]='<li>'.$filename.'</li>';

				return '<ol>'.implode('',$filetaglistarray).'</ol>';
				break;
		}
    }
	
	public static function getFileBoxRows($tablename,$fieldname, $listing_id)
	{
		$db = JFactory::getDBO();
		$fileboxtablename='#__customtables_filebox_'.$tablename.'_'.$fieldname;

		$query = 'SELECT fileid, file_ext FROM '.$fileboxtablename.' WHERE listingid='.(int)$listing_id.' ORDER BY fileid';
		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
