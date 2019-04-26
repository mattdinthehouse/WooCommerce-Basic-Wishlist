<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Admin {

	public function __construct() {
		add_action('all_admin_notices', array($this, 'reminder_notices'), 10);

		add_filter('wcbwl_reminder_notices', array($this, 'create_pages_reminder_notice'), 10);
	}

	public function reminder_notices() {
		$notices = apply_filters('wcbwl_reminder_notices', array());

		foreach($notices as $notice) {
			print '<div id="message" class="notice notice-'.esc_attr($notice['type']).'"><p>'.wp_kses($notice['message'], '<strong><a>').'</p></div>';
		}
	}

	public function create_pages_reminder_notice($notices) {
		if(wc_get_page_id('wishlist') <= 0) {
			$review_settings_url = '#';
			$create_default_url  = '#';

			$notices[] = array(
				'type'    => 'warning',
				'message' => sprintf(__('<strong>No wishlist page is set!</strong> <a href="%s">Review settings</a> or <a href="%s">create a default page</a>', 'wcbwl'), $review_settings_url, $create_default_url),
			);
		}

		return $notices;
	}
}
