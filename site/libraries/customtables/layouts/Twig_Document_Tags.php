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
use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use LayoutProcessor;

defined('_JEXEC') or die();

class Twig_Document_Tags
{
    var CT $ct;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
    }

    function setmetakeywords($metakeywords): void
    {
        $this->ct->document->setMetaData('keywords', $metakeywords);
    }

    function setmetadescription($metadescription): void
    {
        $this->ct->document->setMetaData('description', $metadescription);
    }

    /**
     * @throws Exception
     * @since 3.2.9
     */
    function setpagetitle($pageTitle): void
    {
        if (defined('_JEXEC')) {
            $this->ct->document->setTitle(common::translate($pageTitle));
        } elseif (defined('WPINC')) {
            common::enqueueMessage('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables for WordPress.');
        } else
            common::enqueueMessage('Warning: The {{ document.setpagetitle }} tag is not supported in the current version of the Custom Tables.');
    }

    function setheadtag($tag): void
    {
        $this->ct->document->addCustomTag($tag);
    }

    function script($link): string
    {
        if (defined('_JEXEC')) {
            $this->ct->document->addScript($link);
            return '';
        } elseif (defined('WPINC')) {
            if (!isset($this->ct->LayoutVariables['scripts']))
                $this->ct->LayoutVariables['scripts'] = [];

            $this->ct->LayoutVariables['scripts'][] = $link;
            return '';
        } else {
            return '{{ document.script() }} not supported in this version of Custom Tables';
        }
    }

    function style($link): string
    {
        if (defined('_JEXEC')) {
            $this->ct->document->addStyleSheet($link);
            return '';
        } elseif (defined('WPINC')) {
            if (!isset($this->ct->LayoutVariables['styles']))
                $this->ct->LayoutVariables['styles'] = [];

            $this->ct->LayoutVariables['styles'][] = $link;
            return '';
        } else {
            return '{{ document.style() }} not supported in this version of Custom Tables';
        }
    }

    function jslibrary($library): string
    {
        if (defined('_JEXEC')) {

            switch ($library) {
                case 'jquery':
                    HTMLHelper::_('jquery.framework');
                    break;

                case 'jquery-ui-core':
                    // Add jQuery (if not already included)
                    HTMLHelper::_('jquery.framework');

                    // Add the jQuery UI core library
                    $this->ct->document->addScript('https://code.jquery.com/ui/1.14.0/jquery-ui.min.js');

                    // Add the jQuery UI CSS
                    $this->ct->document->addStyleSheet('https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css');
            }

            return '';
        } elseif (defined('WPINC')) {
            if (!isset($this->ct->LayoutVariables['jslibrary']))
                $this->ct->LayoutVariables['jslibrary'] = [];

            $this->ct->LayoutVariables['jslibrary'][] = $library;
            return '';
        } else {
            return '{{ document.jslibrary() }} not supported in this version of Custom Tables';
        }
    }

    /**
     * @throws Exception
     * @since 3.2.9
     */
    function layout(string $layoutName = ''): ?string
    {
        if ($layoutName == '') {
            common::enqueueMessage('Warning: The {{ document.layout("layout_name") }} layout name is required.');
            return null;
        }

        if (!isset($this->ct->Table)) {
            $this->ct->errors[] = '{{ document.layout }} - Table not loaded.';
            return '';
        }

        $layouts = new Layouts($this->ct);
        $layout = $layouts->getLayout($layoutName);

        if (is_null($layouts->tableId)) {
            $this->ct->errors[] = '{{ document.layout("' . $layoutName . '") }} - Layout "' . $layoutName . ' not found.';
            return '';
        }

        if ($layouts->tableId != $this->ct->Table->tableid) {
            $this->ct->errors[] = '{{ document.layout("' . $layoutName . '") }} - Layout Table ID and Current Table ID do not match.';
            return '';
        }

        $twig = new TwigProcessor($this->ct, $layout, $this->ct->LayoutVariables['getEditFieldNamesOnly'] ?? false);
        $number = 1;
        $html_result = '';

        if ($layouts->layoutType == 6 and !is_null($this->ct->Records)) {
            foreach ($this->ct->Records as $row) {
                $row['_number'] = $number;
                $row['_islast'] = $number == count($this->ct->Records);

                $html_result_layout = $twig->process($row);
                if ($twig->errorMessage !== null)
                    $this->ct->errors[] = $twig->errorMessage;

                if ($this->ct->Env->legacySupport) {
                    $LayoutProc = new LayoutProcessor($this->ct);
                    $LayoutProc->layout = $html_result_layout;
                    $html_result_layout = $LayoutProc->fillLayout($row);
                }

                $html_result .= $html_result_layout;

                $number++;
            }
        } else {
            $html_result = $twig->process($this->ct->Table->record);
            if ($twig->errorMessage !== null)
                $this->ct->errors[] = $twig->errorMessage;

            if ($this->ct->Env->legacySupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $html_result;
                $html_result = $LayoutProc->fillLayout($this->ct->Table->record);
            }
        }
        return $html_result;
    }

    /**
     * @throws Exception
     * @since 3.0.0
     */
    function sitename(): ?string
    {
        if (defined('_JEXEC'))
            return $this->ct->app->get('sitename');
        elseif (defined('WPINC'))
            return get_bloginfo('name');
        else
            common::enqueueMessage('Warning: The {{ document.sitename }} tag is not supported by the current version of the Custom Tables.');

        return null;
    }

    /**
     * @throws Exception
     * @since 3.4.1
     */
    public function get(string $variable)
    {
        return $this->ct->LayoutVariables['globalVariables'][$variable];
    }

    function languagepostfix(): string
    {
        return $this->ct->Languages->Postfix;
    }

    /**
     * @throws Exception
     * @since 3.4.1
     */
    public function set(string $variable, $value)
    {
        $this->ct->LayoutVariables['globalVariables'][$variable] = $value;
    }

}