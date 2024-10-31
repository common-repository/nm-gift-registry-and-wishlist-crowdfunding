<?php

/**
 * Crowdfund contribution details snippet attached to emails
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/plain/crowdfund-contribution-details.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.0.1
 */
defined( 'ABSPATH' ) || exit;

// Total price figure of all the items - without currency symbol
$total = 0;

foreach ( $order_item_ids as $wishlist_item_id => $order_item_id ) {
	$order_item = $order->get_item( $order_item_id );
	$wishlist_item = nmgr_get_wishlist_item( $wishlist_item_id );

	if ( !$order_item || !$wishlist_item ) {
		continue;
	}

	$product = $wishlist_item->get_product();

	if ( !$product ) {
		continue;
	}

	$total = $total + $order->get_line_subtotal( $order_item );

	echo wp_kses_post( $product->get_name() );

	if ( $product->get_sku() ) {
		echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
	}

	echo ' = ' . wp_kses_post( $order->get_formatted_line_subtotal( $order_item ) );

	echo "\n\n";
}

echo "==========\n\n";

$total_price = wc_price( $total, array( 'currency' => $order->get_currency() ) );
echo wp_kses_post( __( 'Total', 'nm-gift-registry-crowdfunding' ) . "\t " . $total_price ) . "\n\n";

