<?php
/**
 * Smoke tests for the plugin's pure logic.
 *
 * Usage:
 *   php tests/smoke-test.php          (standalone, uses the stubs below)
 *   wp eval-file tests/smoke-test.php (inside WordPress, uses real functions)
 *
 * @package WooCommerce_Delivery_Instructions
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { // phpcs:ignore
		return $text;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $text ) {
		return (string) $text;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { // phpcs:ignore
		public $code;
		public $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
	}
}

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order { // phpcs:ignore
		private $meta = array();

		public function get_meta( $key ) {
			return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
		}

		public function update_meta_data( $key, $value ) {
			$this->meta[ $key ] = $value;
		}

		public function delete_meta_data( $key ) {
			unset( $this->meta[ $key ] );
		}

		public function seed_meta( $key, $value ) {
			$this->meta[ $key ] = $value;
		}
	}
}

if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
	function woocommerce_register_additional_checkout_field( $args ) {
		$GLOBALS['wcdi_registered'][] = $args;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wcdi-field-definition.php';
require_once dirname( __DIR__ ) . '/includes/class-wcdi-blocks-checkout.php';
require_once dirname( __DIR__ ) . '/includes/class-wcdi-classic-checkout.php';
require_once dirname( __DIR__ ) . '/includes/class-wcdi-order-display.php';

$GLOBALS['wcdi_checks']   = 0;
$GLOBALS['wcdi_failures'] = 0;

function wcdi_assert( $condition, $message ) {
	++$GLOBALS['wcdi_checks'];

	if ( $condition ) {
		echo "ok: {$message}\n";
		return;
	}

	++$GLOBALS['wcdi_failures'];
	echo "FAIL: {$message}\n";
}

$sanitize = array( 'WCDI_Field_Definition', 'sanitize' );

wcdi_assert( 'Leave at the back door' === $sanitize( '  Leave at the back door  ' ), 'trims surrounding whitespace' );
wcdi_assert( 'alert(1)' === $sanitize( '<script>alert(1)</script>' ), 'strips tags but keeps text' );
wcdi_assert( false === strpos( $sanitize( '<b>Gate code 1234</b>' ), '<' ), 'removes markup' );
wcdi_assert( '' === $sanitize( '' ), 'empty string stays empty' );
wcdi_assert( '' === $sanitize( null ), 'null becomes empty string' );
wcdi_assert( '' === $sanitize( array( 'x' ) ), 'arrays are rejected' );
wcdi_assert( '"quoted" & ampersand' === $sanitize( '"quoted" & ampersand' ), 'keeps harmless punctuation' );
wcdi_assert( WCDI_Field_Definition::MAX_LENGTH === strlen( $sanitize( str_repeat( 'a', 900 ) ) ), 'enforces maximum length' );

$order = new WC_Order();

WCDI_Field_Definition::set_classic_order_value( $order, 'Ring the bell twice' );
wcdi_assert( 'Ring the bell twice' === WCDI_Field_Definition::get_order_value( $order ), 'classic value round-trips' );

$order->seed_meta( WCDI_Field_Definition::BLOCK_META_KEY, 'Block checkout value' );
wcdi_assert( 'Block checkout value' === WCDI_Field_Definition::get_order_value( $order ), 'block value takes precedence' );

$order->seed_meta( WCDI_Field_Definition::BLOCK_META_KEY, '' );
wcdi_assert( 'Ring the bell twice' === WCDI_Field_Definition::get_order_value( $order ), 'falls back to classic value when block value is empty' );

WCDI_Field_Definition::set_classic_order_value( $order, '' );
wcdi_assert( '' === WCDI_Field_Definition::get_order_value( $order ), 'clearing deletes the stored value' );
wcdi_assert( '' === WCDI_Field_Definition::get_order_value( null ), 'non-order input yields empty string' );

$validate = array( 'WCDI_Blocks_Checkout', 'validate_value' );
wcdi_assert( true === $validate( 'Short note' ), 'validate accepts a short value' );
wcdi_assert( $validate( str_repeat( 'a', WCDI_Field_Definition::MAX_LENGTH + 1 ) ) instanceof WP_Error, 'validate rejects oversized value' );

wcdi_assert( 'wcdi/delivery-instructions' === WCDI_Field_Definition::BLOCK_FIELD_ID, 'block field id keeps namespace/key format' );
wcdi_assert( 0 === strpos( WCDI_Field_Definition::BLOCK_META_KEY, '_wc_other/' ), 'block meta key uses WooCommerce prefix' );

$GLOBALS['wcdi_registered'] = array();
$blocks                     = new WCDI_Blocks_Checkout();
$blocks->register_field();
$blocks->register_field();

wcdi_assert( 1 === count( $GLOBALS['wcdi_registered'] ), 'block field registers exactly once' );

if ( ! empty( $GLOBALS['wcdi_registered'] ) ) {
	$args = $GLOBALS['wcdi_registered'][0];

	wcdi_assert( WCDI_Field_Definition::BLOCK_FIELD_ID === $args['id'], 'registered id matches constant' );
	wcdi_assert( 'order' === $args['location'], 'field renders in the order location' );
	wcdi_assert( false === $args['required'], 'field is optional' );
	wcdi_assert( is_callable( $args['sanitize_callback'] ), 'sanitize callback is callable' );
	wcdi_assert( is_callable( $args['validate_callback'] ), 'validate callback is callable' );
	wcdi_assert( '' !== $args['label'], 'field has a label' );
	wcdi_assert( WCDI_Field_Definition::MAX_LENGTH === $args['attributes']['maxLength'], 'front-end maxlength matches constant' );
	wcdi_assert( 'Gate code 42' === call_user_func( $args['sanitize_callback'], ' <b>Gate code 42</b> ' ), 'registered sanitize callback cleans input' );
}

$classic = new WCDI_Classic_Checkout();

$_POST[ WCDI_Field_Definition::CLASSIC_FIELD_NAME ] = ' <b>Use the side gate</b> ';
$posted                                            = $classic->add_posted_data( array() );
wcdi_assert( 'Use the side gate' === $posted[ WCDI_Field_Definition::CLASSIC_FIELD_NAME ], 'classic posted data is sanitised' );

$classic_order = new WC_Order();
$classic->save_field( $classic_order, $posted );
wcdi_assert( 'Use the side gate' === WCDI_Field_Definition::get_order_value( $classic_order ), 'classic save persists to order meta' );

$classic->save_field( $classic_order, array() );
wcdi_assert( '' === WCDI_Field_Definition::get_order_value( $classic_order ), 'missing field clears previous value' );

unset( $_POST[ WCDI_Field_Definition::CLASSIC_FIELD_NAME ] );
wcdi_assert( array( 'billing_first_name' => 'Ada' ) === $classic->add_posted_data( array( 'billing_first_name' => 'Ada' ) ), 'post data untouched when field is absent' );

$display = new WCDI_Order_Display();

$classic_display_order = new WC_Order();
$classic_display_order->seed_meta( WCDI_Field_Definition::CLASSIC_META_KEY, 'Ring the bell twice' );

wcdi_assert( false === WCDI_Order_Display::wc_renders_field( $classic_display_order ), 'classic value is not owned by WooCommerce' );

ob_start();
$display->render_admin( $classic_display_order );
$admin_output = ob_get_clean();
wcdi_assert( 1 === substr_count( $admin_output, 'Delivery notes' ), 'classic value printed once in admin' );

$block_display_order = new WC_Order();
$block_display_order->seed_meta( WCDI_Field_Definition::BLOCK_META_KEY, 'Testing Additional order information' );

wcdi_assert( false === WCDI_Order_Display::wc_renders_field( $block_display_order ), 'block value not owned while the WooCommerce service is absent' );

ob_start();
$display->render_admin( $block_display_order );
wcdi_assert( 1 === substr_count( ob_get_clean(), 'Delivery notes' ), 'block value printed by the plugin without WooCommerce service' );

if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsAdmin' ) ) {
	eval( 'namespace Automattic\WooCommerce\Blocks\Domain\Services; class CheckoutFieldsAdmin {}' );
}

wcdi_assert( true === WCDI_Order_Display::wc_renders_field( $block_display_order ), 'block value detected as owned by WooCommerce' );
wcdi_assert( false === WCDI_Order_Display::wc_renders_field( $classic_display_order ), 'classic value still not owned by WooCommerce' );

ob_start();
$display->render_admin( $block_display_order );
wcdi_assert( '' === ob_get_clean(), 'admin output suppressed for block values WooCommerce already prints' );

ob_start();
$display->render_customer( $block_display_order );
wcdi_assert( '' === ob_get_clean(), 'customer output suppressed for block values WooCommerce already prints' );

ob_start();
$display->render_email( $block_display_order, false, true );
wcdi_assert( '' === ob_get_clean(), 'email output suppressed for block values WooCommerce already prints' );

ob_start();
$display->render_admin( $classic_display_order );
wcdi_assert( 1 === substr_count( ob_get_clean(), 'Delivery notes' ), 'classic value still printed once in admin' );

ob_start();
$display->render_email( $classic_display_order, false, true );
wcdi_assert( 1 === substr_count( ob_get_clean(), 'Ring the bell twice' ), 'classic value still printed in emails' );

printf( "\n%d checks, %d failure(s)\n", $GLOBALS['wcdi_checks'], $GLOBALS['wcdi_failures'] );

if ( 'cli' === PHP_SAPI && 0 !== $GLOBALS['wcdi_failures'] ) {
	exit( 1 );
}
