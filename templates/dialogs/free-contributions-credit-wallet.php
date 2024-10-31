<?php
/**
 * Template for displaying the free contributions credit wallet action for a wishlist
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;

$amt_received = $wishlist->get_free_contributions_amount_available();
?>

<style>
	#nmgrcf-fc-credit-wallet-form section:not(:last-child) {
		margin-bottom: 20px;
	}

	#nmgrcf-fc-credit-wallet-form .nmgr-form-row {
		display: flex;
		align-items: center;
		justify-content: center;
	}

	#nmgrcf-fc-credit-wallet-form label {
		margin-right: 15px;
	}
</style>

<form id="nmgrcf-fc-credit-wallet-form" class="nmgrcf-form"
			data-nmgr_post_action="free_contributions_credit_wallet_action">
	<input type="hidden" name="wishlist_id" value="<?php echo esc_attr( $wishlist->get_id() ); ?>">
	<section class="desc nmgr-text-center">
		<?php
		printf(
			/* translators: 1: amount received, 2: wishlist type title */
			esc_html__( 'You have a total of %1$s in free contributions available to your %2$s.', 'nm-gift-registry-crowdfunding' ),
			'<strong>' . wp_kses_post( wc_price( $amt_received ) ) . '</strong>',
			esc_html( nmgr_get_type_title() )
		);
		echo '<p>' . esc_html__( 'Please note that any amount sent to the wallet would be available for use in the wallet and would no longer be counted as part of your available free contributions.', 'nm-gift-registry-crowdfunding' ) . '<p>';
		?>
	</section>
	<section>
		<div class="nmgr-form-row">
			<label for="nmgrcf_fc_credit_wallet_amt">
				<?php
				esc_html_e( 'Amount to send', 'nm-gift-registry-crowdfunding' );
				?>
			</label>
			<?php
			nmgrcf_price_box( array(
				'name' => 'nmgrcf_fc_credit_wallet_amt',
				'id' => 'nmgrcf_fc_credit_wallet_amt',
				'currency-symbol-border' => true,
				'max' => $amt_received,
				'value' => $amt_received
			) );
			?>
		</div>
	</section>
</form>
