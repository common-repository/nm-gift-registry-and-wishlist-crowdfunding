<?php

/**
 * Email the customer when a crowdfund contribution for an item in his wishlist has been refunded (plain text)
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/plain/customer-refunded-crowdfund-contribution.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.0.1
 */
defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( $email->get_heading() ) . " =\n\n";

/* translators: %s: recipient's name */
echo sprintf( esc_html__( 'Hi %s,', 'nm-gift-registry-crowdfunding' ), esc_html( $email->get_recipient_name() ) ) . "\n\n";

/* translators: 1: wishlist type title, 2: customer full name */
echo sprintf( esc_html__( 'Some amounts contributed to crowdfunded items in your %1$s by %2$s have been refunded. You no longer have these amounts in your crowdfunding account.', 'nm-gift-registry-crowdfunding' ),
	esc_html( nmgr_get_type_title() ),
	'<strong>' . esc_html( $order_customer_name ) . '</strong>'
 ) . "\n\n";

echo esc_html__( 'Here are the details of the refund including the amount remaining for the item in your crowdfunding account after the refund (Amount Left), and the amount you still need to completely fulfill the item (Amount Needed):', 'nm-gift-registry-crowdfunding' ) . "\n\n";


// Show the details of the refund
require_once 'crowdfund-contribution-refunds.php';


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

