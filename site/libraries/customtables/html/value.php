<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\DataTypes\Tree;
use CustomTablesImageMethods;
use JoomlaBasicMisc;
use JHTMLCTTime;
use tagProcessor_Value;

use CT_FieldTypeTag_file;
use CT_FieldTypeTag_image;
use CT_FieldTypeTag_imagegallery;
use CT_FieldTypeTag_filebox;
use CT_FieldTypeTag_sqljoin;
use CT_FieldTypeTag_records;
use CT_FieldTypeTag_log;
use CT_FieldTypeTag_ct;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

use JHTML;

$types_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR;

if (file_exists($types_path . '_type_ct.php')) {
    require_once($types_path . '_type_ct.php');
    require_once($types_path . '_type_file.php');
    require_once($types_path . '_type_filebox.php');
    require_once($types_path . '_type_gallery.php');
    require_once($types_path . '_type_image.php');
    require_once($types_path . '_type_log.php');
    require_once($types_path . '_type_records.php');
    require_once($types_path . '_type_sqljoin.php');
}

class Value
{
    var CT $ct;
    var Field $field;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
    }

    function renderValue(array $fieldrow, ?array &$row, array $option_list)
    {
        $this->field = new Field($this->ct, $fieldrow, $row);

        $rfn = $this->field->realfieldname;
        $rowValue = $row[$rfn] ?? null;

        switch ($this->field->type) {
            case 'int':
            case 'viewcount':
                $thousand_sep = $option_list[0] ?? ($this->field->params[0] ?? '');
                return number_format((int)$rowValue, 0, '', $thousand_sep);

            case 'float':
                $decimals = $option_list[0] != '' ? (int)$option_list[0] : ($this->field->params[0] != '' ? (int)$this->field->params[0] : 2);
                $decimals_sep = $option_list[1] ?? '.';
                $thousand_sep = $option_list[2] ?? '';
                return number_format((float)$rowValue, $decimals, $decimals_sep, $thousand_sep);

            case 'ordering':
                return $this->orderingProcess($rowValue, $row);

            case 'id':
            case 'md5':
            case 'phponadd':
            case 'phponchange':
            case 'phponview':
            case 'alias':
            case 'radio':
            case 'server':
            case 'email':
            case 'url':
                return $rowValue;
            case 'googlemapcoordinates':

                if ($option_list[0] == 'map') {

                    $parts = explode(',', $rowValue);
                    $lat = $parts[0];
                    $lng = $parts[1] ?? '';
                    if ($lat == '' or $lng == '')
                        return '';

                    $width = $option_list[1] ?? '320px';
                    if (!str_contains($width, '%') and !str_contains($width, 'px'))
                        $width .= 'px';

                    $height = $option_list[2] ?? '240px';
                    if (!str_contains($height, '%') and !str_contains($height, 'px'))
                        $height .= 'px';

                    $zoom = (int)$option_list[3] ?? '10';
                    if ($zoom == 0)
                        $zoom = 10;

                    $boxId = 'ct' . $this->field->fieldname . '_map' . $row[$this->ct->Table->realidfieldname];

                    return '<div id="' . $boxId . '" style="width:' . $width . ';height:' . $height . '">'
                        . '</div><script>ctValue_googlemapcoordinates("' . $boxId . '", ' . $lat . ',' . $lng . ',' . $zoom . ')</script>';

                } elseif ($option_list[0] == 'latitude')
                    return explode(',', $rowValue)[0];
                elseif ($option_list[0] == 'longitude') {
                    $parts = explode(',', $rowValue);
                    return ($parts[1] ?? '');
                }
                return $rowValue;

            case 'multilangstring':
            case 'multilangtext':
                return $this->multilingual($row, $option_list);

            case 'string':
                return $this->TextFunctions($rowValue, $option_list);

            case 'text':
                return $this->TextFunctions($rowValue, $option_list);

            case 'blob':
                return $this->blobProcess($rowValue, $option_list);

            case 'color':
                return $this->colorProcess($rowValue, $option_list);

            case 'file':

                return CT_FieldTypeTag_file::process($rowValue, $this->field, $option_list, $row[$this->ct->Table->realidfieldname]);

            case 'image':
                $imageSRC = '';
                $imagetag = '';

                CT_FieldTypeTag_image::getImageSRClayoutview($option_list, $rowValue, $this->field->params, $imageSRC, $imagetag);

                return $imagetag;

            case 'signature':

                CT_FieldTypeTag_image::getImageSRClayoutview($option_list, $rowValue, $this->field->params, $imageSRC, $imagetag);

                $conf = Factory::getConfig();
                $sitename = $conf->get('config.sitename');

                $ImageFolder_ = CustomTablesImageMethods::getImageFolder($this->field->params);

                $ImageFolderWeb = str_replace(DIRECTORY_SEPARATOR, '/', $ImageFolder_);
                $ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);

                $imageSRC = '';
                $imagetag = '';

                $format = $this->field->params[3] ?? 'png';

                if ($format == 'jpeg')
                    $format = 'jpg';

                $imagefileweb = URI::root() . $ImageFolderWeb . '/' . $rowValue . '.' . $format;
                $imagefile = $ImageFolder . DIRECTORY_SEPARATOR . $rowValue . '.' . $format;

                if (file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . $imagefile)) {
                    $width = $this->field->params[0] ?? 300;
                    $height = $this->field->params[1] ?? 150;

                    return '<img src="' . $imagefileweb . '" width="' . $width . '" height="' . $height . '" alt="' . $sitename . '" title="' . $sitename . '" />';
                }
                return null;


            case 'article':
            case 'multilangarticle':
                return $this->articleProcess($rowValue, $option_list);

            case 'imagegallery':

                $getGalleryRows = CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $row[$this->ct->Table->realidfieldname]);

                if ($option_list[0] == '_count')
                    return count($getGalleryRows);

                $imageSRCList = '';
                $imageTagList = '';

                CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $option_list[0],
                    $row[$this->ct->Table->realidfieldname], $this->field->fieldname, $this->field->params, $imageSRCList, $imageTagList, $this->ct->Table->tableid);

                return $imageTagList;

            case 'filebox':

                $FileBoxRows = CT_FieldTypeTag_filebox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $row[$this->ct->Table->realidfieldname]);

                if ($option_list[0] == '_count')
                    return count($FileBoxRows);

                return CT_FieldTypeTag_filebox::process($FileBoxRows, $this->field, $row[$this->ct->Table->realidfieldname], $option_list);

            case 'customtables':
                return $this->listProcess($rowValue, $option_list);

            case 'records':
                return CT_FieldTypeTag_records::resolveRecordType($this->ct, $rowValue, $this->field, $option_list);

            case 'sqljoin':
                return CT_FieldTypeTag_sqljoin::resolveSQLJoinType($rowValue, $this->field->params, $option_list);

            case 'user':
            case 'userid':
                return JHTML::_('ESUserView.render', $rowValue, $option_list[0]);

            case 'usergroup':
                return tagProcessor_Value::showUserGroup((int)$rowValue);

            case 'usergroups':
                return tagProcessor_Value::showUserGroups($rowValue);

            case 'filelink':
                $processor_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                require_once($processor_file);

                return CT_FieldTypeTag_file::process($rowValue, $this->field, $option_list, $row[$this->ct->Table->realidfieldname]);

            case 'log':
                return CT_FieldTypeTag_log::getLogVersionLinks($this->ct, $rowValue, $row);

            case 'checkbox':
                if ((int)$rowValue)
                    return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES');
                else
                    return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');

            case 'date':
            case 'lastviewtime':
                return $this->dataProcess($rowValue, $option_list);

            case 'time':
                require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cttime.php');
                $seconds = JHTMLCTTime::ticks2Seconds($rowValue, $this->field->params);
                return JHTMLCTTime::seconds2FormatedTime($seconds, $option_list[0]);

            case 'changetime':
            case 'creationtime':
                return $this->timeProcess($rowValue, $option_list);
        }
        return null;
    }

    protected function orderingProcess($value, $row): string
    {
        if ($this->ct->Env->print == 1 or ($this->ct->Env->frmt != 'html' and $this->ct->Env->frmt != ''))
            return $value;

        if ($this->ct->Env->isPlugin)
            return $value;

        if (!in_array($this->ct->LayoutVariables['layout_type'], [1, 5, 6]))//If not Simple Catalog and not Catalog Page and not Catalog Item
            return $value;

        $edit_userGroup = (int)$this->ct->Params->editUserGroups;
        $isEditable = CTUser::checkIfRecordBelongsToUser($this->ct, $edit_userGroup);

        $orderby_pair = explode(' ', $this->ct->Ordering->orderby);

        if ($orderby_pair[0] == $this->field->realfieldname and $isEditable)
            $iconClass = '';
        else
            $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::_('tooltipText', 'COM_CUSTOMTABLES_FIELD_ORDERING_DISABLED');

        if ($this->ct->Env->version < 4)
            $result = '<span class="sortable-handler' . $iconClass . '"><i class="ctIconOrdering"></i></span>';
        else
            $result = '<span class="sortable-handler' . $iconClass . '"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>';

        if ($orderby_pair[0] == $this->field->realfieldname) {

            if ($this->ct->Env->version < 4)
                $result .= '<input type="text" style="display:none" name="order[]" size="5" value="' . $value . '" class="width-20 text-area-order " />';
            else
                $result .= '<input type="text" name="order[]" size="5" value="' . $value . '" class="width-20 text-area-order hidden" />';

            $result .= '<input type="checkbox" style="display:none" name="cid[]" value="' . $row[$this->ct->Table->realidfieldname] . '" class="width-20 text-area-order " />';

            $this->ct->LayoutVariables['ordering_field_type_found'] = true;
        }
        return $result;
    }

    protected function multilingual(?array $row, array $option_list)
    {
        $specific_lang = $option_list[4] ?? '';

        $postfix = ''; //first language in the list
        if ($specific_lang != '') {
            $i = 0;
            foreach ($this->ct->Languages->LanguageList as $l) {
                if ($l->sef == $specific_lang) {
                    if ($i != 0)
                        $postfix = '_' . $specific_lang;

                    break;
                }
                $i++;
            }
        } else
            $postfix = $this->ct->Languages->Postfix; //front-end default language

        $fieldname = $this->field->realfieldname . $postfix;
        $rowValue = $row[$fieldname] ?? null;

        return $this->TextFunctions($rowValue, $option_list);
    }

    public function TextFunctions($content, $parameters)
    {
        if (count($parameters) == 0)
            return $content;

        switch ($parameters[0]) {
            case "chars" :

                if (isset($parameters[1]))
                    $count = (int)$parameters[1];
                else
                    $count = -1;

                if (isset($parameters[2]) and $parameters[2] == 'true')
                    $cleanBraces = true;
                else
                    $cleanBraces = false;

                if (isset($parameters[3]) and $parameters[3] == 'true')
                    $cleanQuotes = true;
                else
                    $cleanQuotes = false;

                return JoomlaBasicMisc::chars_trimtext($content, $count, $cleanBraces, $cleanQuotes);

            case "words" :

                if (isset($parameters[1]))
                    $count = (int)$parameters[1];
                else
                    $count = -1;

                if (isset($parameters[2]) and $parameters[2] == 'true')
                    $cleanBraces = true;
                else
                    $cleanBraces = false;

                if (isset($parameters[3]) and $parameters[3] == 'true')
                    $cleanQuotes = true;
                else
                    $cleanQuotes = false;

                return JoomlaBasicMisc::words_trimtext($content, $count, $cleanBraces, $cleanQuotes);

            case "firstimage" :

                return JoomlaBasicMisc::getFirstImage($content);

            default:

                return $content;
        }
    }

    protected function blobProcess($value, array $option_list)
    {
        $fieldType = Fields::getFieldType($this->ct->Table->realtablename, $this->field->realfieldname);
        if ($fieldType != 'blob' and $fieldType != 'tinyblob' and $fieldType != 'mediumblob' and $fieldType != 'longblob')
            return self::TextFunctions($value, $option_list);

        return '[BLOB - ' . JoomlaBasicMisc::formatSizeUnits(strlen($value)) . ']';
    }

    protected function colorProcess($value, array $option_list): string
    {
        if ($value == '')
            $value = '000000';

        if ($option_list[0] == "rgba") {
            $colors = array();
            if (strlen($value) >= 6) {
                $colors[] = hexdec(substr($value, 0, 2));
                $colors[] = hexdec(substr($value, 2, 2));
                $colors[] = hexdec(substr($value, 4, 2));
            }

            if (strlen($value) == 8) {
                $a = hexdec(substr($value, 6, 2));
                $colors[] = round($a / 255, 2);
            }

            if (strlen($value) == 8)
                return 'rgba(' . implode(',', $colors) . ')';
            else
                return 'rgb(' . implode(',', $colors) . ')';
        } else
            return "#" . $value;
    }

    protected function articleProcess($rowValue, array $option_list)
    {
        if (isset($option_list[0]) and $option_list[0] != '')
            $article_field = $option_list[0];
        else
            $article_field = 'title';

        $article = $this->getArticle((int)$rowValue, $article_field);

        if (isset($option_list[1])) {
            $opts = str_replace(':', ',', $option_list[1]);
            return $this->TextFunctions($article, explode(',', $opts));
        } else
            return $article;
    }

    protected function getArticle($articleId, $field)
    {
        // get database handle
        $query = 'SELECT ' . $field . ' FROM #__content WHERE id=' . (int)$articleId . ' LIMIT 1';
        $this->ct->db->setQuery($query);

        $rows = $this->ct->db->loadAssocList();

        if (count($rows) != 1)
            return ""; //return nothing if article not found

        $row = $rows[0];
        return $row[$field];
    }

    protected function listProcess($rowValue, array $option_list): string
    {
        if (count($option_list) > 1 and $option_list[0] != "") {
            if ($option_list[0] == 'group') {
                $rootParent = $this->field->params[0];

                $orientation = 0;// horizontal
                if (isset($option_list[1]) and $option_list[1] == 'vertical')
                    $orientation = 1;// vertical

                $groupArray = CT_FieldTypeTag_ct::groupCustomTablesParents($this->ct, $rowValue, $rootParent);

                //Build structure
                $vlu = '<table><tbody>';

                if ($orientation == 0)
                    $vlu .= '<tr>';

                foreach ($groupArray as $fGroup) {
                    if ($orientation == 1)
                        $vlu .= '<tr>';

                    $vlu .= '<td><h3>' . $fGroup[0] . '</h3><ul>';

                    for ($i = 1; $i < count($fGroup); $i++)
                        $vlu .= '<li>' . $fGroup[$i] . '</li>';

                    $vlu .= '<ul></td><td></td>';

                    if ($orientation == 1)
                        $vlu .= '</tr>';
                }

                if ($orientation == 0)
                    $vlu .= '</tr>';

                $vlu .= '</tbody></table>';

                return $vlu;
            } elseif ($option_list[0] == 'list') {
                if ($rowValue != '') {
                    $vlu = explode(',', $rowValue);
                    $vlu = array_filter($vlu);

                    sort($vlu);

                    $temp_index = 0;
                    return Tree::BuildULHtmlList($vlu, $temp_index, $this->ct->Languages->Postfix);
                }
            }
        } else {
            if ($rowValue != '')
                return implode(',', Tree::getMultyValueTitles($rowValue, $this->ct->Languages->Postfix, 1, ' - ', $this->field->params));
        }
        return '';
    }

    protected function dataProcess($rowValue, array $option_list): string
    {
        if ($rowValue == '' or $rowValue == '0000-00-00' or $rowValue == '0000-00-00 00:00:00')
            return '';

        $PHPDate = strtotime($rowValue);

        if ($option_list[0] != '') {
            if ($option_list[0] == 'timestamp')
                return $PHPDate;

            return date($option_list[0], $PHPDate);
        } else
            return JHTML::date($PHPDate);
    }

    protected function timeProcess($value, array $option_list): string
    {
        $PHPDate = strtotime($value);
        if ($option_list[0] != '') {
            if ($option_list[0] == 'timestamp')
                return $PHPDate;

            return date($option_list[0], $PHPDate);
        } else {
            if ($value == '0000-00-00 00:00:00')
                return '';

            return JHTML::date($PHPDate);
        }
    }

}