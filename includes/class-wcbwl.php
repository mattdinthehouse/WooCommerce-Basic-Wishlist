<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL {

	public function __construct() {
		WC()->wishlist = $this;

		$this->includes();
		$this->hooks();
	}

	private function includes() {
		require_once WCBWL_DIR.'/includes/class-wcbwl-admin.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-frontend.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-setup.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist-item.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist-item-data-store.php';

		$this->admin    = new WCBWL_Admin();
		$this->frontend = new WCBWL_Frontend();
	}

	private function hooks() {
		register_activation_hook(WCBWL_FILE, array('WCBWL_Setup', 'install'));

		add_action('init', array('WCBWL_Setup', 'register_post_types'), 6);

		add_filter('woocommerce_data_stores', array($this, 'register_data_stores'), 10, 1);
	}

	public function register_data_stores($data_stores) {
		$data_stores['wishlist-item'] = 'WCBWL_Wishlist_Item_Data_Store';

		return $data_stores;
	}
}