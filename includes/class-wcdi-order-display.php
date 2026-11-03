<?php
/**
 * Displays the stored value in admin and customer-facing order details.
 *
 * @package WooCommerce_Delivery_Instructions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output helpers for the saved delivery instructions.
 */
class WCDI_Order_Display {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = new self();

		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $instance, 'render_admin' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $instance, 'render_customer' ) );
		add_action( 'woocommerce_email_after_order_table', array( $instance, 'render_email' ), 10, 3 );
	}

	/**
	 * Show the value on the admin order edit screen.
	 *
	 * HPOS and legacy post-based screens both fire this hook.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function render_admin( $order ) {
		$value = WCDI_Field_Definition::get_order_value( $order );

		if ( '' === $value ) {
			return;
		}

		$heading = WCDI_Field_Definition::label();

		printf(
			'<div class="wcdi-order-meta"><p><strong>%1$s:</strong><br />%2$s</p></div>',
			esc_html( $heading ),
			wp_kses_post( nl2br( esc_html( $value ) ) )
		);
	}

	/**
	 * Show the value on the thank-you page and in My Account order view.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function render_customer( $order ) {
		$value = WCDI_Field_Definition::get_order_value( $order );

		if ( '' === $value ) {
			return;
		}

		$heading = WCDI_Field_Definition::label();

		printf(
			'<section class="wcdi-delivery-instructions-details"><h2 class="woocommerce-column__title">%1$s</h2><p>%2$s</p></section>',
			esc_html( $heading ),
			wp_kses_post( nl2br( esc_html( $value ) ) )
		);
	}

	/**
	 * Show the value in transactional emails.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether the email goes to an administrator.
	 * @param bool     $plain_text    Whether the email is plain text.
	 * @return void
	 */
	public function render_email( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$value = WCDI_Field_Definition::get_order_value( $order );

		if ( '' === $value ) {
			return;
		}

		$heading = WCDI_Field_Definition::label();

		if ( $plain_text ) {
			echo "\n" . esc_html( $heading ) . ': ' . esc_html( $value ) . "\n";
			return;
		}

		printf(
			'<div class="wcdi-delivery-instructions-email"><p><strong>%1$s:</strong><br />%2$s</p></div>',
			esc_html( $heading ),
			wp_kses_post( nl2br( esc_html( $value ) ) )
		);
	}
}
