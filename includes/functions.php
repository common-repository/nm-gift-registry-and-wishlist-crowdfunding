<?php
/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

function nmgrcf_get_item( $item_id ) {
	try {
		$item = new NMGRCF_Item( $item_id );
		return $item;
	} catch ( Exception $e ) {
		return false;
	}
}

function nmgrcf_get_wishlist( $wishlist_id = 0, $active = false ) {
	$wishlist_id = $wishlist_id ? $wishlist_id : nmgr_get_current_wishlist_id();

	if ( !$wishlist_id ) {
		return false;
	}

	try {
		$wishlist = new NMGRCF_Wishlist( $wishlist_id );
		return $active ? ($wishlist->is_active() ? $wishlist : false) : $wishlist;
	} catch ( Exception $e ) {
		return false;
	}
}

function nmgrcf_price_box( $args = array() ) {
	$defaults = array(
		'name' => 'nmgr-cf-price',
		'min' => 0,
		'max' => '',
		'title' => nmgrcf()->is_pro ?
		__( 'Amount', 'nm-gift-registry-crowdfunding' ) :
		__( 'Amount', 'nm-gift-registry-crowdfunding-lite' ),
		'id' => '',
		'value' => '',
		'currency-symbol-border' => false,
	);

	$params = wp_parse_args( $args, $defaults );
	?>
	<div class="nmgrcf-price-box">
		<span class="currency-symbol <?php echo $params[ 'currency-symbol-border' ] ? 'border' : ''; ?>">
			<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
		</span>
		<input type="number" step="any" class="price" name="<?php echo esc_attr( $params[ 'name' ] ); ?>"
		<?php
		if ( $params[ 'id' ] ) {
			echo 'id="' . esc_attr( $params[ 'id' ] ) . '"';
		}
		?>
					 min="<?php echo esc_attr( $params[ 'min' ] ); ?>"
					 max="<?php echo esc_attr( $params[ 'max' ] ); ?>"
					 max="<?php echo esc_attr( $params[ 'max' ] ); ?>"
					 value="<?php echo esc_attr( $params[ 'value' ] ); ?>"
					 title="<?php echo esc_attr( $params[ 'title' ] ); ?>">
	</div>
	<?php
}

/**
 * Check if the specified coupon is for the wallet amount
 *
 * @param int|WC_Coupon $coupon_id The coupon id or object
 * @return boolean
 */
function nmgrcf_coupon_is_for_wallet( $coupon_id ) {
	$coupon = new WC_Coupon( $coupon_id );
	return $coupon->get_id() && !empty( $coupon->get_meta( 'nmgrcf_coupon_from_wallet' ) ) ? true : false;
}

/**
 * Remove an amount from the crowdfund wallet
 *
 * @param int|float $amount The amount to remove
 * @param int|NMGR_wishlist $wishlist_id The id or object of the wishlist the wallet belongs to
 * @return void
 */
function nmgrcf_wallet_debit_amount( $amount, $wishlist_id ) {
	$wishlist = nmgr_get_wishlist( $wishlist_id );
	if ( $wishlist ) {
		$wallet_amt = get_post_meta( $wishlist->get_id(), 'nmgrcf_wallet', true );
		update_post_meta( $wishlist->get_id(), 'nmgrcf_wallet', ($wallet_amt - $amount ) );
	}
}

/**
 * Add an amount to the crowdfund wallet
 *
 * @param int|float $amount The amount to add
 * @param int|NMGR_wishlist $wishlist_id The id or object of the wishlist the wallet belongs to
 * @return void
 */
function nmgrcf_wallet_credit_amount( $amount, $wishlist_id ) {
	$wishlist = nmgr_get_wishlist( $wishlist_id );
	if ( $wishlist ) {
		$wallet_amt = get_post_meta( $wishlist->get_id(), 'nmgrcf_wallet', true );
		update_post_meta( $wishlist->get_id(), 'nmgrcf_wallet', ($wallet_amt + $amount ) );
	}
}

function nmgrcf_round( $amt ) {
	return round( $amt, get_option( 'woocommerce_price_num_decimals', 0 ) );
}

/**
 * Check whether the cart has a crowdfund contribution for a wishlist item
 * @return boolean|int false if it doesn't and the id of the wishlist if it does.
 */
function nmgrcf_cart_has_crowdfund_contribution() {
	if ( is_a( wc()->cart, 'WC_Cart' ) && !WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
			if ( $nmgr_cf_data && nmgr_get_wishlist( $nmgr_cf_data[ 'wishlist_id' ], true ) ) {
				return ( int ) $nmgr_cf_data[ 'wishlist_id' ];
			}
		}
	}
	return false;
}

/**
 * Check whether the cart has a free contribution for a wishlist
 * @return boolean|int false if it doesn't and the id of the wishlist if it does.
 */
function nmgrcf_cart_has_free_contribution() {
	if ( is_a( wc()->cart, 'WC_Cart' ) && !WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
			if ( $nmgr_fc_data && nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ], true ) ) {
				return ( int ) $nmgr_fc_data[ 'wishlist_id' ];
			}
		}
	}
	return false;
}

function nmgrcf_get_template( $name, $args = array() ) {
	$args[ 'is_crowdfund_template' ] = true;
	return nmgr_get_template( $name, $args );
}

/**
 * Get the name of the product used to show the crowdfund contribution in the cart
 * @param WC_Cart Item data $cart_item Array of cart item properties
 */
function nmgrcf_get_crowdfund_cart_item_name( $cart_item ) {
	$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
	if ( !empty( $nmgr_cf_data[ 'product_data' ] ) ) {
		return $nmgr_cf_data[ 'product_data' ][ 'name' ] . ' - ' . apply_filters( 'nmgrcf_product_name_description',
				nmgrcf()->is_pro ?
				__( 'Crowdfund Contribution', 'nm-gift-registry-crowdfunding' ) :
				__( 'Crowdfund Contribution', 'nm-gift-registry-crowdfunding-lite' )
		);
	}
}

function nmgrcf_get_free_contributions_settings_dialog_template( $wishlist_id ) {
	if ( !is_admin() && 0 < $wishlist_id && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wishlist = nmgrcf_get_wishlist( $wishlist_id, true );

	if ( !$wishlist ) {
		return;
	}

	$vars = array(
		'settings' => $wishlist->get_free_contributions_settings(),
		'wishlist' => $wishlist,
	);

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-free-contributions-settings-dialog-' . $wishlist_id );
	$modal->set_title( nmgrcf()->is_pro ?
			__( 'Free contributions settings', 'nm-gift-registry-crowdfunding' ) :
			__( 'Free contributions settings', 'nm-gift-registry-crowdfunding-lite' )  );
	$modal->set_content( nmgrcf_get_template( 'dialogs/free-contributions-settings.php', $vars ) );
	$modal->set_footer( $modal->get_save_button( [
			'attributes' => [
				'type' => 'submit',
				'class' => [
					'button-primary',
					'nmgrcf-free-contributions-settings-submit',
				],
				'form' => 'nmgrcf-fc-settings-form'
			]
	] ) );
	return $modal->get();
}

/**
 * Get the text to display in the submit button for crowdfunding an item
 * @return string
 */
function nmgrcf_get_crowdfund_item_button_text() {
	return apply_filters( 'nmgrcf_crowdfund_item_button_text',
		nmgrcf()->is_pro ?
		__( 'Contribute', 'nm-gift-registry-crowdfunding' ) :
		__( 'Contribute', 'nm-gift-registry-crowdfunding-lite' )
	);
}

/**
 * Get the id of the placeholder product used by the plugin for various add to cart actions
 * (Creates the placeholder product if it doesn't exist)
 *
 * @return int|null
 */
function nmgrcf_get_placeholder_product_id() {
	$placeholder_product_id = get_option( 'nmgrcf_product_id' );

	if ( !wc_get_product( $placeholder_product_id ) ) {
		$p = new WC_Product();
		$p->set_name( nmgrcf()->is_pro ?
				__( 'Contribution', 'nm-gift-registry-crowdfunding' ) :
				__( 'Contribution', 'nm-gift-registry-crowdfunding-lite' )
		);
		$p->set_status( 'nmgr-crowdfunded' );
		$p->set_virtual( true );
		$p->set_catalog_visibility( 'hidden' );
		$p->set_sold_individually( true );
		$p->set_regular_price( 0 );
		$p->set_tax_status( 'none' );
		$placeholder_product_id = $p->save();

		if ( $placeholder_product_id ) {
			update_option( 'nmgrcf_product_id', $placeholder_product_id );
		}
	}

	return $placeholder_product_id;
}

/**
 * Get the name of the product used to show the crowdfund contribution in the cart
 * @param WC_Cart Item data $cart_item Array of cart item properties
 */
function nmgrcf_get_free_contribution_cart_item_name( $cart_item ) {
	$nmgr_fc_data = nmgr_get_cart_item_data( $cart_item, 'free_contribution' );
	if ( $nmgr_fc_data && nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ] ) ) {
		return apply_filters( 'nmgrcf_free_contribution_cart_item_name',
			sprintf(
				/* translators: %s: wishlist title */
				nmgrcf()->is_pro ? __( 'Free Contribution - %s', 'nm-gift-registry-crowdfunding' ) : __( 'Free Contribution - %s', 'nm-gift-registry-crowdfunding-lite' ),
				nmgr_get_wishlist( $nmgr_fc_data[ 'wishlist_id' ] )->get_title()
			),
			$cart_item
		);
	}
}

if ( !function_exists( 'nmgrcf_get_free_contributions_template' ) ) {

	/**
	 * Template for displaying free contributions to a wishlist
	 *
	 * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
	 * Currently accepted $atts attributes if array:
	 * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
	 *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
	 * - title [string] The title header to use for the template. Default none.
	 *
	 * @param boolean $echo Whether to echo the template. Default false.
	 *
	 * @return string Template html
	 */
	function nmgrcf_get_free_contributions_template( $atts = '', $echo = false ) {
		return nmgr_get_account_section( 'free_contributions', $atts, $echo );
	}

}

function nmgrcf_get_free_contributions_account_section( $default_vars ) {
	$wishlist = $default_vars[ 'wishlist' ];

	$vars = array_merge( $default_vars, array(
		'columns' => apply_filters( 'nmgrcf_free_contributions_table_columns', array(
			'contributor' => nmgrcf()->is_pro ?
			__( 'Contributor', 'nm-gift-registry-crowdfunding' ) :
			__( 'Contributor', 'nm-gift-registry-crowdfunding-lite' ),
			'order' => nmgrcf()->is_pro ?
			__( 'Order', 'nm-gift-registry-crowdfunding' ) :
			__( 'Order', 'nm-gift-registry-crowdfunding-lite' ),
			'date-contributed' => nmgrcf()->is_pro ?
			__( 'Date contributed', 'nm-gift-registry-crowdfunding' ) :
			__( 'Date contributed', 'nm-gift-registry-crowdfunding-lite' ),
			'amount' => nmgrcf()->is_pro ?
			__( 'Amount', 'nm-gift-registry-crowdfunding' ) :
			__( 'Amount', 'nm-gift-registry-crowdfunding-lite' ),
		) ),
		'contributions' => array(),
		) );

	if ( !is_nmgr_admin() ) {
		unset( $vars[ 'columns' ][ 'order' ] );
	}

	$reference = $wishlist ? $wishlist->get_free_contributions_reference() : '';
	if ( !empty( $reference ) ) {
		foreach ( $reference as $order_id => $ref ) {
			if ( is_array( $ref ) && isset( $ref[ 'purchased_amount' ] ) && 0 < ( int ) $ref[ 'purchased_amount' ] ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$vars[ 'contributions' ][] = array(
						'order' => $order,
						'amount' => $ref[ 'purchased_amount' ],
					);
				}
			}
		}
	}

	$template_1 = apply_filters( 'nmgr_free_contributions_template',
		nmgrcf_get_template( 'account-free-contributions.php', $vars ),
		$vars
	);
	$template = apply_filters_deprecated( 'nmgrcf_free_contributions_template',
		[ $template_1, $vars ],
		'3.0.0',
		'nmgr_free_contributions_template'
	);

	return $template;
}

function nmgrcf_get_wallet_template( $atts = '', $echo = false ) {
	return nmgr_get_account_section( 'wallet', $atts, $echo );
}

function nmgrcf_get_wallet_account_section( $vars ) {
	$wishlist = $vars[ 'wishlist' ];
	if ( !method_exists( $wishlist, 'get_wallet' ) ) {
		return;
	}

	ob_start();
	?>
	<div <?php echo nmgr_format_attributes( $vars[ 'attributes' ] ?? []  ); ?>>
		<div class="nmgr-text-center nmgrcf-amount-in-wallet-display">
			<?php echo wp_kses_post( wc_price( $wishlist->get_wallet()->get_balance() ) ); ?>
		</div>
		<div class="nmgrcf-wallet-actions nmgr-text-center">
			<?php if ( is_nmgr_admin_request() ) : ?>
				<p>
					<?php
					echo wp_kses_post( nmgrcf_get_view_wallet_log_button( $wishlist->get_id() ) );
					echo wp_kses_post( nmgrcf_get_create_coupon_button( $wishlist->get_id(), true ) );
					echo wp_kses_post( nmgrcf_get_reset_wallet_button( $wishlist->get_id() ) );
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_free_contributions_credit_wallet_dialog_template( $wishlist_id ) {
	if ( !is_admin() && 0 < $wishlist_id && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wishlist = nmgrcf_get_wishlist( $wishlist_id, true );

	if ( !$wishlist ) {
		return;
	}

	$vars = array(
		'settings' => $wishlist->get_free_contributions_settings(),
		'wishlist' => $wishlist
	);

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-free-contributions-credit-wallet-dialog-' . $wishlist_id );
	$modal->set_title( nmgrcf()->is_pro ?
			__( 'Send free contributions to wallet', 'nm-gift-registry-crowdfunding' ) :
			__( 'Send free contributions to wallet', 'nm-gift-registry-crowdfunding-lite' )  );
	$modal->set_content( nmgrcf_get_template( 'dialogs/free-contributions-credit-wallet.php', $vars ) );
	$modal->set_footer( $modal->get_save_button( [
			'text' => nmgrcf()->is_pro ?
				__( 'Send', 'nm-gift-registry-crowdfunding' ) :
				__( 'Send', 'nm-gift-registry-crowdfunding-lite' ),
			'attributes' => [
				'type' => 'submit',
				'class' => [
					'button-primary',
					'nmgrcf-free-contributions-credit-wallet-submit',
				],
				'form' => 'nmgrcf-fc-credit-wallet-form',
				'data-notice' => nmgrcf()->is_pro ?
					__( 'Are you sure you want to credit the wallet with the amount?', 'nm-gift-registry-crowdfunding' ) :
					__( 'Are you sure you want to credit the wallet with the amount?', 'nm-gift-registry-crowdfunding-lite' ),
			]
	] ) );
	return $modal->get();
}

function nmgrcf_get_view_wallet_dialog_template( $wishlist_id ) {
	if ( !is_admin() && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wishlist = nmgrcf_get_wishlist( $wishlist_id, true );

	if ( !$wishlist ) {
		return;
	}

	$content = nmgrcf_get_wallet_template( $wishlist->get_id() );

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-wallet-dialog-' . $wishlist_id );
	$modal->set_title( nmgrcf()->is_pro ?
			__( 'Amount in wallet', 'nm-gift-registry-crowdfunding' ) :
			__( 'Amount in wallet', 'nm-gift-registry-crowdfunding-lite' )  );
	$modal->set_content( $content );
	return $modal->get();
}

function nmgrcf_get_wallet_log_dialog_template( $wishlist_id ) {
	if ( !is_admin() && !nmgr_user_has_wishlist( $wishlist_id ) ) {
		return;
	}

	$wallet = new NMGRCF_Wallet( $wishlist_id );

	if ( !$wallet->get_wishlist() ) {
		return;
	}

	$wallet_log = nmgrcf_get_template( 'wallet-log.php', array(
		'wishlist' => nmgr_get_wishlist( $wishlist_id ),
		'wallet' => $wallet,
		'log' => $wallet->get_log(),
		'class' => 'woocommerce',
		'columns' => array(
			'id' => nmgrcf()->is_pro ?
			__( 'ID', 'nm-gift-registry-crowdfunding' ) :
			__( 'ID', 'nm-gift-registry-crowdfunding-lite' ),
			'amount' => nmgrcf()->is_pro ?
			__( 'Amount', 'nm-gift-registry-crowdfunding' ) :
			__( 'Amount', 'nm-gift-registry-crowdfunding-lite' ),
			'type' => nmgrcf()->is_pro ?
			__( 'Transaction type', 'nm-gift-registry-crowdfunding' ) :
			__( 'Transaction type', 'nm-gift-registry-crowdfunding-lite' ),
			'descriptor' => nmgrcf()->is_pro ?
			__( 'Description', 'nm-gift-registry-crowdfunding' ) :
			__( 'Description', 'nm-gift-registry-crowdfunding-lite' ),
			'date' => nmgrcf()->is_pro ?
			__( 'Date', 'nm-gift-registry-crowdfunding' ) :
			__( 'Date', 'nm-gift-registry-crowdfunding-lite' ),
		),
		) );

	$modal = nmgr_get_modal();
	$modal->set_id( 'nmgr-wallet-log-dialog-' . $wishlist_id );
	$modal->set_title( nmgrcf()->is_pro ?
			__( 'Wallet log', 'nm-gift-registry-crowdfunding' ) :
			__( 'Wallet log', 'nm-gift-registry-crowdfunding-lite' )  );
	$modal->set_content( $wallet_log );
	$modal->make_large();
	return $modal->get();
}

/**
 * Is the crowdfunding module enabled
 * @return boolean
 */
function is_nmgrcf_crowdfunding_enabled() {
	return ( bool ) apply_filters( 'is_nmgrcf_crowdfunding_enabled', nmgr_get_option( 'enable_crowdfunding', 1 ) );
}

/**
 * Is the free contributions module enabled
 * @return boolean
 */
function is_nmgrcf_free_contributions_module_enabled() {
	return class_exists( 'NMGRCF_Templates_Free_Contribution' );
}

/**
 * Is the free contributions enabled
 * @return boolean
 */
function is_nmgrcf_free_contributions_enabled() {
	if ( is_nmgrcf_free_contributions_module_enabled() ) {
		return ( bool ) apply_filters( 'is_nmgrcf_free_contributions_enabled', nmgr_get_option( 'enable_free_contributions', 1 ) );
	}
	return false;
}

/**
 * Is the coupons module enabled
 * @return boolean
 */
function is_nmgrcf_coupons_enabled() {
	if ( wc_coupons_enabled() && class_exists( 'NMGRCF_Coupon' ) ) {
		return ( bool ) apply_filters( 'is_nmgrcf_coupons_enabled', true );
	}
	return false;
}

/**
 * Is the wallet module enabled
 * @return boolean
 */
function is_nmgrcf_wallet_enabled() {
	if ( class_exists( 'NMGRCF_Wallet' ) ) {
		return ( bool ) apply_filters( 'is_nmgrcf_wallet_enabled', true );
	}
	return false;
}

function nmgrcf_get_view_wallet_log_button( $wishlist_id ) {
	ob_start();
	?>
	<button type="button"
					class="button nmgrcf-post-action nmgrcf-view-wallet-log-btn"
					data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>"
					data-nmgr_post_action="show_wallet_log_dialog">
						<?php
						echo esc_html( nmgrcf()->is_pro ?
								__( 'View log', 'nm-gift-registry-crowdfunding' ) :
								__( 'View log', 'nm-gift-registry-crowdfunding-lite' )
						);
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_view_wallet_button( $wishlist_id ) {
	ob_start();
	?>
	<button type="button"
					class="button nmgrcf-view-wallet-btn nmgrcf-post-action"
					data-nmgr_post_action="show_view_wallet_dialog"
					data-wishlist-id="<?php echo esc_attr( $wishlist_id ); ?>">
						<?php
						echo esc_html( nmgrcf()->is_pro ?
								__( 'View wallet', 'nm-gift-registry-crowdfunding' ) :
								__( 'View wallet', 'nm-gift-registry-crowdfunding-lite' )
						);
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_reset_wallet_button( $wishlist_id ) {
	ob_start();

	$wishlist = nmgrcf_get_wishlist( $wishlist_id );

	if ( !$wishlist || $wishlist->get_wallet()->has_zero_balance() ) {
		$disabled = 'disabled';
	}

	$block = [ '#nmgr-wallet' ];
	$acc = new NMGR_Account( $wishlist );
	$section = $acc->get_section_data( 'wallet' );
	if ( !empty( $section[ 'replace_on_load' ] ) ) {
		foreach ( $section[ 'replace_on_load' ] as $rep ) {
			$block[] = "#nmgr-$rep";
		}
	}
	?>
	<button type="button"
					class="button nmgrcf-post-action nmgrcf-reset-wallet-btn"
					data-wishlist_id="<?php echo esc_attr( $wishlist_id ); ?>"
					data-nmgr_post_action="reset_wallet_action"
					data-nmgr_block="<?php echo htmlspecialchars( json_encode( $block ) ); ?>"
					data-notice="<?php
					echo esc_attr( nmgrcf()->is_pro ?
							__( 'Resetting the wallet would set the amount in the wallet to zero. Any amount previously available would be lost. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding' ) :
							__( 'Resetting the wallet would set the amount in the wallet to zero. Any amount previously available would be lost. Are you sure you want to continue?', 'nm-gift-registry-crowdfunding-lite' )
					);
					?>"
					<?php echo isset( $disabled ) ? esc_attr( $disabled ) : ''; ?>>
						<?php
						echo esc_html( nmgrcf()->is_pro ?
								__( 'Reset wallet', 'nm-gift-registry-crowdfunding' ) :
								__( 'Reset wallet', 'nm-gift-registry-crowdfunding-lite' )
						);
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_get_create_coupon_button( $wishlist_id, $coupon_from_wallet = false ) {
	if ( !is_nmgrcf_coupons_enabled() ) {
		return;
	}

	ob_start();

	$wishlist = nmgrcf_get_wishlist( $wishlist_id );

	$title = nmgrcf()->is_pro ?
		__( 'Create a coupon to offer discounts on the remaining wishlist items which have not yet been fulfilled.', 'nm-gift-registry-crowdfunding' ) :
		__( 'Create a coupon to offer discounts on the remaining wishlist items which have not yet been fulfilled.', 'nm-gift-registry-crowdfunding-lite' );

	if ( $coupon_from_wallet ) {
		$title = nmgrcf()->is_pro ?
			__( 'Create a coupon from the amount in the wallet that can be used on normal wishlist items.', 'nm-gift-registry-crowdfunding' ) :
			__( 'Create a coupon from the amount in the wallet that can be used on normal wishlist items.', 'nm-gift-registry-crowdfunding-lite' );
	}

	if ( !$wishlist || !$wishlist->has_items() || $wishlist->is_fulfilled() ||
		($coupon_from_wallet && $wishlist->get_wallet() && !$wishlist->get_wallet()->has_positive_balance()) ) {
		$disabled = 'disabled';
	}
	?>
	<button type="button"
					class="button nmgrcf-create-coupon-btn nmgrcf-post-action nmgr-tip"
					data-wishlist_id="<?php echo esc_attr( $wishlist->get_id() ); ?>"
					data-nmgr_post_action="show_create_coupon_dialog"
					<?php echo $coupon_from_wallet ? 'data-coupon_from_wallet="1"' : ''; ?>
					title="<?php echo esc_attr( $title ); ?>"
					<?php echo isset( $disabled ) ? esc_attr( $disabled ) : ''; ?>>
						<?php
						echo esc_html(
							nmgrcf()->is_pro ?
								__( 'Create coupon', 'nm-gift-registry-crowdfunding' ) :
								__( 'Create coupon', 'nm-gift-registry-crowdfunding-lite' )
						);
						?>
	</button>
	<?php
	return ob_get_clean();
}

function nmgrcf_purchase_disabled_html() {
	$html = '<p class="nmgrcf-purchase-disabled-text">' . esc_html( nmgrcf()->is_pro ?
			__( 'Purchase disabled', 'nm-gift-registry-crowdfunding' ) :
			__( 'Purchase disabled', 'nm-gift-registry-crowdfunding-lite' )
		) . '</p>';
	return apply_filters( 'nmgrcf_purchase_disabled_html', $html );
}

function nmgrcf_get_item_maintain_crowdfund_status_notice( $item ) {
	$maintain_crowdfund_status_notice = '';

	if ( is_a( $item, 'NMGRCF_Item' ) && $item->maintain_crowdfund_status() ) {
		if ( $item->is_fulfilled() ) {
			$maintain_crowdfund_status_notice = nmgrcf()->is_pro ?
				__( 'This item is already fulfilled so there is no need to change its crowdfund status.', 'nm-gift-registry-crowdfunding' ) :
				__( 'This item is already fulfilled so there is no need to change its crowdfund status.', 'nm-gift-registry-crowdfunding-lite' );
		} elseif ( $item->is_purchased() ) {
			$maintain_crowdfund_status_notice = nmgrcf()->is_pro ?
				__( 'This item already has purchases and so cannot be crowdfunded.', 'nm-gift-registry-crowdfunding' ) :
				__( 'This item already has purchases and so cannot be crowdfunded.', 'nm-gift-registry-crowdfunding-lite' );
		} elseif ( $item->get_crowdfund_amount_available() ) {
			$maintain_crowdfund_status_notice = nmgrcf()->is_pro ?
				__( 'This item already has crowdfund contributions and so cannot be uncrowdfunded.', 'nm-gift-registry-crowdfunding' ) :
				__( 'This item already has crowdfund contributions and so cannot be uncrowdfunded.', 'nm-gift-registry-crowdfunding-lite' );
		}
	}

	return $maintain_crowdfund_status_notice;
}
