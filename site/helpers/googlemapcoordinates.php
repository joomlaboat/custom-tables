<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use \Joomla\CMS\Version;

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'catalog.php');

class JHTMLGoogleMapCoordinates
{
    static public function render(string $control_name, ?string $value): string
    {
        if ($value === null)
            return '';

        $html = [];
        $html[] = '<div class="controls"><div class="field-calendar"><div class="input-group has-success">';
        $html[] = '<input type="text" class="form-control valid form-control-success" id="' . $control_name . '" name="' . $control_name . '" value="' . htmlspecialchars($value) . '" />';
        $html[] = '<button type="button" class="btn btn-primary" onclick="ctInputbox_googlemapcoordinates(\'' . $control_name . '\')" data-inputfield="comes_' . $control_name . '" data-button="comes_' . $control_name . '_btn">&nbsp;...&nbsp;</button>';
        $html[] = '</div></div></div>';
        $html[] = '<div id="' . $control_name . '_map" style="width: 480px; height: 540px;display:none;"></div>';

        return implode("\n", $html);
    }
}
