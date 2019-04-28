<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Form_Handler {

	public static function init() {
		add_action('wp_loaded', array(__CLASS__, 'save_to_wishlist_action'), 20);
	}

	public static function save_to_wishlist_action() {
		if(!isset($_REQUEST['save-to-wishlist']) || !is_numeric(wp_unslash($_REQUEST['save-to-wishlist']))) {
			return;
		}

		wc_nocache_headers();

		$product_id         = apply_filters('wcbwl_save_to_wishlist_product_id', absint(wp_unslash($_REQUEST['save-to-wishlist'])));
		$saving_to_wishlist = get_post($product_id);

		if(!$saving_to_wishlist) {
			return;
		}

		$was_saved_to_wishlist = WC()->wishlist->save_to_wishlist($product_id);

		if(is_wp_error($was_saved_to_wishlist)) {
			wc_add_notice($was_saved_to_wishlist->get_error_message(), 'error');
		}
		else {
			$title      = sprintf(_x('&ldquo;%s&rdquo;', 'Item name in quotes', 'wcbwl'), get_the_title($product_id));
			$saved_text = sprintf(__('%s has been saved to your wishlist.', 'wcbwl'), $title);
			wc_add_notice($saved_text, 'success');

			$url = apply_filters('wcbwl_save_to_wishlist_redirect', '', $saving_to_wishlist);

			if($url) {
				wp_safe_redirect($url);
				exit;
			}
		}
	}
}