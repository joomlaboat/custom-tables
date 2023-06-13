<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Layouts;
use CustomTables\TwigProcessor;
use Joomla\CMS\HTML\HTMLHelper;

class Edit
{
    var CT $ct;
    var string $layoutContent;
    var ?array $row;
    var int $layoutType;
    var ?string $pageLayoutNameString;
    var ?string $pageLayoutLink;

    function __construct(CT &$ct)
    {
        $this->ct = &$ct;
        $this->row = null;
        $this->layoutType = 0;
        $this->layoutContent = '';
        $this->pageLayoutNameString = null;
        $this->pageLayoutLink = null;
    }

    function load(): bool
    {
        if ($this->ct->Params->editLayout != '') {
            $Layouts = new Layouts($this->ct);
            $this->layoutContent = $Layouts->getLayout($this->ct->Params->editLayout);
            $this->pageLayoutNameString = $this->ct->Params->editLayout;
            $this->pageLayoutLink = '/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' . $Layouts->layoutId;

            if ($Layouts->layoutType === null) {
                $this->ct->app->enqueueMessage('Layout "' . $this->ct->Params->editLayout . '" not found or the type is not set.', 'error');
                return false;
            }
            $this->layoutType = $Layouts->layoutType;
        } else {
            $this->ct->app->enqueueMessage('Layout not set.', 'error');
            return false;
        }
        $this->ct->LayoutVariables['layout_type'] = $this->layoutType;
        return true;
    }

    public function processLayout(?array $row = null): string
    {
        if ($row !== null)
            $this->row = $row;

        if ($this->ct->Env->legacySupport) {
            $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
            require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
            require_once($path . 'layout.php');

            $LayoutProc = new LayoutProcessor($this->ct, $this->layoutContent);
            $this->layoutContent = $LayoutProc->fillLayout(null, null, '||', false, true);
            tagProcessor_Edit::process($this->ct, $this->layoutContent, $row, true);
        }

        $twig = new TwigProcessor($this->ct, $this->layoutContent, true);
        $result = $twig->process($this->row);

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

        return $result;
    }

    function render($row, $formLink, $formName): string
    {
        $result = '';

        if ($row !== null)
            $this->row = $row;

        if (!is_null($this->ct->Params->ModuleId))
            $formName .= $this->ct->Params->ModuleId;

        if ($this->ct->Env->legacySupport) {
            $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
            require_once($path . 'tagprocessor' . DIRECTORY_SEPARATOR . 'edittags.php');
            require_once($path . 'layout.php');
        }

        HTMLHelper::_('jquery.framework');
        jimport('joomla.html.html.bootstrap');

        $this->ct->loadJSAndCSS();

        if (!$this->ct->Params->blockExternalVars and $this->ct->Params->showPageHeading) {
            $result .= '<div class="page-header' . strip_tags($this->ct->Params->pageClassSFX ?? '') . '"><h2 itemprop="headline">'
                . JoomlaBasicMisc::JTextExtended($this->ct->Params->pageTitle) . '</h2></div>';
        }

        $listing_id = $this->row[$this->ct->Table->realidfieldname] ?? 0;

        $result .= '<form action="' . $formLink . '" method="post" name="' . $formName . '" id="' . $formName . '" class="form-validate form-horizontal well" '
            . 'data-tableid="' . $this->ct->Table->tableid . '" data-recordid="' . $listing_id . '" '
            . 'data-version=' . $this->ct->Env->version . '>';

        $result .= ($this->ct->Env->version < 4 ? '<fieldset>' : '<fieldset class="options-form">');

        //Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

        $this->ct->isEditForm = true; //These changes input box prefix

        if ($this->ct->Env->legacySupport) {
            $LayoutProc = new LayoutProcessor($this->ct, $this->layoutContent);

            //Better to run tag processor before rendering form edit elements because of IF statements that can exclude the part of the layout that contains form fields.
            $pageLayout = $LayoutProc->fillLayout($this->row, null, '||', false, true);
            tagProcessor_Edit::process($this->ct, $pageLayout, $this->row);
        } else
            $pageLayout = $this->layoutContent;

        $twig = new TwigProcessor($this->ct, $pageLayout, false, false, true, $this->pageLayoutNameString, $this->pageLayoutLink);
        $pageLayout = $twig->process($this->row);

        if ($twig->errorMessage !== null)
            $this->ct->app->enqueueMessage($twig->errorMessage, 'error');

        if ($this->ct->Params->allowContentPlugins)
            $pageLayout = JoomlaBasicMisc::applyContentPlugins($pageLayout);

        $result .= $pageLayout;

        $returnTo = '';

        if ($this->ct->Env->jinput->get('returnto', '', 'BASE64'))
            $returnTo = base64_decode($this->ct->Env->jinput->get('returnto', '', 'BASE64'));
        elseif ($this->ct->Params->returnTo)
            $returnTo = $this->ct->Params->returnTo;

        $encodedReturnTo = base64_encode($returnTo);

        if ($listing_id == 0) {
            $result .= '<input type="hidden" name="published" value="' . (int)$this->ct->Params->publishStatus . '" />';
        }

        $result .= '<input type="hidden" name="task" id="task" value="save" />'
            . '<input type="hidden" name="returnto" id="returnto" value="' . $encodedReturnTo . '" />'
            . '<input type="hidden" name="listing_id" id="listing_id" value="' . $listing_id . '" />';

        if (!is_null($this->ct->Params->ModuleId))
            $result .= '<input type="hidden" name="ModuleId" id="ModuleId" value="' . $this->ct->Params->ModuleId . '" />';

        $result .= ($this->ct->Env->jinput->getCmd('tmpl', '') != '' ? '<input type="hidden" name="tmpl" value="' . $this->ct->Env->jinput->getCmd('tmpl', '') . '" />' : '')
            . JHtml::_('form.token')
            . '</fieldset>
</form>';

        return $result;
    }
}