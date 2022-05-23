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
use CustomTables\Layouts;

jimport('joomla.html.pane');
jimport('joomla.application.component.view'); //Important to get menu parameters

class CustomTablesViewDetails extends JViewLegacy
{
    var CT $ct;
    var ?array $row;
    var string $layoutDetailsContent;

    function display($tpl = null)
    {
        $this->ct = new CT;
        $this->ct->setParams(null, false);

        $Model = $this->getModel();
        $Model->load($this->ct);

        $this->layoutDetailsContent = '';

        if ($this->ct->Params->detailsLayout != '') {
            $Layouts = new Layouts($this->ct);
            $this->layoutDetailsContent = $Layouts->getLayout($this->ct->Params->detailsLayout);

            if ($Layouts->layouttype == 8)
                $this->ct->Env->frmt = 'xml';
            elseif ($Layouts->layouttype == 9)
                $this->ct->Env->frmt = 'csv';
            elseif ($Layouts->layouttype == 10)
                $this->ct->Env->frmt = 'json';
        }

        $this->row = $this->get('Data');

        if (count($this->row) > 0) {
            $returnto = $this->ct->Params->returnTo;

            if ((!isset($this->row[$this->ct->Table->realidfieldname]) or (int)$this->row[$this->ct->Table->realidfieldname] == 0) and $returnto != '') {
                $this->ct->app->redirect($returnto);
            }

            if ($this->ct->Env->print)
                $this->ct->document->setMetaData('robots', 'noindex, nofollow');

            parent::display($tpl);

            //Save view log
            $this->SaveViewLogForRecord($this->row);
            $this->UpdatePHPOnView($Model, $this->row);
        }
    }

    protected function SaveViewLogForRecord($rec)
    {
        $updateFields = array();

        $allowedTypes = ['lastviewtime', 'viewcount'];

        foreach ($this->ct->Table->fields as $mFld) {
            $t = $mFld['type'];
            if (in_array($t, $allowedTypes)) {

                $allow_count = true;
                $author_user_field = $mFld['typeparams'];

                if (!isset($author_user_field) or $author_user_field == '' or $rec[$this->ct->Env->field_prefix . $author_user_field] == $this->ct->Env->userid)
                    $allow_count = false;

                if ($allow_count) {
                    $n = $this->ct->Env->field_prefix . $mFld['fieldname'];
                    if ($t == 'lastviewtime')
                        $updateFields[] = $n . '="' . date('Y-m-d H:i:s') . '"';
                    elseif ($t == 'viewcount')
                        $updateFields[] = $n . '=' . ((int)($rec[$n]) + 1);
                }
            }
        }

        if (count($updateFields) > 0) {
            $query = 'UPDATE #__customtables_table_' . $this->ct->Table->tablename . ' SET ' . implode(', ', $updateFields) . ' WHERE id=' . $rec[$this->ct->Table->realidfieldname];

            $this->ct->db->setQuery($query);
            $this->ct->db->execute();
        }
    }

    protected function UpdatePHPOnView($Model, $row): bool
    {
        if (!isset($row[$this->ct->Table->realidfieldname]))
            return false;

        foreach ($this->ct->Table->fields as $mFld) {
            if ($mFld['type'] == 'phponview') {
                $fieldname = $mFld['fieldname'];
                $type_params = JoomlaBasicMisc::csv_explode(',', $mFld['typeparams']);
                tagProcessor_PHP::processTempValue($Model, $row, $fieldname, $type_params);
            }
        }
        return true;
    }
}
