<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\CT;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class CustomTablesViewStructure extends JView
{
    var CT $ct;
    var $Model;
    var array $rows;
    var $pagination;
    var int $record_count;
    var $linkable;
    var string $fieldName;
    var $row_break;
    var $image_prefix;
    var string $optionname;

    function display($tpl = null)
    {
        $this->Model = $this->getModel();
        $this->ct = $this->Model->ct;
        $this->rows = $this->Model->getStructure();
        $this->pagination = $this->Model->getPagination();
        $this->record_count = $this->Model->record_count;
        $this->linkable = $this->Model->linkable;
        $this->fieldName = $this->Model->esfieldname;
        $this->row_break = $this->Model->row_break;
        $this->image_prefix = $this->Model->image_prefix;
        $this->optionname = $this->Model->optionname;

        parent::display($tpl);
    }
}
