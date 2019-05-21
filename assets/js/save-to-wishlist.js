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
			.on('click', '.wishlist .product-remove > a', this.onRemoveFromWishlist)
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
	 * Code from woocommerce/assets/js/cart.js
	 */
	SaveToWishlistHandler.prototype.onRemoveFromWishlist = function(e) {
		e.preventDefault();

		var $a = $(e.currentTarget);
		var $wishlist = $a.parents('.wishlist');

		$wishlist.addClass('processing').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.ajax( {
			type:     'GET',
			url:      $a.attr('href'),
			dataType: 'html',
			success:  function(response) {
				update_wc_div(response);
			},
			complete: function() {
				$wishlist.removeClass('processing').unblock();
				$.scroll_to_notices($('[role="alert"]'));
			}
		} );
	};

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

			// added_to_cart event code from woocommerce/assets/js/frontend/cart-fragments.js 
			sessionStorage.setItem(wc_cart_fragments_params.fragment_name, JSON.stringify(fragments));
		}
	};

	/**
	 * Update the .woocommerce div with a string of html.
	 * Code from woocommerce/assets/js/cart.js
	 *
	 * @param {String} html_str The HTML string with which to replace the div.
	 * @param {bool} preserve_notices Should notices be kept? False by default.
	 */
	var update_wc_div = function(html_str, preserve_notices) {
		var $html         = $.parseHTML(html_str);
		var $new_wishlist = $('.wishlist', $html);
		var $notices      = $('.woocommerce-error, .woocommerce-message, .woocommerce-info', $html);

		// No form, cannot do this.
		if($('.wishlist').length === 0) {
			window.location.reload();
			return;
		}

		// Remove errors
		if(!preserve_notices) {
			$('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
		}

		if($new_wishlist.length === 0) {
			// If the checkout is also displayed on this page, trigger reload instead.
			if($('.woocommerce-checkout').length) {
				window.location.reload();
				return;
			}

			// No items to display now! Replace all wishlist content.
			var $wishlist_html = $('.wishlist-empty', $html).closest('.woocommerce');
			$('.wishlist').closest('.woocommerce').replaceWith($wishlist_html);

			// Display errors
			if($notices.length > 0) {
				show_notice($notices);
			}

			// Notify plugins that the wishlist was emptied.
			$(document.body ).trigger('wcbwl_wishlist_emptied');
		}
		else {
			// If the checkout is also displayed on this page, trigger update event.
			if($('.woocommerce-checkout').length) {
				$(document.body).trigger('update_checkout');
			}

			$('.wishlist').replaceWith($new_wishlist);

			if($notices.length > 0) {
				show_notice($notices);
			}
		}

		$(document.body).trigger('wc_fragment_refresh');
	};

	/**
	 * Shows new notices on the page.
	 * Code from woocommerce/assets/js/cart.js
	 *
	 * @param {Object} The Notice HTML Element in string or object form.
	 */
	var show_notice = function(html_element, $target) {
		if(!$target) {
			$target = $('.woocommerce-notices-wrapper:first') || $('.wishlist-empty').closest('.woocommerce') || $('.wishlist');
		}
		$target.prepend(html_element);
	};

	/**
	 * Init SaveToWishlistHandler.
	 */
	new SaveToWishlistHandler();
});
