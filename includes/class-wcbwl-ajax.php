<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_AJAX {

	public static function init() {
		add_action('wc_ajax_save_to_wishlist', array(__CLASS__, 'save_to_wishlist'));
	}

	public static function save_to_wishlist() {
		ob_start();

		if(!isset($_POST['product_id'])) {
			return;
		}

		$product_id        = apply_filters('wcbwl_save_to_wishlist_product_id', absint($_POST['product_id']));
		$passed_validation = apply_filters('wcbwl_save_to_wishlist_validation', true, $product_id);
		$product_status    = get_post_status($product_id);

		$was_saved_to_wishlist = false;

		if($passed_validation && 'publish' === $product_status) {
			$was_saved_to_wishlist = WC()->wishlist->save_to_wishlist($product_id);
		}

		if(is_wp_error($was_saved_to_wishlist)) {
			wc_add_notice($was_saved_to_wishlist->get_error_message(), 'error');

			// If there was an error adding to the cart, redirect to the product page to show any errors.
			$data = array(
				'error'       => true,
				'product_url' => apply_filters('wcbwl_wishlist_redirect_after_error', get_permalink($product_id), $product_id),
			);

			wp_send_json($data);
		}
		else {
			do_action('wcbwl_ajax_saved_to_wishlist', $product_id);

			WC_AJAX::get_refreshed_fragments();
		}
	}
}