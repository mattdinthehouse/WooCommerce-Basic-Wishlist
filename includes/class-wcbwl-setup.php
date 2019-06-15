<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Setup {

	private function __construct() {
		// Not allowed
	}

	public static function install() {
		if(!wcbwl_can_run()) {
			die(__('This site does not meet minimum requirements', 'wcbwl'));
		}

		self::create_tables();

		self::register_post_types();
		self::register_post_status();

		flush_rewrite_rules();
	}

	private static function create_tables() {
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		dbDelta(self::get_db_schema());
	}

	private static function get_db_schema() {
		global $wpdb;

		$collate = ($wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '');

		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_wishlist_items (
	wishlist_item_id BIGINT UNSIGNED NOT NULL auto_increment,
	wishlist_id BIGINT UNSIGNED NOT NULL,
	product_id BIGINT UNSIGNED NOT NULL,
	date_added datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	date_added_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (wishlist_item_id),
	KEY wishlist_id (wishlist_id),
	KEY product_id (product_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_wishlist_itemmeta (
	meta_id BIGINT UNSIGNED NOT NULL auto_increment,
	wishlist_item_id BIGINT UNSIGNED NOT NULL,
	meta_key varchar(255) default NULL,
	meta_value longtext NULL,
	PRIMARY KEY  (meta_id),
	KEY wishlist_item_id (wishlist_item_id),
	KEY meta_key (meta_key(32))
) $collate;
		";

		return $tables;
	}

	public static function create_pages() {
		include_once dirname(WC_PLUGIN_FILE).'/includes/admin/wc-admin-functions.php';

		$pages = array(
			'wishlist' => array(
				'name'    => _x('wishlist', 'Page slug', 'wcbwl'),
				'title'   => _x('Wishlist', 'Page title', 'wcbwl'),
				'content' => '<!-- wp:shortcode -->[woocommerce_wishlist]<!-- /wp:shortcode -->',
			),
		);

		foreach($pages as $key => $page) {
			wc_create_page(esc_sql($page['name']), 'woocommerce_'.$key.'_page_id', $page['title'], $page['content'], !empty($page['parent']) ? wc_get_page_id($page['parent']) : '');
		}
	}

	public static function register_post_types() {
		$wishlist_page_id = wc_get_page_id('wishlist');

		$default_wishlist_slug = 'wishlist';

		if(current_theme_supports('woocommerce')) {
			$wishlist_slug = $wishlist_page_id && get_post($wishlist_page_id) ? urldecode(get_page_uri($wishlist_page_id)) : $default_wishlist_slug;
		} else {
			$wishlist_slug = $default_wishlist_slug;
		}

		register_post_type(
			'wishlist',
			apply_filters(
				'wcbwl_register_post_type_wishlist',
				array(
					'labels'              => array(
						'name'                  => __('Wishlists', 'wcbwl'),
						'singular_name'         => _x('Wishlist', 'wishlist post type singular name', 'wcbwl'),
						'add_new'               => __('Add wishlist', 'wcbwl'),
						'add_new_item'          => __('Add new wishlist', 'wcbwl'),
						'edit'                  => __('Edit', 'wcbwl'),
						'edit_item'             => __('Edit wishlist', 'wcbwl'),
						'new_item'              => __('New wishlist', 'wcbwl'),
						'view_item'             => __('View wishlist', 'wcbwl'),
						'search_items'          => __('Search wishlists', 'wcbwl'),
						'not_found'             => __('No wishlists found', 'wcbwl'),
						'not_found_in_trash'    => __('No wishlists found in trash', 'wcbwl'),
						'parent'                => __('Parent wishlists', 'wcbwl'),
						'menu_name'             => _x('Wishlists', 'Admin menu name', 'wcbwl'),
						'filter_items_list'     => __('Filter wishlists', 'wcbwl'),
						'items_list_navigation' => __('Wishlists navigation', 'wcbwl'),
						'items_list'            => __('Wishlists list', 'wcbwl'),
					),
					'description'         => __('This is where customer wishlists are stored.', 'wcbwl'),
					'public'              => true,
					'show_ui'             => true,
					'capability_type'     => 'shop_order',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => true,
					'show_in_menu'        => current_user_can('manage_woocommerce') ? 'woocommerce' : true,
					'hierarchical'        => false,
					'rewrite'             => array(
						'slug'       => $wishlist_slug,
						'with_front' => false,
						'feeds'      => true,
					),
					'query_var'           => true,
					'supports'            => array('title'),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_rest'        => true,
				)
			)
		);

		do_action('wcbwl_after_register_post_type');
	}

	public static function register_post_status() {
		$wishlist_statuses = apply_filters(
			'wcbwl_register_wishlist_post_statuses',
			array(
				'wcbwl-active' => array(
					'label'                     => _x('Active', 'Wishlist status', 'wcbwl'),
					'public'                    => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'wcbwl'),
				),
				'wcbwl-inactive' => array(
					'label'                     => _x('Inactive', 'Wishlist status', 'wcbwl'),
					'public'                    => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop('Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'wcbwl'),
				),
			)
		);

		foreach($wishlist_statuses as $wishlist_status => $values) {
			register_post_status($wishlist_status, $values);
		}
	}

	public static function wpdb_table_fix() {
		global $wpdb;

		$wpdb->wishlist_itemmeta = $wpdb->prefix.'woocommerce_wishlist_itemmeta';
		$wpdb->tables[]          = 'woocommerce_wishlist_itemmeta';
	}
}