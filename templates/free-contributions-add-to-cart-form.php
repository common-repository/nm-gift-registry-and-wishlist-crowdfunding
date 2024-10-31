<?php
/**
 * Template for adding free contributions to a wishlist
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="nmgrcf-fc-add-to-cart-form-wrapper">
	<?php do_action( 'nmgrcf_before_free_contributions_add_to_cart_form', $wishlist ); ?>
	<form class="nmgrcf-fc-add-to-cart-form" action="<?php the_permalink(); ?>" method="post">
		<label for="nmgrcf_fc_add_to_cart_amt">
			<?php
			$needs_free_contributions = $wishlist->needs_free_contributions();
			if ( $needs_free_contributions ) {
				$label = sprintf(
					/* translators: %s: wishlist type title */
					__( 'Make a free contribution towards the items in this %s.', 'nm-gift-registry-crowdfunding' ),
					nmgr_get_type_title()
				);
			} else {
				$label = sprintf(
					/* translators: %s: wishlist type title */
					__( 'This %s no longer needs free contributions.', 'nm-gift-registry-crowdfunding' ),
					nmgr_get_type_title()
				);
			}

			$description = apply_filters( 'nmgrcf_free_contributions_add_to_cart_description_text', $label, $wishlist );
			echo wp_kses_post( $description );
			?>
		</label>
		<?php
		if ( $needs_free_contributions ) :
			$amt_left = $wishlist->get_free_contributions_amount_needed() ?
				$wishlist->get_free_contributions_amount_left() :
				'';
			$minimum_amount = $settings[ 'minimum_amount' ];
			if ( nmgrcf_round( $amt_left ) < nmgrcf_round( $minimum_amount ) ) {
				$minimum_amount = $amt_left;
			}
			?>

			<div>
				<?php
				nmgrcf_price_box( array(
					'max' => $amt_left,
					'min' => $minimum_amount,
					'value' => 0 === $minimum_amount ? '' : $minimum_amount,
					'name' => 'nmgrcf_fc_add_to_cart_amt',
					'id' => 'nmgrcf_fc_add_to_cart_amt',
					'currency-symbol-border' => true,
				) );
				?>
				<input type="hidden" name="nmgr_wid" value="<?php echo esc_attr( $wishlist->get_id() ); ?>">
				<button type="submit"
								class="nmgrcf_fc_add_to_cart_button button alt <?php echo nmgr_get_option( 'ajax_add_to_cart' ) ? 'nmgr_ajax_add_to_cart' : ''; ?>"
								data-wishlist_id="<?php echo absint( $wishlist->get_id() ); ?>"
								title="<?php
								printf(
									/* translators: %s: wishlist type title */
									esc_html__( 'Contribute an amount to this %s.', 'nm-gift-registry-crowdfunding' ),
									esc_html( nmgr_get_type_title() )
								);
								?>">
									<?php echo esc_html( nmgrcf_get_crowdfund_item_button_text() ); ?>
				</button>
			</div>
		<?php endif; ?>
	</form>
	<?php do_action( 'nmgrcf_after_free_contributions_add_to_cart_form', $wishlist ); ?>
</div>
