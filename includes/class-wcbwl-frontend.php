<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Frontend {

	public function __construct() {
		add_filter('woocommerce_locate_template', array($this, 'locate_template'), 10, 3);

		add_action('after_setup_theme', array($this, 'include_template_functions'), 12);

		add_action('pre_get_posts', array($this, 'route_wishlist_post_to_page'), 10, 1);

		add_action('woocommerce_after_shop_loop_item', 'wcbwl_template_loop_save_to_wishlist', 10);

		add_action('woocommerce_after_add_to_cart_button', 'wcbwl_template_single_save_to_wishlist', 10);

		add_action('wcbwl_simple_save_to_wishlist', 'wcbwl_submit_save_to_wishlist', 10);
		add_action('wcbwl_variable_save_to_wishlist', 'wcbwl_submit_save_to_wishlist', 10);
		add_action('wcbwl_external_save_to_wishlist', 'wcbwl_link_save_to_wishlist', 10);
		add_action('wcbwl_grouped_save_to_wishlist', 'wcbwl_link_save_to_wishlist', 10);

		add_action('wcbwl_wishlist_is_empty', 'wcbwl_empty_wishlist_message', 10);
		add_action('wcbwl_wishlist_is_empty', 'woocommerce_output_all_notices', 5);
	}

	public function locate_template($template, $template_name, $template_path) {
		if(!file_exists($template)) {
			$plugin_template = WCBWL_DIR.'/templates/'.$template_name;

			if(file_exists($plugin_template)) {
				$template = $plugin_template;
			}
		}

		return $template;
	}

	public function include_template_functions() {
		require_once WCBWL_DIR.'/includes/wcbwl-template-functions.php';
	}

	public function route_wishlist_post_to_page($query) {
		if($query->is_main_query() && $query->get('post_type') == 'wishlist') {
			$query->set('post_type', 'page');
			$query->set('p', wc_get_page_id('wishlist'));
			$query->set('name', '');
		}
	}
}