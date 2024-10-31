<?php

/**
 * Email the customer when a crowdfund contribution for an item in his wishlist has been refunded (plain text)
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/plain/customer-refunded-free-contribution.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.0.3
 */
defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( $email->get_heading() ) . " =\n\n";

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry-crowdfunding' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: refunded amount, 2: wishlist type title, 3: wishlist title, 4: customer full name */
echo sprintf( esc_html__( '%1$s contributed to your %2$s %3$s by %4$s has been refunded. You no longer have this amount as part of your free contributions.', 'nm-gift-registry-crowdfunding' ),
	'<strong>' . wp_kses_post( wc_price( $refunded_amount ) ) . '</strong>',
	esc_html( nmgr_get_type_title() ),
	'<strong>' . esc_html( $wishlist->get_title() ) . '</strong>',
	'<strong>' . esc_html( $order_customer_name ) . '</strong>'
 ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

