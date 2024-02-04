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
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use Joomla\CMS\Factory;

/* All tags already implemented using Twig */

class tagProcessor_Set
{
	public static function process(CT &$ct, &$pageLayout)
	{
		tagProcessor_Set::setHeadTag($ct, $pageLayout);
		tagProcessor_Set::setMetaDescription($ct, $pageLayout);
		tagProcessor_Set::setMetaKeywords($ct, $pageLayout);
		tagProcessor_Set::setPageTitle($ct, $pageLayout);
	}

	protected static function setHeadTag(CT &$ct, string &$htmlresult)
	{
		$options = array();
		$fList = CTMiscHelper::getListToReplace('headtag', $options, $htmlresult, '{}');

		$i = 0;
		foreach ($fList as $fItem) {
			$opts = CTMiscHelper::csv_explode(',', $options[$i], '"', false);

			if ($ct->Env->isModal) {
				$htmlresult = str_replace($fItem, $opts[0], $htmlresult);
			} else {
				$document = Factory::getDocument();
				$document->addCustomTag($opts[0]);
				$htmlresult = str_replace($fItem, '', $htmlresult);
			}
			$i++;
		}
	}

	protected static function setMetaDescription(CT &$ct, string &$htmlresult)
	{
		$options = array();
		$fList = CTMiscHelper::getListToReplace('metadescription', $options, $htmlresult, '{}');

		$i = 0;
		foreach ($fList as $fItem) {
			$opts = CTMiscHelper::csv_explode(',', $options[$i], '"', false);
			if ($ct->Env->isModal) {
			} else {
				$doc = Factory::getDocument();
				$doc->setMetaData('description', $opts[0]);
			}

			$htmlresult = str_replace($fItem, '', $htmlresult);

			$i++;
		}

	}

	protected static function setMetaKeywords(CT &$ct, string &$htmlresult)
	{
		$options = array();
		$fList = CTMiscHelper::getListToReplace('metakeywords', $options, $htmlresult, '{}');

		$i = 0;
		foreach ($fList as $fItem) {
			$opts = CTMiscHelper::csv_explode(',', $options[$i], '"', false);

			if ($ct->Env->isModal) {

			} else {
				$doc = Factory::getDocument();
				$doc->setMetaData('keywords', $opts[0]);
			}

			$htmlresult = str_replace($fItem, '', $htmlresult);

			$i++;
		}

	}

	protected static function setPageTitle(CT &$ct, string &$htmlresult)
	{
		$options = array();
		$fList = CTMiscHelper::getListToReplace('pagetitle', $options, $htmlresult, '{}');
		$document = Factory::getDocument();
		$i = 0;
		foreach ($fList as $fItem) {
			$opts = CTMiscHelper::csv_explode(',', $options[$i], '"', false);

			if (!$ct->Env->isModal)
				$document->setTitle(common::translate($opts[0]));

			$htmlresult = str_replace($fItem, '', $htmlresult);
			$i++;
		}

		if (count($fList) == 0 and $ct->Params->pageTitle !== null)
			$document->setTitle(common::translate($ct->Params->pageTitle));
	}
}
