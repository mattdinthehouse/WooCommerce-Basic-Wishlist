/* global wcbwl_save_to_wishlist_params */
jQuery(function($) {

	if(typeof wcbwl_save_to_wishlist_params === 'undefined') {
		return false;
	}

	/**
	 * SaveToWishlistHandler class.
	 */
	var SaveToWishlistHandler = function() {
		$(document.body)
			.on('click', '.save_to_wishlist_button', this.triggerSaveToWishlist)
			//.on('click', '.remove_from_wishlist_button', this.onRemoveFromWishlist)
			.on('save_to_wishlist', this.onSaveToWishlist)
			.on('saved_to_wishlist', this.updateButton)
			.on('saved_to_wishlist removed_from_wishlist', this.updateFragments);
	};

	/**
	 * Trigger a save to wishlist event via a click.
	 */
	SaveToWishlistHandler.prototype.triggerSaveToWishlist = function(e) {
		var $thisbutton = $(this);

		if($thisbutton.is('.ajax_save_to_wishlist')) {
			if(!$thisbutton.attr('data-product_id')) {
				return true;
			}

			e.preventDefault();

			$thisbutton.removeClass('added');
			$thisbutton.addClass('loading');

			var data = {};

			$.each($thisbutton.data(), function(key, value) {
				data[key] = value;
			});

			// Trigger event.
			$(document.body).trigger('save_to_wishlist', [$thisbutton, data]);
		}
	};

	/**
	 * Handle the save to wishlist event.
	 */
	SaveToWishlistHandler.prototype.onSaveToWishlist = function(e, $button, data) {
		// Trigger event.
		$(document.body).trigger('saving_to_wishlist', [$button, data]);

		// Ajax action.
		$.post(wcbwl_save_to_wishlist_params.wc_ajax_url.toString().replace('%%endpoint%%', 'save_to_wishlist'), data, function(response) {
			if(!response) {
				return;
			}

			if(response.error && response.product_url) {
				window.location = response.product_url;
				return;
			}

			// Trigger event so themes can refresh other areas.
			$(document.body).trigger('saved_to_wishlist', [response.fragments, $button]);
		});
	};

	/**
	 * Update fragments after remove from wishlist event in mini-wishlist.
	 */
	/*SaveToWishlistHandler.prototype.onRemoveFromWishlist = function(e) {
		var $thisbutton = $(this),
			$row        = $thisbutton.closest('.woocommerce-mini-wishlist-item');

		e.preventDefault();

		$row.block({
			message: null,
			overlayCSS: {
				opacity: 0.6
			}
		});

		$.post(wcbwl_save_to_wishlist_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_from_wishlist'), { wishlist_item_key : $thisbutton.data('wishlist_item_key') }, function(response) {
			if(!response || !response.fragments) {
				window.location = $thisbutton.attr('href');
				return;
			}
			$(document.body).trigger('removed_from_wishlist', [response.fragments, $thisbutton]);
		}).fail(function() {
			window.location = $thisbutton.attr('href');
			return;
		});
	};*/

	/**
	 * Update wishlist page elements after save to wishlist events.
	 */
	SaveToWishlistHandler.prototype.updateButton = function(e, fragments, $button) {
		$button = typeof $button === 'undefined' ? false : $button;

		if($button) {
			$button.removeClass('loading');
			$button.addClass('added');

			// View wishlist text.
			if($button.parent().find('.saved_to_wishlist').length === 0) {
				$button.after(' <a href="' + wcbwl_save_to_wishlist_params.wishlist_url + '" class="added_to_cart saved_to_wishlist wc-forward" title="' +
					wcbwl_save_to_wishlist_params.i18n_view_wishlist + '">' + wcbwl_save_to_wishlist_params.i18n_view_wishlist + '</a>');
			}

			$(document.body).trigger('wcbwl_wishlist_button_updated', [$button]);
		}
	};

	/**
	 * Update fragments after save to wishlist events.
	 */
	SaveToWishlistHandler.prototype.updateFragments = function(e, fragments) {
		if(fragments) {
			$.each(fragments, function(key) {
				$(key)
					.addClass('updating')
					.fadeTo('400', '0.6')
					.block({
						message: null,
						overlayCSS: {
							opacity: 0.6
						}
					});
			});

			$.each(fragments, function(key, value) {
				$(key).replaceWith(value);
				$(key).stop(true).css('opacity', '1').unblock();
			});

			$(document.body).trigger('wc_fragments_loaded');
		}
	};

	/**
	 * Init SaveToWishlistHandler.
	 */
	new SaveToWishlistHandler();
});
