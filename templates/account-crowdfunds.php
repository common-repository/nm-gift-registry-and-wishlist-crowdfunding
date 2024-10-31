<?php
/**
 * Template for displaying customer contributions for crowdfunded items
 *
 * @link https://docs.nmerimedia.com/nm-gift-registry-and-wishlist/overriding-templates/
 * @version 3.0.0
 * @sync
 */
defined( 'ABSPATH' ) || exit;
?>

<div <?php echo nmgr_format_attributes( $attributes ); ?>>

	<?php
	if ( !$wishlist || empty( $contributions ) ):

		echo wp_kses( nmgr_get_default_account_section_content( 'crowdfunds', $wishlist ),
			array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) );

	else:

		if ( $title ) {
			printf( '<div class="nmgr-template-title crowdfunds">%s</div>',
				wp_kses( $title, array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() ) )
			);
		}

		do_action( 'nmgrcf_before_crowdfunds', $wishlist );
		?>
		<table class="nmgrcf-crowdfunds-table nmgr-table">
			<thead>
				<tr>
					<?php
					foreach ( $contribution_columns as $key => $column ) {
						switch ( $key ) {
							case 'contributor':
							case 'item-crowdfunded':
								echo '<th class="' . esc_attr( $key ) . ' dt-orderable">' . esc_html( $column ) . '</th>';
								break;
							case 'date-contributed':
							case 'order':
								echo '<th class="' . esc_attr( $key ) . ' dt-orderable">' . esc_html( $column ) . '</th>';
								break;
							case 'amount':
								echo '<th class="' . esc_attr( $key ) . ' dt-orderable">' . esc_html( $column ) . '</th>';
								break;
							default:
								echo '<th class="' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
								break;
						}
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $contributions as $contribution ) : ?>
					<tr>
						<?php
						$user = $contribution[ 'order' ]->get_user();
						$using_billing_details = false;
						if ( $user ) {
							$username = "$user->first_name $user->last_name";
							$customer = $username ? $username : $user->display_name;
							$email = $user->user_email;
						} else {
							$customer = $contribution[ 'order' ]->get_formatted_billing_full_name() ?
								$contribution[ 'order' ]->get_formatted_billing_full_name() : __( 'Guest', 'nm-gift-registry-crowdfunding' );
							$email = $contribution[ 'order' ]->get_billing_email() ? $contribution[ 'order' ]->get_billing_email() : '';
							$using_billing_details = true;
						}

						foreach ( $contribution_columns as $key => $column ) :
							?>
							<?php
							switch ( $key ) {
								case 'contributor':
									echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $column ) . '" data-sort="' . esc_attr( $customer ) . '">';
									if ( !$contribution[ 'order' ]->get_formatted_billing_full_name() && !$user ) {
										echo '<span class="nmgr-guest-text">' . esc_html( $customer ) . '</span>';
									} else {
										echo esc_html( $customer );
									}

									if ( apply_filters( "nmgrcf_crowdfunds_table_{$key}_column_show_email", true ) ) {
										echo '<div class="meta-item email">';
										echo $email ? '<strong>' . esc_html__( 'Email:', 'nm-gift-registry-crowdfunding' ) . ' </strong>' . esc_html( sanitize_email( $email ) ) : '&#8212;';
										echo '</div>';
									}

									if ( apply_filters( "nmgrcf_crowdfunds_table_{$key}_column_show_phone", true ) &&
										$using_billing_details && $contribution[ 'order' ]->get_billing_phone() ) {
										echo '<div class="meta-item phone"><strong>' . esc_html__( 'Tel:', 'nm-gift-registry-crowdfunding' ) . ' </strong>' . esc_html( $contribution[ 'order' ]->get_billing_phone() ) . '</div>';
									}

									do_action( "nmgrcf_crowdfunds_table_{$key}_column" );

									echo '</td>';
									break;
								case 'order':
									echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $column ) . '" data-sort="' . esc_attr( $contribution[ 'order' ]->get_id() ) . '">';
									$label = sprintf(
										/* translators: %s: order number */
										__( 'Order #%s', 'nm-gift-registry-crowdfunding' ),
										$contribution[ 'order' ]->get_order_number()
									);
									if ( $contribution[ 'order' ]->get_status() === 'trash' ) {
										echo esc_html( $label );
									} else {
										echo '<a href="' . esc_url( get_edit_post_link( $contribution[ 'order' ]->get_id() ) ) . '">' . esc_html( $label ) . '</a>';
									}
									do_action( "nmgrcf_crowdfunds_table_{$key}_column" );
									echo '</td>';
									break;
								case 'date-contributed':
									$datetime = new DateTime( $contribution[ 'order' ]->get_date_created() );
									echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $column ) . '" data-sort="' . esc_attr( $datetime->getTimestamp() ) . '">';
									echo esc_html( nmgr_format_date( $contribution[ 'order' ]->get_date_created() ) );
									do_action( "nmgrcf_crowdfunds_table_{$key}_column" );
									echo '</td>';
									break;
								case 'item-crowdfunded':
									$product = $contribution[ 'item' ]->get_product();
									$permalink = is_admin() ? get_edit_post_link( $product->get_id() ) : $product->get_permalink( $contribution[ 'item' ]->get_data() );
									echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $column ) . '" data-sort="' . esc_attr( $product->get_name() ) . '">';
									echo $permalink ? '<a href="' . esc_url( $permalink ) . '">' . wp_kses_post( $product->get_name() ) . '</a>' : wp_kses_post( $product->get_name() );
									do_action( "nmgrcf_crowdfunds_table_{$key}_column" );
									echo '</td>';
									break;
								case 'amount':
									echo '<td class="' . esc_attr( $key ) . '" data-title="' . esc_attr( $column ) . '" data-sort="' . esc_attr( $contribution[ 'amount' ] ) . '">';
									echo wp_kses_post( wc_price( $contribution[ 'amount' ] ) );
									do_action( "nmgrcf_crowdfunds_table_{$key}_column" );
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
		do_action( 'nmgrcf_after_crowdfunds', $wishlist );

	endif;
	?>

</div>



