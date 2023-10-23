<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace Joomla\CMS\Form\Field;

\defined('JPATH_PLATFORM') or die;

class CTStatusField extends PredefinedlistField
{
    public $type = 'CTStatus';

    protected $predefinedOptions = array(

        '' => 'JOPTION_SELECT_PUBLISHED',
        1 => 'JPUBLISHED',
        0 => 'JUNPUBLISHED',
        -2 => 'JTRASHED',
        '*' => 'JALL'
    );
}
