<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

if(!function_exists('is_wishlist')) {

	/**
	 * Is_wishlist - Returns true when viewing the wishlist page.
	 *
	 * @return bool
	 */
	function is_wishlist() {
		$page_id = wc_get_page_id('wishlist');

		return ($page_id && is_page($page_id)) || wc_post_content_has_shortcode('woocommerce_wishlist');
	}
}