<?php
/**
 * Loop Save to Wishlist
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/save-to-wishlist.php.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @version     0.1
 */

if(!defined('ABSPATH')) {
	exit;
}

echo apply_filters('woocommerce_loop_save_to_wishlist_link',
	sprintf('<a href="%s" data-product_id="%s" class="%s">%s</a>',
		esc_url(add_query_arg('save-to-wishlist', $args['product_id'])),
		esc_attr(isset($args['product_id']) ? $args['product_id'] : get_the_id()),
		esc_attr(isset($args['class']) ? $args['class'] : 'button' ),
		esc_html($args['save_to_wishlist_text'])
	),
$args);
