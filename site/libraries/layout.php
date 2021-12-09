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

require_once($libpath.'pagetags.php');
require_once($libpath.'itemtags.php');
require_once($libpath.'valuetags.php');
require_once($libpath.'shopingtags.php');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_ct.php');

class LayoutProcessor
{
	var $layout;
	var $layoutType;//item layout type
	var $Model;
	var $advancedtagprocessor;
	var $version = 0;
	
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
		$this->version = $this->ct->Env->version;
		$this->advancedtagprocessor = $this->version = $this->ct->Env->advancedtagprocessor;
	}

	function fillLayout($row=array(),$aLink=null,$tag_chars='[]',$disable_advanced_tags=false,$add_label=false,$fieldNamePrefix='comes_')
	{
		$htmlresult=$this->layout;

		if($this->advancedtagprocessor and !$disable_advanced_tags)
		{
			tagProcessor_If::process($this->Model,$htmlresult,$row);			
			tagProcessor_PHP::process($this->Model,$htmlresult,$row);
		}
		
		if(strpos($htmlresult,'ct_doc_tagset_free')===false)//explainf what is "ct_doc_tagset_free"
		{
			tagProcessor_If::process($this->Model,$htmlresult,$row);

			//Item must be before General to let the following: currenturl:set,paymentid,{id}}
			tagProcessor_Value::processValues($this->Model,$row,$htmlresult,$tag_chars);//to let sqljoin function work
			tagProcessor_Item::process($this->advancedtagprocessor,$this->Model,$row,$htmlresult,$aLink,$add_label,$fieldNamePrefix);
			tagProcessor_General::process($this->Model,$htmlresult,$row);
			tagProcessor_Page::process($this->Model,$htmlresult);

			tagProcessor_Tabs::process($htmlresult);

			if($this->advancedtagprocessor and !$disable_advanced_tags)
				tagProcessor_Set::process($this->Model,$htmlresult);

			if($this->Model->ct->Env->print==1)
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

			$params_ = $mainframe->getParams('com_content');

			$o = new stdClass();
			$o->text = $htmlresult;
			$o->created_by_alias = 0;
		
			JPluginHelper::importPlugin( 'content' );
		
			if($version < 4)
			{
				$dispatcher	= JDispatcher::getInstance();
				$results = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$params_, 0));
			}
			else
				$results = JFactory::getApplication()->triggerEvent( 'onContentPrepare',array ('com_content.article', &$o, &$params_, 0));
			
			$htmlresult = $o->text;
		
			$mydoc->setTitle(JoomlaBasicMisc::JTextExtended($pagetitle)); //because content plugins may overwrite the title
		}
		
		return $htmlresult;
	}

	public static function renderPageHeader(&$Model)
	{
		if ( $Model->params->get( 'show_page_heading', 1 ) )
		{
			$title=JoomlaBasicMisc::JTextExtended($Model->params->get( 'page_title' ));
			echo '
			<div class="page-header'.LayoutProcessor::htmlEscape($Model->params->get('pageclass_sfx'), 'UTF-8').'">
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
