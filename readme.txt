=== RD Order Modifier for WooCommerce ===
Contributors: camper2020
Tags: woocommerce, tax, vat, orders, order editing
Requires at least: 5.0
Tested up to: 6.6.1
Stable tag: 1.1.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate/?hosted_button_id=KAFSMYT5QQH76

Allows editing order items pricing inclusive of tax or VAT and using unit cost instead of items totals.

== Description ==

The RD Order Modifier for WooCommerce allows for the editing of line item prices in the WooCommerce admin area inclusive of tax or VAT as an alternative to the exclusive of tax editing WooCommerce supports by default. You can also choose to edit the prices per unit or per item total.

=== Features ===

* View line item pricing inclusive of tax or VAT
* View pricing per unit and/or on item total
* Edit line item pricing inclusive of tax
* Edit pricing per unit or on item total
* Works with discounts
* Works with refunds
* Works with base shop tax rates
* High Performance Order Storage (HPOS) compatible

=== Premium Version ===

There is a premium version of this plugin available for purchase from the WooCommerce marketplace. The premium version includes all the features of this *FREE* version as well as the following extra features:

* Support for custom fee and shipping line items in addition to product line items
* Support for multiple tax rates
* Support for location based and reduced tax rates
* Adds a quick link to item product page
* Adds a warning if order total has increased after item price edit
* Optionally change the position of the new columns to show after the standard WooCommerce columns instead of before
* Ability to disable the feature for non-taxable items
* Adds a product info quick view button where you can quickly view line item details without leaving the order screen
* Adds “Quick apply” buttons to the input boxes so you can quickly apply the regular or sales price to a line item so you don’t have to check pricing first
* Moves the “Settings” menu into the WooCommerce -> Tax area so that it seamlessly fits with WooCommerce instead of being a separate menu on the WordPress sidebar
* Removes RD branding

<a href="https://www.robotdwarf.com/woocommerce-plugins/admin-order-modifier/" target="_blank">Get the premium version</a>

=== External Services ===

This plugin makes use of an external API connection to our website (robotdwarf.com) to retrieve information and pricing related to our premium offerings. This connection is only active when viewing the *Our Product* menu page of this plugin and does not send or share any usage data or statistics with our website or any 3rd party services.
For more information, please view our <a href="https://www.robotdwarf.com/privacy-policy#free-plugin-users" target="_blank">privacy policy</a>

== Installation & Usage ==

Upload the RD Order Modifier for WooCommerce plugin to your WooCommerce shop, activate it, and then create a new WooCommerce order or edit an existing one and adjust the pricing of any line items.
When creating a new order ensure you have added a tax line item to the order already before adding a product line item otherwise the plugin won't know how to calculate the tax.

Use the Settings page to set how you want the inclusive pricing columns to show and how editing should work (per item or per item total).

== Frequently Asked Questions ==
*NONE AS YET*

== Screenshots ==

1. New column created in the order item list for tax inclusive pricing.
2. Input box for changing inc tax pricing.

== Changelog ==

= 1.1.1 =
Release Date – 06 September 2024
*Remove newsletter signup
*Security fixes
*Compatibility update

= 1.1.0 =
Release Date – 06 April 2024
*Rework products page
*Add external service notice to readme
*Remove variables from translations
*Updated woo.com links to woocommerce.com
*Compatibility update

= 1.0.15 =
Release Date – 02 February 2024
*Fix some JS spacing
*Compatibility update

= 1.0.14 =
*Release Date - 05 January 2024*
*Fixed bug where deactivating WooCommerce would trigger an error
*Added "Premium Plugins" page
*Updated premium version URLs
*Fixed missing version in readme
*Updated compatibility

= 1.0.13 =
*Release Date - 28 November 2023*
*Fixed missing dependency issue in Query Monitor
*Fixed bug in screen check
*Updated compatibility

= 1.0.12 =
*Release Date - 06 November 2023*
*Updated woocommerce.com links to woo.com
*Added mailing list signup links

= 1.0.11 =
*Release Date - 27 October 2023*
*Added High Performance Order Storage (HPOS) support

= 1.0.10 =
*Release Date - 12 October 2023*
*Fixed issue when saving "Show "includes tax" modifier total column" setting