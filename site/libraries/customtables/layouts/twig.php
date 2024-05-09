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
defined('_JEXEC') or die();

use CT_FieldTypeTag_imagegallery;
use Exception;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

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
    var bool $debug;

    /**
     * @throws Exception
     * @since 3.0.0
     */
    public function __construct(CT $ct, $layoutContent, $getEditFieldNamesOnly = false, $DoHTMLSpecialChars = false, $parseParams = true, ?string $layoutName = null, ?string $pageLayoutLink = null)
    {
        $this->debug = true;

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
            common::enqueueMessage($this->errorMessage);
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
                $this->ct->errors[] = '{% endblock %} is missing';
                return;
            }

            $tag1_length = strlen($tag1);
            $record_block = substr($layoutContent, $pos1 + $tag1_length, $pos2 - $pos1 - $tag1_length);
            $record_block_replace = substr($layoutContent, $pos1, $pos2 - $pos1 + strlen($tag2));

            $this->recordBlockReplaceCode = common::generateRandomString();//this is temporary replace placeholder. to not parse content result again
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

        if (defined('_JEXEC')) {
            $CustomTablesWordPluginPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'customtablesword' . DIRECTORY_SEPARATOR . 'customtablesword.php';
            if (file_exists($CustomTablesWordPluginPath)) {
                require_once($CustomTablesWordPluginPath);
                $this->twig->addGlobal('phpword', new Twig_PHPWord_Tags());
            }
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
                            return strval($valueProcessor->renderValue($this->ct->Table->fields[$index], $this->ct->Table->record, $args));
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

    protected function addTwigFilters(): void
    {
        if ($this->ct->Env->advancedTagProcessor and class_exists('CustomTables\ctProHelpers')) {
            $filters = ctProHelpers::TwigFilters();

            foreach ($filters as $filter)
                $this->twig->addFilter($filter);
        }
    }

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

            if ($this->debug) {
                $result = $this->twig->render($this->pageLayoutName, $this->variables);
            } else {
                try {
                    $result = @$this->twig->render($this->pageLayoutName, $this->variables);
                } catch (Exception $e) {
                    $msg = $e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString();
                    $this->errorMessage = $msg;
                    $this->ct->errors[] = $msg;

//				$msg = $e->getMessage();
                    if ($this->pageLayoutLink !== null)
                        $msg = str_replace($this->pageLayoutName, '<a href="' . $this->pageLayoutLink . '" target="_blank">' . $this->pageLayoutName . '</a>', $msg);

                    return 'Error: ' . $msg;
                }
            }
        }

        if ($this->recordBlockFound) {
            $number = 1;
            $record_result = '';

            if ($this->ct->Records !== null) {
                foreach ($this->ct->Records as $blockRow) {
                    $blockRow['_number'] = $number;
                    $blockRow['_islast'] = $number == count($this->ct->Records);

                    $this->ct->Table->record = $blockRow;

                    if ($this->debug) {
                        $row_result = $this->twig->render($this->itemLayoutName, $this->variables);
                    } else {
                        try {
                            $row_result = @$this->twig->render($this->itemLayoutName, $this->variables);
                        } catch (Exception $e) {
                            $this->errorMessage = $e->getMessage();

                            $msg = $e->getMessage();
                            $pos = strpos($msg, '" at line ');

                            if ($pos !== false) {
                                $lineNumber = intval(substr($msg, $pos + 10, -1));
                                $msg = str_replace('" at line ' . $lineNumber, '" at line ' . ($lineNumber + $this->itemLayoutLineStart), $msg);
                            }

                            $msg = str_replace($this->itemLayoutName, $this->pageLayoutName, $msg);

                            if ($this->pageLayoutLink !== null)
                                $msg = str_replace($this->pageLayoutName, '<a href="' . $this->pageLayoutLink . '" target="_blank">' . $this->pageLayoutName . '</a>', $msg);

                            return 'Error: ' . $msg;
                        }
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
            $ct->errors[] = $e->getMessage();
        }
        $this->getEditFieldNamesOnly = $getEditFieldNamesOnly;
    }

    /**
     * @throws Exception
     * @since 3.2.5
     */
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

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
                . DIRECTORY_SEPARATOR . 'value.php');

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
                . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'image.php');

            $image = Value_image::getImageSRCLayoutView($options, $this->ct->Table->record[$rfn], $this->field->params);

            if ($image === null)
                return null;

            return $image['src'];

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

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
                . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

            $files = InputBox_filebox::getFileBoxRows($this->ct->Table->tablename, $this->field->fieldname, $this->ct->Table->record[$this->ct->Table->realidfieldname]);
            $fileList = [];
            foreach ($files as $file)
                $fileList[] = $file->fileid . '.' . $file->file_ext;

            $vlu = implode(',', $fileList);
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
        return $this->field->title ?? '';
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
        if (!isset($this->field->fieldrow))
            return 'Fields not found';

        if (Fields::isVirtualField($this->field->fieldrow))
            return $this->value();

        $args = func_get_args();

        if ($this->ct->isEditForm) {
            $Inputbox = new Inputbox($this->ct, $this->field->fieldrow, $args);
            $value = $Inputbox->getDefaultValueIfNeeded($this->ct->Table->record);

            $this->ct->editFields[] = $this->field->fieldname;

            if (!in_array($this->field->type, $this->ct->editFieldTypes))
                $this->ct->editFieldTypes[] = $this->field->type;

            if ($this->getEditFieldNamesOnly) {
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
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.get(field_name) }} field name not specified.';
            return '';
        }

        if ($this->field->type == 'sqljoin') {
            //2. ?array $options = null
            if (isset($functionParams[1])) {
                if (!is_array($functionParams[1])) {
                    $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.get("' . $fieldName . '",' . $functionParams[1] . ') }} value parameters must be an array.';
                    return '';
                }
                $options = $functionParams[1];
            } else
                $options = null;

            if ($options) {
                $layoutcode = '{{ ' . $fieldName . '(' . self::optionsArrayToString($options) . ') }}';
            } else
                $layoutcode = '{{ ' . $fieldName . ' }}';

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoin.php');

            return Value_tablejoin::renderTableJoinValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {
            $showPublishedString = $functionParams[1] ?? '';

            $separatorCharacter = $functionParams[2] ?? null;

            $layoutcode = '{{ ' . $fieldName . ' }}';

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoinlist.php');

            return Value_tablejoinlist::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname],
                $showPublishedString, $separatorCharacter);
        } else {
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.get }}. Wrong field type "' . $this->field->type . '". ".get" method is only available for Table Join and Records filed types.';
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
        if ($this->ct->isRecordNull($this->ct->Table->record) or count($this->ct->Table->record) < 2)
            return '';

        $functionParams = func_get_args();
        //1. $fieldName
        if (isset($functionParams[0]))
            $fieldName = $functionParams[0];
        else {
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.getvalue(field_name) }}. field_name name not specified.';
            return '';
        }

        $layoutcode = '{{ ' . $fieldName . '.value }}';

        if ($this->field->type == 'sqljoin') {

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoin.php');

            return Value_tablejoin::renderTableJoinValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {

            //2. ?string $showPublishedString = ''
            $showPublishedString = $functionParams[1] ?? '';

            //3. ?string $separatorCharacter = ''
            $separatorCharacter = $functionParams[2] ?? null;

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoinlist.php');

            return Value_tablejoinlist::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname],
                $showPublishedString, $separatorCharacter);
        } else {
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.getvalue }}. Wrong field type "' . $this->field->type . '". ".getvalue" method is only available for Table Join and Records filed types.';
            return '';
        }
    }

    public function layout(string $layoutName, ?string $showPublishedString = '', string $separatorCharacter = ','): string
    {
        if ($showPublishedString === null)
            $showPublishedString = '';

        if ($this->field->type != 'sqljoin' and $this->field->type != 'records') {
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.layout() }}. Wrong field type "' . $this->field->type . '". ".layout()" method is only available for Table Join and Records filed types.';
            return '';
        }

        if ($this->ct->isRecordNull($this->ct->Table->record) or count($this->ct->Table->record) < 2)
            return '';

        $Layouts = new Layouts($this->ct);
        $layoutCode = $Layouts->getLayout($layoutName);

        if ($layoutCode == '') {
            $this->ct->errors[] = '{{ ' . $this->field->fieldname . '.layout("' . $layoutName . '") }} Layout "' . $layoutName . '" not found or is empty.';
            return '';
        }

        if ($this->field->type == 'sqljoin') {
            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoin.php');

            return Value_tablejoin::renderTableJoinValue($this->field, $layoutCode, $this->ct->Table->record[$this->field->realfieldname]);
        } elseif ($this->field->type == 'records') {

            require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value'
                . DIRECTORY_SEPARATOR . 'tablejoinlist.php');

            return Value_tablejoinlist::resolveRecordTypeValue($this->field, $layoutCode, $this->ct->Table->record[$this->field->realfieldname], $showPublishedString, $separatorCharacter);
        }
        return 'impossible';
    }
}
