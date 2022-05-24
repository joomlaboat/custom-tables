<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Tables View class
 */
class CustomtablesViewDocumentation extends JViewLegacy
{
    /**
     * display method of View
     * @return void
     */
    var bool $internal_use = false;
    var float $version;

    public function display($tpl = null)
    {
        $version = new Version;
        $this->version = (float)$version->getShortVersion();

        $this->internal_use = true;
        $jinput = Factory::getApplication()->input;

        if ($jinput->getCmd('tmpl', '') == 'component')
            $this->internal_use = false;

        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('documentation');
            $this->addToolBar();

            if ($this->version < 4)
                $this->sidebar = JHtmlSidebar::render();
        }

        // Set the document
        $this->setDocument();

        /*
        // Display the template
        */
        parent::display($tpl);
    }

    protected function addToolBar()
    {
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_DOCUMENTATION'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=documentation');
    }

    protected function setDocument()
    {
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(Text::_('COM_CUSTOMTABLES_DOCUMENTATION'));
        $this->document->addStyleSheet(JURI::root(true) . "/components/com_customtables/libraries/customtables/media/css/fieldtypes.css");


        $script = '
		<script>
			function readmoreOpenClose(itemid)
			{
			    var obj=document.getElementById(itemid);
				var c=obj.className;
				if(c.indexOf("ct_readmoreOpen")!=-1)
					c=c.replace("ct_readmoreOpen","ct_readmoreClose");
				else if(c.indexOf("ct_readmoreClosed")!=-1)
					c=c.replace("ct_readmoreClosed","ct_readmoreOpen");
				else if(c.indexOf("ct_readmoreClose")!=-1)
					c=c.replace("ct_readmoreClose","ct_readmoreOpen");
				
					
				
				
				obj.className=c;
			}
		</script>
		';

        $this->document->addCustomTag($script);
    }

    function getFieldTypes()
    {
        $xml = $this->getXMLData('fieldtypes_266.xml');
        if (count($xml) == 0 or !isset($xml->type))
            return '';

        return $this->renderFieldTypes($xml->type);
    }

    function getXMLData($file)
    {
        $xml_content = file_get_contents(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $file);
        if ($xml_content != '') {
            $xml = simplexml_load_string($xml_content) or die('Cannot load or parse "' . $file . '" file.');
            return $xml;
        }
        return '';
    }

    function renderFieldTypes(&$types)
    {
        if ($this->internal_use)
            return $this->renderFieldTypesInternal($types);
        else
            return $this->renderFieldTypesGitHub($types);
    }

    function renderFieldTypesInternal(&$types)
    {
        $result = '';

        foreach ($types as $type) {
            $type_att = $type->attributes();

            $is4Pro = (bool)(int)$type_att->proversion;
            $hidedefaultexample = (bool)(int)$type_att->hidedefaultexample;
            $isDepricated = (bool)(int)$type_att->depricated;

            if (!$isDepricated) {

                $class = 'ct_doc_free';
                if ($is4Pro)
                    $class = 'ct_doc_pro';

                if ($this->internal_use) {
                    $result .= '<div class="' . $class . ' ct_readmoreClosed" id="ctDocType_' . $type_att->ct_name . '">';
                    $result .= '<h4 onClick="readmoreOpenClose(\'ctDocType_' . $type_att->ct_name . '\')">' . $type_att->ct_name . ' - <span>' . $type_att->label . '</span>';
                } else {
                    $result .= '<div class="' . $class . '" id="ctDocType_' . $type_att->ct_name . '">';
                    $result .= '<h4>' . $type_att->ct_name . ' - <span>' . $type_att->label . '</span>';
                }

                if ($is4Pro)
                    $result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . Text::_('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

                $result .= '</h4>';

                $result .= '<p>' . $type_att->description . '</p>';

                if (!empty($type->params) and count($type->params) > 0) {
                    $content = $this->renderParametersInternal($type->params, '', '', '', '', true);
                    if ($content != '')
                        $result .= '<hr/><h5>' . Text::_('COM_CUSTOMTABLES_FIELDTYPEPARAMS') . ':</h5>' . $content;

                    $content = null;
                }


                $result .= '<hr/><h5>' . Text::_('COM_CUSTOMTABLES_EDITRECPARAMS') . ':</h5><p>Example 1:<pre class="ct_doc_pre">'
                    . '{{ <i>' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.edit }}</pre></p>';

                if (!empty($type->editparams)) {
                    foreach ($type->editparams as $p) {
                        $params = $p->params;
                        //$result.='<h5>'.Text::_('COM_CUSTOMTABLES_EDITRECPARAMS').':</h5>'
                        $result .= $this->renderParametersInternal($params,
                            '{{ ',
                            '<i>' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
                            '.edit(',
                            ') }}',
                            $hidedefaultexample);
                        break;
                    }

                }


                $result .= '<hr/><h5>' . Text::_('COM_CUSTOMTABLES_VALUEPARAMS') . ':</h5><p>Example 1:<pre class="ct_doc_pre">'
                    . '{{ <i>' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '</i> }}</pre></p>';

                if (!empty($type->valueparams)) {
                    foreach ($type->valueparams as $p) {
                        $params = $p->params;
                        //$result.='<h5>'.Text::_('COM_CUSTOMTABLES_VALUEPARAMS').':</h5>'
                        $result .= $this->renderParametersInternal($params,
                            '{{ ',
                            '<i>' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '</i>',
                            '(',
                            ') }}',
                            $hidedefaultexample);
                        break;
                    }

                }

                $result .= '<h5>' . Text::_('Pure Value (As it is)') . ':</h5>'
                    . '<p>' . Text::_('COM_CUSTOMTABLES_EXAMPLE') . ':<br/><pre class="ct_doc_pre">'
                    . '{{ <i>' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '</i>.value }}'
                    . '</pre></p>';

                $result .= '</div>';
            }
        }

        return $result;
    }

    function renderParametersInternal($params_, $opening_char, $tag_name, $postfix, $closing_char, $hidedefaultexample)
    {
        $result = '';
        $example_values = array();
        $example_values_count = 0;

        if ($params_ != null) {
            $params = $params_->param;

            foreach ($params as $param) {
                $param_att = $param->attributes();

                if (count($param_att) != 0) {
                    $result .= '<li><h6>' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . '</h6>';

                    if (!empty($param_att->type)) {
                        $value_example = '';
                        $result .= $this->renderParamTypeInternal($param, $param_att, $value_example);

                        $example_values[] = $value_example;

                        if ($value_example != '')
                            $example_values_count++;
                    }

                    $result .= '</li>';
                }
            }
        }

        $result_new = '';

        $cleanedParamsStr = implode(',', $this->cleanParams($example_values));
        if ($cleanedParamsStr != '')
            $cleanedParamsStr = '(' . $cleanedParamsStr . ')';

        if ($tag_name == '') {
            if (!(bool)(int)$hidedefaultexample) {
                $result_new .= '<p>' . Text::_('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
                    . $opening_char . $tag_name . $postfix . $cleanedParamsStr . $closing_char . '</pre></p>';
            }
        } else {
            if ($example_values_count > 0) {
                $result_new .= '<p>' . Text::_('COM_CUSTOMTABLES_EXAMPLE') . ': <pre class="ct_doc_pre">'
                    . $opening_char . $tag_name . $postfix . $cleanedParamsStr . $closing_char . '</pre></p>';
            }
        }

        return '<ol>' . $result . '</ol>' . $result_new;
    }

    function renderParamTypeInternal(&$param, &$param_att, &$value_example)
    {
        $result = '';

        $value_example = $param_att->example;

        switch ($param_att->type) {
            case 'number':

                $result .= '<ul class="ct_doc_param_options">
					<li><b>' . Text::_('COM_CUSTOMTABLES_DEFAULT') . '</b>: ' . $param_att->default . '</li>
';
                if (!empty($param_att->min))
                    $result .= '<li><b>' . Text::_('COM_CUSTOMTABLES_MIN') . '</b>: ' . $param_att->min . '</li>';

                if (!empty($param_att->max))
                    $result .= '<li><b>' . Text::_('COM_CUSTOMTABLES_MAX') . '</b>: ' . $param_att->max . '</li>';

                $result .= '</ul>';

                if (!empty($param_att->example))
                    $value_example = $param_att->example;
                else
                    $value_example = $param_att->min;

                break;

            case 'radio':
                $options = explode(',', $param_att->options);
                $value_example = '';
                //<p>'.Text::_('COM_CUSTOMTABLES_OPTIONS').':</p>
                $result .= '<ul class="ct_doc_param_options">';
                foreach ($options as $option) {
                    $parts = explode('|', $option);

                    if ($parts[0] == '')
                        $result .= '<li>(' . $parts[1] . ' - default)</li>';
                    else
                        $result .= '<li><b>' . $parts[0] . '</b>: ' . $parts[1] . '</li>';

                    if ($value_example == '' && $parts[0] != '')
                        $value_example = $parts[0];
                }

                $result .= '</ul>';
                break;

            case 'list':

                $options = $param->option;
                $value_example = '';

                if (!empty($param_att->example))
                    $value_example = $param_att->example;

                $result .= '<p><ul class="ct_doc_param_options">';
                foreach ($options as $option) {
                    $option_att = $option->attributes();

                    $result .= '<li>';

                    if ($option_att->value == '')
                        $par = '(Default. ';
                    else
                        $par = '<b>' . $option_att->value . '</b> - (';

                    $result .= $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description . '.' : '') . ')';

                    $result .= '</li>';

                    if ($value_example == '' and $option_att->value != '')
                        $value_example = $option_att->value;
                }

                $result .= '</ul>';
                break;
        }

        if (!((bool)(int)$param_att->examplenoquotes))
            $value_example = $this->prepareExample($value_example);

        return $result;
    }

    function prepareExample($param)
    {
        if (!is_numeric($param) and $param != 'true' and $param != 'false')
            return '"' . $param . '"';

        return $param;
    }

    function cleanParams($params)
    {
        $new_params = array();
        $count = 0;

        foreach ($params as $param_) {
            $count++;
            $param = trim($param_);
            if ($param != '' and $param != '""') {
                for ($i = 1; $i < $count; $i++)
                    $new_params[] = '""';

                $param = str_replace('<', '&lt;', $param);
                $param = str_replace('>', '&gt;', $param);
                $new_params[] = $param;

                $count = 0;
            }
        }
        return $new_params;
    }

    function renderFieldTypesGitHub(&$types)
    {
        $result = '';

        foreach ($types as $type) {
            $type_att = $type->attributes();

            $is4Pro = (bool)(int)$type_att->proversion;
            $hidedefaultexample = (bool)(int)$type_att->hidedefaultexample;
            $isDepricated = (bool)(int)$type_att->depricated;

            if (!$isDepricated) {
                $result .= '# ' . $type_att->ct_name . '<br/><br/>' . $type_att->label . ' - ' . $type_att->description . '<br/><br/>';

                if (!empty($type->params) and count($type->params) > 0) {
                    $content = $this->renderParametersGitHub($type->params, '', '', '', '', true);
                    if ($content != '')
                        $result .= '## ' . Text::_('COM_CUSTOMTABLES_FIELDTYPEPARAMS') . ':<br/><br/>' . $content;

                    $content = null;
                }


                $result .= '## ' . Text::_('COM_CUSTOMTABLES_EDITRECPARAMS') . ':<br/><br/>Example:'
                    . '`{{ ' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '.edit }}`<br/><br/>';

                if (!empty($type->editparams)) {
                    foreach ($type->editparams as $p) {
                        $params = $p->params;

                        $result .= $this->renderParametersGitHub($params,
                            '{{ ',
                            str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')),
                            '.edit(',
                            ') }}',
                            $hidedefaultexample);
                        break;
                    }
                }

                $result .= '## ' . Text::_('COM_CUSTOMTABLES_VALUEPARAMS') . ':<br/><br/>Example:'
                    . '`{{ ' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . ' }}`';

                if (!empty($type->valueparams)) {
                    foreach ($type->valueparams as $p) {
                        $params = $p->params;

                        $result .= $this->renderParametersGitHub($params,
                            '{{ ', str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')),
                            '(',
                            ') }}',
                            $hidedefaultexample);
                        break;

                    }

                }

                $result .= '## ' . Text::_('Pure Value (As it is)') . ':<br/><br/>'
                    . Text::_('COM_CUSTOMTABLES_EXAMPLE') . ':<br/><br/>'
                    . '`{{ ' . str_replace(' ', '', Text::_('COM_CUSTOMTABLES_FIELDNAME')) . '.value }}`'
                    . '<br/><br/>';
            }
        }

        return $result;
    }

    function renderParametersGitHub($params_, $opening_char, $tag_name, $postfix, $closing_char, $hidedefaultexample)
    {
        $result = '';
        if ($params_ != null) {
            $params = $params_->param;
            $example_values = array();
            $example_values_count = 0;

            $count = 1;
            foreach ($params as $param) {
                $param_att = $param->attributes();

                if (count($param_att) != 0) {
                    $result .= $count . '. ' . $param_att->label . ($param_att->description != '' ? ' - ' . $param_att->description : '') . '<br/>';

                    if (!empty($param_att->type)) {
                        $value_example = '';
                        $result .= $this->renderParamTypeGitHub($param, $param_att, $value_example) . '<br/>';


                        if ($value_example != '') {
                            $example_values[] = $value_example;
                            $example_values_count++;
                        }
                    }
                }
                $count += 1;
            }
        }

        $result_new = '';

        $cleanedParamsStr = implode(',', $this->cleanParams($example_values));
        if ($cleanedParamsStr != '')
            $cleanedParamsStr = '(' . $cleanedParamsStr . ')';

        if ($tag_name == '') {
            if (!(bool)(int)$hidedefaultexample)
                $result_new .= '`' . $opening_char . $tag_name . $postfix . $cleanedParamsStr . $closing_char . '`<br/>';
        } else {
            if ($example_values_count > 0)
                $result_new .= '`' . $opening_char . $tag_name . $postfix . $cleanedParamsStr . $closing_char . '`<br/>';
        }

        return $result . $result_new;
    }

    function renderParamTypeGitHub(&$param, &$param_att, &$value_example)
    {
        $result = '';

        $value_example = $param_att->example;

        switch ($param_att->type) {
            case 'number':

                $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* **' . Text::_('COM_CUSTOMTABLES_DEFAULT') . '** - ' . $param_att->default . '<br/>';

                if (!empty($param_att->min))
                    $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* **' . Text::_('COM_CUSTOMTABLES_MIN') . '** - ' . $param_att->min . '<br/>';

                if (!empty($param_att->max))
                    $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* **' . Text::_('COM_CUSTOMTABLES_MAX') . '** - ' . $param_att->max . '<br/>';

                if (!empty($param_att->example))
                    $value_example = $param_att->example;
                else
                    $value_example = $param_att->min;

                break;

            case 'radio':
                $options = explode(',', $param_att->options);
                $value_example = '';

                foreach ($options as $option) {
                    $parts = explode('|', $option);

                    if ($parts[0] == '')
                        $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* (' . $parts[1] . ' - default)<br/>';
                    else
                        $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* **' . $parts[0] . '** - (' . $parts[1] . ')<br/>';

                    if ($value_example == '' && $parts[0] != '')
                        $value_example = $parts[0];
                }

                //$result.='<br/>';

                break;

            case 'list':

                $options = $param->option;
                $value_example = '';

                if (!empty($param_att->example)) {
                    $value_example = $param_att->example;
                }

                foreach ($options as $option) {
                    $option_att = $option->attributes();

                    if ($option_att->value == '')
                        $par = '(Default. ';
                    else
                        $par = '**' . $option_att->value . '** - (';

                    $result .= '&nbsp;&nbsp;&nbsp;&nbsp;* ' . $par . $option_att->label . ((!empty($option_att->description) and $option_att->description != '') ? '. ' . $option_att->description : '') . ')';

                    $result .= '<br/>';

                    if ($value_example == '' and $option_att->value != '')
                        $value_example = $option_att->value;
                }

                //$result.='<br/>';

                break;
        }

        if (!((bool)(int)$param_att->examplenoquotes))
            $value_example = $this->prepareExample($value_example);

        return $result;
    }

    function getLayoutTags()
    {
        $xml = $this->getXMLData('tags_266.xml');

        if (count($xml) == 0)
            return '';

        return $this->renderLayoutTagSets($xml->tagset);
    }

    function renderLayoutTagSets(&$tagsets)
    {
        if ($this->internal_use)
            return $this->renderLayoutTagSetsInternal($tagsets);
        else
            return $this->renderLayoutTagSetsGitHub($tagsets);
    }

    function renderLayoutTagSetsInternal(&$tagsets)
    {
        $result = '';

        foreach ($tagsets as $tagset) {
            $tagset_att = $tagset->attributes();

            if ((int)$tagset_att->depricated == 0) {
                $is4Pro = (bool)(int)$tagset_att->proversion;
                $class = 'ct_doc_tagset_free';
                if ($is4Pro)
                    $class = 'ct_doc_tagset_pro';

                $result .= '<div class="' . $class . '">';

                $result .= '<h3>' . $tagset_att->label . '';
                if ($is4Pro)
                    $result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . Text::_('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

                $result .= '</h3>';

                $result .= '<p>' . $tagset_att->description . '</p>';
                $result .= $this->renderTagsInternal($tagset->tag, $tagset_att->name);
                $result .= '</div>';
            }
        }
        return $result;
    }

    function renderTagsInternal(&$tags, $tagsetname)
    {
        $result = '';

        foreach ($tags as $tag) {
            $tag_att = $tag->attributes();

            $is4Pro = (bool)(int)$tag_att->proversion;
            $hidedefaultexample = (bool)(int)$tag_att->hidedefaultexample;
            $isDepricated = (bool)(int)$tag_att->depricated;

            $separator = ':';
            if (!empty($tag_att->separator))
                $separator = $tag_att->separator;

            if (!$isDepricated) {
                $class = 'ct_doc_free';
                if ($is4Pro)
                    $class = 'ct_doc_pro';


                if ($tagsetname == 'plugins') {
                    $startchar = '{';
                    $endchar = '}';
                } else {
                    $startchar = '{{ ' . $tag_att->twigclass . '.';
                    $endchar = ' }}';
                }

                $result .= '<div class="' . $class . ' ct_readmoreClosed" id="ctDocTag_' . $tag_att->twigclass . '_' . $tag_att->name . '">';
                $result .= '<a name="' . $tag_att->twigclass . '_' . $tag_att->name . '"></a><h4 onClick="readmoreOpenClose(\'ctDocTag_' . $tag_att->twigclass . '_' . $tag_att->name . '\')">' . $startchar . $tag_att->name . $endchar . ' - <span>' . $tag_att->label . '</span>';

                if ($is4Pro)
                    $result .= '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">' . Text::_('COM_CUSTOMTABLES_AVAILABLE') . '</a></div>';

                $result .= '</h4>';

                if ($tagsetname != 'plugins') {
                    $result .= '<p>' . $tag_att->description . '</p>';

                    if (!empty($tag->params) and count($tag->params) > 0) {
                        $content = $this->renderParametersInternal($tag->params,
                            '{{ ',
                            '<i>' . $tag_att->twigclass . '.' . $tag_att->name . '</i>',
                            '',
                            ' }}',
                            $hidedefaultexample);

                        if ($content != '')
                            $result .= '<h5>' . Text::_('COM_CUSTOMTABLES_PARAMS') . ':</h5>' . $content;

                        $content = null;
                    }
                }
                $result .= '</div>';
            }
        }

        return $result;
    }

    function renderLayoutTagSetsGitHub(&$tagsets)
    {
        $result = '';
        foreach ($tagsets as $tagset) {
            $tagset_att = $tagset->attributes();

            if ((int)$tagset_att->depricated == 0 and $tagset_att->name != 'plugins') {
                $result .= '# ' . $tagset_att->label . '<br/><br/>';
                $result .= $this->renderTagsGitHub($tagset->tag, $tagset_att->name) . '<br/><br/><br/>';
            }
        }
        return $result;
    }

    function renderTagsGitHub(&$tags, $tagsetname)
    {
        $result = '';

        foreach ($tags as $tag) {
            $tag_att = $tag->attributes();
            $hidedefaultexample = (bool)(int)$tag_att->hidedefaultexample;
            $isDepricated = (bool)(int)$tag_att->depricated;

            $separator = ':';
            if (!empty($tag_att->separator))
                $separator = $tag_att->separator;

            if (!$isDepricated) {
                if ($tagsetname == 'plugins') {
                    $startchar = '{';
                    $endchar = '}';
                } else {
                    $startchar = '{{ ' . $tag_att->twigclass . '.';
                    $endchar = ' }}';
                }
                //$tag_att->label
                $result .= '## ' . $tag_att->twigclass . '.' . $tag_att->name . '<br/><br/>' . $tag_att->description . '<br/><br/>';


                if ($tagsetname != 'plugins') {

                    if (!empty($tag->params) and count($tag->params) > 0) {
                        $content = $this->renderParametersGitHub($tag->params,
                            '{{ ',
                            '' . $tag_att->twigclass . '.' . $tag_att->name . '',
                            '',
                            ' }}',
                            $hidedefaultexample);

                        if ($content != '')
                            $result .= '**' . Text::_('COM_CUSTOMTABLES_PARAMS') . '**<br><br>' . $content;

                        $content = null;
                    }

                }

                $result .= '<br/>';
            }
        }

        return $result;
    }

    function reIndexArray($arrays)
    {
        $array = array();
        $i = 0;
        foreach ($arrays as $k => $item) {
            $array[$i] = $item;
            unset($arrays[$k]);
            $i++;
        }
        return $array;
    }
}
