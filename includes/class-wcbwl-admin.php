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

		add_filter('manage_edit-wishlist_columns', array($this, 'wishlist_table_columns'), 10, 1);
		add_action('manage_wishlist_posts_custom_column', array($this, 'render_wishlist_table_columns'), 2, 1);
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

	public function wishlist_table_columns($columns) {
		// Add extra columns
		$columns['items'] = __('Items', 'wcbwl');
		$columns['customer'] = __('Customer', 'wcbwl');

		// Move the date column to the end
		$date_column = $columns['date'];
		unset($columns['date']);
		$columns['date'] = $date_column;

		return $columns;
	}

	public function render_wishlist_table_columns($column) {
		global $post, $the_wishlist, $wp_list_table;

		if(empty($the_wishlist) || $the_wishlist->get_id() != $post->ID) {
			$the_wishlist = new WCBWL_Wishlist($post->ID);
		}

		$column_content = '';

		switch($column) {
			case 'items':
				$item_count = $the_wishlist->get_item_count();
				$column_content .= esc_html(apply_filters('wcbwl_admin_wishlist_item_count', sprintf(_n('%d item', '%d items', $item_count, 'wcbwl'), $item_count), $the_wishlist));
				break;

			case 'customer':
				$customer = new WC_Customer($the_wishlist->get_customer_id());
				if($customer->get_id()) {
					$column_content  = '<a href="user-edit.php?user_id='.absint($customer->get_id()).'">';

					if($customer->get_billing_first_name() || $customer->get_billing_last_name()) {
						$column_content .= esc_html(ucfirst($customer->get_billing_first_name()).' '.ucfirst($customer->get_billing_last_name()));
					}
					else if($customer->get_first_name() || $customer->get_last_name()) {
						$column_content .= esc_html(ucfirst($customer->get_first_name()).' '.ucfirst($customer->get_last_name()));
					}
					else {
						$column_content .= esc_html(ucfirst($customer->get_display_name()));
					}

					$column_content .= '</a>';
				}
				else {
					$column_content .= 'Guest';
				}
				break;
		}

		echo wp_kses(apply_filters('wcbwl_wishlist_list_table_column_content', $column_content, $the_wishlist, $column), array('a' => array('href' => array())));
	}
}
