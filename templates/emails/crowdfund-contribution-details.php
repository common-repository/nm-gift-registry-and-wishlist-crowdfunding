<?php
/**
 * Crowdfund contribution details snippet attached to emails
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-crowdfunding/emails/crowdfund-contribution-details.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Crowdfunding/Templates/Emails
 * @version 2.0.1
 */
defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col"  style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Item', 'nm-gift-registry-crowdfunding' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>"><?php esc_html_e( 'Amount Contributed', 'nm-gift-registry-crowdfunding' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Total price figure of all the items - without currency symbol
			$total = 0;

			foreach ( $order_item_ids as $wishlist_item_id => $order_item_id ) {
				$order_item = $order->get_item( $order_item_id );
				$wishlist_item = nmgr_get_wishlist_item( $wishlist_item_id );

				if ( !$order_item || !$wishlist_item ) {
					continue;
				}

				$product = $wishlist_item->get_product();

				if ( !$product ) {
					continue;
				}

				$image = $product->get_image( array( 32, 32 ) );
				$total = $total + $order->get_line_subtotal( $order_item );
				?>
				<tr>
					<td class="td"  style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php
						echo wp_kses_post( $image );
						echo wp_kses_post( $product->get_name() );

						if ( $product->get_sku() ) {
							echo wp_kses_post( ' (#' . $product->get_sku() . ')' );
						}
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $order_item ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th class="td" scope="row"  style="text-align:<?php echo esc_attr( $text_align ); ?>;border-top-width: 4px;">
					<?php esc_html_e( 'Total', 'nm-gift-registry-crowdfunding' ); ?>
				</th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;border-top-width: 4px;">
					<?php
					$total_price = wc_price( $total, array( 'currency' => $order->get_currency() ) );
					echo wp_kses_post( $total_price );
					?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
