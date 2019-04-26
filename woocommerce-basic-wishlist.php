<?php
/**
 * Plugin Name: WooCommerce Basic Wishlist
 * Plugin URI: 
 * Description: Wishlist plugin for WooCommerce - guest wishlists, sharing
 * Author: https://MattDwyer.cool
 * Author URI: https://mattdwyer.cool
 * Version: 0.1
 * License: GPL2
 * Text Domain: wcbwl
 *
 */

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

define('WCBWL_FILE', __FILE__);
define('WCBWL_DIR',  dirname(WCBWL_FILE));
define('WCBWL_URL',  plugins_url('', WCBWL_FILE));

define('WCBWL_VERSION', '0.1');

require_once WCBWL_DIR.'/includes/class-wcbwl.php';

new WCBWL;