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

use Exception;
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

    public function __construct(CT &$ct, $layoutContent)
    {
        $htmlresult_ = '{% autoescape false %}' . $layoutContent . '{% endautoescape %}';

        $this->ct = $ct;

        $tag1 = '{% block record %}';
        $pos1 = strpos($htmlresult_, $tag1);

        if ($pos1 !== false) {
            $this->recordBlockFound = true;

            $tag2 = '{% endblock %}';

            $pos2 = strpos($htmlresult_, $tag2, $pos1 + strlen($tag1));
            if ($pos2 === false) {
                $this->ct->app->enqueueMessage('{% endblock %} is missing', 'error');
                return;
            }

            $tag1_length = strlen($tag1);
            $record_block = substr($htmlresult_, $pos1 + $tag1_length, $pos2 - $pos1 - $tag1_length);
            $record_block_replace = substr($htmlresult_, $pos1, $pos2 - $pos1 + strlen($tag2));

            $this->recordBlockReplaceCode = JoomlaBasicMisc::generateRandomString();//this is temporary replace placeholder. to not parse content result again

            $htmlresult = str_replace($record_block_replace, $this->recordBlockReplaceCode, $htmlresult_);

            $loader = new ArrayLoader([
                'index' => '{% autoescape false %}' . $htmlresult . '{% endautoescape %}',
                'record' => '{% autoescape false %}' . $record_block . '{% endautoescape %}',
            ]);
        } else {
            $this->recordBlockFound = false;
            $loader = new ArrayLoader([
                'index' => $htmlresult_,
            ]);
        }

        $this->twig = new \Twig\Environment($loader);

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

        $this->twig->addGlobal('document', new Twig_Document_Tags($this->ct));
        //{{ document.setmetakeywords() }}	-	wizard ok
        //{{ document.setmetadescription() }}	-	wizard ok
        //{{ document.setpagetitle() }}	-	wizard ok
        //{{ document.setheadtag() }}	-	wizard ok
        //{{ document.layout("InvoicesItems") }}	-	wizard ok
        //{{ document.sitename() }}	-	wizard ok
        //{{ document.languagepostfix() }}	-	wizard ok

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

        if (isset($this->ct->Table->fields)) {
            $index = 0;
            foreach ($this->ct->Table->fields as $fieldrow) {

                $function = new TwigFunction($fieldrow['fieldname'], function () use (&$ct, $index) {
                    //This function will process record values with field typeparams and with optional arguments
                    //Example:
                    //{{ price }}  - will return 35896.14 if field type parameter is 2,20 (2 decimals)
                    //{{ price(3,",") }}  - will return 35,896.140 if field type parameter is 2,20 (2 decimals) but extra 0 added

                    $args = func_get_args();


                    $valueProcessor = new Value($this->ct);
                    return strval($valueProcessor->renderValue($this->ct->Table->fields[$index], $this->ct->Table->record, $args));
                });

                $this->twig->addFunction($function);

                $this->variables[$fieldrow['fieldname']] = new fieldObject($this->ct, $fieldrow);

                $index++;
            }
        }

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
    }

    public function process(?array $row = null): string
    {
        if ($row !== null)
            $this->ct->Table->record = $row;

        try {
            $result = @$this->twig->render('index', $this->variables);
        } catch (Exception $e) {
            $this->ct->app->enqueueMessage($e->getMessage(), 'error');
            return $e->getMessage();
        }

        if ($this->recordBlockFound) {
            $number = 1;
            $record_result = '';
            foreach ($this->ct->Records as $row) {
                $row['_number'] = $number;
                $this->ct->Table->record = $row;
                $record_result .= @$this->twig->render('record', $this->variables);
                $number++;
            }

            return str_replace($this->recordBlockReplaceCode, $record_result, $result);
        }

        return $result;
    }
}

class fieldObject
{
    var CT $ct;
    var Field $field;

    function __construct(CT &$ct, $fieldrow)
    {
        $this->ct = &$ct;
        $this->field = new Field($ct, $fieldrow, $this->ct->Table->record);
    }

    public function __toString()
    {
        $valueProcessor = new Value($this->ct);
        $vlu = $valueProcessor->renderValue($this->field->fieldrow, $this->ct->Table->record, []);
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

    public function fieldname()
    {
        return $this->field->fieldname;
    }

    public function v()
    {
        return $this->value();
    }

    public function value()
    {
        $options = func_get_args();
        $rfn = $this->field->realfieldname;

        if ($this->field->type == 'image') {
            $imagesrc = '';
            $imagetag = '';

            CT_FieldTypeTag_image::getImageSRClayoutview($options, $this->ct->Table->record[$rfn], $this->field->params, $imagesrc, $imagetag);

            return $imagesrc;
        } elseif ($this->field->type == 'records') {
            $a = explode(",", $this->ct->Table->record[$rfn]);
            $b = array();
            foreach ($a as $c) {
                if ($c != "")
                    $b[] = $c;
            }
            return implode(',', $b);
        } else
            return $this->ct->Table->record[$rfn];
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

    public function label($allowSortBy = false)
    {
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
        $args = func_get_args();

        if ($this->ct->isEditForm) {
            $Inputbox = new Inputbox($this->ct, $this->field->fieldrow, $args);

            $value = $Inputbox->getDefaultValueIfNeeded($this->ct->Table->record);

            return $Inputbox->render($value, $this->ct->Table->record);
        } else {
            $postfix = '';
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

            //Deafult style (borderless)
            if (isset($args[0]) or $args[0] != '') {
                $class_str = $args[0];

                if (str_contains($class_str, ':'))//it's a style, change it to attribute
                    $div_arg = ' style="' . $class_str . '"';
                else
                    $div_arg = ' class="' . $class_str . '"';
            } else
                $div_arg = '';

            // Default attribute - action to save the value
            $args[0] = 'border:none !important;width:auto;box-shadow:none;';

            $onchange = 'ct_UpdateSingleValue(\'' . $this->ct->Env->WebsiteRoot . '\',' . $this->ct->Params->ItemId . ',\''
                . $this->field->fieldname . '\',' . $this->ct->Table->record[$this->ct->Table->realidfieldname] . ',\''
                . $postfix . '\',' . (int)$this->ct->Params->ModuleId . ');';

            if (isset($value_option_list[1]))
                $args[1] .= $value_option_list[1];

            $Inputbox = new Inputbox($this->ct, $this->field->fieldrow, $args, true, $onchange);

            $value = $Inputbox->getDefaultValueIfNeeded($this->ct->Table->record);

            return '<div' . $div_arg . ' id="' . $ajax_prefix . $this->field->fieldname . $postfix . '_div">'
                . $Inputbox->render($value, $this->ct->Table->record)
                . '</div>';
        }
    }

    public function get($fieldname, array $args = []): string
    {
        if ($this->field->type == 'sqljoin') {
            $layoutcode = '{{ ' . $fieldname . ' }}';
            return CT_FieldTypeTag_sqljoin::resolveSQLJoinTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname], $args);
        } elseif ($this->field->type == 'records') {
            $layoutcode = '{{ ' . $fieldname . ' }}';
            return CT_FieldTypeTag_records::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname], $args);
        } else {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get }}. Wrong field type "' . $this->field->type . '". ".get" method is only available for Table Join and Records filed types.', 'error');
            return '';
        }
    }

    public function layout(string $layoutname, array $args = []): string
    {
        if ($this->field->type != 'sqljoin' and $this->field->type != 'records') {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.get }}. Wrong field type "' . $this->field->type . '". ".get" method is only available for Table Join and Records filed types.', 'error');
            return '';
        }

        $args = func_get_args();

        $Layouts = new Layouts($this->ct);
        $layoutcode = $Layouts->getLayout($layoutname);

        if ($layoutcode == '') {
            $this->ct->app->enqueueMessage('{{ ' . $this->field->fieldname . '.layout("' . $layoutname . '") }} Layout "' . $layoutname . '" not found or is empty.', 'error');
            return '';
        }

        if ($this->field->type == 'sqljoin') {
            return CT_FieldTypeTag_sqljoin::resolveSQLJoinTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname], $args);
        } elseif ($this->field->type == 'records') {
            return CT_FieldTypeTag_records::resolveRecordTypeValue($this->field, $layoutcode, $this->ct->Table->record[$this->field->realfieldname], $args);
        }
        return 'impossible';

    }
}
