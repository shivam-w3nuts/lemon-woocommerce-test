=== WooCommerce Delivery Instructions ===
Contributors: shivam-w3nuts
Tags: woocommerce, checkout, delivery, order, blocks
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds an optional delivery instructions field to WooCommerce checkout (classic and Checkout block) and shows it in admin and customer order details.

== Description ==

This plugin adds a single optional text field, "Delivery instructions", to checkout.

* Works with the WooCommerce **Checkout block** (uses the Additional Checkout Fields API, WooCommerce 8.9+).
* Works with the **classic shortcode checkout** (rendered below the order notes).
* The value is sanitised on input and escaped on output.
* Stored on the order and displayed on the admin order screen, on the order-received/thank-you page, in My Account order details and in transactional emails.
* Uses WooCommerce CRUD order APIs (`$order->get_meta()`, `$order->update_meta_data()`) and declares HPOS compatibility. No direct `wp_postmeta` or `$wpdb` access.
* Does not modify WooCommerce core or theme files.

== Installation ==

1. Copy this folder to `wp-content/plugins/woocommerce-delivery-instructions`.
2. Activate **WooCommerce Delivery Instructions** in Plugins.
3. Check out with either checkout type and enter a value in the "Delivery instructions" field.

== Frequently Asked Questions ==

= Does the value appear in the order emails? =

Yes, in both HTML and plain-text emails.

= Can the field be made required? =

Not currently; it is intentionally optional. The field identifiers live in `WCDI_Field_Definition`.

== Testing notes ==

A smoke test can be executed in a real WordPress install with WP-CLI:

`wp eval-file tests/smoke-test.php`

or standalone with a PHP CLI binary:

`php tests/smoke-test.php`

It asserts sanitisation, classic meta round-trip, block meta-key reading, block field registration and classic save behaviour.

== Changelog ==

= 1.0.0 =
* Initial release: classic + block checkout field, order storage, admin/customer/email display.
