<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTables\CT;
use CustomTables\Details;

jimport('joomla.html.pane');
jimport('joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewDetails extends JViewLegacy
{
    var CT $ct;
    var Details $details;

    function display($tpl = null)
    {
        $this->ct = new CT(null, false);

        $this->details = new Details($this->ct);

        if ($this->ct->Env->print)
            $this->ct->document->setMetaData('robots', 'noindex, nofollow');

        if ($this->details->load()) {

            if ($this->details->layoutType == 8)
                $this->ct->Env->frmt = 'xml';
            elseif ($this->details->layoutType == 9)
                $this->ct->Env->frmt = 'csv';
            elseif ($this->details->layoutType == 10)
                $this->ct->Env->frmt = 'json';

            parent::display($tpl);
        }
    }
}
