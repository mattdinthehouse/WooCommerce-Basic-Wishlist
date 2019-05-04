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

if(!function_exists('wcbwl_template_single_save_to_wishlist')) {

	function wcbwl_template_single_save_to_wishlist() {
		$product = wc_get_product();

		do_action('wcbwl_'.$product->get_type().'_save_to_wishlist');
	}
}

if(!function_exists('wcbwl_submit_save_to_wishlist')) {

	function wcbwl_submit_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'single_save_to_wishlist_button',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_submit_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('single-product/save-to-wishlist/submit.php', $args);
	}
}

if(!function_exists('wcbwl_link_save_to_wishlist')) {

	function wcbwl_link_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'single_save_to_wishlist_button',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_link_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('single-product/save-to-wishlist/link.php', $args);
	}
}
