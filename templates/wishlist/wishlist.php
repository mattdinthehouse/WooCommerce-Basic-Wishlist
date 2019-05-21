<?php
/**
 * Wishlist Page
 *
 * @version 0.1
 */

if(!defined('ABSPATH')) {
	exit;
}

do_action('wcbwl_before_wishlist');

?>

	<table class="shop_table shop_table_responsive cart wishlist" cellspacing="0">
		<thead>
			<tr>
				<th class="product-remove">&nbsp;</th>
				<th class="product-thumbnail">&nbsp;</th>
				<th class="product-name"><?php esc_html_e('Product', 'wcbwl'); ?></th>
				<th class="product-price"><?php esc_html_e('Price', 'wcbwl'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action('wcbwl_before_wishlist_contents'); ?>

			<?php

				foreach($wishlist->get_items() as $wishlist_item) {
					$product_id        = apply_filters('wcbwl_wishlist_item_product_id', $wishlist_item->get_product_id(), $wishlist_item);
					$_product          = apply_filters('wcbwl_wishlist_item_product', wc_get_product($product_id), $wishlist_item);
					$product_permalink = apply_filters('wcbwl_wishlist_item_permalink', get_permalink($product_id), $wishlist_item);
					
					?>
					<tr class="<?php echo esc_attr(apply_filters('wcbwl_wishlist_item_class', 'wishlist_item cart_item', $wishlist_item)); ?>">

						<td class="product-remove">
							<?php
								if($is_user_owner) {
									echo apply_filters('wcbwl_wishlist_item_remove_link', sprintf(
										'<a href="%s" class="remove" data-item_id="%s" aria-label="%s">&times;</a>',
										esc_url(add_query_arg('remove-wishlist-item', $wishlist_item->get_id())),
										esc_attr($wishlist_item->get_id()),
										__('Remove this item', 'wcbwl')
									), $wishlist_item);
								}
								else {
									print '&nbsp;';
								}
							?>
						</td>

						<td class="product-thumbnail">
							<?php
								$thumbnail = apply_filters('wcbwl_wishlist_item_thumbnail', get_the_post_thumbnail($product_id, 'woocommerce_thumbnail'), $wishlist_item);

								if(!$product_permalink) {
									echo $thumbnail;
								} else {
									printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
								}
							?>
						</td>

						<td class="product-name" data-title="<?php esc_attr_e('Product', 'wcbwl'); ?>">
							<?php
								if(!$product_permalink) {
									echo wp_kses_post(apply_filters('wcbwl_wishlist_item_name', get_the_title($product_id), $wishlist_item).'&nbsp;');
								} else {
									echo wp_kses_post(apply_filters('wcbwl_wishlist_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), get_the_title($product_id)), $wishlist_item));
								}

								do_action('wcbwl_after_wishlist_item_name', $wishlist_item);

								echo wcbwl_get_formatted_wishlist_item_data($wishlist_item);
							?>
						</td>

						<td class="product-price" data-title="<?php esc_attr_e('Price', 'wcbwl'); ?>">
							<?php
								echo apply_filters('wcbwl_wishlist_item_price', ($_product ? $_product->get_price_html() : ''), $wishlist_item);
							?>
						</td>
					</tr>
					<?php
				}
			?>

			<?php do_action('wcbwl_after_wishlist_contents'); ?>
		</tbody>
	</table>

<?php

do_action('wcbwl_after_wishlist');
