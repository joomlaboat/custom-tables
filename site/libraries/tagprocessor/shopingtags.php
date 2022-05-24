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
use Joomla\CMS\Factory;

class tagProcessor_Shopping
{
    public static function getShoppingCartLink(CT &$ct, &$htmlresult, &$row)
    {
        $app = Factory::getApplication();

        $options = array();
        $fList = JoomlaBasicMisc::getListToReplace('cart', $options, $htmlresult, '{}');

        $opt_i = 0;
        foreach ($fList as $fItem) {
            $theLink = JoomlaBasicMisc::curPageURL();

            $option_pair = explode(',', $options[$opt_i]);


            if (!str_contains($theLink, '?'))
                $theLink .= '?';
            else
                $theLink .= '&';

            $cart_prefix = 'customtables_'; //We don't really need it, because it already contains the table name

            switch ($option_pair[0]) {
                case 'count' :

                    $cookieValue = $app->input->cookie->getVar($cart_prefix . $ct->Table->tablename);

                    $vlu = '0';
                    if (isset($cookieValue)) {
                        $items = explode(';', $cookieValue);

                        foreach ($items as $item) {
                            $pair = explode(',', $item);
                            if (count($pair) == 2)//first is ID sencond - count: example 45,6 - 6 items with id 45
                            {
                                if ((int)$pair[0] == $row[$ct->Table->realidfieldname]) {
                                    $vlu = (int)$pair[1];
                                    break;
                                }
                            }
                        }
                    }

                    $htmlresult = str_replace($fItem, $vlu, $htmlresult);
                    break;

                case 'addtocart' :
                    $theLink .= 'task=cart_addtocart&listing_id=' . $row[$ct->Table->realidfieldname];
                    $htmlresult = str_replace($fItem, $theLink, $htmlresult);
                    break;

                case 'form_addtocart' :

                    $cookieValue = $app->input->cookie->getVar($cart_prefix . $ct->Table->tablename);
                    if (isset($cookieValue)) {
                        $items = explode(';', $cookieValue);
                        $cnt = count($items);
                        $found = false;
                        for ($i = 0; $i < $cnt; $i++) {
                            $pair = explode(',', $items[$i]);
                            if (count($pair) == 2) //otherwise ignore the shit
                            {
                                if ((int)$pair[0] == $row[$ct->Table->realidfieldname])
                                    $vlu = (int)$pair[1];
                            }
                        }

                    } else
                        $vlu = '0';

                    if (isset($option_pair[1]) and $option_pair[1] != '')
                        $button_label = $option_pair[2];
                    else
                        $button_label = 'Add';

                    if (isset($option_pair[2]) and $option_pair[2] != '')
                        $button_class = $option_pair[2];
                    else
                        $button_class = 'btn';

                    $input_button = '<input type="submit" value="' . $button_label . '" class="' . $button_class . '" />';
                    $input_style = '';
                    $result = '<form action="" method="post" id="ct_addtocartform">
					<input type="hidden" name="listing_id" value="' . $row[$ct->Table->realidfieldname] . '" />
					<input type="hidden" name="task" value="cart_form_addtocart" />
					<input type="text" class="inputbox" style="' . $input_style . '" name="itemcount" value="1" />' . $input_button . '
					</form>
					';

                    $htmlresult = str_replace($fItem, $result, $htmlresult);


                    break;


                case 'setitemcount' :

                    $cookieValue = $app->input->cookie->getVar($cart_prefix . $ct->Table->tablename);
                    if (isset($cookieValue)) {
                        $items = explode(';', $cookieValue);
                        $cnt = count($items);
                        $found = false;
                        for ($i = 0; $i < $cnt; $i++) {
                            $pair = explode(',', $items[$i]);
                            if (count($pair) == 2) //otherwise ignore the shit
                            {
                                if ((int)$pair[0] == $row[$ct->Table->realidfieldname])
                                    $vlu = (int)$pair[1];
                            }
                        }

                    } else
                        $vlu = '0';

                    if (isset($option_pair[2]) and $option_pair[2] != '')
                        $button_label = $option_pair[2];
                    else
                        $button_label = 'Update';

                    if (isset($option_pair[2]) and $option_pair[2] != '')
                        $button_class = $option_pair[2];
                    else
                        $button_class = 'btn';

                    $input_button = '<input type="submit" value="' . $button_label . '" class="' . $button_class . '" />';

                    $result = '
					<form action="" method="post" id="ct_updatecartform">
					<input type="hidden" name="listing_id" value="' . $row[$ct->Table->realidfieldname] . '" />
					<input type="hidden" name="task" value="cart_setitemcount" />
					<input type="text" class="inputbox" name="itemcount" value="' . $vlu . '" />' . $input_button . '
					</form>
					';

                    $htmlresult = str_replace($fItem, $result, $htmlresult);

                    break;

                case 'deleteitem' :

                    $theLink .= 'task=cart_deleteitem&listing_id=' . $row[$ct->Table->realidfieldname];
                    $htmlresult = str_replace($fItem, $theLink, $htmlresult);

                    break;

                default:

                    break;
            }//switch($option_pair[0])

            $opt_i++;
        }
    }
}
