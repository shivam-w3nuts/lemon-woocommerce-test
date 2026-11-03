<?php
/**
 * Plugin Name:       WooCommerce Delivery Instructions
 * Plugin URI:        https://github.com/shivam-w3nuts/lemon-woocommerce-test
 * Description:       Adds an optional "Delivery instructions" field to checkout (classic and Checkout block), stores it with the order, and shows it in admin and customer order details.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            shivam-w3nuts
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocommerce-delivery-instructions
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.9
 * WC tested up to:   9.5
 *
 * @package WooCommerce_Delivery_Instructions
 */

defined( 'ABSPATH' ) || exit;

define( 'WCDI_VERSION', '1.0.0' );
define( 'WCDI_PLUGIN_FILE', __FILE__ );
define( 'WCDI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCDI_PLUGIN_FILE, true );
		}
	}
);

/**
 * Boot the plugin once WooCommerce is guaranteed to be loaded.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'WooCommerce Delivery Instructions requires WooCommerce to be installed and active.', 'woocommerce-delivery-instructions' )
					);
				}
			);
			return;
		}

		load_plugin_textdomain( 'woocommerce-delivery-instructions', false, dirname( plugin_basename( WCDI_PLUGIN_FILE ) ) . '/languages' );

		require_once WCDI_PLUGIN_DIR . 'includes/class-wcdi-field-definition.php';
		require_once WCDI_PLUGIN_DIR . 'includes/class-wcdi-classic-checkout.php';
		require_once WCDI_PLUGIN_DIR . 'includes/class-wcdi-blocks-checkout.php';
		require_once WCDI_PLUGIN_DIR . 'includes/class-wcdi-order-display.php';

		WCDI_Classic_Checkout::init();
		WCDI_Blocks_Checkout::init();
		WCDI_Order_Display::init();
	},
	20
);
