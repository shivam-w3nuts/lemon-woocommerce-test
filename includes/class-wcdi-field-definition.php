<?php
/**
 * Single source of truth for the delivery instructions field.
 *
 * @package WooCommerce_Delivery_Instructions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field identifiers, sanitisation and order-meta access.
 */
class WCDI_Field_Definition {

	/**
	 * Checkout block field id. Must be "{namespace}/{key}".
	 */
	const BLOCK_FIELD_ID = 'wcdi/delivery-instructions';

	/**
	 * Meta key WooCommerce uses for additional checkout fields.
	 *
	 * The Additional Checkout Fields API stores values as "_wc_other/{id}".
	 */
	const BLOCK_META_KEY = '_wc_other/wcdi/delivery-instructions';

	/**
	 * Posted field name and meta key used by the classic checkout.
	 */
	const CLASSIC_FIELD_NAME = 'wcdi_delivery_instructions';
	const CLASSIC_META_KEY  = '_wcdi_delivery_instructions';

	/**
	 * Maximum accepted length, in characters.
	 */
	const MAX_LENGTH = 500;

	/**
	 * Field label.
	 *
	 * @return string
	 */
	public static function label() {
		return __( 'Delivery notes', 'woocommerce-delivery-instructions' );
	}

	/**
	 * Sanitise a raw submitted value.
	 *
	 * @param mixed $raw Raw, unslashed input.
	 * @return string
	 */
	public static function sanitize( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}

		$value = trim( sanitize_text_field( (string) $raw ) );

		if ( '' === $value ) {
			return '';
		}

		if ( function_exists( 'mb_substr' ) ) {
			$value = mb_substr( $value, 0, self::MAX_LENGTH );
		} else {
			$value = substr( $value, 0, self::MAX_LENGTH );
		}

		return trim( $value );
	}

	/**
	 * Get the stored value for an order, whichever checkout produced it.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	public static function get_order_value( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return '';
		}

		$value = $order->get_meta( self::BLOCK_META_KEY );

		if ( '' === $value || null === $value ) {
			$value = $order->get_meta( self::CLASSIC_META_KEY );
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return (string) $value;
	}

	/**
	 * Store a value on an order using the classic-checkout meta key.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $value Already sanitised value.
	 * @return void
	 */
	public static function set_classic_order_value( $order, $value ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( '' === $value ) {
			$order->delete_meta_data( self::CLASSIC_META_KEY );
			return;
		}

		$order->update_meta_data( self::CLASSIC_META_KEY, $value );
	}
}
