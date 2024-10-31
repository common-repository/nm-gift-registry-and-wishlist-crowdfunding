<?php
/**
 * Refund items details snippet attached to emails
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/crowdfund-contribution-refunds.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.2.0
 */
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 30px; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Item', 'nm-gift-registry-crowdfunding' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Refunded Amount', 'nm-gift-registry-crowdfunding' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Amount Left', 'nm-gift-registry-crowdfunding' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Amount Needed', 'nm-gift-registry-crowdfunding' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $wishlist_item_ids_to_amts as $item_id => $amt_refunded ) {
				$item = $wishlist->get_item( $item_id );

				if ( !$item ) {
					continue;
				}

				$product = $item->get_product();

				if ( !$product ) {
					continue;
				}
				?>
				<tr>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php
						echo wp_kses_post( $product->get_image( array( 32, 32 ) ) );

						echo wp_kses_post( $product->get_name() );

						echo ' (' . wp_kses_post( wc_price( $item->get_crowdfund_amount_needed() ) ) . ')';

						if ( $product->get_sku() ) {
							echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
						}
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( wc_price( $amt_refunded ) ); ?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( wc_price( $item->get_crowdfund_amount_available() ) ); ?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( wc_price( $item->get_crowdfund_amount_left() ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Summary', 'nm-gift-registry-crowdfunding' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %s: wishlist type title %s */
			esc_html__( 'Here is an overview of the crowdfund status of your %s.', 'nm-gift-registry-crowdfunding' ),
			esc_html( nmgr_get_type_title() )
		);
		?>
	</p>
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<!--<tfoot>-->
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php esc_html_e( 'Total crowdfund amount expected', 'nm-gift-registry-crowdfunding' ); ?>
			</th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php echo wp_kses_post( wc_price( $wishlist->get_crowdfund_amount_needed() ) ); ?>
			</td>
		</tr>
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php esc_html_e( 'Total crowdfund amount available', 'nm-gift-registry-crowdfunding' ); ?>
			</th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php echo wp_kses_post( wc_price( $wishlist->get_crowdfund_amount_available() ) ); ?>
			</td>
		</tr>
		<!--</tfoot>-->
	</table>
</div>
