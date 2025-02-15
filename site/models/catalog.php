<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelCatalog extends BaseDatabaseModel
{
	var CT $ct;
	var string $showcartitemsprefix;

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function cart_emptycart(): bool
	{
		common::inputCookieSet($this->showcartitemsprefix . $this->ct->Table->tablename, '',
			time() - 3600,
			Factory::getApplication()->get('cookie_path', '/'),
			Factory::getApplication()->get('cookie_domain'));
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function cart_deleteitem(): bool
	{
		$listing_id = common::inputGetCmd('listing_id', '');
		if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		$this->cart_setitemcount(0);

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function cart_setitemcount($itemcount = -1): bool
	{
		$listing_id = common::inputGetCmd('listing_id', '');
		if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		if ($itemcount == -1)
			$itemcount = common::inputGetInt('itemcount', 0);

		$cookieValue = common::inputCookieGet($this->showcartitemsprefix . $this->ct->Table->tablename);

		if (isset($cookieValue)) {
			$items = explode(';', $cookieValue);
			$cnt = count($items);
			$found = false;
			for ($i = 0; $i < $cnt; $i++) {
				$pair = explode(',', $items[$i]);
				if (count($pair) != 2)
					unset($items[$i]); //delete the line
				else {
					if ((int)$pair[0] == $listing_id) {
						if ($itemcount == 0) {
							unset($items[$i]); //delete item
						} else {
							//update counter
							$pair[1] = $itemcount;
							$items[$i] = implode(',', $pair);
						}
						$found = true;
					}
				}
			}//for

			if (!$found)
				$items[] = $listing_id . ',' . $itemcount; // add new item

			$items = array_values($items);
		} else
			$items = array($listing_id . ',' . $itemcount); //add new

		$nc = implode(';', $items);
		setcookie($this->showcartitemsprefix . $this->ct->Table->tablename, $nc, time() + 3600 * 24);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function cart_form_addtocart($itemcount = -1): bool
	{
		$listing_id = common::inputGetCmd('listing_id', '');
		if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		if ($itemcount == -1)
			$itemcount = common::inputGetInt('itemcount', 0);

		$cookieValue = common::inputCookieGet($this->showcartitemsprefix . $this->ct->Table->tablename);

		if (isset($cookieValue)) {
			$items = explode(';', $cookieValue);
			$cnt = count($items);
			$found = false;
			for ($i = 0; $i < $cnt; $i++) {
				$pair = explode(',', $items[$i]);
				if (count($pair) != 2)
					unset($items[$i]); //delete it
				else {
					if ((int)$pair[0] == $listing_id) {
						$new_itemcount = (int)$pair[1] + $itemcount;
						if ($new_itemcount == 0) {
							unset($items[$i]); //delete item
						} else {
							//update counter
							$pair[1] = $new_itemcount;
							$items[$i] = implode(',', $pair);
						}
						$found = true;
					}
				}
			}//for

			if (!$found)
				$items[] = $listing_id . ',' . $itemcount; // add new item

			$items = array_values($items);
		} else
			$items = array($listing_id . ',' . $itemcount); //add new

		$nc = implode(';', $items);
		setcookie($this->showcartitemsprefix . $this->ct->Table->tablename, $nc, time() + 3600 * 24);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function cart_addtocart(): bool
	{
		$listing_id = common::inputGetCmd('listing_id', '');
		if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		$cookieValue = common::inputCookieGet($this->showcartitemsprefix . $this->ct->Table->tablename);

		if (isset($cookieValue)) {
			$items = explode(';', $cookieValue);
			$cnt = count($items);
			$found = false;
			for ($i = 0; $i < $cnt; $i++) {
				$pair = explode(',', $items[$i]);
				if (count($pair) != 2)
					unset($items[$i]); //delete the line
				else {
					if ((int)$pair[0] == $listing_id) {
						//update counter
						$pair[1] = ((int)$pair[1]) + 1;
						$items[$i] = implode(',', $pair);
						$found = true;
					}
				}
			}

			if (!$found)
				$items[] = $listing_id . ',1'; // add new item

			$items = array_values($items);
		} else
			$items = array($listing_id . ',1'); //add new

		$nc = implode(';', $items);

		common::inputCookieSet($this->showcartitemsprefix . $this->ct->Table->tablename,
			$nc,
			time() + 3600 * 24,
			Factory::getApplication()->get('cookie_path', '/'),
			Factory::getApplication()->get('cookie_domain'));

		return true;
	}
}
