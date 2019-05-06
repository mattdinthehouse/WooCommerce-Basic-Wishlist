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
		require_once WCBWL_DIR.'/includes/class-wcbwl-form-handler.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-frontend.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-setup.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-shortcodes.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist-data-store-cpt.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist-item.php';
		require_once WCBWL_DIR.'/includes/class-wcbwl-wishlist-item-data-store.php';

		$this->admin    = new WCBWL_Admin();
		$this->frontend = new WCBWL_Frontend();

		WCBWL_Form_Handler::init();
	}

	private function hooks() {
		register_activation_hook(WCBWL_FILE, array('WCBWL_Setup', 'install'));

		add_action('init', array('WCBWL_Setup', 'register_post_types'), 5);
		add_action('init', array('WCBWL_Setup', 'register_post_status'), 9);
		add_action('init', array('WCBWL_Shortcodes', 'init'), 10);

		add_action('init', array('WCBWL_Setup', 'wpdb_table_fix'), 0);
		add_action('switch_blog', array('WCBWL_Setup', 'wpdb_table_fix'), 0);

		add_filter('woocommerce_data_stores', array($this, 'register_data_stores'), 10, 1);

		add_action('wp_login', array($this, 'update_wishlist_from_session'), 10, 2);
	}

	public function register_data_stores($data_stores) {
		$data_stores['wishlist']      = 'WCBWL_Wishlist_Data_Store_CPT';
		$data_stores['wishlist-item'] = 'WCBWL_Wishlist_Item_Data_Store';

		return $data_stores;
	}

	public function save_to_wishlist($product_id, $wishlist_id = 0, $item_data = array()) {
		$wishlist = ($wishlist_id ? new WCBWL_Wishlist($wishlist_id) : $this->get_wishlist_for_user());
		if(!$wishlist->get_id()) {
			WCBWL_Wishlist::populate_defaults($wishlist);
		}

		$item = new WCBWL_Wishlist_Item();
		$item->set_product_id($product_id);

		$item_data = (array) apply_filters('wcbwl_save_to_wishlist_item_data', $item_data, $product_id, $wishlist);
		foreach($item_data as $key => $value) {
			$item->update_meta_data($key, $value);
		}

		$item->save();

		do_action('wcbwl_save_to_wishlist', $item, $product_id, $wishlist, $item_data);

		$wishlist->add_item($item);

		$wishlist->save();
		
		WC()->session->set('wishlist', $wishlist->get_id());
		WC()->session->set_customer_session_cookie(true);

		return true;
	}

	public function generate_wishlist_key() {
		return 'wc_'.apply_filters('wcbwl_generate_wishlist_key', 'wishlist_'.wp_generate_password(13, false));
	}

	public function get_wishlist_statuses() {
		$wishlist_statuses = array(
			'wcbwl-active'   => _x('Active', 'Wishlist status', 'wcbwl'),
			'wcbwl-inactive' => _x('Inactive', 'Wishlist status', 'wcbwl'),
		);
		
		return apply_filters('wcbwl_wishlist_statuses', $wishlist_statuses);
	}

	public function get_wishlist_for_user($user_id = 0) {
		$wishlist_id = 0;

		if(!$user_id && is_user_logged_in()) {
			$user_id = get_current_user_id();
		}

		if(!$user_id) {
			$wishlist_id = WC()->session->get('wishlist', 0);
		}

		if($user_id) {
			$wishlists = get_posts(array(
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'post_type'      => 'wishlist',
				'post_status'    => 'any',
				'post_author'    => $user_id,
			));

			if(!empty($wishlists)) {
				$wishlist_id = current($wishlists);
			}
		}

		return new WCBWL_Wishlist($wishlist_id);
	}

	public function update_wishlist_from_session($user_login, $user) {
		$wishlist_id = WC()->session->get('wishlist', 0);

		if($wishlist_id) {
			$wishlist = new WCBWL_Wishlist($wishlist_id);
			WCBWL_Wishlist::populate_defaults($wishlist, $user->ID);
			$wishlist->save();
		}
	}
}