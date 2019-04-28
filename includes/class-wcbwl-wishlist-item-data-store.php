<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Wishlist_Item_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	protected $meta_type = 'wishlist_item';

	protected $object_id_field_for_meta = 'wishlist_item_id';

	public function create(&$item) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix.'woocommerce_wishlist_items', array(
				'wishlist_id'    => $item->get_wishlist_id('edit'),
				'product_id'     => $item->get_product_id('edit'),
				'date_added'     => gmdate('Y-m-d H:i:s', $item->get_date_added('edit')->getOffsetTimestamp()),
				'date_added_gmt' => gmdate('Y-m-d H:i:s', $item->get_date_added('edit')->getTimestamp()),
			)
		);
		$item->set_id($wpdb->insert_id);
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache($item);

		do_action('woocommerce_new_wishlist_item', $item->get_id(), $item);
	}

	public function update(&$item) {
		global $wpdb;

		$changes = $item->get_changes();

		if(array_intersect(array('wishlist', 'product_id', 'date_added'), array_keys($changes))) {
			$wpdb->update(
				$wpdb->prefix.'woocommerce_wishlist_items', array(
				'wishlist_id'    => $item->get_wishlist_id('edit'),
				'product_id'     => $item->get_product_id('edit'),
				'date_added'     => gmdate('Y-m-d H:i:s', $item->get_date_added('edit')->getOffsetTimestamp()),
				'date_added_gmt' => gmdate('Y-m-d H:i:s', $item->get_date_added('edit')->getTimestamp()),
				), array('wishlist_item_id' => $item->get_id())
			);
		}

		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache($item);

		do_action('woocommerce_update_wishlist_item', $item->get_id(), $item);
	}

	public function delete(&$item, $args = array()) {
		if($item->get_id()) {
			global $wpdb;
			do_action('woocommerce_before_delete_wishlist_item', $item->get_id());
			$wpdb->delete($wpdb->prefix.'woocommerce_wishlist_items', array('wishlist_item_id' => $item->get_id()));
			$wpdb->delete($wpdb->prefix.'woocommerce_wishlist_itemmeta', array('wishlist_item_id' => $item->get_id()));
			do_action('woocommerce_delete_wishlist_item', $item->get_id());
			$this->clear_cache($item);
		}
	}

	public function read(&$item) {
		global $wpdb;

		$item->set_defaults();

		$data = wp_cache_get('item-'.$item->get_id(), 'wishlist-items');

		if(false === $data) {
			$data = $wpdb->get_row($wpdb->prepare("SELECT wishlist_id, product_id, date_added_gmt FROM {$wpdb->prefix}woocommerce_wishlist_items WHERE wishlist_item_id = %d LIMIT 1;", $item->get_id()));
			wp_cache_set('item-'.$item->get_id(), $data, 'wishlist-items');
		}

		if(!$data) {
			throw new Exception(__('Invalid wishlist item.', 'wcbwl'));
		}

		$item->set_props(
			array(
				'wishlist_id' => $data->wishlist_id,
				'product_id'  => $data->product_id,
				'date_added'  => 0 < $data->date_added_gmt ? wc_string_to_timestamp($data->date_added_gmt) : null,
			)
		);
		$item->read_meta_data();

		$item->set_object_read(true);
	}

	public function clear_cache(&$item) {
		wp_cache_delete('item-'.$item->get_id(), 'wishlist-items');
		wp_cache_delete('wishlist-items-'.$item->get_wishlist_id(), 'wishlists');
		wp_cache_delete($item->get_id(), $this->meta_type.'_meta' );
	}
}
