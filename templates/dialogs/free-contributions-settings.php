<?php
/**
 * Template for displaying the free contributions settings for a wishlist
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>
<style>
	#nmgrcf-fc-settings-form label {
		width: 150px;
		float: left;
	}

	#nmgrcf-fc-settings-form	.nmgrcf-form-row {
		clear: both;
	}

	#nmgrcf-fc-settings-form	.nmgrcf-form-row:not(:last-child) {
		margin-bottom: 23px;
	}
</style>


<form id="nmgrcf-fc-settings-form" class="nmgrcf-form"
			data-nmgr_post_action="save_free_contributions_settings">
	<input type="hidden" name="wishlist_id" value="<?php echo esc_attr( $wishlist->get_id() ); ?>">
	<section>
		<div class="nmgrcf-form-row">
			<label for="nmgrcf_fc_min_amt">
				<?php
				esc_html_e( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding' );
				echo wp_kses( nmgr_get_svg( array(
					'icon' => 'info',
					'class' => 'align-with-text nmgr-tip',
					'style' => 'margin-left:3px;',
					'title' => __( 'Set a minimum amount that contributors can send to your wallet.', 'nm-gift-registry-crowdfunding' ),
					'fill' => 'gray',
					) ), nmgr_allowed_svg_tags() );
				?>
			</label>
			<?php
			nmgrcf_price_box( array(
				'name' => 'minimum_amount',
				'id' => 'nmgrcf_fc_min_amt',
				'currency-symbol-border' => true,
				'value' => $settings[ 'minimum_amount' ]
			) );
			?>
		</div>
		<div class="nmgr-form-row">
			<label for="nmgrcf_fc_amt_needed">
				<?php
				esc_html_e( 'Total contribution needed (optional)', 'nm-gift-registry-crowdfunding' );
				echo wp_kses( nmgr_get_svg( array(
					'icon' => 'info',
					'class' => 'align-with-text nmgr-tip',
					'style' => 'margin-left:3px;',
					'title' => __( 'Set a total amount that can be contributed to your wallet after which contributions would be disabled.', 'nm-gift-registry-crowdfunding' ),
					'fill' => 'gray',
					) ), nmgr_allowed_svg_tags() );
				?>
			</label>
			<?php
			nmgrcf_price_box( array(
				'name' => 'amount_needed',
				'id' => 'nmgrcf_fc_amt_needed',
				'currency-symbol-border' => true,
				'value' => $settings[ 'amount_needed' ]
			) );
			?>
		</div>
	</section>
</form>
