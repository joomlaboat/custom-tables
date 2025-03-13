<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use Exception;
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
		$languageTag = '';

		// Joomla detection
		if (defined('_JEXEC')) {
			try {
				if (CUSTOMTABLES_JOOMLA_MIN_4) {
					// Joomla 4+ method
					$languageTag = Factory::getApplication()->getLanguage()->getTag();

				} else {
					// Joomla 3 method
					$languageTag = Factory::getLanguage()->getTag();
				}
			} catch (Exception $e) {
				// Fallback if language detection fails
				$languageTag = 'en-GB';
			}
		} // WordPress detection
		elseif (defined('WPINC')) {
			// Get WordPress locale (e.g., 'en_US', 'sk_SK')
			$languageTag = \is_user_logged_in() ? \get_user_locale() : \get_locale();
		}

		// Process language list
		$index = 0;
		foreach ($this->LanguageList as $languageItem) {
			if ($languageItem->language == $languageTag) {
				$this->tag = $languageItem->sef;
				return ($index == 0) ? '' : '_' . $languageItem->sef;
			}
			$index++;
		}

		return '';
	}

	function getDefaultLanguage()
	{
		$db = Factory::getDBO();
		$query = 'SELECT params FROM #__extensions WHERE ' . $db->quoteName('name') . '="com_languages" LIMIT 1';
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (count($rows) == 0)
			return '';

		return json_decode($rows[0]->params);
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
