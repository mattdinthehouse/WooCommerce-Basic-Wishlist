<?php
/**
 * Wishlist item data (when outputting non-flat)
 *
 * @version 0.1
 */

if(!defined('ABSPATH')) {
	exit;
}

wc_get_template('cart/cart-item-data.php', array('item_data' => $item_data));