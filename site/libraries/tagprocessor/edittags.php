<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Twig_Html_Tags;

class tagProcessor_Edit
{
    public static function process(CT &$ct, &$pagelayout, &$row): array
    {
        $ct_html = new Twig_Html_Tags($ct, false);

        tagProcessor_Edit::process_captcha($ct_html, $pagelayout); //Converted to Twig. Original replaced.

        $buttons = tagProcessor_Edit::process_button($ct_html, $pagelayout);

        $fields = tagProcessor_Edit::process_fields($ct, $pagelayout, $row); //Converted to Twig. Original replaced.
        return ['fields' => $fields, 'buttons' => $buttons];
    }

    protected static function process_captcha($ct_html, &$pagelayout): void
    {
        $options = [];
        $captchas = JoomlaBasicMisc::getListToReplace('captcha', $options, $pagelayout, '{}');

        foreach ($captchas as $captcha) {
            $captcha_code = $ct_html->captcha();
            $pagelayout = str_replace($captcha, $captcha_code, $pagelayout);
        }
    }

    protected static function process_button($ct_html, &$pagelayout)
    {
        $options = [];
        $buttons = JoomlaBasicMisc::getListToReplace('button', $options, $pagelayout, '{}');

        if (count($buttons) == 0)
            return null;

        for ($i = 0; $i < count($buttons); $i++) {
            $option = JoomlaBasicMisc::csv_explode(',', $options[$i]);

            if ($option[0] != '')
                $type = $option[0];//button set
            else
                $type = 'save';

            $title = $option[1] ?? '';
            $redirectlink = $option[2] ?? null;
            $optional_class = $option[3] ?? '';

            $b = $ct_html->button($type, $title, $redirectlink, $optional_class);

            $pagelayout = str_replace($buttons[$i], $b, $pagelayout);
        }

        return $ct_html->button_objects;
    }

    protected static function process_fields(CT &$ct, &$pagelayout, &$row): array
    {
        require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'esinputbox.php');

        $inputBox = new ESInputBox($ct);

        if ($ct->Params->requiredLabel != '')
            $inputBox->requiredLabel = $ct->Params->requiredLabel;

        //Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
        $calendars = array();
        $replaceItCode = JoomlaBasicMisc::generateRandomString();
        $items_to_replace = array();

        $field_objects = tagProcessor_Edit::renderFields($row, $pagelayout, $inputBox, $calendars, $replaceItCode, $items_to_replace);

        foreach ($items_to_replace as $item)
            $pagelayout = str_replace($item[0], $item[1], $pagelayout);

        return $field_objects;
    }

    protected static function renderFields(&$row, &$pagelayout, $inputBox, &$calendars, $replaceItCode, &$items_to_replace): array
    {
        $field_objects = [];
        $calendars = array();

        //custom layout
        if (!isset($inputBox->ct->Table->fields) or !is_array($inputBox->ct->Table->fields))
            return [];

        for ($f = 0; $f < count($inputBox->ct->Table->fields); $f++) {
            $fieldrow = $inputBox->ct->Table->fields[$f];
            $options = array();
            $entries = JoomlaBasicMisc::getListToReplace($fieldrow['fieldname'], $options, $pagelayout, '[]');

            if (count($entries) > 0) {
                for ($i = 0; $i < count($entries); $i++) {
                    $option_list = JoomlaBasicMisc::csv_explode(',', $options[$i]);

                    $result = '';

                    if ($fieldrow['type'] == 'date')
                        $calendars[] = $inputBox->ct->Env->field_prefix . $fieldrow['fieldname'];

                    if ($fieldrow['type'] != 'dummy')
                        $result = $inputBox->renderFieldBox($fieldrow, $row, $option_list);

                    if ($inputBox->ct->Env->frmt == 'json') {
                        $field_objects[] = $result;
                        $result = '';
                    }

                    $newReplaceItCode = $replaceItCode . str_pad(count($items_to_replace), 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

                    $items_to_replace[] = array($newReplaceItCode, $result);
                    $pagelayout = str_replace($entries[$i], $newReplaceItCode, $pagelayout);
                }
            }
        }

        return $field_objects;
    }
}
