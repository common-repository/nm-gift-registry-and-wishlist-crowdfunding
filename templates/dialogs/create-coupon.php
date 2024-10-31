<?php
/**
 * Template for creating a coupon
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<style>
	#nmgrcf-create-coupon-form input,
	#nmgrcf-create-coupon-form select,
	#nmgrcf-create-coupon-form .select2-container {
		width: 66% !important;
		clear: both;
	}
</style>

<form id="nmgrcf-create-coupon-form" class="nmgrcf-form" data-nmgr_post_action="create_coupon">
	<div class="nmgr-notice">
		<?php esc_html_e( 'Please note that the coupon created can only be used on wishlist items which are not crowdfunded or fulfilled. You can edit more details of the coupon on the coupon\'s page after creation.', 'nm-gift-registry-crowdfunding' ); ?>
	</div>

	<input type="hidden" name="wishlist_id" value="<?php echo esc_attr( $wishlist->get_id() ); ?>">

	<p>
		<label for="discount_type"><?php esc_html_e( 'Discount type', 'nm-gift-registry-crowdfunding' ); ?></label>
		<select id="discount_type" name="discount_type">
			<?php
			$coupon_types = wc_get_coupon_types();
			if ( $coupon_from_wallet && isset( $coupon_types[ 'percent' ] ) ) {
				unset( $coupon_types[ 'percent' ] );
			}

			foreach ( $coupon_types as $key => $label ) :
				?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="coupon_amount"><?php esc_html_e( 'Coupon amount', 'nm-gift-registry-crowdfunding' ); ?></label>
		<input type="number" id="coupon_amount" name="coupon_amount" required min="0"
		<?php if ( $coupon_from_wallet && $wishlist->get_wallet() ) : ?>
						 max="<?php echo esc_attr( $wishlist->get_wallet()->get_balance() ); ?>"
					 <?php endif; ?>
					 >
	</p>
	<p>
		<label for="product_ids">
			<?php
			esc_html_e( 'Wishlist items', 'nm-gift-registry-crowdfunding' );
			echo wp_kses( nmgr_get_svg( array(
				'icon' => 'info',
				'class' => 'align-with-text nmgr-tip',
				'style' => 'margin-left:3px;',
				'title' => __( 'Leave blank to apply coupon to all eligible wishlist items.', 'nm-gift-registry-crowdfunding' ),
				'fill' => 'gray',
				) ), nmgr_allowed_svg_tags() );
			?>
		</label>
		<select class="nmgr-coupon-product-ids" id="product_ids" name="product_ids[]" multiple>
			<?php
			foreach ( $wishlist->get_items() as $item ) :
				if ( !$item->is_fulfilled() && !$item->is_crowdfunded() ) :
					?>
					<option value="<?php echo esc_attr( $item->get_product_id() ); ?>"><?php echo esc_html( $item->get_product()->get_name() ); ?></option>
					<?php
				endif;
			endforeach;
			?>
		</select>
	</p>
	<?php if ( $coupon_from_wallet ) : ?>
		<input type="hidden" id="coupon_from_wallet" name="coupon_from_wallet" value="1">
	<?php endif; ?>
</form>
