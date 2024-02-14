<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

use Joomla\CMS\Factory;

class Languages
{
	var array $LanguageList;
	var string $Postfix;
	var string $tag;

	function __construct()
	{
		$this->LanguageList = $this->getLanguageList();
		$this->Postfix = $this->getLangPostfix();
	}

	function getLanguageList(): array
	{
		if (defined('_JEXEC')) {
			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('published', 1);

			$rows = database::loadObjectList('#__languages', ['lang_id AS id', 'lang_code AS language', 'title AS caption', 'title', 'sef AS original_sef'], $whereClause, 'lang_id');

			$this->LanguageList = array();
			foreach ($rows as $row) {
				$parts = explode('-', $row->original_sef);
				$row->sef = $parts[0];
				$this->LanguageList[] = $row;
			}
			return $this->LanguageList;
		} else {

			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$languages = get_available_languages();
			$translations = wp_get_available_translations();

			$language_info = array();
			$language_info[] = (object)[
				'id' => 1,
				'language' => 'en_US',
				'caption' => 'English',
				'title' => 'English (United States)',
				'original_sef' => 'en',
				'sef' => 'en'];

			$i = 2;
			foreach ($languages as $lang_code) {
				$translation = $this->wp_findLanguageTranslation($translations, $lang_code);

				$parts = explode('_', $lang_code);
				$sef = $parts[0];

				$language_info[] = (object)[
					'id' => $i,
					'language' => $lang_code,
					'caption' => $translation['english_name'],
					'title' => $translation['english_name'] . ' (' . $translation['native_name'] . ')',
					'original_sef' => $sef,
					'sef' => $sef];

				$i += 1;
			}
			return $language_info;
		}
	}

	protected function wp_findLanguageTranslation($translations, $lang_code)
	{
		foreach ($translations as $translation) {
			if ($translation['language'] == $lang_code)
				return $translation;
		}
		return null;
	}

	function getLangPostfix(): string
	{
		if (defined('_JEXEC')) {
			$langObj = Factory::getLanguage();
			$nowLang = $langObj->getTag();
			$index = 0;
			foreach ($this->LanguageList as $lang) {
				if ($lang->language == $nowLang) {

					$this->tag = $lang->sef;

					if ($index == 0)
						return '';
					else
						return '_' . $lang->sef;
				}

				$index++;
			}
		}
		return '';
	}

	function getLanguageTagByID($language_id): string
	{
		foreach ($this->LanguageList as $lang) {
			if ($lang->id == $language_id)
				return $lang->language;
		}
		return '';
	}

	function getLanguageByCODE($code): int
	{
		if (defined('_JEXEC')) {
			//$query = ' SELECT lang_id AS id FROM #__languages WHERE lang_code="' . $code . '" LIMIT 1';

			$whereClause = new MySQLWhereClause();
			$whereClause->addCondition('lang_code', $code);

			$rows = database::loadObjectList('#__languages', ['lang_id AS id'], $whereClause, null, null, 1);
			if (count($rows) != 1)
				return -1;

			return $rows[0]->id;
		}
		return -1;
	}
}
