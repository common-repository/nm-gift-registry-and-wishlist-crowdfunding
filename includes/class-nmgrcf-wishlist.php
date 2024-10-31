<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Wishlist extends NMGR_Wishlist {

	protected $wallet;

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	public function get_items() {
		return array_map( 'nmgrcf_get_item', parent::get_items() );
	}

	public function get_crowdfund_amount_needed() {
		$amount_needed = 0;
		foreach ( $this->get_items() as $item ) {
			$amount_needed += $item->get_crowdfund_amount_needed();
		}
		return $amount_needed;
	}

	public function get_crowdfund_amount_received() {
		$amount = 0;
		foreach ( $this->get_items() as $item ) {
			$amount += $item->get_crowdfund_amount_received();
		}
		return $amount;
	}

	public function get_crowdfund_amount_available() {
		$available = 0;
		foreach ( $this->get_items() as $item ) {
			$available += $item->get_crowdfund_amount_available();
		}
		return $available;
	}

	public function get_crowdfund_amount_left() {
		$left = 0;
		foreach ( $this->get_items() as $item ) {
			$left += $item->get_crowdfund_amount_left();
		}
		return $left;
	}

	public function get_wallet() {
		if ( $this->is_wallet_transfer_enabled() ) {
			if ( is_a( $this->wallet, 'NMGRCF_Wallet' ) ) {
				return $this->wallet;
			}
			$this->wallet = new NMGRCF_Wallet( $this->get_id() );
			return $this->wallet;
		}
		return false;
	}

	/**
	 * Get the ids of coupons attached to this wishlist
	 */
	public function get_coupon_ids() {
		$coupon_ids = get_post_meta( $this->get_id(), 'nmgrcf_coupon_ids', true );
		return !empty( $coupon_ids ) ? $coupon_ids : array();
	}

	/**
	 * Get the ids of coupons created from wallet amount
	 * @param type $wishlist_id
	 * @return type
	 */
	public function get_wallet_coupon_ids() {
		$coupon_ids = get_post_meta( $this->get_id(), 'nmgrcf_wallet_coupon_ids', true );
		return !empty( $coupon_ids ) ? $coupon_ids : array();
	}

	/**
	 * Get the settings for making free contributions to a wishlist
	 *
	 * @return array
	 */
	public function get_free_contributions_settings() {
		$db_settings = get_post_meta( ( int ) $this->get_id(), 'free_contributions_settings', true );
		$default_settings = array(
			'enabled' => 1,
			'minimum_amount' => 0,
			'amount_needed' => 0,
			'credited_to_wallet' => 0,
		);

		return wp_parse_args( $db_settings, $default_settings );
	}

	/**
	 * Get the reference of all free contributions made to the wishlist
	 * @return array|false
	 */
	public function get_free_contributions_reference() {
		return get_post_meta( $this->get_id(), 'free_contributions_reference', true );
	}

	/**
	 * Get the total amount of free contributions expected for a wishlist
	 */
	public function get_free_contributions_amount_needed() {
		return ( float ) $this->get_free_contributions_settings()[ 'amount_needed' ];
	}

	/**
	 * Get the total amount of free contributions received for a wishlist
	 * @return int|float
	 */
	public function get_free_contributions_amount_received() {
		$amt_received = 0;

		if ( $this->is_free_contributions_enabled() ) {
			$reference = $this->get_free_contributions_reference();
			if ( !empty( $reference ) ) {
				$amt_received = array_sum( wp_list_pluck( $reference, 'purchased_amount' ) );
			}
		}
		return $amt_received;
	}

	/**
	 * Get the amount left to be received of the total free contribution amount
	 * required for a wishlist
	 *
	 * Note that this function returns 0 if no total free contribution amount has been
	 * set by the wishlist owner. So it is best to check if a total free contribution
	 * amount has been set first before using this function to get the amount left of
	 * that total that has been received.
	 *
	 * @return int|float
	 */
	public function get_free_contributions_amount_left() {
		return max( $this->get_free_contributions_amount_needed() -
			$this->get_free_contributions_amount_received(), 0 );
	}

	/**
	 * Get the amount of free contributions that have been sent to the wallet.
	 *
	 * @param int $wishlist_id Wishlist id
	 * @return int
	 */
	public function get_free_contributions_credited_to_wallet() {
		$settings = $this->get_free_contributions_settings();
		return isset( $settings[ 'credited_to_wallet' ] ) ? ( float ) $settings[ 'credited_to_wallet' ] : 0;
	}

	/**
	 * Get the amount of free contributions that is actually available to the wishlist owner.
	 *
	 * This is simply based on the amount of free contributions received, but it takes into
	 * account the amount of free contributions sent to the wallet and ignores it as it is
	 * no longer considered free contributions once in the wallet.
	 *
	 * @return int|float
	 */
	public function get_free_contributions_amount_available() {
		$received_amt = $this->get_free_contributions_amount_received();
		$wallet_amt = $this->get_free_contributions_credited_to_wallet();

		if ( !$received_amt || !$wallet_amt ) {
			return $received_amt;
		}

		return $received_amt - $wallet_amt;
	}

	/**
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_needed() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_unpurchased_amount' );
		return $this->get_unpurchased_amount();
	}

	/**
	 *
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_received() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_purchased_amount' );
		return $this->get_purchased_amount();
	}

	/**
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_available() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_purchased_amount' );
		return $this->get_purchased_amount();
	}

	/**
	 * @deprecated since version 2.5
	 */
	public function get_purchased_amount_left() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_unpurchased_amount' );
		return $this->get_unpurchased_amount();
	}

	public function get_unpurchased_amount() {
		$amount = 0;
		foreach ( $this->get_items() as $item ) {
			if ( !$item->is_crowdfunded() ) {
				$amount += $item->get_unpurchased_amount();
			}
		}
		return $amount;
	}

	/**
	 * Get the total amount left to be received for all the items in the wishlist
	 * (both crowdfunded and normal items) to be fulfilled.
	 *
	 * @deprecated since version 2.5
	 */
	public function get_total_amount_left() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_total_unpurchased_amount' );
		return $this->get_total_unpurchased_amount();
	}

	/**
	 * Get the total amount received for all the items in the wiahlist.
	 * (This includes debits from the wallet)
	 *
	 * @deprecated since version 2.5
	 */
	public function get_total_amount_available() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5', __CLASS__ . '::get_total_purchased_amount' );
		return $this->get_total_purchased_amount();
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Check if free contributions are enabled for the wishlist
	 *
	 * @return boolean
	 */
	public function is_free_contributions_enabled() {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return false;
		}

		$settings = $this->get_free_contributions_settings();
		return $settings[ 'enabled' ] ? true : false;
	}

	/**
	 * Whether the wishlist still needs to accept free contributions
	 * @return boolean True if there are free contribution amounts left to receive
	 */
	public function needs_free_contributions() {
		return $this->get_free_contributions_amount_needed() ?
			( bool ) $this->get_free_contributions_amount_left() :
			true;
	}

	/**
	 * Check if the wishlist has a normal (non-crowdfunded) item
	 */
	function has_normal_item() {
		foreach ( $this->get_items() as $item ) {
			if ( !$item->is_crowdfunded() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether wallet transfers are enabled for the wishlist items
	 *
	 * @return boolean True if the wishlist has crowdfunded items or if normal items
	 * are allowed to be transferred to the wallet
	 */
	public function is_wallet_transfer_enabled() {
		if ( is_nmgrcf_wallet_enabled() ) {
			return ( bool ) ($this->has_crowdfunded_item() || nmgr_get_option( 'enable_wallet_transfer_all' ));
		}
		return false;
	}

	public function has_crowdfunded_item() {
		foreach ( $this->get_items() as $item ) {
			if ( $item->is_crowdfunded() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether the wishlist has the amount for it to be fulfilled.
	 *
	 * This amount takes into account the money in the wallet
	 */
	public function has_fulfill_amount() {
		$balance = $this->get_wallet() ? $this->get_wallet()->get_balance() : 0;
		return $this->get_total_purchased_amount() &&
			(nmgrcf_round( $this->get_total_unpurchased_amount() ) <= nmgrcf_round( $balance ));
	}

	/*
	  |--------------------------------------------------------------------------
	  |
	 * Actions
	  |--------------------------------------------------------------------------
	 */

	public function update_free_contributions_reference( $reference ) {
		update_post_meta( $this->get_id(), 'free_contributions_reference', $reference );
		do_action( 'nmgrcf_free_contributions_reference_updated', $this );
	}

}
