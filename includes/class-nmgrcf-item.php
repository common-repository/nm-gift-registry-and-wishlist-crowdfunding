<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Item extends NMGR_Wishlist_Item {

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	public function get_wishlist() {
		return nmgrcf_get_wishlist( $this->get_wishlist_id() );
	}

	public function get_crowdfund_data() {
		$data = get_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_data', true );
		return $data ? $data : array();
	}

	public function get_crowdfund_reference() {
		return get_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_reference', true );
	}

	/**
	 * Get the amount needed to fulfill a crowdfunded item.
	 *
	 * This amount needed to fulfill the item from the start of the campaign to the end.
	 * It shows the total amount of contributions to be received before the item can be
	 * marked as fulfilled. For this reason, it is based on the original price of the item
	 * and the quantity of the item.
	 */
	public function get_crowdfund_amount_needed() {
		$amt = 0;
		if ( $this->is_crowdfunded() ) {
			$amt = $this->get_total() * $this->get_unpurchased_quantity() / $this->get_quantity();
		}
		return $amt;
	}

	/**
	 * Get the amount received for a crowdfunded item
	 *
	 * (This is the ledger balance, determined by the orders in which contributions have been
	 * made or refunded  towards the item).
	 * This amount does not take into account money used to fund the item from the wallet.
	 * Its takes into account strictly only contributions used to fund the item from woocommerce orders.
	 */
	public function get_crowdfund_amount_received() {
		$amt_received = 0;

		if ( $this->is_crowdfunded() ) {
			$crowdfund_reference = $this->get_crowdfund_reference();
			if ( !empty( $crowdfund_reference ) ) {
				$amt_received = array_sum( wp_list_pluck( $crowdfund_reference, 'purchased_amount' ) );
			}
		}

		return $amt_received;
	}

	/**
	 * Get the crowdfunded amount available for an item.
	 *
	 * (This is the real available balance for the item determined by the crowdfund
	 * amount received for the item, the amount debited from the wallet and the
	 * amount credited to the wallet.
	 *
	 * This amount helps to determine whether the crowdfunded item has been fulfilled.
	 */
	public function get_crowdfund_amount_available() {
		if ( !$this->is_crowdfunded() ) {
			return 0;
		}

		$amt_available = ($this->get_crowdfund_amount_received() + $this->get_total_debits_from_wallet()) - $this->get_total_credits_to_wallet();

		return $amt_available;
	}

	/**
	 * Get the amount left to be received for a crowdfunded item.
	 * If this amount is positive, the crowdfunded item would remain unfulfilled.
	 * This amount determines how much more the wishlist owner needs to get in order
	 * to mark the item as completely crowdfunded.
	 */
	public function get_crowdfund_amount_left() {
		return max( $this->get_crowdfund_amount_needed() - $this->get_crowdfund_amount_available(), 0 );
	}

	public function get_total_purchased_amount() {
		return parent::get_total_purchased_amount() + $this->get_crowdfund_amount_available();
	}

	/**
	 * Alias for 'get_purchased_amount'
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_received() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_purchased_amount' );
		return $this->get_purchased_amount();
	}

	/**
	 * Get the purchased amount available for an item.
	 *
	 * (This is the real available balance for the item determined by the actual
	 * amount purchased for the item, the amount credited to the wallet,
	 * and the amount debited from the wallet to the item).
	 *
	 * This function is meant to be used for normal (non-crowdfunded) items.
	 * It is the equivalent of 'nmgrcf_item_get_crowdfund_amount_available' for
	 * crowdfunded items.
	 *
	 * @deprecated since version 2,5
	 */
	public function get_purchased_amount_available() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_purchased_amount' );
		return $this->get_purchased_amount();
	}

	/**
	 * Get the purchased amount available for an item.
	 *
	 * (This is the real available balance for the item determined by the actual
	 * amount purchased for the item, the amount credited to the wallet,
	 * and the amount debited from the wallet to the item).
	 *
	 * This function is meant to be used for normal (non-crowdfunded) items.
	 * It is the equivalent of 'nmgrcf_item_get_crowdfund_amount_available' for
	 * crowdfunded items.
	 */
	public function get_purchased_amount() {
		$amt = 0;
		if ( !$this->is_crowdfunded() ) {
			$amt = (parent::get_purchased_amount() + $this->get_total_debits_from_wallet()) - $this->get_total_credits_to_wallet();
		}
		return $amt;
	}

	public function get_unpurchased_amount() {
		$amt = 0;
		if ( !$this->is_crowdfunded() ) {
			$amt = $this->get_total() - $this->get_purchased_amount();
		}
		return $amt;
	}

	/**
	 * Get the amount left to be received for a non-crowdfunded item.
	 *
	 * If this amount is positive, the item would remain unfulfilled.
	 * This amount determines how much more the wishlist owner needs to get
	 * in order to mark the item as fulfilled.
	 *
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_left() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_unpurchased_amount' );
		return $this->get_unpurchased_amount();
	}

	/**
	 * Get the amount received for an item from crowdfund contributions or normal purchases
	 * (Works for both crowdfunded and normal items)
	 *
	 * @deprecated since version 2.5
	 */
	public function get_amount_received() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_total_purchased_amount' );
		return $this->get_total_purchased_amount();
	}

	/**
	 * Get the amount available to an item from crowdfund contributions or normal purchases
	 * (Works for both crowdfunded and normal items)
	 *
	 * @deprecated since version 2.5
	 */
	public function get_amount_available() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5' );
		return $this->is_crowdfunded() ? $this->get_crowdfund_amount_available() : $this->get_purchased_amount();
	}

	/**
	 * Get the amount left to be received for a crowdfunded or normal item
	 * (Works for both crowdfunded and normal items)
	 *
	 * @deprecated since version 2.5
	 */
	public function get_amount_left() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_total_unpurchased_amount' );
		return $this->get_total_unpurchased_amount();
	}

	/**
	 * Get the amount needed to fulfill an item.
	 * (Works for both crowdfunded and normal items)
	 *
	 * For crowdfunded items, it is the original amount needed to fulfill the item from
	 * the start of the campaign when the purchased quantity is zero.
	 * For normal items, it is simply the current amount needed to fulfill the item and this
	 * may change based on the current purchased quantity of the item
	 *
	 * @deprecated since version 2.5
	 */
	public function get_amount_needed() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_crowdfund_amount_needed' );
		return $this->get_crowdfund_amount_needed();
	}

	/**
	 * Get the amounts credited to the wallet for the item
	 * @return array
	 */
	public function get_credits_to_wallet() {
		$ref = get_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_credits_to_wallet', true );
		return empty( $ref ) ? array() : $ref;
	}

	/**
	 * Get the amounts debited from the wallet for the item
	 * @return array
	 */
	public function get_debits_from_wallet() {
		$ref = get_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_debits_from_wallet', true );
		return empty( $ref ) ? array() : $ref;
	}

	/**
	 * Get the total amount credited to the wallet for the item
	 * @return int|float
	 */
	public function get_total_credits_to_wallet() {
		return array_sum( $this->get_credits_to_wallet() );
	}

	/**
	 * Get the total amount debited from the wallet for the item
	 * @return int}float
	 */
	public function get_total_debits_from_wallet() {
		return array_sum( $this->get_debits_from_wallet() );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Whether any amount received for an item has been credited to the wallet
	 * @return mixed False if not.
	 */
	public function is_credited_to_wallet() {
		$credits = $this->get_total_credits_to_wallet();
		return $credits ? $credits : false;
	}

	/**
	 * Whether any amount received for an item has been debited from the wallet
	 * @return mixed False if not.
	 */
	public function is_debited_from_wallet() {
		$debits = $this->get_total_debits_from_wallet();
		return $debits ? $debits : false;
	}

	/**
	 * Whether item purchase is disabled on the frontend.
	 * For normal items, this means the product cannot be added to the cart.
	 * For crowdfunded items, this means a contribution cannot be added to the cart.
	 *
	 * @return boolean
	 */
	public function is_purchase_disabled() {
		$wishlist = $this->get_wishlist();
		$cond1 = method_exists( $wishlist, 'has_fulfill_amount' ) &&
			$wishlist->has_fulfill_amount() &&
			$this->is_wallet_transfer_enabled();
		$cond2 = $this->is_credited_to_wallet();

		return apply_filters( 'is_nmgrcf_item_purchase_disabled', ($cond1 || $cond2 ), $this );
	}

	/**
	 * Whether the crowdfunding for an item has been completely fulfilled
	 */
	public function is_crowdfunding_fulfilled() {
		return $this->is_crowdfunded() &&
			nmgrcf_round( $this->get_crowdfund_amount_available() ) >= nmgrcf_round( $this->get_crowdfund_amount_needed() );
	}

	/**
	 * Whether the current crowdfund status of the item should be maintained
	 * (This flag should prevent the crowdfund status from being changed
	 * when the item is saved.
	 *
	 * @return boolean
	 */
	public function maintain_crowdfund_status() {
		return $this->is_fulfilled() || $this->is_purchased() || $this->has_crowdfund_contributions() ||
			(method_exists( $this, 'is_archived' ) && $this->is_archived());
	}

	/**
	 * Check if the wishlist item has crowdfund contributions
	 * (This function is simply an alias for 'get_crowdfund_amount_available')
	 * @return boolean
	 */
	public function has_crowdfund_contributions() {
		return ( bool ) $this->get_crowdfund_amount_available();
	}

	public function is_crowdfunded() {
		return ( bool ) get_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfunded', true );
	}

	/**
	 * Whether the item has the amount for it to be fulfilled.
	 */
	public function has_fulfill_amount() {
		return nmgrcf_round( 0 ) >= nmgrcf_round( $this->get_total_unpurchased_amount() );
	}

	/**
	 * Whether wallet transfers are enabled for this wishlist item
	 *
	 * @return boolean True if the item is crowdfunded or if it is a normal item
	 * and transfers are enabled for normal items.
	 */
	public function is_wallet_transfer_enabled() {
		$is_archived = method_exists( $this, 'is_archived' ) ? $this->is_archived() : false;
		if ( is_nmgrcf_wallet_enabled() && !$is_archived ) {
			return ( bool ) ($this->is_crowdfunded() || nmgr_get_option( 'enable_wallet_transfer_all' ));
		}
		return false;
	}

	public function is_crowdfunding_enabled() {
		$is_archived = method_exists( $this, 'is_archived' ) ? $this->is_archived() : false;
		return is_nmgrcf_crowdfunding_enabled() && !$is_archived;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Actions
	  |--------------------------------------------------------------------------
	 */

	public function make_crowdfunded( $crowdfund_data = array() ) {
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfunded', 1 );

		if ( !empty( $crowdfund_data ) ) {
			update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_data', $crowdfund_data );
		}
	}

	public function unmake_crowdfunded() {
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfunded', 0 );
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_data', array() );
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_reference', array() );
	}

	/**
	 * Transfer the amount received for the wishlist item to the wallet
	 *
	 * @return boolean True if the wallet was credited with the amount, WP_Error if not
	 */
	public function credit_wallet() {
		$wallet = new NMGRCF_Wallet( $this->get_wishlist_id() );
		return $wallet->credit_item_amount( $this );
	}

	/**
	 * Fund the wishlist item from the amount in the wallet
	 *
	 * @return boolean True if wallet was debited, WP_Error if not.
	 */
	public function debit_wallet() {
		$wallet = new NMGRCF_Wallet( $this->get_wishlist_id() );
		return $wallet->debit_item_amount( $this );
	}

	public function update_crowdfund_reference( $reference ) {
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_crowdfund_reference', $reference );
		do_action( 'nmgrcf_item_crowdfund_reference_updated', $this );
	}

	public function add_amount_to_credits_to_wallet( $amount ) {
		$credits = $this->get_credits_to_wallet();
		$credits[] = $amount;
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_credits_to_wallet', $credits );
	}

	public function add_amount_to_debits_from_wallet( $amount ) {
		$debits = $this->get_debits_from_wallet();
		$debits[] = $amount;
		update_metadata( $this->meta_type, $this->get_id(), 'nmgrcf_debits_from_wallet', $debits );
	}

}
