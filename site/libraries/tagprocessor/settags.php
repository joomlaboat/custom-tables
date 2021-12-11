<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

/* All tags already implemented using Twig */

class tagProcessor_Set
{
    public static function process(&$ct,&$pagelayout)
    {
        tagProcessor_Set::setHeadTag($pagelayout);
        tagProcessor_Set::setMetaDescription($pagelayout);
        tagProcessor_Set::setMetaKeywords($pagelayout);
		tagProcessor_Set::setPageTitle($ct,$pagelayout);
    }

    protected static function setMetaKeywords(&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('metakeywords',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);

			$doc = JFactory::getDocument();
			$doc->setMetaData( 'keywords', $opts[0] );

			$htmlresult=str_replace($fItem,'',$htmlresult);
			$i++;
		}

	}

	protected static function setMetaDescription(&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('metadescription',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);

			$doc = JFactory::getDocument();
			$doc->setMetaData( 'description', $opts[0] );

			$htmlresult=str_replace($fItem,'',$htmlresult);
			$i++;
		}

	}

	protected static function setPageTitle(&$ct,&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('pagetitle',$options,$htmlresult,'{}');
        $mydoc = JFactory::getDocument();
		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
            $mydoc->setTitle(JoomlaBasicMisc::JTextExtended($opts[0]));

			$htmlresult=str_replace($fItem,'',$htmlresult);
			$i++;
		}

        if(count($fList)==0)
            $mydoc->setTitle(JoomlaBasicMisc::JTextExtended($ct->Env->menu_params->get( 'page_title' )));
	}

    protected static function setHeadTag(&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('headtag',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);

			$document = JFactory::getDocument();
			$document->addCustomTag($opts[0]);

			$htmlresult=str_replace($fItem,'',$htmlresult);
			$i++;
		}

	}


}
