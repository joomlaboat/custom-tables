<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

class DataTypes
{
    public static function fieldTypeTranslation()
    {
        $typeArray = array(
            'string' => 'COM_CUSTOMTABLES_FIELDS_STRING',
            'multilangstring' => 'COM_CUSTOMTABLES_FIELDS_MULTILANGSTRING',
            'text' => 'COM_CUSTOMTABLES_FIELDS_TEXT',
            'multilangtext' => 'COM_CUSTOMTABLES_FIELDS_MULTILANGTEXT',
            'int' => 'COM_CUSTOMTABLES_FIELDS_INTEGER',
            'float' => 'COM_CUSTOMTABLES_FIELDS_FLOAT',
            'customtables' => 'COM_CUSTOMTABLES_FIELDS_EXTRA_SEARCH',
            'records' => 'COM_CUSTOMTABLES_FIELDS_MULTI_SQL_JOIN',
            'checkbox' => 'COM_CUSTOMTABLES_FIELDS_CHECKBOX',
            'radio' => 'COM_CUSTOMTABLES_FIELDS_RADIO_BUTTONS',
            'email' => 'COM_CUSTOMTABLES_FIELDS_EMAIL',
            'url' => 'COM_CUSTOMTABLES_FIELDS_URL',
            'date' => 'COM_CUSTOMTABLES_FIELDS_DATE',
            'time' => 'COM_CUSTOMTABLES_FIELDS_TIME',
            'image' => 'COM_CUSTOMTABLES_FIELDS_IMAGE',
            'imagegallery' => 'COM_CUSTOMTABLES_FIELDS_IMAGE_GALLERY',
            'signature' => 'COM_CUSTOMTABLES_FIELDS_SIGNATURE',
            'ordering' => 'COM_CUSTOMTABLES_FIELDS_ORDERING',
            'filebox' => 'COM_CUSTOMTABLES_FIELDS_FILE_BOX',
            'file' => 'COM_CUSTOMTABLES_FIELDS_FILE',
            'filelink' => 'COM_CUSTOMTABLES_FIELDS_FILE_LINK',
            'creationtime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_CREATION_DATE_TIME',
            'changetime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_CHANGE_DATE_TIME',
            'lastviewtime' => 'COM_CUSTOMTABLES_FIELDS_AUTO_LAST_VIEW_DATE_TIME',
            'viewcount' => 'COM_CUSTOMTABLES_FIELDS_AUTO_VIEW_COUNT',
            'userid' => 'COM_CUSTOMTABLES_FIELDS_AUTO_AUTHOR_USER_ID',
            'user' => 'COM_CUSTOMTABLES_FIELDS_USER',
            'server' => 'COM_CUSTOMTABLES_FIELDS_SERVER',
            'alias' => 'COM_CUSTOMTABLES_FIELDS_ALIAS',
            'color' => 'COM_CUSTOMTABLES_FIELDS_COLOR',
            'id' => 'COM_CUSTOMTABLES_FIELDS_AUTO_ID',
            'phponadd' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONADD_SCRIPT',
            'phponchange' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONCHANGE_SCRIPT',
            'phponview' => 'COM_CUSTOMTABLES_FIELDS_PHP_ONVIEW_SCRIPT',
            'sqljoin' => 'COM_CUSTOMTABLES_FIELDS_SQL_JOIN',
            'googlemapcoordinates' => 'COM_CUSTOMTABLES_FIELDS_GOOGLE_MAP_COORDINATES',
            'dummy' => 'COM_CUSTOMTABLES_FIELDS_DUMMY_USED_FOR_TRANSLATION',
            'article' => 'COM_CUSTOMTABLES_FIELDS_ARTICLE_LINK',
            'multilangarticle' => 'COM_CUSTOMTABLES_FIELDS_MULTILINGUAL_ARTICLE',
            'md5' => 'COM_CUSTOMTABLES_FIELDS_MDFIVE_HASH',
            'log' => 'COM_CUSTOMTABLES_FIELDS_MODIFICATION_LOG',
            'usergroup' => 'COM_CUSTOMTABLES_FIELDS_USER_GROUP',
            'usergroups' => 'COM_CUSTOMTABLES_FIELDS_USER_GROUPS',
            'blob' => 'COM_CUSTOMTABLES_FIELDS_BLOB'
        );

        return $typeArray;
    }

    public static function isrequiredTranslation()
    {
        $isrequiredArray = array(
            1 => 'COM_CUSTOMTABLES_FIELDS_YES',
            0 => 'COM_CUSTOMTABLES_FIELDS_NO'
        );

        return $isrequiredArray;
    }
}