<?php

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

if(!function_exists('wcbwl_template_loop_save_to_wishlist')) {

	function wcbwl_template_loop_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'save_to_wishlist_button',
						('yes' === get_option('woocommerce_enable_ajax_add_to_cart')) ? 'ajax_save_to_wishlist' : '',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_loop_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('loop/save-to-wishlist.php', $args);
	}
}

if(!function_exists('wcbwl_template_single_save_to_wishlist')) {

	function wcbwl_template_single_save_to_wishlist() {
		$product = wc_get_product();

		do_action('wcbwl_'.$product->get_type().'_save_to_wishlist');
	}
}

if(!function_exists('wcbwl_submit_save_to_wishlist')) {

	function wcbwl_submit_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'single_save_to_wishlist_button',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_submit_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('single-product/save-to-wishlist/submit.php', $args);
	}
}

if(!function_exists('wcbwl_link_save_to_wishlist')) {

	function wcbwl_link_save_to_wishlist($args = array()) {
		$defaults = array(
			'product_id' => get_the_id(),
			'class'      => implode(
				' ',
				array_filter(
					array(
						'button',
						'single_save_to_wishlist_button',
					)
				)
			),
			'save_to_wishlist_text' => __('Save to wishlist', 'wcbwl'),
		);

		$args = apply_filters('wcbwl_link_save_to_wishlist_args', wp_parse_args($args, $defaults));

		wc_get_template('single-product/save-to-wishlist/link.php', $args);
	}
}

if(!function_exists('wcbwl_empty_wishlist_message')) {

	function wcbwl_empty_wishlist_message() {
		echo '<p class="cart-empty wishlist-empty">'.wp_kses_post(apply_filters('wcbwl_empty_wishlist_message', __('Your wishlist is currently empty.', 'wcbwl'))).'</p>';
	}
}

if(!function_exists('wcbwl_get_formatted_wishlist_item_data')) {

	// Pretty much all of this function's logic is copy-pasted from wc_get_formatted_cart_item_data() in WC 3.6.2
	function wcbwl_get_formatted_wishlist_item_data($wishlist_item, $flat = false) {
		$item_data = array();

		$product = wc_get_product($wishlist_item->get_product_id());

		foreach($wishlist_item->get_meta_data() as $meta) {
			$label = $meta->key;
			$value = $meta->value;

			$taxonomy = wc_attribute_taxonomy_name(str_replace('attribute_pa_', '', urldecode($label)));

			if(taxonomy_exists($taxonomy)) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by('slug', $value, $taxonomy);
				if(!is_wp_error($term) && $term && $term->name) {
					$value = $term->name;
				}
				$label = wc_attribute_label($taxonomy);
			} else {
				// If this is a custom option slug, get the options name.
				$value = apply_filters('woocommerce_variation_option_name', $value, null, $taxonomy, $product);
				$label = wc_attribute_label(str_replace('attribute_', '', $label), $product);
			}

			$item_data[] = array(
				'key'   => $label,
				'value' => $value,
			);
		}

		// Filter item data to allow 3rd parties to add more to the array.
		$item_data = apply_filters('wcbwl_get_item_data', $item_data, $wishlist_item);

		// Format item data ready to display.
		foreach($item_data as $key => $data) {
			// Set hidden to true to not display meta on cart.
			if(!empty($data['hidden'])) {
				unset($item_data[$key]);
				continue;
			}
			$item_data[$key]['key']     = !empty($data['key']) ? $data['key'] : $data['name'];
			$item_data[$key]['display'] = !empty($data['display']) ? $data['display'] : (string) $data['value'];
		}

		// Output flat or in list format.
		if(count($item_data) > 0) {
			ob_start();

			if($flat) {
				foreach($item_data as $data) {
					echo esc_html($data['key']) . ': ' . wp_kses_post($data['display']) . "\n";
				}
			} else {
				wc_get_template('wishlist/wishlist-item-data.php', array('item_data' => $item_data));
			}

			return ob_get_clean();
		}

		return '';
	}
}
