<?php

/**
 * Plugin Name:          BlueX for WooCommerce
 * Plugin URI:           https://bluex.cl/
 * Description:          Add Blue Express shipping methods to your WooCommerce store.
 * Author:               Blue Express
 * Author URI:           https://bluex.cl/
 * Version:              2.1.3
 * License:              GPLv2 or later
 * Text Domain:          woocommerce-bluex
 * Domain Path:          /languages
 * WC requires at least: 3.0
 * WC tested up to:      4.4
 *
 */

defined('ABSPATH') || exit;

define('WC_CORREIOS_VERSION', '3.8.0');
define('WC_CORREIOS_PLUGIN_FILE', __FILE__);
//HPOS compatibility
use \Automattic\WooCommerce\Utilities\FeaturesUtil;

add_action('before_woocommerce_init', function () {
	if (!class_exists(FeaturesUtil::class))
		return;
	FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
});
if (!class_exists('WC_Correios')) {
	include_once dirname(__FILE__) . '/includes/class-wc-correios.php';

	add_action('plugins_loaded', array('WC_Correios', 'init'));
}

add_action('rest_api_init', function () {
	register_rest_route('customapi/v1', '/trackingCode', array(
		'methods' => 'PUT',
		'callback' => 'updateTrackingCode',
		'permission_callback' => '__return_true'
	));
});


function updateTrackingCode(WP_REST_Request $request)
{
	// Sanitize input values from the request
	$orderId = sanitize_text_field($request['orderId']);
	$trackingCode = sanitize_text_field($request['trackingCode']);

	// Early return if either orderId or trackingCode is empty
	if (empty($orderId) || empty($trackingCode)) {
		return false;
	}

	// Retrieve the order object
	$order = wc_get_order($orderId);
	if (!$order) {
		// Return false if the order object could not be retrieved
		return false;
	}

	// Update order meta data
	if (method_exists($order, 'update_meta_data')) {
		// Using update_meta_data method if available
		$order->update_meta_data('_correios_tracking_code', $trackingCode);
		$order->update_meta_data('id-post-bluex', true);
		$order->save();
	} else {
		// Fallback to direct post meta update
		update_post_meta($orderId, '_correios_tracking_code', $trackingCode);
		update_post_meta($orderId, 'id-post-bluex', true);
	}

	return true;
}
