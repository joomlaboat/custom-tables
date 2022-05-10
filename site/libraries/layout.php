<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Version;

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

// Include library dependencies

$libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR;
require_once($libpath.'generaltags.php');//added to twig
require_once($libpath.'fieldtags.php');//added to twig
require_once($libpath.'settags.php'); //added to twig
require_once($libpath.'iftags.php'); //comes with twig
require_once($libpath.'tabstags.php');

require_once($libpath.'pagetags.php');//added to twig
require_once($libpath.'itemtags.php');//not all added to twig
require_once($libpath.'valuetags.php');//added to twig
require_once($libpath.'shopingtags.php');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_ct.php');

class LayoutProcessor
{
	var $layout;
	var $layoutType;//item layout type
	var $advancedtagprocessor;
	var $version = 0;
	
	var $ct;

	function __construct(&$ct, $layout = null, $layoutType = null)
	{
		$this->ct = $ct;
		$this->version = $this->ct->Env->version;
		$this->advancedtagprocessor = $this->version = $this->ct->Env->advancedtagprocessor;
		
		$this->layout = $layout;
	}

	function fillLayout(array $row = [],$aLink=null,$tag_chars='[]',$disable_advanced_tags=false,$add_label=false)
	{
		//$this->ct->Table->record = $row;
		$htmlresult=$this->layout;

		if($this->advancedtagprocessor and !$disable_advanced_tags)
		{
			tagProcessor_If::process($this->ct,$htmlresult,$row);			
			tagProcessor_PHP::process($this->ct,$htmlresult,$row);
		}
		
		if(strpos($htmlresult,'ct_doc_tagset_free')===false)//explainf what is "ct_doc_tagset_free"
		{
			tagProcessor_If::process($this->ct,$htmlresult,$row);

			//Item must be before General to let the following: currenturl:set,paymentid,{id}}
			tagProcessor_Value::processValues($this->ct,$row,$htmlresult,$tag_chars);//to let sqljoin function work
			tagProcessor_Item::process($this->ct,$row,$htmlresult,$aLink,$add_label);
			tagProcessor_General::process($this->ct,$htmlresult,$row);
			tagProcessor_Page::process($this->ct,$htmlresult);

			tagProcessor_Tabs::process($htmlresult);

			if($this->advancedtagprocessor and !$disable_advanced_tags)
				tagProcessor_Set::process($this->ct,$htmlresult);

			if($this->ct->Env->print==1)
			{
				$htmlresult=str_replace('<a href','<span link',$htmlresult);
				$htmlresult=str_replace('</a>','</span>',$htmlresult);
			}
		}

		return $htmlresult;
	}
}
