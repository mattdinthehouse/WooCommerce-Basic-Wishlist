<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

class WCBWL_Wishlist extends WC_Data {

	protected $data = array(
		'wishlist_key'         => '',
		'date_created'         => '',
		'status'               => '',
		'customer_id'          => 0,
		'customer_ip_address'  => '',
		'customer_user_agent'  => '',
		'version'              => '',
	);

	protected $cache_group = 'wishlists';

	protected $data_store_name = 'wishlist';

	protected $object_type = 'wishlist';

	protected $items = null;

	protected $items_to_delete = array();

	/**
	 * Constructor.
	 *
	 * @param int|object|WCBWL_Wishlist $wishlist ID to load from the DB, or WCBWL_Wishlist object.
	 */
	public function __construct($wishlist = 0) {
		parent::__construct($wishlist);

		if($wishlist instanceof WCBWL_Wishlist_Item) {
			$this->set_id($wishlist->get_id());
		}
		else if(!empty($wishlist->ID)) {
			$this->set_id($wishlist->ID);
		}
		else if(is_numeric($wishlist) && $wishlist > 0) {
			$this->set_id($wishlist);
		}
		else {
			$this->set_object_read(true);
		}

		$this->data_store = WC_Data_Store::load('wishlist');
		if($this->get_id() > 0) {
			$this->data_store->read($this);
		}
	}



	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	public function save() {
		if($this->data_store) {
			// Trigger action before saving to the DB. Allows you to adjust object props before save.
			do_action('wcbwl_before_wishlist_object_save', $this, $this->data_store);

			if($this->get_id()) {
				$this->data_store->update($this);
			} else {
				$this->data_store->create($this);
			}
		}
		$this->save_items();
		return $this->get_id();
	}

	protected function save_items() {
		// Delete items.
		foreach($this->items_to_delete as $item) {
			$item->delete();
		}
		$this->items_to_delete = array();

		// Add/save items.
		if(is_array($this->items)) {
			$this->items = array_filter($this->items);
			foreach($this->items as $item_key => $item) {
				$item->set_wishlist_id($this->get_id());

				$item_id = $item->save();

				// If ID changed (new item saved to DB)...
				if($item_id !== $item_key) {
					$this->items[$item_id] = $item;

					unset($this->items[$item_key]);
				}
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	public function get_wishlist_key($context = 'view') {
		return $this->get_prop('wishlist_key', $context);
	}

	public function get_date_created($context = 'view') {
		return $this->get_prop('date_created', $context);
	}

	public function get_status($context = 'view') {
		$status = $this->get_prop('status', $context);

		if(empty($status) && 'view' === $context) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters('wcbwl_default_wishlist_status', 'wcbwl-active');
		}

		return $status;
	}

	public function get_customer_id($context = 'view') {
		return $this->get_prop('customer_id', $context);
	}

	public function get_customer_ip_address( $context = 'view' ) {
		return $this->get_prop('customer_ip_address', $context);
	}

	public function get_customer_user_agent( $context = 'view' ) {
		return $this->get_prop('customer_user_agent', $context);
	}

	public function get_version($context = 'view') {
		return $this->get_prop('version', $context);
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	public function set_wishlist_key($value) {
		$this->set_prop('wishlist_key', $value);
	}

	public function set_date_created($value) {
		return $this->set_date_prop('date_created', $value);
	}

	public function set_status($new_status) {
		$old_status = $this->get_status();
		$new_status = 'wcbwl-' === substr($new_status, 0, 6) ? substr($new_status, 6) : $new_status;

		// If setting the status, ensure it's set to a valid status.
		if(true === $this->object_read) {
			// Only allow valid new status.
			if(!in_array('wcbwl-'.$new_status, $this->get_valid_statuses(), true) && 'trash' !== $new_status) {
				$new_status = 'wcbwl-inactive';
			}

			// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
			if($old_status && !in_array('wcbwl-'.$old_status, $this->get_valid_statuses(), true) && 'trash' !== $old_status) {
				$old_status = 'inactive';
			}
		}

		$this->set_prop('status', $new_status);

		return array(
			'from' => $old_status,
			'to'   => $new_status,
		);
	}

	public function set_customer_id($value) {
		$this->set_prop('customer_id', absint($value));
	}

	public function set_customer_ip_address( $value ) {
		$this->set_prop('customer_ip_address', $value);
	}

	public function set_customer_user_agent( $value ) {
		$this->set_prop('customer_user_agent', $value);
	}

	public function set_version($value) {
		$this->set_prop('version', $value);
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	protected function get_valid_statuses() {
		return array_keys(WC()->wishlist->get_wishlist_statuses());
	}

	public function get_items() {
		if(null === $this->items) {
			$this->items = array_filter($this->data_store->read_items($this));
		}

		return apply_filters('wcbwl_wishlist_get_items', $this->items, $this);
	}

	public function get_item($item_id, $load_from_db = true) {
		if($load_from_db) {
			$this->get_items();
		}

		return (!empty($this->items[$item_id]) ? $this->items[$item_id] : false);
	}

	public function remove_item($item_id) {
		$item = $this->get_item($item_id);
		$this->items_to_delete[] = $item;
		unset($this->items[$item->get_id()]);
	}

	public function add_item($item) {
		// Make sure existing items are loaded so we can append this new one.
		$this->get_items();

		// Set parent.
		$item->set_wishlist_id($this->get_id());

		// Append new row with generated temporary ID.
		$item_id = $item->get_id();

		if($item_id) {
			$this->items[$item_id] = $item;
		} else {
			$this->items['new:'.count($this->items)] = $item;
		}
	}
}