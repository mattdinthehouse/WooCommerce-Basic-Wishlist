<?php
/**
 * Plugin Name: WooCommerce Basic Wishlist
 * Plugin URI: 
 * Description: Wishlist plugin for WooCommerce - guest wishlists, sharing
 * Author: https://MattDwyer.cool
 * Author URI: https://mattdwyer.cool
 * Version: 1.0
 * License: GPL2
 * Text Domain: wcbwl
 * WC requires at least: 3.5.0
 * WC tested up to: 3.8.0
 *
 */

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

define('WCBWL_FILE', __FILE__);
define('WCBWL_DIR',  dirname(WCBWL_FILE));
define('WCBWL_URL',  plugins_url('', WCBWL_FILE));

define('WCBWL_VERSION', '1.0');
define('WCBWL_MIN_WC_VERSION', '3.5.0');

require_once WCBWL_DIR.'/includes/class-wcbwl.php';

function WCBWL() {
	static $instance = null;

	if(is_null($instance) && wcbwl_can_run()) {
		$instance = new WCBWL;
	}

	return $instance;
}
add_action('plugins_loaded', 'WCBWL');

register_activation_hook(WCBWL_FILE, array('WCBWL', 'install'));


function wcbwl_can_run() {
	return class_exists('WooCommerce') && version_compare(WC()->version, WCBWL_MIN_WC_VERSION, '>=');
}