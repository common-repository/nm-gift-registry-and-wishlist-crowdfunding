<?php

/**
 * Refund items details snippet attached to emails
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/plain/crowdfund-contribution-refunds.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.2.0
 */
defined( 'ABSPATH' ) || exit;

foreach ( $wishlist_item_ids_to_amts as $item_id => $amt_refunded ) {
	$item = $wishlist->get_item( $item_id );

	if ( !$item ) {
		continue;
	}

	$product = $item->get_product();

	if ( !$product ) {
		continue;
	}

	echo wp_kses_post( $product->get_name() );
	echo ' (' . wp_kses_post( wc_price( $item->get_crowdfund_amount_needed() ) ) . ')';

	if ( $product->get_sku() ) {
		echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
	}

	echo "\n";

	echo esc_html__( 'Refunded Amount', 'nm-gift-registry-crowdfunding' ) . ":\t " . wp_kses_post( strip_tags( wc_price( $amt_refunded ) ) );

	echo "\n";

	echo esc_html__( 'Amount Left', 'nm-gift-registry-crowdfunding' ) . ":\t " . wp_kses_post( strip_tags( wc_price( $item->get_crowdfund_amount_available() ) ) );

	echo "\n";

	echo esc_html__( 'Amount Needed', 'nm-gift-registry-crowdfunding' ) . ":\t " . wp_kses_post( strip_tags( wc_price( $item->get_crowdfund_amount_left() ) ) );

	echo "\n\n";
}

echo "==========\n\n";

echo esc_html( wc_strtoupper( __( 'Summary', 'nm-gift-registry-crowdfunding' ) ) ) . "\n\n";

printf(
	/* translators: %s: wishlist type title %s */
	esc_html__( 'Here is an overview of the crowdfund status of your %s.', 'nm-gift-registry-crowdfunding' ),
	esc_html( nmgr_get_type_title() )
);

echo "\n\n";

/* translators: %s: wishlist type title */
echo esc_html__( 'Total crowdfund amount expected', 'nm-gift-registry-crowdfunding' )
 . ":\t "
 . wp_kses_post( strip_tags( wc_price( $wishlist->get_crowdfund_amount_needed() ) ) )
 . "\n\n";

echo esc_html__( 'Total crowdfund amount available', 'nm-gift-registry-crowdfunding' ) . ":\t " . wp_kses_post( strip_tags( wc_price( $wishlist->get_crowdfund_amount_available() ) ) ) . "\n\n";

