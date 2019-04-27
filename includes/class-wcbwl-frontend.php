<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Frontend {

	public function __construct() {
		add_filter('woocommerce_locate_template', array($this, 'locate_template'), 10, 3);

		add_action('after_setup_theme', array($this, 'include_template_functions'), 12);

		add_action('woocommerce_after_shop_loop_item', 'wcbwl_template_loop_save_to_wishlist', 10);
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
}