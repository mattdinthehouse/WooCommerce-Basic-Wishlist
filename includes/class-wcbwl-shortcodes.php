<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Shortcodes {

	public static function init() {
		$shortcodes = array(
			'woocommerce_wishlist' => __CLASS__.'::wishlist',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode(apply_filters("{$shortcode}_shortcode_tag", $shortcode), $function);
		}
	}

	public static function wishlist() {
		return empty(WC()->wishlist) ? '' : WC_Shortcodes::shortcode_wrapper(array(__CLASS__, 'wishlist_output'));
	}

	public static function wishlist_output() {
		$wishlist = false;

		if(is_singular('wishlist')) {
			$wishlist = new WCBWL_Wishlist(get_the_id());
		}
		else {
			$wishlist = WC()->wishlist->get_wishlist_from_current_user();
		}

		$wishlist = apply_filters('wcbwl_wishlist_shortcode_object', $wishlist);
		if(!$wishlist) {
			$wishlist = new WCBWL_Wishlist();
		}

		$args = array(
			'wishlist' => $wishlist,
		);

		if($wishlist->is_empty() ) {
			wc_get_template('wishlist/wishlist-empty.php', $args);
		}
		else {
			wc_get_template('wishlist/wishlist.php', $args);
		}
	}
}