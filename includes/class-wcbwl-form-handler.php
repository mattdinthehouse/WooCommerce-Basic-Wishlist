<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Form_Handler {

	public static function init() {
		add_action('wp_loaded', array(__CLASS__, 'save_to_wishlist_action'), 20);
		add_action('wp_loaded', array(__CLASS__, 'remove_wishlist_item_action'), 20);
		add_action('wp_loaded', array(__CLASS__, 'update_wishlist_items_action'), 20);

		add_filter('woocommerce_add_to_cart_product_id', array(__CLASS__, 'stop_variable_add_to_cart'), 10, 1);

		add_filter('wcbwl_save_to_wishlist_product_id', array(__CLASS__, 'handle_variation_save_to_wishlist'), 10, 1);

		add_filter('wcbwl_save_to_wishlist_item_data', array(__CLASS__, 'add_variable_product_wishlist_item_data'), 10, 2);
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

	public static function remove_wishlist_item_action() {
		if(!isset($_REQUEST['remove-wishlist-item']) || !is_numeric(wp_unslash($_REQUEST['remove-wishlist-item']))) {
			return;
		}

		wc_nocache_headers();

		$item_id          = apply_filters('wcbwl_remove_wishlist_item_item_id', absint(wp_unslash($_REQUEST['remove-wishlist-item'])));
		$wishlist_item    = new WCBWL_Wishlist_Item($item_id);
		$current_wishlist = WC()->wishlist->get_wishlist_from_current_user();

		if($current_wishlist->get_id() && $current_wishlist->get_id() == $wishlist_item->get_wishlist_id()) {
			$wishlist_item->delete();
		}

		wc_add_notice(__('Item deleted.', 'wcbwl'));
	}

	public static function update_wishlist_items_action() {
		if(!isset($_REQUEST['update_wishlist_items']) || !is_numeric(wp_unslash($_REQUEST['update_wishlist_items']))) {
			return;
		}

		wc_nocache_headers();

		$wishlist_id = apply_filters('wcbwl_update_wishlist_items_wishlist_id', absint(wp_unslash($_REQUEST['update_wishlist_items'])));
		$wishlist    = new WCBWL_Wishlist($wishlist_id);

		foreach($wishlist->get_items() as $item) {
			if(isset($_REQUEST['wishlist_item'][$item->get_id()]) && is_array($_REQUEST['wishlist_item'][$item->get_id()])) {
				foreach($_REQUEST['wishlist_item'][$item->get_id()] as $key => $value) {
					$item->update_meta_data($key, $value);
				}

				$item->save();
			}
		}

		wc_add_notice(__('Wishlist updated.', 'wcbwl'));
	}

	public static function stop_variable_add_to_cart($product_id) {
		if(isset($_REQUEST['save-to-wishlist'])) {
			$product_id = -1;
		}

		return $product_id;
	}

	public static function handle_variation_save_to_wishlist($product_id) {
		if(isset($_REQUEST['variation_id']) && is_numeric($_REQUEST['variation_id'])) {
			$variation_id = absint(wp_unslash($_REQUEST['variation_id']));

			if(get_post_type($variation_id) == 'product_variation') {
				$product_id = $variation_id;
			}
		}

		return $product_id;
	}

	public static function add_variable_product_wishlist_item_data($item_data, $product_id) {
		$product = wc_get_product($product_id);

		if($product->is_type('variable', 'variation')) {
			$attribute_data = array();

			foreach($product->get_variation_attributes() as $name => $values) {
				$key = sanitize_title($name);
				$key = (strpos($key, 'attribute_') === 0 ? $key : 'attribute_'.$key);

				if(isset($_REQUEST[$key]) && in_array($_REQUEST[$key], $values, true)) {
					$attribute_data[$key] = $_REQUEST[$key];
				}
			}

			$item_data = array_merge($item_data, $attribute_data);
		}

		return $item_data;
	}
}