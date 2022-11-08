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
use CustomTables\CT;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

require_once('render_html.php');
require_once('render_xlsx.php');
require_once('render_csv.php');
require_once('render_json.php');
require_once('render_xml.php');
require_once('render_image.php');

class tagProcessor_CatalogTableView
{
    use render_html;
    use render_xlsx;
    use render_csv;
    use render_json;
    use render_xml;
    use render_image;

    public static function process(CT &$ct, int $layoutType, string &$pageLayout, string $new_replaceitecode)
    {
        $vlu = '';

        //Catalog Table View
        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('catalogtable', $options, $pageLayout, '{}');

        $i = 0;
        foreach ($fList as $fItem) {
            $pair = JoomlaBasicMisc::csv_explode(';', $options[$i], '"', true);
            $fields = $pair[0];

            if ($ct->Env->frmt == 'csv') {
                $vlu = self::get_CatalogTable_CSV($ct, $fields);
                $pageLayout = str_replace($fItem, $new_replaceitecode, $pageLayout);
            } elseif ($ct->Env->frmt == 'json') {
                $vlu = self::get_CatalogTable_JSON($ct, $fields);
                $pageLayout = str_replace($fItem, $new_replaceitecode, $pageLayout);
            } elseif ($ct->Env->frmt == 'xml') {
                $vlu = self::get_CatalogTable_XML($ct, $layoutType, $fields);
                $pageLayout = str_replace($fItem, $new_replaceitecode, $pageLayout);
            } elseif ($ct->Env->frmt == 'xlsx') {
                self::get_CatalogTable_XLSX($fields);
            } else {
                $class = '';
                $dragdrop = '';

                if (isset($pair[1])) {
                    $parts = explode(',', $pair[1]);
                    if ($parts[0] != '')
                        $class = $parts[0];

                    if (isset($parts[1]))
                        $dragdrop = $parts[1] == 'dragdrop';
                }

                $vlu = self::get_CatalogTable_HTML($ct, $layoutType, $fields, $class, $dragdrop);
                $pageLayout = str_replace($fItem, $new_replaceitecode, $pageLayout);
            }
            $i++;
        }
        return $vlu;
    }
}
