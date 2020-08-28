<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_ct
{
    public static function ResolveStructure(&$Model,&$htmlresult)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('resolve',$options,$htmlresult,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$value=$options[$i];



			$vlu=implode(',',$Model->es->getMultyValueTitles($value,$Model->langpostfix,1, ' - '));
			$htmlresult=str_replace($fItem,$vlu,$htmlresult);
			$i++;
		}
	}
    
    public static function groupCustomTablesParents(&$Model,$esvaluestring, $rootparent)
	{
		$GroupList=explode(',',$esvaluestring);
		$GroupNames=array();
		$Result=array();
		foreach($GroupList as $GroupItem)
		{
			if(strlen($GroupItem)>0)
			{
				$TriName=explode('.',$GroupItem);

				if(count($TriName)>=3)
				{
					if(!in_array($TriName[1],$GroupNames))
					{
						$GroupNames[]=$TriName[1];
						$Result[$TriName[1]][] =$Model->es->getOptionTitleFull($rootparent.'.'.$TriName[1].'.',$Model->langpostfix);
					}
					$Result[$TriName[1]][] = $Model->es->getOptionTitleFull($rootparent.'.'.$TriName[1].'.'.$TriName[2].'.',$Model->langpostfix);
				}
			}
		}

		return array_values($Result);
	}
}
