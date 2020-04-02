<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');

class ESLayouts
{

    public static function getLayoutID($layoutname)
    {
		$db = JFactory::getDBO();

		if($tablename=='')
			return 0;

		$query = 'SELECT id FROM #__customtables_layouts AS s WHERE layoutname="'.$layoutname.'" LIMIT 1';
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if(count($rows)!=1)
            return 0;

		return $rows[0]->id;
	}

    public static function getLayout($layoutname,&$type)
	{

		if($layoutname=='')
			return '';

		$db = JFactory::getDBO();

		$query = 'SELECT id, layoutcode, UNIX_TIMESTAMP(changetimestamp) AS ts, layouttype FROM #__customtables_layouts WHERE layoutname="'.$layoutname.'" LIMIT 1';
		$db->setQuery( $query );
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		$row=$rows[0];
        $type=(int)$row['layouttype'];

		$content=ESLayouts::getLayoutFileContent($row['id'],$row['ts'],$layoutname);
		if($content!='')
			return $content;

		//Get all layouts recursevly
		$layoutcode=$row['layoutcode'];
		ESLayouts::processLayoutTag($layoutcode);
		return $layoutcode;
	}
	
	public static function processLayoutTag(&$htmlresult)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('layout',$options,$htmlresult,'{}');
        
        if(count($fList)==0)
            return false;
        
        require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
        
		$i=0;
		foreach($fList as $fItem)
		{
            $layoutname=$options[$i];
            $type='';
            $layout=ESLayouts::getLayout($layoutname,$type);
            
			$htmlresult=str_replace($fItem,$layout,$htmlresult);
			$i++;
		}
    }


	public static function getLayoutFileContent($id=0,$db_layout_ts=0,$layoutname)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'layouts';
		$filename=$layoutname.'.html';

		if (file_exists($path.DIRECTORY_SEPARATOR.$filename))
		{
			$file_ts=filemtime ($path.DIRECTORY_SEPARATOR.$filename);

			if($db_layout_ts==0)
			{
				$db = JFactory::getDBO();
				$query = 'SELECT UNIX_TIMESTAMP(changetimestamp) AS ts FROM #__customtables_layouts WHERE id='.$id.' LIMIT 1';
				$db->setQuery( $query );
				if (!$db->query())    die( $db->stderr());
				$recs = $db->loadAssocList( );
                if(count($recs)==0)
                    $db_layout_ts=0;
                else
                {
                    $rec=$recs[0];
                    $db_layout_ts=$rec['ts'];
                }
			}

			if($file_ts>$db_layout_ts)
			{

				$content=file_get_contents($path.DIRECTORY_SEPARATOR.$filename);

				$db = JFactory::getDBO();

				$query = 'UPDATE #__customtables_layouts SET layoutcode="'.addslashes($content).'",changetimestamp=FROM_UNIXTIME('.$file_ts.') WHERE id='.$id;

				$db->setQuery( $query );

				if (!$db->query()) {
					$this->setError( $db->getErrorMsg() );
					return false;
				}

				//$row->load($id);
				return $content;
			}
		}

		return '';
	}

    public static function storeAsFile(&$data)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'layouts';
		$filename=$data['layoutname'].'.html';

		file_put_contents($path.DIRECTORY_SEPARATOR.$filename, $data['layoutcode']);

		$file_ts=filemtime ($path.DIRECTORY_SEPARATOR.$filename);
		if($file_ts=='')
		{
			//No permission -  file not saved
		}
		else
		{
			$id=(int)$data['id'];
			if($id==0)
				$id=ESLayouts::getLayoutID($data['layoutname']);

			$db = JFactory::getDBO();
			$query = 'UPDATE #__customtables_layouts SET changetimestamp=FROM_UNIXTIME('.$file_ts.') WHERE id='.$id;
				$db->setQuery( $query );

			if (!$db->query())
			{
				$this->setError( $db->getErrorMsg() );
				return false;
			}
		}

		return $file_ts;

	}

}
?>
