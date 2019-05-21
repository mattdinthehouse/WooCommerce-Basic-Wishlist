<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Shortcodes {

	public static function init() {
		$shortcodes = array(
			'woocommerce_wishlist' => __CLASS__.'::wishlist',
		);

		foreach($shortcodes as $shortcode => $function) {
			add_shortcode(apply_filters("{$shortcode}_shortcode_tag", $shortcode), $function);
		}
	}

	public static function wishlist() {
		return empty(WC()->wishlist) ? '' : WC_Shortcodes::shortcode_wrapper(array(__CLASS__, 'wishlist_output'));
	}

	public static function wishlist_output() {
		$wishlist = false;

		$wishlist_key = get_query_var('wishlist');
		if($wishlist_key) {
			$wishlist = WCBWL_Wishlist::get_using_key($wishlist_key);
		}
		else {
			$wishlist = WC()->wishlist->get_wishlist_from_current_user();
		}

		$wishlist = apply_filters('wcbwl_wishlist_shortcode_object', $wishlist);
		if(!$wishlist) {
			$wishlist = new WCBWL_Wishlist();
		}

		$is_user_owner = false;
		if(is_user_logged_in()) {
			$is_user_owner = ($wishlist->get_customer_id() == get_current_user_id());
		}
		else {
			$is_user_owner = ($wishlist->get_id() == WC()->wishlist->get_wishlist_from_session()->get_id());
		}

		$args = array(
			'wishlist'      => $wishlist,
			'is_user_owner' => $is_user_owner,
		);

		if($wishlist->is_empty()) {
			wc_get_template('wishlist/wishlist-empty.php', $args);
		}
		else {
			wc_get_template('wishlist/wishlist.php', $args);
		}
	}
}