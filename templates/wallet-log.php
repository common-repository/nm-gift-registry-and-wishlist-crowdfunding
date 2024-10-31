<?php
/**
 * Template for displaying log of all actions in a wallet
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<div id="nmgrcf-wallet-log"
		 class="<?php echo esc_attr( $class ); ?>"
		 data-wishlist-id="<?php echo $wishlist ? absint( $wishlist->get_id() ) : 0; ?>">

	<?php
	do_action( 'nmgrcf_before_wallet_log', $wishlist );
	?>
	<table class="nmgrcf-wallet-log-table responsive nmgr-table">
		<thead>
			<tr>
				<?php
				foreach ( $columns as $key => $label ) {
					switch ( $key ) {
						case 'id':
						case 'type':
						case 'descriptor':
							echo '<th class="' . esc_attr( $key ) . ' dt-orderable">' . esc_html( $label ) . '</th>';
							break;
						case 'amount':
						case 'date':
							echo '<th class="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</th>';
							break;
					}
				}
				?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $log as $log_item ) : ?>
				<tr>
					<?php foreach ( $columns as $key => $label ) : ?>
						<?php
						$value = $log_item[ $key ] ?? '';

						switch ( $key ) {
							case 'id':
								echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $label ) . '" data-sort="' . esc_attr( $value ) . '">';
								echo esc_html( $value );
								echo '</td>';
								break;
							case 'type':
								echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $label ) . '" data-sort="' . esc_attr( $value ) . '">';
								echo esc_html( strtolower( $value ) );
								echo '</td>';
								break;
							case 'descriptor':
							case 'description':
								$event_desc = $wallet->get_event_description( $log_item[ 'event_code' ] );
								$desc = !empty( $event_desc ) ? $event_desc[ 'description' ] : '';
								echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $label ) . '" data-sort="' . esc_attr( $desc ) . '">';
								echo '<span title="' . esc_attr( $value ) . '">' . esc_html( $desc ) . '</span>';
								echo '</td>';
								break;
							case 'date':
								$datetime = new DateTime( $value );
								echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $label ) . '" data-sort="' . esc_attr( $datetime->getTimestamp() ) . '">';
								echo esc_html( $value );
								echo '</td>';
								break;
							case 'amount':
								echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $label ) . '" data-sort="' . esc_attr( $value ) . '">';
								echo wp_kses_post( wc_price( $value ) );
								echo '</td>';
								break;
						}
						?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php
	do_action( 'nmgrcf_after_wallet_log', $wishlist );
	?>

</div>



