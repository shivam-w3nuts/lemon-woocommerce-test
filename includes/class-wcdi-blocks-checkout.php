<?php
/**
 * Checkout block integration via the Additional Checkout Fields API.
 *
 * @package WooCommerce_Delivery_Instructions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the delivery instructions field for the Cart/Checkout blocks.
 *
 * The API (WooCommerce 8.9+) renders, validates, persists and re-displays the
 * field on the block checkout and stores the value as order meta.
 */
class WCDI_Blocks_Checkout {

	/**
	 * Whether the field has already been registered on this request.
	 *
	 * @var bool
	 */
	private static $registered = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = new self();

		add_action( 'woocommerce_blocks_loaded', array( $instance, 'register_field' ) );
		add_action( 'woocommerce_init', array( $instance, 'register_field' ) );
		// Fallback for requests where the blocks package loads later or earlier.
		add_action( 'init', array( $instance, 'register_field' ), 20 );
	}

	/**
	 * Register the field with WooCommerce, once.
	 *
	 * @return void
	 */
	public function register_field() {
		if ( self::$registered ) {
			return;
		}

		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'                => WCDI_Field_Definition::BLOCK_FIELD_ID,
				'label'             => WCDI_Field_Definition::label(),
				'location'          => 'order',
				'type'              => 'text',
				'required'          => false,
				'attributes'        => array(
					'maxLength'    => WCDI_Field_Definition::MAX_LENGTH,
					'autocomplete' => 'off',
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_value' ),
				'validate_callback' => array( __CLASS__, 'validate_value' ),
			)
		);

		self::$registered = true;
	}

	/**
	 * Sanitise a value submitted through the block checkout.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_value( $value ) {
		return WCDI_Field_Definition::sanitize( $value );
	}

	/**
	 * Reject values that are too long (defence in depth for non-UI clients).
	 *
	 * @param mixed $value Submitted value.
	 * @return true|WP_Error
	 */
	public static function validate_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return true;
		}

		$raw = wp_strip_all_tags( (string) $value );

		if ( function_exists( 'mb_strlen' ) ? mb_strlen( $raw ) > WCDI_Field_Definition::MAX_LENGTH : strlen( $raw ) > WCDI_Field_Definition::MAX_LENGTH ) {
			return new WP_Error(
				'wcdi-delivery-instructions-too-long',
				sprintf(
					/* translators: %d: maximum number of characters. */
					__( 'Delivery instructions must be %d characters or fewer.', 'woocommerce-delivery-instructions' ),
					WCDI_Field_Definition::MAX_LENGTH
				)
			);
		}

		return true;
	}
}
