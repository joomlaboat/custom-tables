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

	function fillLayout($row=array(),$aLink=null,$tag_chars='[]',$disable_advanced_tags=false,$add_label=false)
	{
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

	public static function applyContentPlugins(&$htmlresult)
	{
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		$mainframe = JFactory::getApplication('site');

		if(method_exists($mainframe,'getParams'))
		{
			$mydoc = JFactory::getDocument();
			$pagetitle=$mydoc->getTitle(); //because content plugins may overwrite the title

			$content_params = $mainframe->getParams('com_content');

			$o = new stdClass();
			$o->text = $htmlresult;
			$o->created_by_alias = 0;
		
			JPluginHelper::importPlugin( 'content' );
		
			if($version < 4)
			{
				$dispatcher	= JDispatcher::getInstance();
				$results = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$content_params, 0));
			}
			else
				$results = JFactory::getApplication()->triggerEvent( 'onContentPrepare',array ('com_content.article', &$o, &$content_params, 0));
			
			$htmlresult = $o->text;
		
			$mydoc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle)); //because content plugins may overwrite the title
		}
		
		return $htmlresult;
	}

	public static function renderPageHeader(&$ct)
	{
		if ($ct->Env->menu_params->get( 'show_page_heading', 1 ) )
		{
			$title=JoomlaBasicMisc::JTextExtended($ct->Env->menu_params->get( 'page_title' ));
			echo '
			<div class="page-header'.LayoutProcessor::htmlEscape($ct->Env->menu_params->get('pageclass_sfx'), 'UTF-8').'">
				<h2 itemprop="headline">
					'.$title.'
				</h2>
			</div>
			';
		}
	}

	public static function htmlEscape($var, $charset = 'UTF-8', $shorten = false, $length = 40)
	{
		if (isset($var) && is_string($var) && strlen($var) > 0)
		{
			$filter = new JFilterInput();
			$string = $filter->clean(html_entity_decode(htmlentities($var, ENT_COMPAT, $charset)), 'HTML');
			if ($shorten)
			{
				return self::shorten($string,$length);
			}
			return $string;
		}
		else
		{
			return '';
		}
	}

	protected static function shorten($string, $length = 40, $addTip = true)
	{
		if (self::checkString($string))
		{
			$initial = strlen($string);
			$words = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
			$words_count = count((array)$words);

			$word_length = 0;
			$last_word = 0;
			for (; $last_word < $words_count; ++$last_word)
			{
				$word_length += strlen($words[$last_word]);
				if ($word_length > $length)
				{
					break;
				}
			}

			$newString	= implode(array_slice($words, 0, $last_word));
			$final	= strlen($newString);
			if ($initial != $final && $addTip)
			{
				$title = self::shorten($string, 400 , false);
				return '<span class="hasTip" title="'.$title.'" style="cursor:help">'.trim($newString).'...</span>';
			}
			elseif ($initial != $final && !$addTip)
			{
				return trim($newString).'...';
			}
		}
		return $string;
	}
}
