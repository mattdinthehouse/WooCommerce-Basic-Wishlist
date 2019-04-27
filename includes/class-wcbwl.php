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

		$this->admin    = new WCBWL_Admin();
		$this->frontend = new WCBWL_Frontend();
	}

	private function hooks() {
		register_activation_hook(WCBWL_FILE, array('WCBWL_Setup', 'install'));

		add_action('init', array('WCBWL_Setup', 'register_post_types'), 6);
	}
}