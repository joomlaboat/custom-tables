<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class FindSimilarImage
{
    static public function find($uploadedfile,$level_identity,$realtablename,$realfieldname,$ImageFolder,$additional_filter='')
    {
        if($level_identity<0)
            $level_identity=0;
	
    	$ci=new compareImages;
		
        $db = JFactory::getDBO();
        
        $query = 'SELECT '.$realfieldname.' AS photoid FROM '.$realtablename.' WHERE '.$realfieldname.'>0'.($additional_filter!='' ? ' AND '.$additional_filter : '');

    	$db->setQuery($query);
        $photorows=$db->loadObjectList();

        foreach($photorows as $photorow)
        {
    	    $photoid=$photorow->photoid;

            if($photoid!=0)
            {
                //foreach($ext_list as $ext)
                //{
                    $image_file=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$photoid.'.jpg';///.$ext;
                    if($image_file!=$uploadedfile)
                    {
                        if(file_exists($image_file))
                        {
                            $index=$ci->compare($uploadedfile,$image_file);
                            if($index<=$level_identity)
                                return $photoid;
                        }
                    }
                //}//for each
            }//if
        }//foreach($photorows as $photorow)
    }//function
}//class
