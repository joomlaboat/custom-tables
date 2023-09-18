<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

class OrderingHTML
{
    public static function getOrderBox(Ordering &$ordering): string
    {
        $lists = $ordering->getSortByFields();
        $order_values = $lists->values;
        $order_list = $lists->titles;
        $result = '<select name="esordering" id="esordering" onChange="ctOrderChanged(this.value);" class="inputbox">' . PHP_EOL;

        for ($i = 0; $i < count($order_values); $i++) {
            $result .= '<option value="' . $order_values[$i] . '" ' . ($ordering->ordering_processed_string == $order_values[$i] ? ' selected ' : '') . '>'
                . htmlspecialchars($order_list[$i] ?? '')
                . '</option>' . PHP_EOL;
        }

        $result .= '</select>' . PHP_EOL;
        return $result;
    }
}