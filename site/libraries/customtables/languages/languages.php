<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;

class Languages
{
    var array $LanguageList;
    var string $Postfix;

    function __construct()
    {
        $this->LanguageList = $this->getLanguageList();
        $this->Postfix = $this->getLangPostfix();
    }

    function getLanguageList(): array
    {
        $db = Factory::getDBO();

        $query = 'SELECT lang_id AS id, lang_code AS language, title AS caption, title, sef AS original_sef FROM #__languages WHERE published=1 ORDER BY lang_id';
        $db->setQuery($query);

        $rows = $db->loadObjectList();

        $this->LanguageList = array();
        foreach ($rows as $row) {
            $parts = explode('-', $row->original_sef);
            $row->sef = $parts[0];
            $this->LanguageList[] = $row;
        }
        return $this->LanguageList;
    }

    function getLangPostfix(): string
    {
        $langObj = Factory::getLanguage();
        $nowLang = $langObj->getTag();
        $index = 0;
        foreach ($this->LanguageList as $lang) {
            if ($lang->language == $nowLang) {
                if ($index == 0)
                    return '';
                else
                    return '_' . $lang->sef;
            }

            $index++;
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
        $db = Factory::getDBO();

        $query = ' SELECT lang_id AS id FROM #__languages WHERE lang_code="' . $code . '" LIMIT 1';

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if (count($rows) != 1)
            return -1;

        return $rows[0]->id;
    }
}
