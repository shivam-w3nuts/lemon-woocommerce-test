<?php
/**
 * Classic (shortcode) checkout integration.
 *
 * @package WooCommerce_Delivery_Instructions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders and persists the field for the classic checkout form.
 */
class WCDI_Classic_Checkout {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = new self();
		add_action( 'woocommerce_after_order_notes', array( $instance, 'render_field' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( $instance, 'add_posted_data' ) );
		add_action( 'woocommerce_checkout_create_order', array( $instance, 'save_field' ), 10, 2 );
	}

	/**
	 * Output the field below the order notes on the classic checkout.
	 *
	 * @return void
	 */
	public function render_field() {
		if ( ! function_exists( 'woocommerce_form_field' ) ) {
			return;
		}

		$value = $this->get_posted_value();

		echo '<div class="wcdi-delivery-instructions">';

		woocommerce_form_field(
			WCDI_Field_Definition::CLASSIC_FIELD_NAME,
			array(
				'type'              => 'text',
				'class'             => array( 'form-row-wide' ),
				'label'             => WCDI_Field_Definition::label() . ' (' . __( 'optional', 'woocommerce-delivery-instructions' ) . ')',
				'placeholder'       => __( 'e.g. Leave the parcel at the back door', 'woocommerce-delivery-instructions' ),
				'required'          => false,
				'maxlength'         => WCDI_Field_Definition::MAX_LENGTH,
				'custom_attributes' => array(
					'maxlength'    => WCDI_Field_Definition::MAX_LENGTH,
					'autocomplete' => 'off',
				),
			),
			$value
		);

		echo '</div>';
	}

	/**
	 * Add the sanitised value to the checkout data array.
	 *
	 * @param array $data Posted checkout data.
	 * @return array
	 */
	public function add_posted_data( $data ) {
		$name = WCDI_Field_Definition::CLASSIC_FIELD_NAME;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this filter runs.
		if ( ! isset( $_POST[ $name ] ) ) {
			return $data;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised on the next line.
		$data[ $name ] = WCDI_Field_Definition::sanitize( wp_unslash( $_POST[ $name ] ) );

		return $data;
	}

	/**
	 * Persist the value onto the order using CRUD/HPOS-safe APIs.
	 *
	 * @param WC_Order $order Order object being created.
	 * @param array    $data  Sanitised checkout data.
	 * @return void
	 */
	public function save_field( $order, $data ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$name = WCDI_Field_Definition::CLASSIC_FIELD_NAME;

		if ( ! isset( $data[ $name ] ) || ! is_scalar( $data[ $name ] ) ) {
			WCDI_Field_Definition::set_classic_order_value( $order, '' );
			return;
		}

		WCDI_Field_Definition::set_classic_order_value( $order, WCDI_Field_Definition::sanitize( $data[ $name ] ) );
	}

	/**
	 * Value to repopulate the field with after a failed validation pass.
	 *
	 * @return string
	 */
	private function get_posted_value() {
		$name = WCDI_Field_Definition::CLASSIC_FIELD_NAME;

		if ( function_exists( 'WC' ) && WC()->checkout() ) {
			$value = WC()->checkout()->get_value( $name );

			return WCDI_Field_Definition::sanitize( $value );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only repopulation.
		return isset( $_POST[ $name ] ) ? WCDI_Field_Definition::sanitize( wp_unslash( $_POST[ $name ] ) ) : '';
	}
}
