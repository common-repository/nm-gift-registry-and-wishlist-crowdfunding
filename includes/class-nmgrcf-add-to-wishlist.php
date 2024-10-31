<?php
/**
 * Actions related to making a product crowdfunded when adding it to a wishlist
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Add_To_Wishlist {

	public static function run() {
		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}

		add_action( 'nmgr_add_to_wishlist_option_row_end', [ __CLASS__, 'add_crowdfunding_option_to_add_to_wishlist' ], 10, 2 );
		add_filter( 'nmgr_add_to_wishlist_dialog_content_args', [ __CLASS__, 'add_crowdfund_wishlist_item_options' ] );
		add_action( 'nmgr_add_to_wishlist_dialog_content_after_options', [ __CLASS__, 'show_crowdfunding_options' ] );
		add_action( 'nmgr_data_after_save', [ __CLASS__, 'save_crowdfunding_prop_to_wishlist_item' ] );
	}

	public static function add_crowdfunding_option_to_add_to_wishlist( $product, $args ) {
		?>
		<div class="crowdfunding">
			<?php
			$field_id = "nmgr_cf[{$product->get_id()}]";
			$data_title_off = nmgrcf()->is_pro ?
				__( 'Mark as a crowdfunded item', 'nm-gift-registry-crowdfunding' ) :
				__( 'Mark as a crowdfunded item', 'nm-gift-registry-crowdfunding-lite' );
			$data_title_on = sprintf(
				/* translators: %s: wishlist type title */
				nmgrcf()->is_pro ? __( 'This item is crowdfunded in this %s', 'nm-gift-registry-crowdfunding' ) : __( 'This item is crowdfunded in this %s', 'nm-gift-registry-crowdfunding-lite' ),
				nmgr_get_type_title()
			);
			$data_has_contributions_text = nmgrcf()->is_pro ?
				__( 'This item already has crowdfund contributions and so cannot be uncrowdfunded.', 'nm-gift-registry-crowdfunding' ) :
				__( 'This item already has crowdfund contributions and so cannot be uncrowdfunded.', 'nm-gift-registry-crowdfunding-lite' );
			?>
			<div class="nmgr-btn-group">
				<div class="nmgr-btn">
					<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" value="1" class="atw-cf-enable disabled"
								 data-options="<?php echo esc_attr( "atw-cf-options-{$product->get_id()}" ); ?>"
								 name="<?php echo esc_attr( $field_id ); ?>"
								 <?php
								 if ( isset( $args[ 'wishlist_item_options' ][ 'nmgr_cf' ][ $product->get_id() ] ) ) {
									 echo wp_kses( $args[ 'wishlist_item_options' ][ 'nmgr_cf' ][ $product->get_id() ], [] );
								 }
								 ?>>
					<label for="<?php echo esc_attr( $field_id ); ?>" class="icon"
								 data-title-on="<?php echo esc_attr( $data_title_on ); ?>"
								 data-title-off="<?php echo esc_attr( $data_title_off ); ?>"
								 data-has-contribution-text="<?php echo esc_attr( $data_has_contributions_text ); ?>"
								 title="<?php echo esc_attr( $data_title_off ); ?>">
									 <?php
									 echo wp_kses( nmgr_get_svg( array(
										 'icon' => 'users',
										 'sprite' => false,
										 'path' => nmgrcf()->path . 'assets/svg/',
										 'size' => 1.5,
										 'class' => 'align-with-text',
										 'fill' => '#ddd',
										 ) ), nmgr_allowed_svg_tags() );
									 ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	public static function add_crowdfund_wishlist_item_options( $args ) {
		$in_wishlist_options = array();
		$products = $args[ 'product' ]->is_type( 'grouped' ) ? $args[ 'grouped_products' ] : array( $args[ 'product' ] );

		foreach ( $products as $product ) {
			foreach ( $args[ 'wishlists' ] as $wishlist ) {
				$item = $wishlist->get_item_by_product( $product );
				$nmgrcf_item = nmgrcf_get_item( $item );
				$is_crowdfunded = $nmgrcf_item ? $nmgrcf_item->is_crowdfunded() : '';
				$is_purchased = $nmgrcf_item ? ($nmgrcf_item->get_crowdfund_amount_available() ? 1 : '') : '';
				$in_wishlist_options[ 'nmgr_cf' ][ $product->get_id() ][] = 'data-in-wishlist-' . ( int ) $wishlist->get_id() . '="' . ( bool ) $is_crowdfunded . '"';
				$in_wishlist_options[ 'nmgr_cf' ][ $product->get_id() ][] = 'data-purchased-' . ( int ) $wishlist->get_id() . '="' . ( bool ) $is_purchased . '"';
			}
		}

		foreach ( $in_wishlist_options as $key => $product_options ) {
			foreach ( $product_options as $product_id => $raw_options ) {
				$args[ 'wishlist_item_options' ][ $key ][ $product_id ] = implode( ' ', $raw_options );
			}
		}

		return $args;
	}

	public static function show_crowdfunding_options( $args ) {
		$products = array( $args[ 'product' ] );
		if ( isset( $args[ 'grouped_products' ] ) && !empty( $args[ 'grouped_products' ] ) ) {
			$products = $args[ 'grouped_products' ];
		}

		foreach ( $products as $product ) {
			$input_id = "nmgr_cf_min_amount[{$product->get_id()}]";
			?>
			<div class="atw-crowdfunding atw-cf-options-<?php echo esc_attr( $product->get_id() ); ?> nmgr-hide">
				<?php
				nmgrcf_price_box( array(
					'title' => nmgrcf()->is_pro ?
						__( 'Minimum amount that should be contributed to this item.', 'nm-gift-registry-crowdfunding' ) :
						__( 'Minimum amount that should be contributed to this item.', 'nm-gift-registry-crowdfunding-lite' ),
					'name' => $input_id,
					'id' => $input_id,
				) );
				?>
				<label for="<?php echo esc_attr( $input_id ); ?>" class="min-amount-text">
					<?php
					echo esc_html( nmgrcf()->is_pro ?
							__( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding' ) :
							__( 'Minimum contribution (optional)', 'nm-gift-registry-crowdfunding-lite' )
					);
					?>
				</label>
			</div>
			<?php
		}
	}

	/**
	 * Determine whether a wishlist item should be crowdfunded after
	 * it has been added to the wishlist
	 */
	public static function save_crowdfunding_prop_to_wishlist_item( $item ) {
		if ( 'wishlist_item' !== $item->get_object_type() ) {
			return;
		}

		$nmgrcf_item = nmgrcf_get_item( $item );

		if ( $nmgrcf_item->maintain_crowdfund_status() ) {
			return;
		}

		$product_id = absint( filter_input( INPUT_POST, 'nmgr_pid', FILTER_SANITIZE_NUMBER_INT ) );
		$wishlist_id = absint( filter_input( INPUT_POST, 'nmgr_wid', FILTER_SANITIZE_NUMBER_INT ) );

		/**
		 *  This ensures we are in the add to wishlist action in the frontend on shop, product, category pages, e.t.c.
		 */
		if ( $product_id && $wishlist_id ) {
			/**
			 * Because we want to manage both grouped products and single products automatically.
			 * we make sure every variable we need is an array.
			 * (We're expecting $_REQUEST['nmgr_cf'] to be an array so not need to convert this to array).
			 */
			$qty = [ $product_id => 1 ];

			// phpcs:disable WordPress.Security.NonceVerification
			if ( isset( $_REQUEST[ 'nmgr_qty' ] ) ) {
				$qty = is_array( $_REQUEST[ 'nmgr_qty' ] ) ?
					array_map( 'absint', $_REQUEST[ 'nmgr_qty' ] ) :
					[ $product_id => absint( $_REQUEST[ 'nmgr_qty' ] ) ];
			}

			$cf_item = sanitize_text_field( $_REQUEST[ 'nmgr_cf' ][ $nmgrcf_item->get_product_id() ] ?? null );
			// phpcs:enable

			if ( $cf_item && !$nmgrcf_item->is_fulfilled() ) {
				$crowdfund_data = array();
				// phpcs:ignore WordPress.Security.NonceVerification
				$cf_amt = ( float ) $_REQUEST[ 'nmgr_cf_min_amount' ][ $nmgrcf_item->get_product_id() ] ?? null;

				if ( $cf_amt && 0 < $cf_amt ) {
					$crowdfund_data[ 'min_amount' ] = $cf_amt;
				}

				$nmgrcf_item->make_crowdfunded( $crowdfund_data );
			} elseif ( !$cf_item &&
				isset( $qty[ $nmgrcf_item->get_product_id() ] ) && !$nmgrcf_item->is_fulfilled() &&
				$nmgrcf_item->is_crowdfunded() && !$nmgrcf_item->has_crowdfund_contributions() ) {
				$nmgrcf_item->unmake_crowdfunded();
			}
		} elseif ( filter_input( INPUT_POST, 'items' ) ) {
			/**
			 * In this case we are saving items in the wishlist items table
			 * In this situation, a wishlist item being saved has been made or unmade crowdfunded so we have to
			 * update its crowdfund status
			 */
			$posted_items = ( string ) wp_unslash( filter_input( INPUT_POST, 'items', FILTER_SANITIZE_STRING ) );
			$items = array();
			parse_str( $posted_items, $items );

			if ( isset( $items[ 'wishlist_item_crowdfunded' ][ $nmgrcf_item->get_id() ] ) ) {
				$crowdfund_data = array();

				if ( isset( $items[ 'nmgrcf_crowdfund_data' ][ 'min_amount' ][ $nmgrcf_item->get_id() ] ) &&
					0 < $items[ 'nmgrcf_crowdfund_data' ][ 'min_amount' ][ $nmgrcf_item->get_id() ] ) {
					$crowdfund_data[ 'min_amount' ] = ( float ) wp_unslash( $items[ 'nmgrcf_crowdfund_data' ][ 'min_amount' ][ $nmgrcf_item->get_id() ] );
				}

				$nmgrcf_item->make_crowdfunded( $crowdfund_data );
			} else {
				$nmgrcf_item->unmake_crowdfunded();
			}
		} elseif ( isset( $_REQUEST[ 'nmgr_add_items_data' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// In this case we are adding items from the add items dialog in the single wishlist screen admin area
			// phpcs:ignore WordPress.Security.NonceVerification
			$product_data = array_filter( wc_clean( wp_unslash( ( array ) $_REQUEST[ 'nmgr_add_items_data' ] ) ) );

			$item_is_crowdfunded = ( int ) $nmgrcf_item->is_crowdfunded();

			foreach ( $product_data as $data ) {
				if ( isset( $data[ 'product_id' ], $data[ 'product_crowdfund' ] ) &&
					$data[ 'product_id' ] && ($data[ 'product_id' ] == $nmgrcf_item->get_product_id()) ) {
					$crowdfund = $data[ 'product_crowdfund' ];
					if ( $crowdfund && !$nmgrcf_item->is_fulfilled() && !$item_is_crowdfunded ) {
						$nmgrcf_item->make_crowdfunded();
					} elseif ( !$crowdfund && !$nmgrcf_item->is_fulfilled() && !$nmgrcf_item->has_crowdfund_contributions() ) {
						$nmgrcf_item->unmake_crowdfunded();
					}
				}
			}
		}
	}

}
