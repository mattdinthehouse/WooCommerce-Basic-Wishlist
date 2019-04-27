<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

if(!function_exists('wcbwl_template_loop_save_to_wishlist')) {

	function wcbwl_template_loop_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'save_to_wishlist_button',
						//$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_loop_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('loop/save-to-wishlist.php', $args);
	}
}