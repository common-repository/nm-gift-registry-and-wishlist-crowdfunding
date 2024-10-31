<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

/**
 * Order actions related to crowdfunding
 * @sync
 */
class NMGRCF_Order_Crowdfund {

	public static function run() {
		add_action( 'admin_footer', array( __CLASS__, 'show_contribution_order_item_thumbnail' ) );

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}

		add_action( 'woocommerce_checkout_create_order_line_item',
			array( __CLASS__, 'set_crowdfund_contribution_order_item_props' ), 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta',
			array( __CLASS__, 'set_crowdfund_contribution_order_item_thumbnail' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item',
			array( __CLASS__, 'add_crowdfund_contribution_order_item_meta_data' ), 10, 3 );
		add_action( 'woocommerce_checkout_update_order_meta',
			array( __CLASS__, 'add_crowdfund_contribution_order_meta_data' ) );
		add_action( 'woocommerce_order_item_meta_start',
			array( __CLASS__, 'display_crowdfund_contribution_data_in_order_itemmeta_table' ), 10, 2 );
		add_action( 'woocommerce_before_order_itemmeta',
			array( __CLASS__, 'display_crowdfund_contribution_data_in_order_itemmeta_table' ), 10, 2 );
		add_filter( 'nmgr_wishlist_get_items_in_order',
			array( __CLASS__, 'set_crowdfund_contribution_as_wishlist_item_in_order' ), 10, 3 );
		add_filter( 'nmgr_item_is_fulfilled',
			array( __CLASS__, 'set_crowdfund_item_fulfilled_condition' ), 10, 2 );
		add_filter( 'nmgr_do_order_payment_actions',
			array( __CLASS__, 'do_order_payment_actions_for_crowdfund_contribution' ), 10, 2 );
		add_action( 'nmgr_order_payment_complete',
			array( __CLASS__, 'update_wishlist_item_crowdfund_contribution_reference' ), 10, 3 );
		add_action( 'nmgr_order_payment_complete',
			array( __CLASS__, 'email_customer_new_crowdfund_contribution' ), 99, 3 );
		add_filter( 'nmgr_shop_order_column_data',
			array( __CLASS__, 'recognise_wishlists_with_crowdfund_contributions' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed',
			array( __CLASS__, 'update_crowdfund_contribution_reference_ordered_amount' ), 10, 3 );
	}

	/**
	 * Show thumbnails in the appropriate thumbnail table cell
	 * for crowdfund and free contributions on the edit order page if these have been set.
	 *
	 * This is the final part of the hack that is used to show order item thumbnails for
	 * crowdfunds and free contributions which are usually associated with a placeholder
	 * product which has no default thumbnail.
	 */
	public static function show_contribution_order_item_thumbnail() {
		global $current_screen;

		if ( isset( $current_screen, $current_screen->post_type ) && 'shop_order' === $current_screen->post_type ) {
			?>
			<script type="text/javascript">
				var containers = document.querySelectorAll('.nmgrcf-contribution-order-item-thumbnail');
				if (containers.length) {
					for (var container of containers) {
						var wrapper = container.closest('tr.item').querySelector('.wc-order-item-thumbnail');
						wrapper.innerHTML = container.innerHTML;
					}
				}
			</script>
			<?php

		}
	}

	/**
	 * Set the properties of the crowdfund order item to reflect the properties of the
	 * wishlist product being crowdfunded rather than the crowdfund placeholder product.
	 *
	 * (Note that some properties cannot be set here such as the product thumbnail as they
	 * cannot be set in the class. They have to be set separately if there is a filter for them)
	 */
	public static function set_crowdfund_contribution_order_item_props( $item, $cart_item_key, $cart_item ) {
		$nmgr_cf_data = nmgr_get_cart_item_data( $cart_item, 'crowdfund' );
		if ( $nmgr_cf_data ) {
			$wishlist_item = nmgr_get_wishlist_item( $nmgr_cf_data[ 'wishlist_item_id' ] );
			if ( !$wishlist_item ) {
				return;
			}

			$item->set_props( array(
				'name' => nmgrcf_get_crowdfund_cart_item_name( $cart_item ),
				'product_id' => 0 // Set product id to 0 to avoid associating the crowdfund contribution with a product in admin
			) );
		}
	}

	/**
	 * Set the thumbnail of each order item representing a crowdfund contribution
	 * in the order item table on the shop order page.
	 *
	 * This is necessary because the order item not attached to any product so it has no
	 * thumbnail by default. We want to attach the thumbnail of the product that is being
	 * crowdfunded.
	 *
	 * In this case we are adding the thumbnail directly to the order item table row and hiding it
	 * to display it in the appropriate cell for the thumbnail using js. This is a hack as there is
	 * no proper filter to do this.
	 */
	public static function set_crowdfund_contribution_order_item_thumbnail( $item_id, $item ) {
		$cf = $item->get_meta( 'nmgr_cf' );

		if ( $cf ) {
			$product = wc_get_product( $cf[ 'product_id' ] ?? $cf[ 'wishlist_item_product_id' ] ?? 0 );

			if ( $product ) {
				echo '<div class="nmgrcf-contribution-order-item-thumbnail" style="display:none;">' .
				wp_kses_post( $product->get_image( 'thumbnail', array( 'title' => '' ), false ) ) .
				'</div>';
			}
		}
	}

	public static function add_crowdfund_contribution_order_item_meta_data( $item, $cart_item_key, $values ) {
		$nmgr_cf_data = nmgr_get_cart_item_data( $values, 'crowdfund' );
		if ( $nmgr_cf_data ) {
			$wishlist_item = nmgr_get_wishlist_item( $nmgr_cf_data[ 'wishlist_item_id' ] );
			if ( !$wishlist_item ) {
				return;
			}
			$item->add_meta_data( 'nmgr_cf', $nmgr_cf_data );
		}
	}

	public static function add_crowdfund_contribution_order_meta_data( $order_id ) {
		$wishlist_items_data = array();
		$order = wc_get_order( $order_id );

		if ( !$order ) {
			return;
		}

		foreach ( $order->get_items() as $order_item ) {
			$meta = $order_item->get_meta( 'nmgr_cf' );

			if ( $meta ) {

				$wishlist = nmgr_get_wishlist( $meta[ 'wishlist_id' ], true );
				if ( !$wishlist ) {
					continue;
				}

				$wishlist_item = $wishlist->get_item( $meta[ 'wishlist_item_id' ] );
				if ( !$wishlist_item ) {
					continue;
				}

				$wishlist_items_data[] = array_merge( $meta, array(
					'order_item_id' => $order_item->get_id(), // order item id
					) );
			}
		}

		if ( !empty( $wishlist_items_data ) ) {
			$order_meta = array();
			foreach ( $wishlist_items_data as $data ) {
				$order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_id' ] = $data[ 'wishlist_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_item_ids' ][] = $data[ 'wishlist_item_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'order_item_ids' ][ $data[ 'wishlist_item_id' ] ] = $data[ 'order_item_id' ];
				$order_meta[ $data[ 'wishlist_id' ] ][ 'sent_customer_new_contribution_email' ] = 'no';
			}

			$order->add_meta_data( 'nmgr_cf', $order_meta );
			$order->save();
		}
	}

	public static function display_crowdfund_contribution_data_in_order_itemmeta_table( $item_id, $item ) {
		if ( !is_admin() && !nmgr_get_option( 'show_order_item', 1 ) ) {
			return;
		}

		$meta = $item->get_meta( 'nmgr_cf' );
		if ( $meta ) {
			$wishlist = nmgr_get_wishlist( $meta[ 'wishlist_id' ], true );

			if ( !$wishlist ) {
				return;
			}

			$title = sprintf(
				/* translators: %s: wishlist type title */
				nmgrcf()->is_pro ? __( 'This contribution is made for an item in this %s', 'nm-gift-registry-crowdfunding' ) : __( 'This contribution is made for an item in this %s', 'nm-gift-registry-crowdfunding-lite' ),
				nmgr_get_type_title()
			);
			$link = nmgr_get_wishlist_link( $wishlist, array( 'title' => $title ) );

			echo wp_kses(
				'<div class="nmgr-order-item-wishlist">'
				. sprintf(
					/* translators: %s: wishlist type title */
					nmgrcf()->is_pro ? __( 'For %s: ', 'nm-gift-registry-crowdfunding' ) : __( 'For %s: ', 'nm-gift-registry-crowdfunding-lite' ),
					esc_html( nmgr_get_type_title() )
				)
				. $link
				. '</div>',
				array_merge( wp_kses_allowed_html( 'post' ), nmgr_allowed_svg_tags() )
			);
		}
	}

	/**
	 * This function allows us to specify that there are wishlist items in the order (from a crowdfunded item)
	 * because normally these items are not detected as normal wishlist items since they are paid for
	 * differently.
	 *
	 * The function is mainly used in the NMGR_Wishlist class for wishlist messages.
	 */
	public static function set_crowdfund_contribution_as_wishlist_item_in_order( $items_in_order, $order, $wishlist ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			$meta = $item->get_meta( 'nmgr_cf' );
			if ( $meta && $meta[ 'wishlist_id' ] == $wishlist->get_id() ) {
				$items_in_order[ $item_id ] = array(
					'name' => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'variation_id' => $item->get_variation_id(),
					'total' => $item->get_total() - $order->get_total_refunded_for_item( $item_id ),
				);
			}
		}

		return $items_in_order;
	}

	public static function set_crowdfund_item_fulfilled_condition( $bool, $item ) {
		if ( nmgrcf_get_item( $item )->is_crowdfunded() ) {
			return 0 >= $item->get_unpurchased_quantity();
		}
		return $bool;
	}

	public static function do_order_payment_actions_for_crowdfund_contribution( $bool, $order ) {
		return $order->get_meta( 'nmgr_cf' ) ? true : $bool;
	}

	/**
	 * Update the crowdfund amount of a wishlist item
	 */
	public static function update_wishlist_item_crowdfund_contribution_reference( $order_id, $order_wishlist_data, $order ) {
		$wishlist_crowdfund_data = $order->get_meta( 'nmgr_cf' );

		if ( !$wishlist_crowdfund_data ) {
			return;
		}

		$order_item_ids = array();
		foreach ( $wishlist_crowdfund_data as $wishlist_data ) {
			$ids = isset( $wishlist_data[ 'order_item_ids' ] ) ? $wishlist_data[ 'order_item_ids' ] : array();
			$order_item_ids = $order_item_ids + $ids;
		}

		if ( empty( $order_item_ids ) ) {
			return;
		}

		$refunded_items = array();

		/**
		 * At this point we have an array of wishlist item ids to their order_item_id counterparts
		 * so we update the purchased amount of each item individually
		 */
		foreach ( $order_item_ids as $wishlist_item_id => $order_item_id ) {
			// Get the wishlist item object
			$wishlist_item = nmgrcf_get_item( $wishlist_item_id );

			if ( !$wishlist_item || !$wishlist_item->is_crowdfunded() ) {
				continue;
			}

			$product = $wishlist_item->get_product();

			// Get the item's crowdfund reference from the database
			$cfr_meta = $wishlist_item->get_crowdfund_reference();
			$crowdfund_reference = $cfr_meta ? $cfr_meta : array();

			// If the crowdfund reference doesn't exist, flag this as a new order crowdfund item
			$item_is_new = isset( $crowdfund_reference[ $order_id ] ) ? false : true;

			// Get the order item
			$order_item = $order->get_item( $order_item_id );

			/**
			 * If we don't have the order item object, we assume the item has been removed from the order
			 * (This might be due to a refund or something).
			 * So we simply delete the crowdfund reference of this item for the order if it exists,
			 * update its fulfilled status, and return
			 */
			if ( !$order_item ) {
				if ( isset( $crowdfund_reference[ $order_id ] ) ) {
					$original_purchased_amount = nmgrcf_round( array_sum( wp_list_pluck( $crowdfund_reference, 'purchased_amount' ) ) );

					unset( $crowdfund_reference[ $order_id ] );

					$new_purchased_amount = nmgrcf_round( array_sum( wp_list_pluck( $crowdfund_reference, 'purchased_amount' ) ) );

					/**
					 * If the item is fulfilled, possibly reset this
					 */
					if ( $wishlist_item->is_fulfilled() && $product &&
						($new_purchased_amount < nmgrcf_round( $product->get_price() * $wishlist_item->get_quantity() ) ) ) {
						$wishlist_item->set_purchased_quantity( 0 );
						$wishlist_item->save();
					}

					$wishlist_item->update_crowdfund_reference( $crowdfund_reference );

					/**
					 * The item was previously in the order because it has a crowdfund reference
					 * for the order id, but since it no longer has it, let's assume that the refunded
					 * amount of the item has changed. (That is, all of the item is refunded).
					 * So we add the item to the refunded_items array.
					 */
					if ( $new_purchased_amount < $original_purchased_amount ) {
						$refunded_items[ $wishlist_item->get_id() ] = $original_purchased_amount - $new_purchased_amount;
					}
				}
				continue;
			}

			// Set a default crowdfund reference for new items
			$default_cfr = array(
				'ordered_amount' => 0,
				'refunded_amount' => 0,
				'purchased_amount' => 0,
			);

			/**
			 * If this item's crowdfund reference has not be set for this order, set the new one
			 * else get the one set for this order
			 */
			$cfr = $item_is_new ? $default_cfr : $crowdfund_reference[ $order_id ];

			// Set a new crowdfund reference for the item for this order based on current item price
			$ordered_amount = $order_item->get_subtotal();

			$_refunded_amount = 0; // this is always negative
			foreach ( $order->get_refunds() as $refund ) {
				foreach ( $refund->get_items( 'line_item' ) as $refunded_item ) {
					if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $order_item_id ) {
						$_refunded_amount += $refunded_item->get_subtotal();
					}
				}
			}
			$refunded_amount = $_refunded_amount * -1;

			$new_cfr = array(
				'ordered_amount' => $ordered_amount,
				'refunded_amount' => $refunded_amount,
				'purchased_amount' => $ordered_amount - $refunded_amount,
			);

			/**
			 * If the order payment is cancelled, update the crowdfund reference and
			 * purchased amount for the wishlist item
			 */
			if ( $order->has_status( nmgr_get_payment_cancelled_order_statuses() ) ) {
				$new_cfr[ 'purchased_amount' ] = 0;
			}

			/**
			 * If the item is not new and the new purchased amount is less than the old purchased amount,
			 * we assume the item is refunded  (or the order payment is cancelled)
			 * so we add it to the refunded_items array
			 */
			if ( !$item_is_new && ( nmgrcf_round( $new_cfr[ 'purchased_amount' ] ) < nmgrcf_round( $cfr[ 'purchased_amount' ] ) ) ) {
				$refunded_items[ $wishlist_item->get_id() ] = $cfr[ 'purchased_amount' ] - $new_cfr[ 'purchased_amount' ];
			}

			/**
			 * if the stored crowdfund reference is not equal to the new crowdfund reference
			 * the item purchased amount might have changed, so update it
			 */
			if ( $cfr !== $new_cfr ) {
				$crowdfund_reference[ $order_id ] = $new_cfr;

				$wishlist_item->update_crowdfund_reference( $crowdfund_reference );

				$new_purchased_amount = nmgrcf_round( array_sum( wp_list_pluck( $crowdfund_reference, 'purchased_amount' ) ) );

				// Item purchased quantity may need to be updated after changing the crowdfund reference
				if ( !$wishlist_item->is_fulfilled() && $product &&
					($new_purchased_amount >= nmgrcf_round( $product->get_price() * $wishlist_item->get_quantity() )) ) {
					$wishlist_item->set_purchased_quantity( $wishlist_item->get_quantity() );
					$wishlist_item->save();
				}
			}
		}

		// If we have items in the refunded_items array, set up the refund action.
		if ( !empty( $refunded_items ) ) {
			self::email_customer_refunded_crowdfund_contribution( $refunded_items, $wishlist_crowdfund_data, $order );
		}
	}

	/**
	 * Email the customer when a new crowdfund contribution has been made for an item in his wishlist
	 *
	 * @param int $order_id The order id
	 * @param array $order_wishlist_data The order meta value which holds all the information for the wishlists in the order
	 * @param WC_Order $order
	 */
	public static function email_customer_new_crowdfund_contribution( $order_id, $order_wishlist_data, $order ) {
		WC()->mailer();
		// As a precaution, let's just make sure we're doing this only if the order is paid
		if ( !$order->is_paid() || !class_exists( 'NMGR_Email' ) ) {
			return;
		}

		/**
		 * $order_wishlist_data maybe be empty but we are not using it anyway because we want to get
		 * information specifically for crowdfund contributions in the order so we use the 'nmgr_cf meta value in the order.
		 */
		$order_crowdfund_data = $order->get_meta( 'nmgr_cf' );

		if ( empty( $order_crowdfund_data ) ) {
			return;
		}

		// Loop through the wishlists in the order and send email only if it hasn't been sent
		foreach ( $order_crowdfund_data as $wishlist_id => $wishlist_data ) {
			if ( isset( $wishlist_data[ 'sent_customer_new_contribution_email' ] ) &&
				'no' === $wishlist_data[ 'sent_customer_new_contribution_email' ] ) {
				$emailer = new NMGR_Email( 'email_customer_new_crowdfund_contribution', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'order_item_ids' ] = $wishlist_data[ 'order_item_ids' ];
				$emailer->template_args[ 'order_customer_name' ] = NMGR_Mailer::get_order_customer_name( $order );
				$emailer->template_args[ 'is_crowdfund_template' ] = true; // Flag to get email template from crowdfund plugin templates folder
				$emailer->template_args[ 'wishlist' ] = nmgrcf_get_wishlist( $wishlist_id );
				$emailer->trigger();

				$order_crowdfund_data[ $wishlist_id ][ 'sent_customer_new_contribution_email' ] = 'yes';
				$order->update_meta_data( 'nmgr_cf', $order_crowdfund_data );
				$order->save();
			}
		}
	}

	public static function email_customer_refunded_crowdfund_contribution( $refunded_items, $order_crowdfund_data, $order ) {
		WC()->mailer();
		$refunded_wishlists = array();
		$wishlists_to_wishlist_item_ids = wp_list_pluck( $order_crowdfund_data, 'wishlist_item_ids' );

		foreach ( $wishlists_to_wishlist_item_ids as $wishlist_id => $wishlist_item_ids ) {
			$refunded_items_in_wishlist = array_intersect_key( $refunded_items, array_flip( $wishlist_item_ids ) );
			if ( !empty( $refunded_items_in_wishlist ) ) {
				$refunded_wishlists[ $wishlist_id ] = $refunded_items_in_wishlist;
			}
		}

		if ( class_exists( 'NMGR_Email' ) && !empty( $refunded_wishlists ) ) {
			foreach ( $refunded_wishlists as $wishlist_id => $wishlist_item_ids_to_amts ) {
				$emailer = new NMGR_Email( 'email_customer_refunded_crowdfund_contribution', $wishlist_id );
				$emailer->template_args[ 'order' ] = $order;
				$emailer->template_args[ 'wishlist_item_ids_to_amts' ] = $wishlist_item_ids_to_amts;
				$emailer->template_args[ 'order_customer_name' ] = NMGR_Mailer::get_order_customer_name( $order );
				$emailer->template_args[ 'is_crowdfund_template' ] = true;
				$emailer->template_args[ 'wishlist' ] = nmgrcf_get_wishlist( $wishlist_id );
				$emailer->trigger();
			}
		}
	}

	/**
	 * Make order list table recognise wishlists with crowdfunded items.
	 */
	public static function recognise_wishlists_with_crowdfund_contributions( $column_data, $order ) {
		$cf = $order->get_meta( 'nmgr_cf' );

		if ( !empty( $cf ) ) {
			foreach ( $cf as $wishlist_data ) {
				if ( isset( $wishlist_data[ 'wishlist_id' ] ) ) {
					$wishlist_id = $wishlist_data[ 'wishlist_id' ];
					$item_count = isset( $wishlist_data[ 'order_item_ids' ] ) ?
						count( $wishlist_data[ 'order_item_ids' ] ) :
						0;

					if ( isset( $column_data[ $wishlist_id ] ) ) {
						$column_data[ $wishlist_id ][ 'item_count' ] = $column_data[ $wishlist_id ][ 'item_count' ] + $item_count;
					} else {
						$column_data[ $wishlist_id ] = array(
							'wishlist_id' => $wishlist_id,
							'item_count' => $item_count
						);
					}
				}
			}
		}

		return $column_data;
	}

	/**
	 * Update the crowdfunded amount for a wishlist item when an order is created
	 */
	public static function update_crowdfund_contribution_reference_ordered_amount( $order_id, $posted_data, $order ) {
		$order_wishlist_data = $order->get_meta( 'nmgr_cf' );
		if ( $order_wishlist_data ) {
			self::update_wishlist_item_crowdfund_contribution_reference( $order_id, $order_wishlist_data, $order );
		}
	}

}
