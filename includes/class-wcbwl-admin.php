<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Admin {

	public function __construct() {
		add_action('all_admin_notices', array($this, 'reminder_notices'), 10);

		add_filter('wcbwl_reminder_notices', array($this, 'create_pages_reminder_notice'), 10);

		add_action('admin_post_wcbwl-create-default-pages', array($this, 'create_default_pages'), 10);

		add_filter('woocommerce_settings_pages', array($this, 'insert_page_controls'), 10, 1);

		add_filter('display_post_states', array($this, 'add_display_post_states'), 10, 2);
	}

	public function reminder_notices() {
		$notices = apply_filters('wcbwl_reminder_notices', array());

		foreach($notices as $notice) {
			print '<div id="message" class="notice notice-'.esc_attr($notice['type']).'"><p>'.wp_kses($notice['message'], '<strong><a>').'</p></div>';
		}
	}

	public function create_pages_reminder_notice($notices) {
		if(wc_get_page_id('wishlist') <= 0) {
			$review_settings_url = esc_html(admin_url('admin.php?page=wc-settings&tab=advanced'));
			$create_default_url  = esc_html(admin_url('admin-post.php?action=wcbwl-create-default-pages'));

			$notices[] = array(
				'type'    => 'warning',
				'message' => sprintf(__('<strong>No wishlist page is set!</strong> <a href="%s">Review settings</a> or <a href="%s">create a default page</a>', 'wcbwl'), $review_settings_url, $create_default_url),
			);
		}

		return $notices;
	}

	public function create_default_pages() {
		WCBWL_Setup::create_pages();

		wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=advanced'));
	}

	public function insert_page_controls($settings) {
		$settings = array_merge(
			array_slice($settings, 0, 3),
			array(
				array(
					'title'    => __('Wishlist page', 'wcbwl'),
					'desc'     => sprintf(__('Page contents: [%s]', 'wcbwl'), 'woocommerce_wishlist'),
					'id'       => 'woocommerce_wishlist_page_id',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
				),
			),
			array_slice($settings, 3)
		);

		return $settings;
	}

	public function add_display_post_states($post_states, $post) {
		if(wc_get_page_id('wishlist') === $post->ID) {
			$post_states['wcbwl_page_for_wishlist'] = __('Wishlist Page', 'wcbwl');
		}

		return $post_states;
	}
}
