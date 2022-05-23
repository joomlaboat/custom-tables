<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

//use CustomTables\CTUser;
use \Joomla\CMS\Factory;

//use \JoomlaBasicMisc;
//use \Joomla\CMS\Uri\Uri;


class Forms
{
    var $ct;

    function __construct(&$ct)
    {
        $this->ct = $ct;
    }

    function renderFieldLabel(&$field)
    {
        if ($field->type == 'dummy')
            return $field->title;

        $field_label = '<label id="' . $this->ct->Env->field_input_prefix . $field->fieldname . '-lbl" for="' . $this->ct->Env->field_input_prefix . $field->fieldname . '" ';
        $class = ($field->description != '' ? 'hasPopover' : '') . '' . ($field->isrequired ? ' required' : '');

        if ($class != '')
            $field_label .= ' class="' . $class . '"';

        $field_label .= ' title="' . $field->title . '"';

        if ($field->description != "")
            $field_label .= ' data-content="' . $field->description . '"';

        $field_label .= ' data-original-title="' . $field->title . '">' . $field->title;

        if ($field->isrequired)
            $field_label .= '<span class="star">&#160;*</span>';

        $field_label .= '</label>';

        return $field_label;
    }


}