<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL {

	public $query = null;

	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	private function includes() {
		require_once WCBWL_DIR.'/includes/class-wcbwl-admin.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-setup.php';

		$this->admin = new WCBWL_Admin();
	}

	private function hooks() {
		register_activation_hook(WCBWL_FILE, array('WCBWL_Setup', 'install'));

		add_action('woocommerce_loaded', array($this, 'init_woocommerce'), 0);

		add_action('init', array('WCBWL_Setup', 'register_post_types'), 6);
	}

	public function init_woocommerce() {
		WC()->wishlist = $this;
	}
}