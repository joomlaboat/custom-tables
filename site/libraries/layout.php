<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\CT;

$libpath = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'tagprocessor' . DIRECTORY_SEPARATOR;
require_once($libpath . 'generaltags.php');//added to twig
require_once($libpath . 'fieldtags.php');//added to twig
require_once($libpath . 'settags.php'); //added to twig
require_once($libpath . 'iftags.php'); //comes with twig
require_once($libpath . 'pagetags.php');//added to twig
require_once($libpath . 'itemtags.php');//not all added to twig
require_once($libpath . 'valuetags.php');//added to twig
require_once($libpath . 'shopingtags.php');

class LayoutProcessor
{
	var string $layout;
	var int $layoutType;//item layout type
	var bool $advancedTagProcessor;
	var CustomTables\CT $ct;

	function __construct(CT &$ct, $layout = '')
	{
		$this->ct = $ct;
		$this->advancedTagProcessor = $this->ct->Env->advancedTagProcessor;
		$this->layout = $layout;
	}

	function fillLayout(?array $row = null, $aLink = null, $tag_chars = '[]', $disable_advanced_tags = false, $add_label = false): string
	{
		$htmlresult = $this->layout;

		if ($this->advancedTagProcessor and !$disable_advanced_tags) {
			tagProcessor_If::process($this->ct, $htmlresult, $row);

			if ($this->ct->Env->CustomPHPEnabled)
				tagProcessor_PHP::process($this->ct, $htmlresult, $row);
		}

		if (!str_contains($htmlresult, 'ct_doc_tagset_free'))//explain what is "ct_doc_tagset_free"
		{
			tagProcessor_If::process($this->ct, $htmlresult, $row);

			//Item must be before General to let the following: currenturl:set,paymentid,{id}}
			tagProcessor_Value::processValues($this->ct, $htmlresult, $row, $tag_chars);//to let sqljoin function work
			tagProcessor_Item::process($this->ct, $htmlresult, $row, $add_label);
			tagProcessor_General::process($this->ct, $htmlresult, $row);
			tagProcessor_Page::process($this->ct, $htmlresult);

			if ($this->advancedTagProcessor and !$disable_advanced_tags)
				tagProcessor_Set::process($this->ct, $htmlresult);

			if ($this->ct->Env->print == 1) {
				$htmlresult = str_replace('<a href', '<span link', $htmlresult);
				$htmlresult = str_replace('</a>', '</span>', $htmlresult);
			}
		}
		return $htmlresult;
	}
}
