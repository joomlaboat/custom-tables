<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CT_FieldTypeTag_FileBox;
use CT_FieldTypeTag_imagegallery;
use Exception;
use Joomla\CMS\Factory;
use JoomlaBasicMisc;
use CT_FieldTypeTag_sqljoin;
use CT_FieldTypeTag_records;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

use CT_FieldTypeTag_image;

$types_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR;

if (file_exists($types_path))
    require_once($types_path . '_type_image.php');

class TwigProcessor
{
    var CT $ct;
    var bool $loaded = false;
    var \Twig\Environment $twig;
    var array $variables = [];
    var bool $recordBlockFound;
    var string $recordBlockReplaceCode;
    var bool $DoHTMLSpecialChars;
    var bool $getEditFieldNamesOnly;
    var ?string $errorMessage;
    var string $pageLayoutName;
    var ?string $pageLayoutLink;
    var string $itemLayoutName;
    var string $itemLayoutLineStart;
    var bool $parseParams;

    public function __construct(CT $ct, $layoutContent, $getEditFieldNamesOnly = false, $DoHTMLSpecialChars = false, $parseParams = true, ?string $layoutName = null, ?string $pageLayoutLink = null)
    {
        $this->parseParams = $parseParams;
        $this->errorMessage = null;
        $this->DoHTMLSpecialChars = $DoHTMLSpecialChars;
        $this->ct = $ct;
        $this->getEditFieldNamesOnly = $getEditFieldNamesOnly;
        $this->ct->LayoutVariables['getEditFieldNamesOnly'] = $getEditFieldNamesOnly;

        $layoutContent = '{% autoescape false %}' . $layoutContent . '{% endautoescape %}';

        $this->pageLayoutName = preg_replace("/[^A-Za-z\d\-]/", '', ($layoutName ?? 'Inline_Layout'));
        $this->pageLayoutLink = $pageLayoutLink;
        $this->itemLayoutName = $this->pageLayoutName . '_Item';

        $tag1 = '{% block record %}';
        $pos1 = strpos($layoutContent, $tag1);

        if (!class_exists('Twig\Loader\ArrayLoader')) {
            $this->errorMessage = 'Twig not loaded. Go to Global Configuration/ Custom Tables Configuration to enable it.';
            Factory::getApplication()->enqueueMessage($this->errorMessage, 'error');
            return;
        }

        $this->itemLayoutLineStart = 0;
        if ($pos1 !== false) {
            $this->recordBlockFound = true;

            $tempContentBeforeBlock = substr($layoutContent, 0, $pos1);
            $this->itemLayoutLineStart = substr_count($tempContentBeforeBlock, "\n");

            $tag2 = '{% endblock %}';
            $pos2 = strpos($layoutContent, $tag2, $pos1 + strlen($tag1));

            if ($pos2 === false) {
                $this->ct->app->enqueueMessage('{% endblock %} is missing', 'error');
                return;
            }

            $tag1_length = strlen($tag1);
            $record_block = substr($layoutContent, $pos1 + $tag1_length, $pos2 - $pos1 - $tag1_length);
            $record_block_replace = substr($layoutContent, $pos1, $pos2 - $pos1 + strlen($tag2));

            $this->recordBlockReplaceCode = JoomlaBasicMisc::generateRandomString();//this is temporary replace placeholder. to not parse content result again
            $pageLayoutContent = str_replace($record_block_replace, $this->recordBlockReplaceCode, $layoutContent);

            $loader = new ArrayLoader([
                $this->pageLayoutName => '{% autoescape false %}' . $pageLayoutContent . '{% endautoescape %}',
                $this->itemLayoutName => '{% autoescape false %}' . $record_block . '{% endautoescape %}',
            ]);
        } else {
            $this->recordBlockFound = false;
            $loader = new ArrayLoader([
                $this->pageLayoutName => $layoutContent,
            ]);
        }
        $this->twig = new \Twig\Environment($loader);
        $this->addGlobals();
        $this->addFieldValueMethods();
        $this->addTwigFilters();
    }

    protected function addGlobals(): void
    {
        $this->twig->addGlobal('document', new Twig_Document_Tags($this->ct));
        //{{ document.setmetakeywords() }}	-	wizard ok
        //{{ document.setmetadescription() }}	-	wizard ok
        //{{ document.setpagetitle() }}	-	wizard ok
        //{{ document.setheadtag() }}	-	wizard ok
        //{{ document.layout("InvoicesItems") }}	-	wizard ok
        //{{ document.sitename() }}	-	wizard ok
        //{{ document.languagepostfix() }}	-	wizard ok

        $this->twig->addGlobal('fields', new Twig_Fields_Tags($this->ct));
        //{{ fields.list() }}	-	wizard ok
        //{{ fields.count() }}	-	wizard ok
        //{{ fields.json() }}	-	wizard ok

        $this->twig->addGlobal('user', new Twig_User_Tags($this->ct));
        //{{ user.name() }}	-	wizard ok
        //{{ user.username() }}	-	wizard ok
        //{{ user.email() }}	-	wizard ok
        //{{ user.id() }}	-	wizard ok
        //{{ user.lastvisitdate() }}	-	wizard ok
        //{{ user.registerdate() }}	-	wizard ok
        //{{ user.usergroups() }}	-	wizard ok

        $this->twig->addGlobal('url', new Twig_Url_Tags($this->ct));
        //{{ url.link() }}	-	wizard ok
        //{{ url.format() }}	-	wizard ok
        //{{ url.base64() }}	-	wizard ok
        //{{ url.root() }}	-	wizard ok
        //{{ url.getint() }}	-	wizard ok
        //{{ url.getstring() }}	-	wizard ok
        //{{ url.getuint() }}	-	wizard ok
        //{{ url.getfloat() }}	-	wizard ok
        //{{ url.getword() }}	-	wizard ok
        //{{ url.getalnum() }}	-	wizard ok
        //{{ url.getcmd() }}	-	wizard ok
        //{{ url.getstringandencode() }}	-	wizard ok
        //{{ url.getstringanddecode() }}	-	wizard ok
        //{{ url.itemid() }}	-	wizard ok
        //{{ url.set() }}	-	wizard ok
        //{{ url.server() }}	-	wizard ok

        $this->twig->addGlobal('html', new Twig_Html_Tags($this->ct));
        //{{ html.add() }}	-	wizard ok
        //{{ html.batch() }}	-	wizard ok
        //{{ html.button() }}	-	wizard ok
        //{{ html.captcha() }}	-	wizard ok
        //{{ html.goback() }}	-	wizard ok
        //{{ html.importcsv() }}	-	wizard ok
        //{{ html.tablehead() }}	-	wizard ok
        //{{ html.limit() }}	-	wizard ok
        //{{ html.message() }}	-	wizard ok
        //{{ html.navigation() }}	-	wizard ok
        //{{ html.orderby() }}	-	wizard ok
        //{{ html.pagination() }}	-	wizard ok
        //{{ html.print() }}	-	wizard ok
        //{{ html.recordcount }}	-	wizard ok
        //{{ html.recordlist }}	-	wizard ok
        //{{ html.search() }}	-	wizard ok
        //{{ html.searchbutton() }}	-	wizard ok
        //{{ html.toolbar() }}	-	wizard ok
        //{{ html.base64encode() }}	-	wizard ok

        $this->twig->addGlobal('record', new Twig_Record_Tags($this->ct));
        //{{ record.advancedjoin(function, tablename, field_findwhat, field_lookwhere, field_readvalue, additional_where, order_by_option, value_option_list) }}	-	wizard ok

        //{{ record.joincount(join_table) }}
        //{{ record.joinavg(join_table,value_field_name) }}
        //{{ record.joinmin(join_table,value_field_name) }}
        //{{ record.joinmax(join_table,value_field_name) }}
        //{{ record.joinvalue(join_table,value_field_name) }}
        //{{ record.jointable(layout,filter,orderby,limit) }}

        //{{ record.id }}	-	wizard ok
        //{{ record.number }}	-	wizard ok
        //{{ record.published }}	-	wizard ok

        $CustomTablesWordPluginPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtablesword' . DIRECTORY_SEPARATOR . 'customtablesword.php';
        if (file_exists($CustomTablesWordPluginPath)) {
            require_once($CustomTablesWordPluginPath);
            $this->twig->addGlobal('phpword', new Twig_PHPWord_Tags());
        }

        $this->variables = [];

        //{{ table.id }}	-	wizard ok
        //{{ table.name }}	-	wizard ok
        //{{ table.title }}	-	wizard ok
        //{{ table.description }}	-	wizard ok
        //{{ table.records }} same as {{ records.count }}	-	wizard ok
        //{{ table.fields }} same as {{ fields.count() }}	-	wizard ok

        //{{ tables.getvalue(tablename,field_name,recordid_or_filter, orderby) }}
        //{{ tables.getrecord(layoutname,recordid_or_filter, orderby) }}
        //{{ tables.getrecords(layoutname,filter,orderby,limit) }}

        $this->twig->addGlobal('table', new Twig_Table_Tags($this->ct));
        $this->twig->addGlobal('tables', new Twig_Tables_Tags($this->ct));
    }

    protected function addFieldValueMethods(): void
    {
        if (isset($this->ct->Table->fields)) {
            $index = 0;
            foreach ($this->ct->Table->fields as $fieldRow) {
                if ($fieldRow === null or count($fieldRow) == 0) {
                    $this->errorMessage = 'addFieldValueMethods: Field row is empty.';
                } else {
                    if ($this->parseParams) {
                        $function = new TwigFunction($fieldRow['fieldname'], function () use (&$ct, $index) {
                            //This function will process record values with field typeparams and with optional arguments
                            //Example:
                            //{{ price }}  - will return 35896.14 if field type parameter is 2,20 (2 decimals)
                            //{{ price(3,",") }}  - will return 35,896.140 if field type parameter is 2,20 (2 decimals) but extra 0 added

                            $args = func_get_args();

                            $valueProcessor = new Value($this->ct);
                            return strval($valueProcessor->renderValue($this->ct->Table->fields[$index], $this->ct->Table->record, $args, true));
                        });
                    } else {
                        $function = new TwigFunction($fieldRow['fieldname'], function () use (&$ct, $index) {
                            //This function will process record values with field typeparams and with optional arguments
                            //Example:
                            //{{ price }}  - will return 35896.14 if field type parameter is 2,20 (2 decimals)
                            //{{ price(3,",") }}  - will return 35,896.140 if field type parameter is 2,20 (2 decimals) but extra 0 added

                            $args = func_get_args();

                            $valueProcessor = new Value($this->ct);
                            return strval($valueProcessor->renderValue($this->ct->Table->fields[$index], $this->ct->Table->record, $args, false));
                        });
                    }

                    $this->twig->addFunction($function);
                    $this->variables[$fieldRow['fieldname']] = new fieldObject($this->ct, $fieldRow, $this->DoHTMLSpecialChars,
                        $this->getEditFieldNamesOnly, $this->parseParams);
                    $index++;
                }
            }
        }
    }

    protected function addTwigFilters()
    {
        $filter = new TwigFilter('base64encode', function ($string) {
            return base64_encode($string);
        });

        $this->twig->addFilter($filter);

        $filter = new TwigFilter('base64decode', function ($string) {
            return base64_decode($string);
        });

        $this->twig->addFilter($filter);

        $filter = new TwigFilter('ucwords', function ($string) {
            $string = mb_strtolower($string, "UTF-8");
            return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
        });

        $this->twig->addFilter($filter);

        $filter = new TwigFilter('md5', function ($string) {
            return md5($string);
        });

        $this->twig->addFilter($filter);

        $filter = new TwigFilter('json_decode', function ($string) {
            return json_decode($string);
        });

        $this->twig->addFilter($filter);

        $filter = new TwigFilter('json_encode', function ($string) {
            return json_encode($string);
        });

        $this->twig->addFilter($filter);
    }

    /**
     * @throws Twig\Error\RuntimeError
     * @throws Twig\Error\SyntaxError
     * @throws Twig\Error\LoaderError
     */
    public function process(?array $row = null): string
    {
        if (!class_exists('Twig\Loader\ArrayLoader'))
            return '';

        if ($row !== null)
            $this->ct->Table->record = $row;

        $isSingleRecord = false;

        if ($row == null and isset($this->ct->LayoutVariables['layout_type']) and in_array($this->ct->LayoutVariables['layout_type'], [1, 5])) {

            if ($this->ct->Params->listing_id != null and $this->ct->Params->listing_id != '')
                $isSingleRecord = true;
        }

        if ($isSingleRecord) {
            $result = '';
        } else {
            try {
                $result = @$this->twig->render($this->pageLayoutName, $this->variables);
            } catch (Exception $e) {
                $this->errorMessage = $e->getMessage();

                $msg = $e->getMessage();
                if ($this->pageLayoutLink !== null)
                    $msg = str_replace($this->pageLayoutName, '<a href="' . $this->pageLayoutLink . '" target="_blank">' . $this->pageLayoutName . '</a>', $msg);

                return 'Error: ' . $msg;
            }
        }

        if ($this->recordBlockFound) {
            $number = 1;
            $record_result = '';

            if ($this->ct->Records !== null) {
                foreach ($this->ct->Records as $blockRow) {
                    $blockRow['_number'] = $number;
                    $this->ct->Table->record = $blockRow;
                    try {
                        $row_result = @$this->twig->render($this->itemLayoutName, $this->variables);
                    } catch (Exception $e) {
                        $this->errorMessage = $e->getMessage();

                        $msg = $e->getMessage();
                        $pos = strpos($msg, '" at line ');

                        if ($pos !== false) {
                            $lineNumberString = intval(substr($msg, $pos + 10, -1));
                            $lineNumber = intval($lineNumberString);
                            $msg = str_replace('" at line ' . $lineNumberString, '" at line ' . ($lineNumber + $this->itemLayoutLineStart), $msg);
                        }

                        $msg = str_replace($this->itemLayoutName, $this->pageLayoutName, $msg);

                        if ($this->pageLayoutLink !== null)
                            $msg = str_replace($this->pageLayoutName, '<a href="' . $this->pageLayoutLink . '" target="_blank">' . $this->pageLayoutName . '</a>', $msg);

                        return 'Error: ' . $msg;
                    }

                    $TR_tag_params = array();
                    $TR_tag_params['id'] = 'ctTable_' . $this->ct->Table->tableid . '_' . $blockRow[$this->ct->Table->realidfieldname];

                    if (isset($this->ct->LayoutVariables['ordering_field_type_found']) and $this->ct->LayoutVariables['ordering_field_type_found'])
                        $TR_tag_params['data-draggable-group'] = $this->ct->Table->tableid;

                    $row_result = Ordering::addEditHTMLTagParams($row_result, 'tr', $TR_tag_params);

                    if ($isSingleRecord and $blockRow[$this->ct->Table->realidfieldname] == $this->ct->Params->listing_id)
                        return $row_result; //This allows modal edit form functionality, to load single record after Save click
                    else
                        $record_result .= $row_result;

                    $number++;
                }
            } else {
                $record_result = 'Catalog Page or Simple Catalog layout with "{% block record %}" used as Table Join value. Use Catalog Item or Details layout instead.';
            }
            $result = str_replace($this->recordBlockReplaceCode, $record_result, $result);
        }

        if ($this->ct->Table != null and $this->ct->Table->tableid != null and $row == null and
            isset($this->ct->LayoutVariables['layout_type']) and in_array($this->ct->LayoutVariables['layout_type'], [1, 5])) {
            $result = Ordering::addTableTagID($result, $this->ct->Table->tableid);
        }

        if (isset($this->ct->LayoutVariables['ordering_field_type_found']) and $this->ct->LayoutVariables['ordering_field_type_found']) {

            $result = Ordering::addTableBodyTagParams($result, $this->ct->Table->tableid);
            $result = '<form id="ctTableForm_' . $this->ct->Table->tableid . '" method="post">' . $result . '</form>';
        }
        return $result;
    }
}

class fieldObject
{
    var CT $ct;
    var Field $field;
    var bool $DoHTMLSpecialChars;
    var bool $getEditFieldNamesOnly;
    var bool $parseParams;

    function __construct(CT &$ct, $fieldRow, $DoHTMLSpecialChars = false, $getEditFieldNamesOnly = false, $parseParams = true)
    {
        $this->parseParams = $parseParams;
        $this->DoHTMLSpecialChars = $DoHTMLSpecialChars;
        $this->ct = $ct;

        try {
            $this->field = new Field($ct, $fieldRow, $this->ct->Table->record, $this->parseParams);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->getEditFieldNamesOnly = $getEditFieldNamesOnly;
    }

    public function __toString()
    {
        if (!isset($this->field))
            return 'Field not initialized.';

        $valueProcessor = new Value($this->ct);
        $vlu = $valueProcessor->renderValue($this->field->fieldrow, $this->ct->Table->record, [], $this->parseParams);

        if ($this->DoHTMLSpecialChars)
            $vlu = htmlentities($vlu, ENT_QUOTES + ENT_IGNORE + ENT_DISALLOWED + ENT_HTML5, "UTF-8");

        return strval($vlu);
    }

    public function __call($name, $arguments)
    {
        if ($this->field->fieldname == 'user') {
            $user_parameters = ['name', 'username', 'email', 'id', 'lastvisitdate', 'registerdate', 'usergroups'];
            if (in_array($name, $user_parameters)) {
                $user = new Twig_User_Tags($this->ct);

                $single_argument = 0;
                if (count($arguments) > 0)
                    $single_argument = $arguments[0];

                return $user->{$name}($single_argument);
            }
        }
        return 'unknown';
    }

    public function v()
    {
        return $this->value();
    }

    public function value()
    {
        if (!isset($this->field))
            return 'Field not initialized.';

        if ($this->ct->Table->record === null)
            return '';

        $options = func_get_args();
        $rfn = $this->field->realfieldname;

        if ($this->field->type == 'image') {
            $imageSRC = '';
            $imagetag = '';

            CT_FieldTypeTag_image::getImageSRCLayoutView($options, $this->ct->Table->record[$rfn], $this->field->params, $imageSRC, $imagetag);

            return $imageSRC;
        } elseif ($this->field->type == 'records') {
            $a = explode(",", $this->ct->Table->record[$rfn]);
            $b = array();
            foreach ($a as $c) {
                if ($c != "")
                    $b[] = $c;
            }
            $vlu = implode(',', $b);
        } elseif ($this->field->type == 'imagegallery') {
            $id = $this->ct->Table->record[$this->ct->Table->realidfieldname];
            $rows = CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename, $this->field->fieldname, $id);
            $imageSRCList = CT_FieldTypeTag_imagegallery::getImageGallerySRC($rows, $options[0] ?? '', $this->field->fieldname, $this->field->params, $this->ct->Table->tableid);

            $vlu = implode(($options[1] ?? ';'), $imageSRCList);

        } elseif ($this->field->type == 'filebox') {
            $vlu = implode(',', CT_FieldTypeTag_FileBox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $this->ct->Table->record[$this->ct->Table->realidfieldname]));
        } elseif ($this->field->type == 'virtual') {
            $valueProcessor = new Value($this->ct);
            $vlu = $valueProcessor->renderValue($this->field->fieldrow, $this->ct->Table->record, []);

            if ($this->DoHTMLSpecialChars)
                $vlu = htmlentities($vlu, ENT_QUOTES + ENT_IGNORE + ENT_DISALLOWED + ENT_HTML5, "UTF-8");

            return $vlu;
        } else {
            $vlu = $this->ct->Table->record[$rfn];
        }

        if ($this->DoHTMLSpecialChars)
            $vlu = htmlentities($vlu, ENT_QUOTES + ENT_IGNORE + ENT_DISALLOWED + ENT_HTML5, "UTF-8");

        return $vlu;
    }

    public function int(): int
    {
        return intval($this->value());
    }

    public function float(): float
    {
        return floatval($this->value());
    }

    public function t()
    {
        return $this->title();
    }

    public function title()
    {
        return $this->field->title;
    }

    public function l($allowSortBy = false)
    {
        $this->label($allowSortBy);
    }

    public function label($allowSortBy = false)
    {
        if (!isset($this->field)) {
            return 'Field not initialized.';
        }

        $forms = new Forms($this->ct);
        return $forms->renderFieldLabel($this->field, $allowSortBy);
    }

    public function description()
    {
        return $this->field->description;
    }

    public function type()
    {
        return $this->field->type;
    }

    public function params(): ?array
    {
        return $this->field->params;
    }

    public function edit()
    {
        if (Fields::isVirtualField($this->field->fieldrow))
            return $this->value();

        $args = func_get_args();

        if ($this->ct->isEditForm) {
            $Inputbox = new Inputbox($this->ct, $this->field->fieldrow, $args);
            $value = $Inputbox->getDefaultValueIfNeeded($this->ct->Table->record);

            if ($this->getEditFieldNamesOnly) {
                $this->ct->editFields[] = $this->field->fieldname;
                return '';
            } else
                return $Inputbox->render($value, $this->ct->Table->record);

        } else {
            $postfix = '';

            if ($this->ct->Table->record === null)
                $ajax_prefix = 'com__';//example: com_153_es_fieldname or com_153_ct_fieldname
            else
                $ajax_prefix = 'com_' . $this->ct->Table->record[$this->ct->Table->realidfieldname] . '_';//example: com_153_es_fieldname or com_153_ct_fieldname

            if ($this->field->type == 'multilangstring') {
                if (isset($args[4])) {
                    //multilingual field specific language
                    foreach ($this->ct->Languages->LanguageList as $lang) {
                        if ($lang->sef == $args[4]) {
                            $postfix = $lang->sef;
                            break;
                        }
                    }
                }
            }

            //Default style (borderless)
            if (isset($args[0]) and $args[0] != '') {
                $class_str = $args[0];

                if (str_contains($class_str, ':'))//it's a style, change it to attribute
                    $div_arg = ' style="' . $class_str . '"';
                else
                    $div_arg = ' class="' . $class_str . '"';
            } else
                $div_arg = '';

            // Default attribute - action to save the value
            $args[0] = 'border:none !important;width:auto;box-shadow:none;';

            if ($this->ct->Table->record === null) {

                if ($this->ct->Table->recordlist === null)
                    $this->ct->getRecordList();

                $listOfRecords = implode(',', $this->ct->Table->recordlist);
                $onchange = 'ct_UpdateAllRecordsValues(\'' . $this->ct->Env->WebsiteRoot . '\',' . $this->ct->Params->ItemId . ',\''
                    . $this->field->fieldname . '\',\'' . $listOfRecords . '\',\''
                    . $postfix . '\',' . ($this->ct->Params->ModuleId ?? 0) . ');';
            } else {
                $onchange = 'ct_UpdateSingleValue(\'' . $this->ct->Env->WebsiteRoot . '\',' . $this->ct->Params->ItemId . ',\''
                    . $this->field->fieldname . '\',\'' . $this->ct->Table->record[$this->ct->Table->realidfieldname] . '\',\''
                    . $postfix . '\',' . ($this->ct->Params->ModuleId ?? 0) . ');';
            }

            if (isset($value_option_list[1]))
                $args[1] .= $value_option_list[1];

            $Inputbox = new Inputbox($this->ct, $this->field->fieldrow, $args, true, $onchange);

            $value = $Inputbox->getDefaultValueIfNeeded($this->ct->Table->record);

            if ($this->ct->Table->record === null) {
                return '<div' . $div_arg . ' id="' . $ajax_prefix . $this->field->fieldname . $postfix . '_div">'
                    . $Inputbox->render($value, null)
                    . '</div>';
            } else {
                return '<div' . $div_arg . ' id="' . $ajax_prefix . $this->field->fieldname . $postfix . '_div">'
                    . $Inputbox->render($value, $this->ct->Table->record)
                    . '</div>';
            }
        }
    }

    public function get(): string
    {
        if ($this->ct->isRecordNull($this->ct->Table->record) or count($this->ct->Table->record) < 2)
            return '';

        $functionParams = func_get_args();
        //1. $fieldName
        if (isset($functionParams[0]))
            $fieldName = $functionParams[0];
        else {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get(field_name) }} field name not specified.', 'error');
            return '{{ ' . $this->field->fieldname . '.get(field_name) }} field name not specified.';
        }

        if ($this->field->type == 'sqljoin') {
            //2. ?array $options = null
            if (isset($functionParams[1])) {
                if (!is_array($functionParams[1])) {
                    $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get("' . $fieldName . '",' . $functionParams[1] . ') }} value parameters must be an array.', 'error');
                    return '{{ ' . $this->field->fieldname . '.get(field_name) }} field name not specified.';
                }
                $options = $functionParams[1];
            } else
                $options = null;

            if ($options) {
                $layoutcode = '{{ ' . $fieldName . '(' . self::optionsArrayToString($options) . ') }}';
            } else
                $layoutcode = '{{ ' . $fieldName . ' }}';

            return CT_FieldTypeTag_sqljoin::resolveSQLJoinTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {
            //2. ?string $showPublishedString = ''
            if (isset($functionParams[1]) and is_array($functionParams[1]))
                $showPublishedString = $functionParams[1];
            else
                $showPublishedString = '';

            //3. ?string $separatorCharacter = ''
            if (isset($functionParams[2]) and is_array($functionParams[2]))
                $separatorCharacter = $functionParams[2];
            else
                $separatorCharacter = null;

            $layoutcode = '{{ ' . $fieldName . ' }}';
            return CT_FieldTypeTag_records::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname],
                $showPublishedString, $separatorCharacter);
        } else {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get }}. Wrong field type "' . $this->field->type . '". ".get" method is only available for Table Join and Records filed types.', 'error');
            return '';
        }
    }

    protected function optionsArrayToString(array $options): string
    {
        $new_options = [];
        foreach ($options as $option) {
            if (is_numeric($option))
                $new_options[] = $option;
            elseif (is_array($option))
                $new_options[] = '[' . self::optionsArrayToString($option) . ']';
            else
                $new_options[] = '"' . $option . '"';
        }
        return implode(',', $new_options);
    }

    public function getvalue(): string
    {
        if ($this->ct->isRecordNull($this->ct->Table->record) and count($this->ct->Table->record) < 2)
            return '';

        $functionParams = func_get_args();
        //1. $fieldName
        if (isset($functionParams[0]))
            $fieldName = $functionParams[0];
        else {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.getvalue(field_name) }}. field_name name not specified.', 'error');
            return '{{ ' . $this->field->fieldname . '.getvalue(field_name) }}. field_name name not specified.';
        }

        $layoutcode = '{{ ' . $fieldName . '.value }}';

        if ($this->field->type == 'sqljoin') {
            return CT_FieldTypeTag_sqljoin::resolveSQLJoinTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {

            //2. ?string $showPublishedString = ''
            if (isset($functionParams[1]) and is_array($functionParams[1]))
                $showPublishedString = $functionParams[1];
            else
                $showPublishedString = '';

            //3. ?string $separatorCharacter = ''
            if (isset($functionParams[2]) and is_array($functionParams[2]))
                $separatorCharacter = $functionParams[2];
            else
                $separatorCharacter = null;

            return CT_FieldTypeTag_records::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname], $showPublishedString, $separatorCharacter);
        } else {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.getvalue }}. Wrong field type "' . $this->field->type . '". ".getvalue" method is only available for Table Join and Records filed types.', 'error');
            return '';
        }
    }

    public function layout(string $layoutName, ?string $showPublishedString = '', string $separatorCharacter = ','): string
    {
        if ($showPublishedString === null)
            $showPublishedString = '';

        if ($this->field->type != 'sqljoin' and $this->field->type != 'records') {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get }}. Wrong field type "' . $this->field->type . '". ".get" method is only available for Table Join and Records filed types.', 'error');
            return '';
        }

        if ($this->ct->isRecordNull($this->ct->Table->record) or count($this->ct->Table->record) < 2)
            return '';

        $Layouts = new Layouts($this->ct);
        $layoutCode = $Layouts->getLayout($layoutName);

        if ($layoutCode == '') {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.layout("' . $layoutName . '") }} Layout "' . $layoutName . '" not found or is empty.', 'error');
            return '';
        }

        if ($this->field->type == 'sqljoin') {
            return CT_FieldTypeTag_sqljoin::resolveSQLJoinTypeValue($this->field, $layoutCode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {
            return CT_FieldTypeTag_records::resolveRecordTypeValue($this->field, $layoutCode, $this->ct->Table->record[$this->field->realfieldname], $showPublishedString, $separatorCharacter);
        }
        return 'impossible';
    }
}
