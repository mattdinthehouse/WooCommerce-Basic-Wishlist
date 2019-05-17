<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Frontend {

	public function __construct() {
		add_filter('woocommerce_locate_template', array($this, 'locate_template'), 10, 3);

		add_action('after_setup_theme', array($this, 'include_template_functions'), 12);

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_action('pre_get_posts', array($this, 'route_wishlist_post_to_page'), 10, 1);

		add_filter('page_link', array($this, 'rewrite_wishlist_permalink'), 10, 2);

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

	public function enqueue_scripts() {
		if('yes' === get_option('woocommerce_enable_ajax_add_to_cart')) {
			wp_register_script('wcbwl-save-to-wishlist', WCBWL_URL.'/assets/js/save-to-wishlist.js', array('jquery'), WCBWL_VERSION, true);
			wp_enqueue_script('wcbwl-save-to-wishlist');
			wp_localize_script('wcbwl-save-to-wishlist', 'wcbwl_save_to_wishlist_params', apply_filters('wcbwl_save_to_wishlist_params', array(
				'ajax_url'           => WC()->ajax_url(),
				'wc_ajax_url'        => WC_AJAX::get_endpoint('%%endpoint%%'),
				'i18n_view_wishlist' => esc_attr__('View wishlist', 'wcbwl'),
				'wishlist_url'       => apply_filters('wcbwl_save_to_wishlist_redirect', wc_get_page_permalink('wishlist'), null),
			)));
		}
	}

	public function route_wishlist_post_to_page($query) {
		if($query->is_main_query() && $query->get('post_type') == 'wishlist') {
			$query->set('post_type', 'page');
			$query->set('p', wc_get_page_id('wishlist'));
			$query->set('name', '');
		}
	}

	public function rewrite_wishlist_permalink($link, $post_id) {
		if(!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
			if($post_id == wc_get_page_id('wishlist')) {
				$wishlist_key = get_query_var('wishlist');
				if($wishlist_key) {
					$wishlist = WCBWL_Wishlist::get_using_key($wishlist_key);
				}
				else {
					$wishlist = WC()->wishlist->get_wishlist_from_current_user();
				}

				if($wishlist->get_id()) {
					$link = get_permalink($wishlist->get_id());
				}
			}
		}

		return $link;
	}
}