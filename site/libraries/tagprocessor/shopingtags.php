<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class tagProcessor_Shopping
{
    public static function getShoppingCartLink(&$Model,&$htmlresult,&$row)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('cart',$options,$htmlresult,'{}');

		$opt_i=0;
		foreach($fList as $fItem)
		{
			$theLink=JoomlaBasicMisc::curPageURL();

			$option_pair=explode(',',$options[$opt_i]);


			if(strpos($theLink,'?')===false)
				$theLink.='?';
			else
				$theLink.='&';
				
			$cart_prefix='customtables_'; //We don't really need it, because it already contains the table name
			
			switch($option_pair[0])
			{
				case 'count' :

					$app = JFactory::getApplication();
					$cookieValue = $app->input->cookie->getVar($cart_prefix.'_'.$Model->establename);
					
					$vlu='0';
					if (isset($cookieValue))
					{
						$items=explode(';',$cookieValue);

						foreach($items as $item)
						{
							$pair=explode(',',$item);
							if(count($pair)==2)//first is ID sencond - count: example 45,6 - 6 items with id 45
							{
								if((int)$pair[0]==$row['listing_id'])
								{
									$vlu=(int)$pair[1];
									break;
								}
							}
						}
					}

					$htmlresult=str_replace($fItem,$vlu,$htmlresult);
					break;

				case 'addtocart' :
					$theLink.='task=cart_addtocart&cartprefix='.$cart_prefix.'&listing_id='.$row['listing_id'];
					$htmlresult=str_replace($fItem,$theLink,$htmlresult);
					break;

				case 'form_addtocart' :

					$cookieValue = $app->input->cookie->getVar($cart_prefix.'_'.$Model->establename);
					if (isset($cookieValue))
					{
						$items=explode(';',$cookieValue);
						$cnt=count($items);
						$found=false;
						for($i=0;$i<$cnt;$i++)
						{
							$pair=explode(',',$items[$i]);
							if(count($pair)==2) //otherwise ignore the shit
							{
								if((int)$pair[0]==$row['listing_id'])
									$vlu=(int)$pair[1];
							}
						}

					}
					else
						$vlu='0';

					if(isset($option_pair[1]) and $option_pair[1]!='')
						$button_label=$option_pair[2];
					else
						$button_label='Add';

					if(isset($option_pair[2]) and $option_pair[2]!='')
						$button_class=$option_pair[2];
					else
						$button_class='btn';

					$input_button='<input type="submit" value="'.$button_label.'" class="'.$button_class.'" />';

					$result='<form action="" method="post" id="ct_addtocartform">
					<input type="hidden" name="listing_id" value="'.$row['listing_id'].'" />
					<input type="hidden" name="task" value="cart_form_addtocart" />
					<input type="hidden" name="cartprefix" value="'.$cart_prefix.'" />
					<input type="text" class="inputbox" style="'.$input_style.'" name="itemcount" value="1" />'.$input_button.'
					</form>
					';

					$htmlresult=str_replace($fItem,$result,$htmlresult);


					break;


				case 'setitemcount' :

					$cookieValue = $app->input->cookie->getVar($cart_prefix.'_'.$Model->establename);
					if (isset($cookieValue))
					{
						$items=explode(';',$cookieValue);
						$cnt=count($items);
						$found=false;
						for($i=0;$i<$cnt;$i++)
						{
							$pair=explode(',',$items[$i]);
							if(count($pair)==2) //otherwise ignore the shit
							{
								if((int)$pair[0]==$row['listing_id'])
									$vlu=(int)$pair[1];
							}
						}

					}
					else
						$vlu='0';

					if(isset($option_pair[2]) and $option_pair[2]!='')
						$button_label=$option_pair[2];
					else
						$button_label='Update';

					if(isset($option_pair[2]) and $option_pair[2]!='')
						$button_class=$option_pair[2];
					else
						$button_class='btn';

					$input_button='<input type="submit" value="'.$button_label.'" class="'.$button_class.'" />';

					$result='
					<form action="" method="post" id="ct_updatecartform">
					<input type="hidden" name="listing_id" value="'.$row['listing_id'].'" />
					<input type="hidden" name="task" value="cart_setitemcount" />
					<input type="hidden" name="cartprefix" value="'.$cart_prefix.'" />
						<input type="text" class="inputbox" style="'.$input_style.'" name="itemcount" value="'.$vlu.'" />'.$input_button.'
					</form>
					';

					$htmlresult=str_replace($fItem,$result,$htmlresult);


					break;

				case 'deleteitem' :

					$theLink.='task=cart_deleteitem&cartprefix='.$cart_prefix.'&listing_id='.$row['listing_id'];
					$htmlresult=str_replace($fItem,$theLink,$htmlresult);

					break;



				default:

					break;
			}//switch($option_pair[0])

			$opt_i++;


		}

	}
}
