<?php
/**
 * Email the customer when he has received a crowdfund contribution for an item in his wishlist
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/customer-new-crowdfund-contribution.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.0.1
 */
defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email->get_heading(), $email );
?>

<p>
	<?php
	/* translators: %s: recipient's name */
	printf( esc_html__( 'Hi %s,', 'nm-gift-registry-crowdfunding' ), esc_html( $email->get_recipient_name() ) );
	?>
</p>

<p>
	<?php
	/* translators: 1: wishlist type title, 2: wishlist title, 3: customer full name */
	printf( esc_html__( 'Contributions have been made for some crowdfunded items in your %1$s %2$s by %3$s.', 'nm-gift-registry-crowdfunding' ),
		esc_html( nmgr_get_type_title() ),
		'<strong>' . esc_html( $email->get_wishlist()->get_title() ) . '</strong>',
		'<strong>' . esc_html( $order_customer_name ) . '</strong>'
	);
	?>
</p>

<p>
	<?php
	/* translators: %s: date */
	printf( esc_html__( 'Here are the details of the contributions made on %s.', 'nm-gift-registry-crowdfunding' ),
		esc_html( wc_format_datetime( $order->get_date_paid() ) )
	);
	?>
</p>

<?php
// Table showing the details of the contribution
require_once 'crowdfund-contribution-details.php';


/**
 * Maybe show wishlist messages
 */
$message_obj = $email->get_wishlist()->get_message_in_order( $order->get_id() );

if ( $message_obj && nmgr_get_option( 'email_customer_new_contribution_checkout_message', 1 ) ) {
	?>
	<div style="margin-bottom: 40px;">
		<p>
			<?php
			/* translators: %s: customer full name */
			printf( esc_html__( 'You have also been sent a message by %s.', 'nm-gift-registry-crowdfunding' ),
				'<strong>' . esc_html( $order_customer_name ) . '</strong>'
			);
			?>
		</p>

		<?php
		/*
		 * @hooked NMGR_Mailer::email_message() Show the message sent to the wishlist's owner on checkout
		 */
		do_action( 'nmgr_email_checkout_message', $message_obj->content, $email );
		?>
	</div>
<?php } ?>

<p style="margin-bottom: 40px;"><?php esc_html_e( 'Congratulations, we look forward to processing more contributions for you.', 'nm-gift-registry-crowdfunding' ); ?></p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
