<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\CTUser;

$types_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR;
require_once($types_path . '_type_ct.php');
require_once($types_path . '_type_file.php');
require_once($types_path . '_type_filebox.php');
require_once($types_path . '_type_gallery.php');
require_once($types_path . '_type_image.php');
require_once($types_path . '_type_log.php');
require_once($types_path . '_type_records.php');
require_once($types_path . '_type_sqljoin.php');

defined('_JEXEC') or die('Restricted access');

use CustomTables\Field;
use Joomla\CMS\Factory;

class tagProcessor_Value
{
    public static function processValues(CT &$ct, &$row, &$htmlresult, $tag_chars = '[]'): void
    {
        $items_to_replace = array();
        $isGalleryLoaded = array();
        $getGalleryRows = array();
        $isFileBoxLoaded = array();
        $getFileBoxRows = array();

        if (isset($row) and is_array($row) and count($row) > 0) {
            foreach ($ct->Table->fields as $ESField) {
                $field = new Field($ct, $ESField, $row);

                $replaceitecode = md5(JoomlaBasicMisc::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $field->fieldname);

                $temp_items_to_replace = tagProcessor_Value::processPureValues($ct, $htmlresult, $row, $isGalleryLoaded, $getGalleryRows, $isFileBoxLoaded, $getFileBoxRows, $tag_chars);
                if (count($temp_items_to_replace) != 0)
                    $items_to_replace = array_merge($items_to_replace, $temp_items_to_replace);

                $temp_items_to_replace = tagProcessor_Value::processEditValues($ct, $htmlresult, $row, $tag_chars);
                if (count($temp_items_to_replace) != 0)
                    $items_to_replace = array_merge($items_to_replace, $temp_items_to_replace);

                $ValueOptions = array();
                $ValueList = JoomlaBasicMisc::getListToReplace($field->fieldname, $ValueOptions, $htmlresult, $tag_chars);

                $fieldtype = $field->type;
                $fieldname = $field->fieldname;

                if ($fieldtype == 'imagegallery') {
                    if (!isset($isGalleryLoaded[$fieldname]) or !$isGalleryLoaded[$fieldname]) {
                        $isGalleryLoaded[$fieldname] = true;
                        $r = CT_FieldTypeTag_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                        $getGalleryRows[$fieldname] = $r;
                    }

                    if (isset($isGalleryLoaded[$fieldname]) and count($getGalleryRows[$fieldname]) == 0)
                        $isEmpty = true;
                    else
                        $isEmpty = false;

                } elseif ($fieldtype == 'filebox') {

                    if (count($isFileBoxLoaded) > 0) {
                        if (!$isFileBoxLoaded[$fieldname]) {
                            $isFileBoxLoaded[$fieldname] = true;
                            $getFileBoxRows[$fieldname] = CT_FieldTypeTag_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                        }
                    } else {
                        $isFileBoxLoaded[$fieldname] = true;
                        $getFileBoxRows[$fieldname] = CT_FieldTypeTag_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                    }


                    if (isset($isFileBoxLoaded[$fieldname]) and count($getFileBoxRows[$fieldname]) == 0)
                        $isEmpty = true;
                    else
                        $isEmpty = false;

                } else {
                    //isEmpty
                    $isEmpty = tagProcessor_Value::isEmpty($row[$field->realfieldname], $field);
                }

                // IF
                tagProcessor_If::IFStatment('[_if:' . $field->fieldname . ']', '[_endif:' . $field->fieldname . ']', $htmlresult, $isEmpty);

                // IF NOT
                tagProcessor_If::IFStatment('[_ifnot:' . $field->fieldname . ']', '[_endifnot:' . $field->fieldname . ']', $htmlresult, !$isEmpty);

                if ($isEmpty) {
                    foreach ($ValueList as $ValueListItem)
                        $htmlresult = str_replace($ValueListItem, '', $htmlresult);
                } else {
                    $i = 0;
                    foreach ($ValueOptions as $ValueOption) {
                        $value_option_list = JoomlaBasicMisc::csv_explode(',', $ValueOption, '"', false);

                        $vlu = tagProcessor_Value::getValueByType($ct, $ESField, $row, $value_option_list);

                        //this is temporary replace string - part of the mechanism to avoid getting values of another fields
                        $new_replaceitecode = $replaceitecode . str_pad($field->id, 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

                        $items_to_replace[] = array($new_replaceitecode, $vlu);
                        $htmlresult = str_replace($ValueList[$i], $new_replaceitecode, $htmlresult);

                        $i++;
                    }
                }
                //process field names

            }
        }//isset

        //replace temprary items with values
        foreach ($items_to_replace as $item)
            $htmlresult = str_replace($item[0], $item[1], $htmlresult);

    }

    public static function processPureValues(CT &$ct, &$htmlresult, &$row, &$isGalleryLoaded, &$getGalleryRows, &$isFileBoxLoaded, &$getFileBoxRows, $tag_chars = '[]')
    {
        $listing_id = ($row[$ct->Table->realidfieldname] ?? 0);

        $items_to_replace = array();

        $pureValueOptions = array();
        $pureValueList = JoomlaBasicMisc::getListToReplace('_value', $pureValueOptions, $htmlresult, $tag_chars);
        $p = 0;
        foreach ($pureValueOptions as $pureValueOption) {
            $pureValueOptionArr = explode(':', $pureValueOption);
            if (count($pureValueOptionArr) == 1)
                $pureValueOptionArr[1] = '';

            $i = 0;
            foreach ($ct->Table->fields as $ESField) {
                $field = new Field($ct, $ESField, $row);

                $replaceitecode = md5(JoomlaBasicMisc::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $field->fieldname);

                if ($pureValueOptionArr[0] == $field->fieldname) {

                    $fieldtype = $field->type;
                    $fieldname = $field->fieldname;

                    if ($fieldtype == 'imagegallery') {
                        if (count($isGalleryLoaded) > 0) {
                            if (!isset($isGalleryLoaded[$fieldname]) or $isGalleryLoaded[$fieldname] == false) {
                                //load if not loaded
                                $isGalleryLoaded[$fieldname] = true;
                                $getGalleryRows[$fieldname] = CT_FieldTypeTag_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                            }
                        } else {
                            //load if not loaded
                            $isGalleryLoaded[$fieldname] = true;
                            $getGalleryRows[$fieldname] = CT_FieldTypeTag_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                        }

                        if (count($getGalleryRows[$fieldname]) == 0)
                            $isEmpty = true;
                        else
                            $isEmpty = false;
                    } elseif ($fieldtype == 'filebox') {
                        if (count($isFileBoxLoaded) > 0) {
                            if (!$isFileBoxLoaded[$fieldname]) {
                                //load if not loaded
                                $isFileBoxLoaded[$fieldname] = true;
                                $getFileBoxRows[$fieldname] = CT_FieldTypeTag_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                            }

                        } else {
                            //load if not loaded
                            $isFileBoxLoaded[$fieldname] = true;
                            $getFileBoxRows[$fieldname] = CT_FieldTypeTag_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
                        }

                        if (count($getFileBoxRows[$fieldname]) == 0)
                            $isEmpty = true;
                        else
                            $isEmpty = false;
                    } elseif ($fieldtype == 'checkbox') {
                        $isEmpty = false;
                    } else {
                        $isEmpty = tagProcessor_Value::isEmpty($row[$field->realfieldname], $field);
                    }

                    $ifname = '[_if:_value:' . $field->fieldname . ']';
                    $endifname = '[_endif:_value:' . $field->fieldname . ']';

                    if ($isEmpty) {
                        do {
                            $textlength = strlen($htmlresult);

                            $startif_ = strpos($htmlresult, $ifname);
                            if ($startif_ === false)
                                break;
                            else {

                                $endif_ = strpos($htmlresult, $endifname);
                                if (!($endif_ === false)) {
                                    $p = $endif_ + strlen($endifname);
                                    $htmlresult = substr($htmlresult, 0, $startif_) . substr($htmlresult, $p);
                                }
                            }

                        } while (1 == 1);//$textlengthnew!=$textlength);

                        $htmlresult = str_replace($pureValueList[$p], '', $htmlresult);
                    } else {
                        $htmlresult = str_replace($ifname, '', $htmlresult);
                        $htmlresult = str_replace($endifname, '', $htmlresult);

                        $vlu = '';

                        if ($fieldtype == 'image') {
                            $imagesrc = '';
                            $imagetag = '';

                            $new_array = array();

                            if (count($pureValueOptionArr) > 1) {
                                for ($i = 1; $i < count($pureValueOptionArr); $i++)
                                    $new_array[] = $pureValueOptionArr[$i];
                            }

                            CT_FieldTypeTag_image::getImageSRClayoutview($new_array, $row[$field->realfieldname], $field->params, $imagesrc, $imagetag);

                            $vlu = $imagesrc;
                        } elseif ($fieldtype == 'imagegallery') {
                            $imagetaglist = '';
                            $imagesrclist = '';

                            $new_array = array();

                            if (count($pureValueOptionArr) > 1) {
                                for ($i = 1; $i < count($pureValueOptionArr); $i++)
                                    $new_array[] = $pureValueOptionArr[$i];
                            }

                            if (count($new_array) > 0) {
                                $option = $new_array[0];
                                CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows[$fieldname],
                                    $option, $row[$ct->Table->realidfieldname], $fieldname, $field->params, $imagesrclist, $imagetaglist, $ct->Table->tableid);
                            }

                            $vlu = $imagesrclist;
                        } elseif ($fieldtype == 'filebox') {
                            $vlu = CT_FieldTypeTag_filebox::process($getFileBoxRows[$fieldname], $field, $row[$ct->Table->realidfieldname], ['', 'link', '32', '_blank', ';']);
                        } elseif ($fieldtype == 'records') {
                            $a = explode(",", $row[$field->realfieldname]);
                            $b = array();
                            foreach ($a as $c) {
                                if ($c != "")
                                    $b[] = $c;
                            }
                            $vlu = implode(',', $b);
                        } elseif ($fieldtype == 'file') {
                            if (isset($pureValueOptionArr[1]) and $pureValueOptionArr[1] != '') {
                                $processor_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                                require_once($processor_file);

                                $new_array = array();

                                if (count($pureValueOptionArr) > 1) {
                                    for ($i = 1; $i < count($pureValueOptionArr); $i++)
                                        $new_array[] = $pureValueOptionArr[$i];
                                }

                                $vlu = CT_FieldTypeTag_file::process($row[$field->realfieldname], $field, $new_array, $row[$ct->Table->realidfieldname]);
                            } else
                                $vlu = $row[$field->realfieldname];
                        } else {
                            $vlu = $row[$field->realfieldname];
                        }

                        //this is temporary replace string - part of the mechanism to avoid getting values of another fields
                        $new_replaceitecode = $replaceitecode . str_pad($field->id, 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

                        $items_to_replace[] = array($new_replaceitecode, $vlu);
                        $htmlresult = str_replace($pureValueList[$p], $new_replaceitecode, $htmlresult);
                    }
                }
                $i++;
            }
            $p++;
        }

        return $items_to_replace;

    }//function

    public static function isEmpty(&$rowValue, $field): bool
    {
        $fieldtype = $field->type;

        if ($fieldtype == 'int' or $fieldtype == 'user' or $fieldtype == 'userid' or $fieldtype == 'usergroup') {
            $v = (int)$rowValue;
            if ($v == 0)
                return true;
            else
                return false;
        } elseif ($fieldtype == 'float') {
            $v = (float)$rowValue;
            if ($v == 0)
                return true;
            else
                return false;
        } elseif ($fieldtype == 'checkbox') {
            $v = (int)$rowValue;
            if ($v == 0)
                return true;
            else
                return false;
        } elseif ($fieldtype == 'records' or $fieldtype == 'usergroups') {
            if ($rowValue == '' or $rowValue == ',' or $rowValue == ',,')
                return true;
            else
                return false;
        } elseif ($fieldtype == 'date') {
            if ($rowValue == '' or $rowValue == '0000-00-00')
                return true;
            else
                return false;
        } elseif ($fieldtype == 'time') {
            if ($rowValue == '' or $rowValue == '0')
                return true;
            else
                return false;
        } elseif ($fieldtype == 'image') {
            if ($rowValue == '' or $rowValue == '-1' or $rowValue == '0')
                return true;
            else {
                //check if file exists
                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

                $ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);

                $image_prefix = '_esthumb';

                $img = $rowValue;
                if (str_contains($img, '-')) {
                    //$isShortcut=true;
                    $img = str_replace('-', '', $img);
                }

                $imagefile_ext = 'jpg';
                $imagefile = $ImageFolder . DIRECTORY_SEPARATOR . $image_prefix . '_' . $img . '.' . $imagefile_ext;

                if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imagefile))
                    return false;
                else
                    return true;
            }
        } elseif ($fieldtype == 'customtables') {
            if ($rowValue == '' or $rowValue == ',.')
                return true;
            else
                return false;
        } elseif ($fieldtype == 'sqljoin') {
            if ($rowValue == 0)
                return true;
            else
                return false;
        } else {
            if ($rowValue == '')
                return true;
            else
                return false;
        }
    }

    public static function processEditValues(CT &$ct, &$htmlresult, &$row, $tag_chars = '[]')
    {
        $items_to_replace = array();
        $pureValueOptions = array();
        $pureValueList = JoomlaBasicMisc::getListToReplace('_edit', $pureValueOptions, $htmlresult, $tag_chars);

        if (count($pureValueList) > 0) {
            require_once(JPATH_SITE
                . DIRECTORY_SEPARATOR . 'components'
                . DIRECTORY_SEPARATOR . 'com_customtables'
                . DIRECTORY_SEPARATOR . 'libraries'
                . DIRECTORY_SEPARATOR . 'esinputbox.php');

            $esinputbox = new ESInputBox($ct);
            if ($ct->Params->requiredLabel != '')
                $esinputbox->requiredlabel = $ct->Params->requiredLabel;

            require_once(JPATH_SITE
                . DIRECTORY_SEPARATOR . 'components'
                . DIRECTORY_SEPARATOR . 'com_customtables'
                . DIRECTORY_SEPARATOR . 'libraries'
                . DIRECTORY_SEPARATOR . 'tagprocessor'
                . DIRECTORY_SEPARATOR . 'itemtags.php');

            $edit_userGroup = (int)$ct->Params->editUserGroups;
            $isEditable = CTUser::checkIfRecordBelongsToUser($ct, $edit_userGroup);
        } else
            $isEditable = false;

        $p = 0;
        foreach ($pureValueOptions as $pureValueOption) {
            $pureValueOptionArr = explode(':', $pureValueOption);

            $style = '';
            if (!isset($pureValueOptionArr[1]))
                $style = ' style="width:auto; !important;border:none !important;box-shadow:none;"';

            $i = 0;
            foreach ($ct->Table->fields as $ESField) {
                $replaceItCode = md5(JoomlaBasicMisc::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $ESField['fieldname']);

                if ($pureValueOptionArr[0] == $ESField['fieldname']) {
                    //this is temporary replace string - part of the mechanism to avoid getting values of another fields
                    $newReplaceItCode = $replaceItCode . str_pad($ESField['id'], 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

                    if ($isEditable) {
                        $postfix = '';
                        $ajax_prefix = 'com_' . $row[$ct->Table->realidfieldname] . '_';//example: com_153_es_fieldname or com_153_ct_fieldname

                        $value_option_list = array();
                        if (isset($pureValueOptionArr[1]))
                            $value_option_list = JoomlaBasicMisc::csv_explode(',', $pureValueOptionArr[1], '"', false);

                        if ($ESField['type'] == 'multilangstring') {
                            if (isset($value_option_list[4])) {
                                //multilingual field specific language
                                foreach ($ct->Languages->LanguageList as $lang) {
                                    if ($lang->sef == $value_option_list[4]) {
                                        $postfix = $lang->sef;
                                        break;
                                    }
                                }
                                $newReplaceItCode .= $postfix;
                            }
                        }

                        $onchange = 'ct_UpdateSingleValue(\'' . $ct->Env->WebsiteRoot . '\',' . $ct->Params->ItemId . ',\''
                            . $ESField['fieldname'] . '\',' . $row[$ct->Table->realidfieldname] . ',\''
                            . $postfix . '\',' . (int)$ct->Params->ModuleId . ');';

                        $attributes = 'onchange="' . $onchange . '"' . $style;

                        if (isset($value_option_list[1]))
                            $value_option_list[1] .= ' ' . $attributes;
                        else
                            $value_option_list[1] = $attributes;

                        $vlu = '<div class="" id="' . $ajax_prefix . $ESField['fieldname'] . $postfix . '_div">'
                            . $esinputbox->renderFieldBox($ESField, $row, $value_option_list);
                        $vlu .= '</div>';
                    } else {
                        //$fieldtype = $ESField['type'];
                        //$fieldname = $ESField['fieldname'];

                        //$rowValue='';
                        //tagProcessor_Value::doMultiValues($ct,$ESField,$row,$fieldtype,$rowValue,$fieldname);
                        $vlu = $row[$ESField['realfieldname']];
                    }

                    $items_to_replace[] = array($newReplaceItCode, $vlu);
                    $htmlresult = str_replace($pureValueList[$p], $newReplaceItCode, $htmlresult);
                }
                $i++;
            }
            $p++;
        }

        return $items_to_replace;
    }

    static public function getValueByType(CT &$ct, $ESField, $row, $option_list)
    {
        $valueProcessor = new CustomTables\Value($ct);

        return $valueProcessor->renderValue($ESField, $row, $option_list);
    }

    public static function showUserGroup($userid)
    {
        $db = Factory::getDBO();
        $query = 'SELECT title FROM #__usergroups WHERE id=' . (int)$userid . ' LIMIT 1';

        $db->setQuery($query);

        $options = $db->loadAssocList();
        if (count($options) != 0)
            return $options[0]['title'];

        return '';
    }

    public static function showUserGroups($valuearray_str)
    {
        if ($valuearray_str == '')
            return '';

        $db = Factory::getDBO();

        $where = array();
        $valuearray = explode(',', $valuearray_str);
        foreach ($valuearray as $value) {
            if ($value != '') {
                $where[] = 'id=' . (int)$value;
            }
        }

        $query = 'SELECT title FROM #__usergroups WHERE ' . implode(' OR ', $where) . ' ORDER BY title';

        $db->setQuery($query);

        $options = $db->loadAssocList();

        if (count($options) == 0)
            return '';

        $groups = array();
        foreach ($options as $opt)
            $groups[] = $opt['title'];

        return implode(',', $groups);
    }
}
