<?php

/**
 * Email the customer when he has received a free contribution for his wishlist (plain text)
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/plain/customer-new-free-contribution.php
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

/* translators: 1: contribution price, 2: customer full name, 3: wishlist type title, 4: wishlist title */
echo sprintf( esc_html__( 'You have just received a free contribution of %1$s from %2$s for your %3$s %4$s.', 'nm-gift-registry-crowdfunding' ),
	'<strong>' . wp_kses_post( wc_price( $order_item->get_subtotal() ) ) . '</strong>',
	'<strong>' . esc_html( $order_customer_name ) . '</strong>',
	esc_html( nmgr_get_type_title() ),
	'<strong>' . esc_html( $email->get_wishlist()->get_title() ) . '</strong>'
 ) . "\n\n";

/**
 * Maybe show wishlist messages
 */
$message_obj = $email->get_wishlist()->get_message_in_order( $order->get_id() );

if ( $message_obj && nmgr_get_option( 'email_customer_new_free_contribution_checkout_message', 1 ) ) {
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	/* translators: %s: customer full name */
	echo sprintf( esc_html__( 'You have also been sent a message by %s.', 'nm-gift-registry-crowdfunding' ),
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	) . "\n\n";

	/*
	 * @hooked NMGR_Mailer::email_message() Show the message sent to the wishlist's owner on checkout
	 */
	do_action( 'nmgr_email_checkout_message', $message_obj->content, $email );
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'Congratulations, we look forward to processing more contributions for you.', 'nm-gift-registry-crowdfunding' ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

