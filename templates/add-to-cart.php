<?php
/**
 * Template for adding contribution for a crowdfunded item to the cart
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<td class="actions add_to_cart">
	<?php
	$amt_received = $item->get_crowdfund_amount_available();
	$amt_left = $item->get_crowdfund_amount_left();
	$received_text = sprintf(
		/* translators: %s: amount received */
		nmgrcf()->is_pro ? __( '%s received already!', 'nm-gift-registry-crowdfunding' ) : __( '%s received already!', 'nm-gift-registry-crowdfunding-lite' ),
		wc_price( $amt_received )
	);
	?>
	<div class="nmgr-cf-progressbar-wrapper">
		<?php if ( $amt_received ) : ?>
			<div class="amount-received"><?php echo wp_kses_post( $received_text ); ?></div>
		<?php endif; ?>
		<div class="amount-left">
			<?php
			printf(
				/* translators: %s: amount still needed */
				nmgrcf()->is_pro ? esc_html__( '%s still needed', 'nm-gift-registry-crowdfunding' ) : esc_html__( '%s still needed', 'nm-gift-registry-crowdfunding-lite' ),
				wp_kses_post( wc_price( $amt_left ) )
			);
			?>
		</div>
		<?php
		$progress_total = $amt_received + $amt_left;
		$title_attribute = sprintf(
			/* translators: 1: amount received, 2: amount needed */
			nmgrcf()->is_pro ? __( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding' ) : __( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding-lite' ),
			wc_price( $amt_received ),
			wc_price( $progress_total )
		);
		echo wp_kses( nmgr_progressbar( $progress_total, $amt_received, $title_attribute ),
			array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) );
		?>
	</div>

	<?php
	if ( $item->is_purchase_disabled() ) :

		echo wp_kses( nmgrcf_purchase_disabled_html(),
			array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) );

	else:
		do_action( 'nmgrcf_item_before_add_to_cart_form', $args );
		?>

		<form class="nmgr-cf nmgr-add-to-cart-form" action="<?php the_permalink(); ?>" method="post">
			<input type="hidden" name="nmgr-cf-wishlist-item-id" value="<?php echo absint( $item->get_id() ); ?>" />
			<input type="hidden" name="nmgr-cf-wishlist-id" value="<?php echo absint( $wishlist->get_id() ); ?>" />
			<?php
			do_action( 'nmgrcf_item_add_to_cart_form', $args );


			$crowdfund_data = $item->get_crowdfund_data();
			$minimum_amount = 0;

			if ( isset( $crowdfund_data[ 'min_amount' ] ) && $crowdfund_data[ 'min_amount' ] &&
				nmgrcf_round( $amt_left ) > nmgrcf_round( $crowdfund_data[ 'min_amount' ] ) ) {
				$minimum_amount = ( float ) $crowdfund_data[ 'min_amount' ];
			}

			nmgrcf_price_box( array(
				'max' => $amt_left,
				'min' => $minimum_amount,
				'value' => 0 === $minimum_amount ? '' : $minimum_amount,
				'currency-symbol-border' => true,
			) );

			$cls = nmgr()->is_pro && !nmgr_get_option( 'ajax_add_to_cart' ) ? '' : 'nmgr_ajax_add_to_cart';
			?>
			<button type="submit"
							class="nmgr_add_to_cart_button nmgr_cf button alt <?php echo sanitize_html_class( $cls ); ?>"
							data-product_id="<?php echo absint( $product->get_id() ); ?>"
							data-wishlist_item_id="<?php echo absint( $item->get_id() ); ?>"
							data-wishlist_id="<?php echo absint( $wishlist->get_id() ); ?>"
							title="<?php
							echo esc_html( nmgrcf()->is_pro ?
									__( 'Contribute an amount to the cost of this item', 'nm-gift-registry-crowdfunding' ) :
									__( 'Contribute an amount to the cost of this item', 'nm-gift-registry-crowdfunding-lite' )
							);
							?>">
								<?php echo esc_html( nmgrcf_get_crowdfund_item_button_text() ); ?>
			</button>
		</form>
		<?php
		do_action( 'nmgrcf_item_after_add_to_cart_form', $args );

	endif;
	?>
</td>
