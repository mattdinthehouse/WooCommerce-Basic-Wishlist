<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Wishlist_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	protected $meta_type = 'post';

	public function create(&$wishlist) {
		$wishlist->set_wishlist_key(WC()->wishlist->generate_wishlist_key());

		$wishlist->set_version(WCBWL_VERSION);
		$wishlist->set_date_created(current_time('timestamp', true));

		$id = wp_insert_post(
			apply_filters(
				'wcbwl_new_wishlist_data',
				array(
					'post_date'     => gmdate('Y-m-d H:i:s', $wishlist->get_date_created('edit')->getOffsetTimestamp()),
					'post_date_gmt' => gmdate('Y-m-d H:i:s', $wishlist->get_date_created('edit')->getTimestamp()),
					'post_type'     => 'wishlist',
					'post_status'   => $wishlist->get_status(),
					'ping_status'   => 'closed',
					'post_author'   => $wishlist->get_customer_id(),
					'post_title'    => sprintf(__('Wishlist &ndash; %s', 'wcbwl'), strftime(_x('%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'wbcwl'))),
					'post_name'     => $wishlist->get_wishlist_key(),
				)
			),
			true
		);

		if($id && !is_wp_error($id)) {
			$wishlist->set_id($id);
			$this->update_post_meta($wishlist);
			$wishlist->save_meta_data();
			$wishlist->apply_changes();
			$this->clear_caches($wishlist);
		}

		do_action('wcbwl_new_wishlist', $wishlist->get_id());
	}

	public function read(&$wishlist) {
		$wishlist->set_defaults();
		$post_object = get_post($wishlist->get_id());

		if(!$wishlist->get_id() || !$post_object || $post_object->post_type !== 'wishlist') {
			throw new Exception(__('Invalid wishlist.', 'wcbwl'));
		}

		$wishlist->set_props(
			array(
				'wishlist_key'  => $post_object->post_name,
				'date_created'  => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp($post_object->post_date_gmt) : null,
				'status'        => $post_object->post_status,
				'customer_id'   => $post_object->post_author,
			)
		);

		$this->read_wishlist_data($wishlist, $post_object);
		$wishlist->read_meta_data();
		$wishlist->set_object_read(true);
	}

	public function update(&$wishlist) {
		$wishlist->save_meta_data();
		$wishlist->set_version(WC_VERSION);

		if(null === $wishlist->get_date_created('edit')) {
			$wishlist->set_date_created(current_time('timestamp', true));
		}

		$changes = $wishlist->get_changes();

		// Only update the post when the post data changes.
		if(array_intersect(array('wishlist_key', 'date_created', 'status', 'customer_id'), array_keys($changes))) {
			$post_data = array(
				'post_date'         => gmdate('Y-m-d H:i:s', $wishlist->get_date_created('edit')->getOffsetTimestamp()),
				'post_date_gmt'     => gmdate('Y-m-d H:i:s', $wishlist->get_date_created('edit')->getTimestamp()),
				'post_status'       => $wishlist->get_status(),
				'post_author'       => $wishlist->get_customer_id(),
				'post_name'         => $wishlist->get_wishlist_key(),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if(doing_action('save_post')) {
				$GLOBALS['wpdb']->update($GLOBALS['wpdb']->posts, $post_data, array('ID' => $wishlist->get_id()));
				clean_post_cache($wishlist->get_id());
			} else {
				wp_update_post(array_merge(array('ID' => $wishlist->get_id()), $post_data));
			}
			$wishlist->read_meta_data(true); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta($wishlist);
		$wishlist->apply_changes();
		$this->clear_caches($wishlist);

		do_action('wcbwl_update_wishlist', $wishlist->get_id());
	}

	public function delete(&$wishlist, $args = array()) {
		$id = $wishlist->get_id();

		if(!$id) {
			return;
		}

		wp_delete_post($id);
		$wishlist->set_id(0);
		do_action('wcbwl_delete_wishlist', $id);
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	protected function read_wishlist_data(&$wishlist, $post_object) {
		$id = $wishlist->get_id();

		$wishlist->set_props(
			array(
				'version' => get_post_meta($id, '_wishlist_version', true),
			)
		);
	}

	protected function update_post_meta(&$wishlist) {
		$updated_props     = array();
		$meta_key_to_props = array(
			'_wishlist_version' => 'version',
		);

		$props_to_update = $this->get_props_to_update($wishlist, $meta_key_to_props);

		foreach($props_to_update as $meta_key => $prop) {
			$value = $wishlist->{"get_$prop"}('edit');
			$value = is_string($value) ? wp_slash($value) : $value;

			$updated = $this->update_or_delete_post_meta($wishlist, $meta_key, $value);

			if($updated) {
				$updated_props[] = $prop;
			}
		}

		do_action('wcbwl_wishlist_object_updated_props', $wishlist, $updated_props);
	}

	protected function clear_caches(&$wishlist) {
		clean_post_cache($wishlist->get_id());
		wp_cache_delete('wishlist-items-'.$wishlist->get_id(), 'wishlists');
	}

	public function read_items($wishlist) {
		global $wpdb;

		// Get from cache if available.
		$items = 0 < $wishlist->get_id() ? wp_cache_get('wishlist-items-'.$wishlist->get_id(), 'wishlists') : false;
		$items = false;

		if(false === $items) {
			$items = $wpdb->get_results(
				$wpdb->prepare("SELECT wishlist_item_id, wishlist_id, product_id, date_added_gmt FROM {$wpdb->prefix}woocommerce_wishlist_items WHERE wishlist_id = %d ORDER BY wishlist_item_id;", $wishlist->get_id())
			);
			foreach($items as $item) {
				wp_cache_set('item-'.$item->wishlist_item_id, $item, 'wishlist-items');
			}
			if(0 < $wishlist->get_id()) {
				wp_cache_set('wishlist-items-'.$wishlist->get_id(), $items, 'wishlists');
			}
		}

		if(!empty($items)) {
			$items = array_map(array($this, 'get_item'), array_combine(wp_list_pluck($items, 'wishlist_item_id'), $items));
		} else {
			$items = array();
		}

		return $items;
	}

	protected function get_item($data) {
		$item = new WCBWL_Wishlist_Item();
		$item->set_props(
			array(
				'wishlist_id' => $data->wishlist_id,
				'product_id'  => $data->product_id,
				'date_added'  => 0 < $data->date_added_gmt ? wc_string_to_timestamp($data->date_added_gmt) : null,
			)
		);
		$item->set_object_read(true);

		return $item;
	}

	public function delete_items($wishlist, $type = null) {
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_wishlist_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_wishlist_items items WHERE itemmeta.wishlist_item_id = items.wishlist_item_id AND items.wishlist_id = %d", $wishlist->get_id()));
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_wishlist_items WHERE wishlist_id = %d", $wishlist->get_id()));

		$this->clear_caches($wishlist);
	}
}
