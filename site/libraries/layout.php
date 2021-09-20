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
require_once($libpath.'generaltags.php');
require_once($libpath.'fieldtags.php');
require_once($libpath.'settags.php');
require_once($libpath.'iftags.php');
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
	var $number;
	var $recordlist;
	var $toolbar_array;
	var $Model;
	var $advancedtagprocessor;
	var $version = 0;

	function __construct()
	{
		$version = new Version;
		$this->version = (int)$version->getShortVersion();
		
		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
		if(file_exists($phptagprocessor))
		{
			require_once($phptagprocessor);
			$this->advancedtagprocessor=true;

		$servertagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'servertags.php';

		if(file_exists($servertagprocessor_file))
			require_once($servertagprocessor_file);

		}
		else
			$this->advancedtagprocessor=false;
	}

	function fillLayout($row,$aLink=null,$toolbar=array(),$tag_chars='[]',$disable_advanced_tags=false,$add_label=false,$fieldNamePrefix='comes_')
	{

		if(!is_array($toolbar) or count($toolbar)==0)
			$toolbar=$this->toolbar_array;


		$htmlresult=$this->layout;

		if($this->advancedtagprocessor and !$disable_advanced_tags)
		{
			tagProcessor_If::process($this->Model,$htmlresult,$row,$this->recordlist,$this->number);
			
			//NO LONGER NEEDED BECAUSE ITS BEING PROCESSED WHILE READING THE PARENT LAYOUT
			//tagProcessor_General::processLayoutTag($htmlresult);
			
			tagProcessor_PHP::process($this->Model,$htmlresult,$row,$this->recordlist,$this->number);
		}
		
		if(strpos($htmlresult,'ct_doc_tagset_free')===false)//explainf what is "ct_doc_tagset_free"
		{
			tagProcessor_If::process($this->Model,$htmlresult,$row,$this->recordlist,$this->number);

			//Item must be before General to let the following: currenturl:set,paymentid,{id}}
			tagProcessor_Value::processValues($this->Model,$row,$htmlresult,$tag_chars);//to let sqljoin function work
			tagProcessor_Item::process($this->advancedtagprocessor,$this->Model,$row,$htmlresult,$aLink,$toolbar,$this->recordlist,$this->number,$add_label,$fieldNamePrefix);
			tagProcessor_General::process($this->Model,$htmlresult,$row,$this->recordlist,$this->number);
			tagProcessor_Page::process($this->Model,$htmlresult);

			tagProcessor_Tabs::process($this->Model,$htmlresult);



			if($this->advancedtagprocessor and !$disable_advanced_tags)
				tagProcessor_Set::process($this->Model,$htmlresult);


			if($this->Model->print==1)
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
