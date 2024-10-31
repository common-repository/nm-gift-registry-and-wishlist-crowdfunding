<?php
/**
 * Actions related to the management of crowdfunded items in the wishlist items table
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Item_Table {

	public static function run() {
		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}

		add_filter( 'nmgr_items_view_parts_data', [ __CLASS__, 'crowdfunding_items_view_data' ], 10, 2 );
		add_filter( 'nmgr_delete_item_notice', array( __CLASS__, 'set_crowdfund_delete_notice' ), 5, 2 );
		add_filter( 'nmgr_item_view_attributes', [ __CLASS__, 'add_item_view_classes' ], 10, 2 );
		add_action( 'nmgr_after_items_actions', array( __CLASS__, 'after_items_actions_show_wallet_transfer_notice' ), 1, 3 );
		add_filter( 'nmgr_items_total_table_rows', array( __CLASS__, 'add_items_total_crowdfund_rows' ), 10, 2 );
		add_filter( 'nmgr_item_actions', array( __CLASS__, 'add_item_actions' ), 10, 2 );
		add_filter( 'nmgr_add_items_table_columns', array( __CLASS__, 'add_crowdfund_column_to_add_items_dialog_in_admin' ) );
		add_filter( 'nmgr_get_template_args', array( __CLASS__, 'get_crowdfund_add_to_cart_template' ) );
		add_action( 'nmgr_item_view_after_title', array( __CLASS__, 'maybe_show_wallet_transfers_icon' ) );
		add_action( 'nmgr_item_before_add_to_cart_form', array( __CLASS__, 'maybe_show_purchase_disabled_text' ) );
		add_action( 'nmgr_before_items', array( __CLASS__, 'hide_elements_if_purchase_disabled' ) );
		add_action( 'nmgr_post_action', [ __CLASS__, 'post_action' ] );
	}

	public static function crowdfunding_items_view_data( $data, $view ) {
		$hide = is_nmgr_wishlist() &&
			method_exists( $view->get_wishlist(), 'has_crowdfunded_item' ) &&
			!$view->get_wishlist()->has_crowdfunded_item();

		if ( nmgr_get_option( 'enable_crowdfunding', 1 ) && !$hide ) {
			$label = nmgrcf()->is_pro ?
				__( 'Crowdfunded', 'nm-gift-registry-crowdfunding' ) :
				__( 'Crowdfunded', 'nm-gift-registry-crowdfunding-lite' );

			$data[ 'crowdfunding' ] = [
				'label' => $label,
				'table_header_content' => nmgr_get_svg( array(
					'icon' => 'users',
					'size' => 1,
					'fill' => '#ccc',
					'class' => 'nmgr-tip nmgr-cursor-help',
					'title' => $label,
				) ),
				'priority' => 75,
				'content' => [ __CLASS__, 'items_view_crowdfunding_content' ],
				'content_container_attributes' => [
					'class' => 'nmgr-text-center',
					'data-title' => $label,
					'data-sort-value' => $view->get_item() ? ( int ) $view->get_item()->is_crowdfunded() : '',
				],
			];
		}

		return $data;
	}

	public static function post_action( $args ) {
		switch ( $args[ 'post_action' ] ?? null ) {
			case 'toggle_item_crowdfund':
				nmgr_check_wishlist_permission( $args[ 'wishlist_id' ] ?? false  );

				if ( !empty( $args[ 'wishlist_item_ids' ] ) ) {
					$success = false;
					$response = [];

					foreach ( ($args[ 'wishlist_item_ids' ] ) as $item_id ) {
						$item = nmgrcf_get_item( $item_id );

						if ( $item->maintain_crowdfund_status() ) {
							$notice = nmgrcf_get_item_maintain_crowdfund_status_notice( $item );
							if ( !$notice ) {
								$notice = nmgrcf()->is_pro ?
									__( 'The crowdfunded status of some of the selected items count not be changed as they were ineligible.', 'nm-gift-registry-crowdfunding' ) :
									__( 'The crowdfunded status of some of the selected items count not be changed as they were ineligible.', 'nm-gift-registry-crowdfunding-lite' );
							}
							$msg = '<strong>' . $item->get_product()->get_name() . '</strong> - ' . $notice;
							$response[ 'toast_notice' ][] = nmgr_get_toast_notice( $msg, 'error' );
							continue;
						}

						if ( $item->is_crowdfunded() && !$item->has_crowdfund_contributions() ) {
							$item->unmake_crowdfunded();
						} elseif ( !$item->is_crowdfunded() ) {
							$item->make_crowdfunded();
						}

						$success = true;
					}

					if ( $success ) {
						$response[ 'replace_templates' ] = [
							'#nmgr-items' => nmgr_get_items_template( $args[ 'wishlist_id' ] )
						];
						$response[ 'toast_notice' ][] = nmgr_get_success_toast_notice();
					}

					wp_send_json( $response );
				}
				break;
		}
	}

	public static function items_view_crowdfunding_content( $view ) {
		ob_start();
		$items_args = $view->get_args();
		$item = $view->get_item();
		$crowdfunded = ( int ) $item->is_crowdfunded();

		if ( $items_args[ 'editable' ] ) {
			$available = $item->get_crowdfund_amount_available();
			$left = $item->get_crowdfund_amount_left();
			$amt_available = wc_price( $available );
			$amt_left = wc_price( $left );
			$amt_needed = $item->get_crowdfund_amount_needed();
		}
		?>
		<div>
			<div class="view">
				<?php
				if ( $crowdfunded ) {
					$icon_args = array(
						'icon' => 'users',
						'fill' => 'currentColor',
						'class' => 'nmgr-tip',
						'title' => nmgrcf()->is_pro ?
						__( 'Crowdfunded', 'nm-gift-registry-crowdfunding' ) :
						__( 'Crowdfunded', 'nm-gift-registry-crowdfunding-lite' ),
					);

					$icon_args[ 'title' ] = apply_filters( 'nmgrcf_items_table_body_crowdfunding_icon_tooltip', $icon_args[ 'title' ] );

					echo wp_kses( nmgr_get_svg( $icon_args ), nmgr_allowed_svg_tags() );

					if ( $items_args[ 'editable' ] ) {
						if ( $available ) {
							$amt_available_text = sprintf(
								/* translators: %s: amount received */
								nmgrcf()->is_pro ? __( '%s received already!', 'nm-gift-registry-crowdfunding' ) : __( '%s received already!', 'nm-gift-registry-crowdfunding-lite' ),
								$amt_available
							);
						} else {
							$amt_available_text = sprintf(
								/* translators: %s: amount received */
								nmgrcf()->is_pro ? __( '%s received.', 'nm-gift-registry-crowdfunding' ) : __( '%s received.', 'nm-gift-registry-crowdfunding-lite' ),
								$amt_available
							);
						}

						$amt_left_text = sprintf(
							/* translators: %s: amount still needed */
							nmgrcf()->is_pro ? __( '%s still needed.', 'nm-gift-registry-crowdfunding' ) : __( '%s still needed.', 'nm-gift-registry-crowdfunding-lite' ),
							$amt_left
						);

						$title_attribute = sprintf(
							/* translators: 1: amount received, 2: amount needed */
							nmgrcf()->is_pro ? __( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding' ) : __( '%1$s of %2$s received.', 'nm-gift-registry-crowdfunding-lite' ),
							wc_price( $available ),
							wc_price( $amt_needed )
						);

						$progress_total = $available + $left;
						echo wp_kses(
							nmgr_progressbar( $progress_total, $available, $title_attribute, true, false ),
							array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() )
						);
						?>
						<div class="cf-info">
							<div class="amt-received nmgr-tip" title="<?php echo esc_attr( strip_tags( $amt_available_text ) ); ?>">
								<?php
								echo wp_kses( nmgr_get_svg( array(
									'icon' => 'cart-full',
									'class' => 'align-with-text',
									'style' => 'margin-right:2px;',
									'fill' => 'currentColor',
									) ), nmgr_allowed_svg_tags() ) . wp_kses_post( $amt_available );
								?>
							</div>
							<div class="amt-needed nmgr-tip" title="<?php echo esc_attr( strip_tags( $amt_left_text ) ); ?>">
								<?php
								echo wp_kses( nmgr_get_svg( array(
									'icon' => 'cart-empty',
									'class' => 'align-with-text',
									'style' => 'margin-right:2px;',
									'fill' => 'currentColor',
									) ), nmgr_allowed_svg_tags() ) . wp_kses_post( $amt_left );
								?>
							</div>
						</div>
						<?php
					}
				} else {
					echo wp_kses( nmgr_get_svg( array(
						'icon' => 'users',
						'fill' => '#ccc',
						'class' => 'nmgr-tip',
						'title' => nmgrcf()->is_pro ?
							__( 'Not crowdfunded', 'nm-gift-registry-crowdfunding' ) :
							__( 'Not crowdfunded', 'nm-gift-registry-crowdfunding-lite' ),
						) ), nmgr_allowed_svg_tags() );
				}
				?>
			</div>
			<?php if ( $items_args[ 'editable' ] ) : ?>
				<div class="edit" style="display: none;">
					<?php
					$checkbox_args = array(
						'input_id' => 'toggle-crowdfund-status-' . $item->get_id(),
						'input_name' => 'wishlist_item_crowdfunded[' . $item->get_id() . ']',
						'checked' => $item->is_crowdfunded(),
						'input_attributes' => array(),
						'label_attributes' => array(),
					);

					if ( $item->maintain_crowdfund_status() ) {
						$maintain_crowdfund_status_notice = nmgrcf_get_item_maintain_crowdfund_status_notice( $item );
						$checkbox_args[ 'input_attributes' ][ 'readonly' ] = 'readonly';
						$checkbox_args[ 'label_attributes' ][ 'title' ] = $maintain_crowdfund_status_notice; // for html notice
						$checkbox_args[ 'label_attributes' ][ 'data-nmgrcf_disabled_notice' ] = $maintain_crowdfund_status_notice; // for js alert
					}
					/**
					 * Checkbox switch must be present even if crowdfunding is disabled in order
					 * to retain the crowdfund status when the items are saved.
					 */
					echo nmgr_get_checkbox_switch( $checkbox_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>

					<?php if ( !$item->maintain_crowdfund_status() ) : ?>
						<div class="crowdfunding-data">
							<?php
							$id = 'nmgr-cf-min-amount' . $item->get_id();
							$crowdfund_data = $item->get_crowdfund_data();

							nmgrcf_price_box( array(
								'title' => nmgrcf()->is_pro ?
									__( 'Minimum amount that should be contributed to this item.', 'nm-gift-registry-crowdfunding' ) :
									__( 'Minimum amount that should be contributed to this item.', 'nm-gift-registry-crowdfunding-lite' ),
								'name' => 'nmgrcf_crowdfund_data[min_amount][' . $item->get_id() . ']',
								'id' => $id,
								'value' => isset( $crowdfund_data[ 'min_amount' ] ) ? $crowdfund_data[ 'min_amount' ] : '',
							) );
							?>
							<div>
								<label for="<?php echo esc_attr( $id ); ?>" class="min-amount-text">
									<?php
									echo esc_html(
										nmgrcf()->is_pro ?
											__( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding' ) :
											__( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding-lite' )
									);
									?>
								</label>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function set_crowdfund_delete_notice( $notice, $item ) {
		if ( method_exists( $item, 'get_crowdfund_amount_available' ) && $item->get_crowdfund_amount_available() ) {
			if ( method_exists( $item, 'is_wallet_transfer_enabled' ) && $item->is_wallet_transfer_enabled() ) {
				$notice .= ' ' . (nmgrcf()->is_pro ?
					__( 'Please move the item\'s crowdfunded contributions to the wallet before deletion if applicable to prevent them from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding' ) :
					__( 'Please move the item\'s crowdfunded contributions to the wallet before deletion if applicable to prevent them from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding-lite' )
					);
			} else {
				$notice .= ' ' . (nmgrcf()->is_pro ?
					__( 'This would also delete the item\'s crowdfunded contributions.', 'nm-gift-registry-crowdfunding' ) :
					__( 'This would also delete the item\'s crowdfunded contributions.', 'nm-gift-registry-crowdfunding-lite' )
					);
			}
		} elseif (
			$item->get_purchased_quantity() &&
			method_exists( $item, 'is_wallet_transfer_enabled' ) &&
			$item->is_wallet_transfer_enabled()
		) {
			remove_filter( 'nmgr_delete_item_notice', array( 'NMGR_Templates', 'notify_of_item_purchased_status' ), 10 );
			$notice .= ' ' . (nmgrcf()->is_pro ?
				__( 'Please move the item\'s purchased amount to the wallet before deletion to prevent it from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding' ) :
				__( 'Please move the item\'s purchased amount to the wallet before deletion to prevent it from being lost. Click OK to delete anyway.', 'nm-gift-registry-crowdfunding-lite' )
				);
		}
		return $notice;
	}

	public static function add_item_view_classes( $attrs, $view ) {
		if ( $view->get_item()->is_crowdfunded() ) {
			$attrs[ 'class' ][] = 'item-crowdfunded';
		}

		if ( $view->get_item()->is_wallet_transfer_enabled() ) {
			$attrs[ 'class' ][] = 'wallet-transfer-enabled';
		}

		if ( $view->get_item()->is_purchase_disabled() ) {
			$attrs[ 'class' ][] = 'purchase-disabled';
		}

		return $attrs;
	}

	/**
	 * If we have enough money in the wallet to fulfill the amount left for the
	 * wishlist to be fulfilled, notify
	 */
	public static function after_items_actions_show_wallet_transfer_notice( $items, $wishlist, $items_args ) {
		if ( $items_args[ 'editable' ] && $wishlist->has_items() &&
			$wishlist->is_wallet_transfer_enabled() && $wishlist->has_fulfill_amount() ) :
			echo wp_kses( nmgr_get_svg( array(
				'icon' => 'heart',
				'class' => 'nmgr-tip',
				'fill' => '#999',
				'style' => 'vertical-align:sub;margin-right:7px',
				'size' => '1.5em',
				'title' => sprintf(
					/* translators: %s: wishlist type title */
					nmgrcf()->is_pro ? __( 'Congratulations, you have received enough money to fulfill all the items in  your %s. Contributions are now disabled for all crowdfunded items and maybe for normal items if applicable. However you can make wallet transfers if necessary.', 'nm-gift-registry-crowdfunding' ) : __( 'Congratulations, you have received enough money to fulfill all the items in  your %s. Contributions are now disabled for all crowdfunded items and maybe for normal items if applicable. However you can make wallet transfers if necessary.', 'nm-gift-registry-crowdfunding-lite' ),
					esc_html( nmgr_get_type_title() )
				),
				) ), nmgr_allowed_svg_tags() );
		endif;
	}

	public static function add_items_total_crowdfund_rows( $rows, $view ) {
		if ( empty( $view->get_args()[ 'editable' ] ) ) {
			return $rows;
		}

		function nmgrcf_item_totals_notice( $title ) {
			return nmgr_get_svg( array(
				'icon' => 'info',
				'class' => 'align-with-text nmgr-tip',
				'style' => 'margin-left:4px;',
				'size' => '.8',
				'title' => $title,
				'fill' => '#999',
				) );
		}

		$wishlist = $view->get_wishlist();

		if ( method_exists( $wishlist, 'has_crowdfunded_item' ) && $wishlist->has_crowdfunded_item() ) {
			$remove = [ 'amount_purchased', 'amount_needed', 'total' ];
			foreach ( $remove as $key ) {
				if ( isset( $rows[ $key ] ) ) {
					unset( $rows[ $key ] );
				}
			}

			$rows[ 'normal_amount_needed' ] = [
				'priority' => 10,
				'label' => (nmgrcf()->is_pro ?
				__( 'Normal amount still needed', 'nm-gift-registry-crowdfunding' ) :
				__( 'Normal amount still needed', 'nm-gift-registry-crowdfunding-lite' )) .
				nmgrcf_item_totals_notice( sprintf(
						/* translators: %s: wishlist type title */
						nmgrcf()->is_pro ? __( 'This is the amount still needed to completely fulfill all the normal (non-crowdfunded) items in the %s.', 'nm-gift-registry-crowdfunding' ) : __( 'This is the amount still needed to completely fulfill all the normal (non-crowdfunded) items in the %s.', 'nm-gift-registry-crowdfunding-lite' ),
						esc_html( nmgr_get_type_title() )
				) ) . ' :',
				'content' => wc_price( $wishlist->get_unpurchased_amount() ),
				'show' => $wishlist->has_normal_item(),
			];

			$rows[ 'crowdfund_amount_needed' ] = [
				'priority' => 20,
				'label' => (nmgrcf()->is_pro ?
				__( 'Crowdfund amount still needed', 'nm-gift-registry-crowdfunding' ) :
				__( 'Crowdfund amount still needed', 'nm-gift-registry-crowdfunding-lite' )) .
				nmgrcf_item_totals_notice( sprintf(
						/* translators: %s: wishlist type title */
						nmgrcf()->is_pro ? __( 'This is the amount still needed to completely fulfill all the crowdfunded items in the %s.', 'nm-gift-registry-crowdfunding' ) : __( 'This is the amount still needed to completely fulfill all the crowdfunded items in the %s.', 'nm-gift-registry-crowdfunding-lite' ),
						esc_html( nmgr_get_type_title() )
				) ) . ' :',
				'content' => wc_price( $wishlist->get_crowdfund_amount_left() ),
			];

			$rows[ 'total_amount_needed' ] = [
				'priority' => 30,
				'label' => (nmgrcf()->is_pro ?
				__( 'Total amount needed', 'nm-gift-registry-crowdfunding' ) :
				__( 'Total amount needed', 'nm-gift-registry-crowdfunding-lite' )) .
				nmgrcf_item_totals_notice( sprintf(
						/* translators: %s: wishlist type title */
						nmgrcf()->is_pro ? __( 'This is the amount left to be received for all the items in your %s to make it fulfilled.', 'nm-gift-registry-crowdfunding' ) : __( 'This is the amount left to be received for all the items in your %s to make it fulfilled.', 'nm-gift-registry-crowdfunding-lite' ),
						nmgr_get_type_title()
				) ) . ' :',
				'class' => [ 'nmgrcf-border-top' ],
				'content' => wc_price( $wishlist->get_total_unpurchased_amount() )
			];

			$rows[ 'total_amount_received' ] = [
				'priority' => 40,
				'label' => (nmgrcf()->is_pro ?
				__( 'Total amount received', 'nm-gift-registry-crowdfunding' ) :
				__( 'Total amount received', 'nm-gift-registry-crowdfunding-lite' )) .
				nmgrcf_item_totals_notice( sprintf(
						/* translators: %s: wishlist type title */
						nmgrcf()->is_pro ? __( 'This is the amount you have already received for all the items in your %s. It shows the progress of your campaign and helps determine its fulfillment.', 'nm-gift-registry-crowdfunding' ) : __( 'This is the amount you have already received for all the items in your %s. It shows the progress of your campaign and helps determine its fulfillment.', 'nm-gift-registry-crowdfunding-lite' ),
						esc_html( nmgr_get_type_title() )
				) ) . ' :',
				'class' => [ 'nmgr-grey' ],
				'content' => wc_price( $wishlist->get_total_purchased_amount() )
			];
		}

		$rows[ 'wallet_amount' ] = [
			'priority' => 50,
			'label' => (nmgrcf()->is_pro ?
			__( 'Amount in wallet', 'nm-gift-registry-crowdfunding' ) :
			__( 'Amount in wallet', 'nm-gift-registry-crowdfunding-lite' )) .
			nmgrcf_item_totals_notice( nmgrcf()->is_pro ?
				__( 'This is the amount you currently have in your wallet that can be used to fund items. It is not currently part of the money received for any item.', 'nm-gift-registry-crowdfunding' ) :
				__( 'This is the amount you currently have in your wallet that can be used to fund items. It is not currently part of the money received for any item.', 'nm-gift-registry-crowdfunding-lite' ) ) . ' :',
			'class' => [ 'nmgr-grey' ],
			'show' => method_exists( $wishlist, 'is_wallet_transfer_enabled' ) && $wishlist->is_wallet_transfer_enabled(),
			'content' => $wishlist->get_wallet() ? wc_price( $wishlist->get_wallet()->get_balance() ) : '',
		];

		if ( isset( $rows[ 'total' ] ) ) {
			$rows[ 'wallet_amount' ][ 'class' ][] = 'nmgrcf-border-top';
		}

		return $rows;
	}

	public static function add_item_actions( $actions, $view ) {
		$item = $view->get_item();

		$actions[ 'crowdfund' ] = [
			'text' => nmgrcf()->is_pro ?
			__( 'Toggle crowdfund status', 'nm-gift-registry-crowdfunding' ) :
			__( 'Toggle crowdfund status', 'nm-gift-registry-crowdfunding-lite' ),
			'priority' => 50,
			'attributes' => [
				'class' => [
					'crowfund-wishlist-item',
					'nmgr-post-action',
				],
				'href' => '#',
				'data-nmgr_post_action' => 'toggle_item_crowdfund',
				'data-wishlist_item_id' => $item ? $item->get_id() : 0,
			],
			'show' => method_exists( $item, 'is_crowdfunding_enabled' ) && $item->is_crowdfunding_enabled(),
			'show_in_bulk_actions' => is_nmgrcf_crowdfunding_enabled(),
		];


		if ( method_exists( $item, 'is_wallet_transfer_enabled' ) && $item->is_wallet_transfer_enabled() ) {
			$actions[ 'credit_wallet' ] = [
				'text' => nmgrcf()->is_pro ?
				__( 'Send received amount to wallet', 'nm-gift-registry-crowdfunding' ) :
				__( 'Send received amount to wallet', 'nm-gift-registry-crowdfunding-lite' ),
				'priority' => 60,
				'attributes' => [
					'class' => [
						'nmgr-credit-wallet',
						'nmgr-cf',
						'nmgrcf-post-action',
					],
					'href' => '#',
					'data-nmgr_post_action' => 'item_debit_credit_wallet_action',
					'data-context' => 'credit',
					'data-wishlist_item_id' => $item ? $item->get_id() : 0,
					'data-notice' => nmgrcf()->is_pro ?
					__( 'If you send the amount received for this item to the wallet, contributions or purchases would be disabled for the item and it can only be funded from the wallet. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding' ) :
					__( 'If you send the amount received for this item to the wallet, contributions or purchases would be disabled for the item and it can only be funded from the wallet. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding-lite' ),
				]
			];
			$actions[ 'debit_wallet' ] = [
				'text' => nmgrcf()->is_pro ?
				__( 'Fund from wallet', 'nm-gift-registry-crowdfunding' ) :
				__( 'Fund from wallet', 'nm-gift-registry-crowdfunding-lite' ),
				'priority' => 70,
				'attributes' => [
					'class' => [
						'nmgr-debit-wallet',
						'nmgr-cf',
						'nmgrcf-post-action',
					],
					'href' => '#',
					'data-nmgr_post_action' => 'item_debit_credit_wallet_action',
					'data-context' => 'debit',
					'data-wishlist_item_id' => $item ? $item->get_id() : 0,
					'data-notice' => nmgrcf()->is_pro ?
					__( 'Are you sure you want to fund this item from the wallet? Please note that contributions or purchases would be disabled for the item and it can only be funded from the wallet in the future.', 'nm-gift-registry-crowdfunding' ) :
					__( 'Are you sure you want to fund this item from the wallet? Please note that contributions or purchases would be disabled for the item and it can only be funded from the wallet in the future.', 'nm-gift-registry-crowdfunding-lite' ),
				]
			];
		}
		return $actions;
	}

	public static function add_crowdfund_column_to_add_items_dialog_in_admin( $columns ) {
		$text = nmgrcf()->is_pro ?
			__( 'Crowdfund', 'nm-gift-registry-crowdfunding' ) :
			__( 'Crowdfund', 'nm-gift-registry-crowdfunding-lite' );
		$opt_1 = nmgrcf()->is_pro ?
			__( 'No', 'nm-gift-registry-crowdfunding' ) :
			__( 'No', 'nm-gift-registry-crowdfunding-lite' );
		$opt_2 = nmgrcf()->is_pro ?
			__( 'Yes', 'nm-gift-registry-crowdfunding' ) :
			__( 'Yes', 'nm-gift-registry-crowdfunding-lite' );

		$columns[ $text ] = '<td data-title="' . $text . '"><select name="product_crowdfund"><option value="0">' . $opt_1 . '</option><option value="1">' . $opt_2 . '</option></td>';
		return $columns;
	}

	public static function get_crowdfund_add_to_cart_template( $args ) {
		if (
			'account/items/item-actions-add_to_cart.php' === $args[ 'template_name' ] &&
			isset( $args[ 'args' ][ 'item' ] )
		) {
			$nmgrcf_item = nmgrcf_get_item( $args[ 'args' ][ 'item' ] );
			$product = $nmgrcf_item->get_product();
			if (
				$nmgrcf_item->is_crowdfunded() &&
				!$nmgrcf_item->is_fulfilled() &&
				!$product->is_type( 'external' ) &&
				$product->is_in_stock() &&
				$product->is_purchasable()
			) {
				$args[ 'template_name' ] = 'add-to-cart.php';
				$args[ 'template_path' ] = 'nm-gift-registry-crowdfunding/';
				$args[ 'default_path' ] = nmgrcf()->path . 'templates/';
				$args[ 'args' ][ 'item' ] = $nmgrcf_item;
			}
		}
		return $args;
	}

	/**
	 * Show the wallet icon next to the item title as notification
	 * if the item can only be funded from the wallet
	 */
	public static function maybe_show_wallet_transfers_icon( $args ) {
		if ( $args[ 'item' ]->is_credited_to_wallet() ) {
			echo wp_kses( nmgr_get_svg( array(
				'icon' => 'credit-card-full',
				'style' => 'margin-left:5px',
				'class' => 'align-with-text nmgr-tip',
				'title' => nmgrcf()->is_pro ?
					__( 'This item can only be funded from the wallet. Checkout purchases are disabled for it.', 'nm-gift-registry-crowdfunding' ) :
					__( 'This item can only be funded from the wallet. Checkout purchases are disabled for it.', 'nm-gift-registry-crowdfunding-lite' ),
				'fill' => '#aaa',
				) ), nmgr_allowed_svg_tags() );
		}
	}

	/**
	 * Show purchase disabled text if purchases for the item are disabled
	 *
	 * This function is specifically for normal wishlist items due to the
	 * hook 'nmgr_item_before_add_to_cart_form' as crowdfunded items have
	 * their own add-to-cart template which uses a separate hook.
	 */
	public static function maybe_show_purchase_disabled_text( $item ) {
		if ( $item->is_purchase_disabled() ) {
			echo wp_kses(
				nmgrcf_purchase_disabled_html(),
				array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() )
			);
		}
	}

	/**
	 * Hide elements on the items table if purchases are disabled for any item
	 *
	 * This should hide the add to cart form for normal items if purchase disabled
	 * as well as the checkbox for them.
	 */
	public static function hide_elements_if_purchase_disabled( $items ) {
		foreach ( $items as $item ) {
			if ( $item->is_purchase_disabled() ) {
				?>
				<style>
					#nmgr-items.woocommerce.single tr.item.purchase-disabled input.nmgr-select,
					#nmgr-items tr.item.purchase-disabled .nmgr-add-to-cart-form>* {
						display: none;
					}
				</style>
				<?php
				break;
			}
		}
	}

}
