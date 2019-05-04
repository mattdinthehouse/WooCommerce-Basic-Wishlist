<?php
/**
 * Empty wishlist page
 *
 * @version 0.1
 */

if(!defined('ABSPATH')) {
	exit;
}

do_action('wcbwl_wishlist_is_empty');

if(wc_get_page_id('wishlist') > 0) {
	?>
	<p class="return-to-shop return-to-wishlist">
		<a class="button wc-backward" href="<?php echo esc_url(apply_filters('wcbwl_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
			<?php esc_html_e('Return to shop', 'wcbwl'); ?>
		</a>
	</p>
	<?php
}
