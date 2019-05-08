<?php
/**
 * Single Save to Wishlist using an <button type="submit"> tag
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/save-to-wishlist.php.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @version     0.1
 */

if(!defined('ABSPATH')) {
	exit;
}

echo apply_filters('woocommerce_single_save_to_wishlist_submit',
	sprintf('<button type="submit" name="save-to-wishlist" value="%s" class="%s">%s</button>',
		esc_attr(isset($args['product_id']) ? $args['product_id'] : get_the_id()),
		esc_attr(isset($args['class']) ? $args['class'] : 'button'),
		esc_html($args['save_to_wishlist_text'])
	),
$args);
