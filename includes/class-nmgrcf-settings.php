<?php

/**
 * Integrates crowdfunding plugin settings with main plugin settings
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF_Settings {

	public static function run() {
		add_filter( 'pre_update_option_nmgr_settings', array( __CLASS__, 'pre_update_option_actions' ), 10, 2 );
		add_filter( 'nmgr_settings_tabs', array( __CLASS__, 'add_settings_tab' ) );
		add_filter( 'nmgr-settings_tab_sections', array( __CLASS__, 'add_modules_tab' ), 10, 2 );
		add_filter( 'nmgr-settings_tab_sections', array( __CLASS__, 'add_crowdfund_emails' ), 10, 2 );
		add_filter( 'nmgr-settings_tab_sections', array( __CLASS__, 'add_free_contributions_emails' ), 10, 2 );
	}

	public static function pre_update_option_actions( $new_value, $old_value ) {
		if ( isset( $old_value[ 'enable_crowdfunding' ] ) &&
			$old_value[ 'enable_crowdfunding' ] !== $new_value[ 'enable_crowdfunding' ] ) {
			if ( !$new_value[ 'enable_crowdfunding' ] ) {
				$new_value[ 'email_customer_new_crowdfund_contribution_enabled' ] = '';
				$new_value[ 'email_customer_refunded_crowdfund_contribution_enabled' ] = '';
			} else {
				// Values are reset to plugin defaults
				$new_value[ 'email_customer_new_crowdfund_contribution_enabled' ] = 1;
				$new_value[ 'email_customer_refunded_crowdfund_contribution_enabled' ] = 1;
			}
		}

		if ( isset( $old_value[ 'enable_free_contributions' ] ) &&
			$old_value[ 'enable_free_contributions' ] !== $new_value[ 'enable_free_contributions' ] ) {
			if ( !$new_value[ 'enable_free_contributions' ] ) {
				$new_value[ 'email_customer_new_free_contribution_enabled' ] = '';
				$new_value[ 'email_customer_refunded_free_contribution_enabled' ] = '';
			} else {
				// Values are reset to plugin defaults
				$new_value[ 'email_customer_new_free_contribution_enabled' ] = 1;
				$new_value[ 'email_customer_refunded_free_contribution_enabled' ] = 1;
			}
		}

		return $new_value;
	}

	public static function add_settings_tab( $tabs ) {
		if ( isset( $tabs[ 'modules' ], $tabs[ 'modules' ][ 'sections' ] ) ) {
			$tabs[ 'modules' ][ 'sections' ][ 'crowdfunding' ] = nmgrcf()->is_pro ?
				__( 'Crowdfunding', 'nm-gift-registry-crowdfunding' ) :
				__( 'Crowdfunding', 'nm-gift-registry-crowdfunding-lite' );
		}
		return $tabs;
	}

	public static function add_modules_tab( $sections, $tab ) {
		if ( 'modules' === $tab ) {
			$sections[ 'crowdfunding' ] = array(
				'title' => '',
				'section' => 'crowdfunding',
				'fields' => array(
					array(
						'id' => 'enable_crowdfunding',
						'label' => nmgrcf()->is_pro ?
						__( 'Enable crowdfunding', 'nm-gift-registry-crowdfunding' ) :
						__( 'Enable crowdfunding', 'nm-gift-registry-crowdfunding-lite' ),
						'type' => 'checkbox',
						'default' => 1,
						'desc' => nmgrcf()->is_pro ?
						__( 'Allow wishlist items to be crowdfunded', 'nm-gift-registry-crowdfunding' ) :
						__( 'Allow wishlist items to be crowdfunded', 'nm-gift-registry-crowdfunding-lite' )
					)
				)
			);

			if ( is_nmgrcf_free_contributions_module_enabled() ) {
				$sections[ 'crowdfunding' ][ 'fields' ][] = array(
					'id' => 'enable_free_contributions',
					'label' => __( 'Enable free contributions', 'nm-gift-registry-crowdfunding' ),
					'type' => 'checkbox',
					'default' => 1,
					'desc' => __( 'Allow wishlists to receive contributions not attached to items', 'nm-gift-registry-crowdfunding' )
				);
			}

			if ( is_nmgrcf_wallet_enabled() ) {
				$sections[ 'crowdfunding' ][ 'fields' ][] = array(
					'id' => 'enable_wallet_transfer_all',
					'label' => __( 'Allow non-crowdfunded wishlist items to send and receive money from the wallet', 'nm-gift-registry-crowdfunding' ),
					'type' => 'checkbox',
					'default' => 0,
					'desc' => __( 'Enable wallet transfers for normal wishlist items', 'nm-gift-registry-crowdfunding' ),
					'desc_tip' => __( 'By default sending and receiving money from the wallet is enabled only for crowdfunded items', 'nm-gift-registry-crowdfunding' ),
				);
			}
		}

		return $sections;
	}

	public static function add_crowdfund_emails( $sections, $tab ) {
		if ( is_nmgrcf_crowdfunding_enabled() && 'emails' === $tab ) {
			$sections[ 'email_customer_new_crowdfund_contribution' ] = array(
				'title' => __( 'New crowdfund contribution', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Email the customer when a crowdfund contribution has been made for an item in his wishlist. The order would have been marked as processing or completed at this time.', 'nm-gift-registry-crowdfunding' ),
				'is_customer_email' => true,
				'fields' => array(
					array(
						'id' => 'email_customer_new_crowdfund_contribution_enabled',
						'label' => __( 'Enable/Disable', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'default' => 1,
						'custom_attributes' => array(
							'disabled' => !nmgr_get_option( 'enable_crowdfunding', 1 ) ? true : false,
						),
						'desc_tip' => __( 'This would be automatically disabled if the crowdfunding module is disabled.' ),
					),
					array(
						'id' => 'email_customer_new_crowdfund_contribution_checkout_message',
						'label' => __( 'Show checkout message', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'desc' => __( 'If a message was sent to the wishlist\'s owner at checkout during the order, show it in this email', 'nm-gift-registry-crowdfunding' ),
						'desc_tip' => __( 'This only works if the "messages" module is enabled.', 'nm-gift-registry-crowdfunding' ),
						'custom_attributes' => array(
							'disabled' => nmgr_get_option( 'enable_messages', 1 ) ? false : true,
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_crowdfund_contribution_subject',
						'label' => __( 'Subject', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}'
						),
						'placeholder' => sprintf(
							/* translators: %s: wishlist type title */
							__( '[{site_title}]: A crowdfund contribution has been made for an item in your %s', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title() )
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_crowdfund_contribution_heading',
						'label' => __( 'Email heading', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}' ),
						'placeholder' => __( 'New Crowdfund Contribution: {wishlist_title}', 'nm-gift-registry-crowdfunding' ),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_crowdfund_contribution_email_type',
						'label' => __( 'Email type', 'nm-gift-registry-crowdfunding' ),
						'type' => 'select',
						'desc_tip' => __( 'Choose which format of email to send.', 'nm-gift-registry-crowdfunding' ),
						'class' => 'email_type wc-enhanced-select',
						'options' => nmgr()->settings()->get_email_type_options(),
						'default' => __( 'html', 'nm-gift-registry-crowdfunding' ),
					),
				)
			);

			$sections[ 'email_customer_refunded_crowdfund_contribution' ] = array(
				'title' => __( 'Refunded crowdfund contribution', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Email the customer when crowdfund contributions made for items in his wishlist have been refunded.', 'nm-gift-registry-crowdfunding' ),
				'is_customer_email' => true,
				'fields' => array(
					array(
						'id' => 'email_customer_refunded_crowdfund_contribution_enabled',
						'label' => __( 'Enable/Disable', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'default' => 1,
						'custom_attributes' => array(
							'disabled' => !nmgr_get_option( 'enable_crowdfunding', 1 ) ? true : false,
						),
						'desc_tip' => __( 'This would be automatically disabled if the crowdfunding module is disabled.', 'nm-gift-registry-crowdfunding' ),
					),
					array(
						'id' => 'email_customer_refunded_crowdfund_contribution_subject',
						'label' => __( 'Subject', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}'
						),
						'placeholder' => sprintf(
							/* translators: %s: wishlist type title */
							__( '[{site_title}]: %s crowdfund contribution refunded', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title( 'cf' ) )
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_refunded_crowdfund_contribution_heading',
						'label' => __( 'Email heading', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}' ),
						'placeholder' => __( 'Refunded crowdfund contribution: {wishlist_title}', 'nm-gift-registry-crowdfunding' ),
						'default' => '',
					),
					array(
						'id' => 'email_customer_refunded_crowdfund_contribution_email_type',
						'label' => __( 'Email type', 'nm-gift-registry-crowdfunding' ),
						'type' => 'select',
						'desc_tip' => __( 'Choose which format of email to send.', 'nm-gift-registry-crowdfunding' ),
						'class' => 'email_type wc-enhanced-select',
						'options' => nmgr()->settings()->get_email_type_options(),
						'default' => __( 'html', 'nm-gift-registry-crowdfunding' ),
					),
				)
			);
		}
		return $sections;
	}

	public static function add_free_contributions_emails( $sections, $tab ) {
		if ( is_nmgrcf_free_contributions_enabled() && 'emails' === $tab ) {
			$sections[ 'email_customer_new_free_contribution' ] = array(
				'title' => __( 'New free contribution', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Email the customer when a free contribution has been made to his wishlist. The order would have been marked as processing or completed at this time.', 'nm-gift-registry-crowdfunding' ),
				'is_customer_email' => true,
				'fields' => array(
					array(
						'id' => 'email_customer_new_free_contribution_enabled',
						'label' => __( 'Enable/Disable', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'default' => 1,
						'custom_attributes' => array(
							'disabled' => !nmgr_get_option( 'enable_free_contributions', 1 ) ? true : false,
						),
						'desc_tip' => __( 'This would be automatically disabled if the free contributions module is disabled.' ),
					),
					array(
						'id' => 'email_customer_new_free_contribution_checkout_message',
						'label' => __( 'Show checkout message', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'desc' => __( 'If a message was sent to the wishlist\'s owner at checkout during the order, show it in this email', 'nm-gift-registry-crowdfunding' ),
						'desc_tip' => __( 'This only works if the "messages" module is enabled.', 'nm-gift-registry-crowdfunding' ),
						'custom_attributes' => array(
							'disabled' => nmgr_get_option( 'enable_messages', 1 ) ? false : true,
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_free_contribution_subject',
						'label' => __( 'Subject', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}'
						),
						'placeholder' => sprintf(
							/* translators: %s: wishlist type title */
							__( '[{site_title}]: A free contribution has been made to your %s', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title() )
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_free_contribution_heading',
						'label' => __( 'Email heading', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}' ),
						'placeholder' => __( 'New Free Contribution: {wishlist_title}', 'nm-gift-registry-crowdfunding' ),
						'default' => '',
					),
					array(
						'id' => 'email_customer_new_free_contribution_email_type',
						'label' => __( 'Email type', 'nm-gift-registry-crowdfunding' ),
						'type' => 'select',
						'desc_tip' => __( 'Choose which format of email to send.', 'nm-gift-registry-crowdfunding' ),
						'class' => 'email_type wc-enhanced-select',
						'options' => nmgr()->settings()->get_email_type_options(),
						'default' => __( 'html', 'nm-gift-registry-crowdfunding' ),
					),
				)
			);

			$sections[ 'email_customer_refunded_free_contribution' ] = array(
				'title' => __( 'Refunded free contribution', 'nm-gift-registry-crowdfunding' ),
				'description' => __( 'Email the customer when free contributions made to his wishlist have been refunded.', 'nm-gift-registry-crowdfunding' ),
				'is_customer_email' => true,
				'fields' => array(
					array(
						'id' => 'email_customer_refunded_free_contribution_enabled',
						'label' => __( 'Enable/Disable', 'nm-gift-registry-crowdfunding' ),
						'type' => 'checkbox',
						'default' => 1,
						'custom_attributes' => array(
							'disabled' => !nmgr_get_option( 'enable_free_contributions', 1 ) ? true : false,
						),
						'desc_tip' => __( 'This would be automatically disabled if the free contributions module is disabled.', 'nm-gift-registry-crowdfunding' ),
					),
					array(
						'id' => 'email_customer_refunded_free_contribution_subject',
						'label' => __( 'Subject', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}'
						),
						'placeholder' => sprintf(
							/* translators: %s: wishlist type title */
							__( '[{site_title}]: %s free contribution refunded', 'nm-gift-registry-crowdfunding' ), esc_html( nmgr_get_type_title( 'cf' ) )
						),
						'default' => '',
					),
					array(
						'id' => 'email_customer_refunded_free_contribution_heading',
						'label' => __( 'Email heading', 'nm-gift-registry-crowdfunding' ),
						'type' => 'text',
						'desc_tip' => sprintf(
							/* translators: %s: Available placeholders for use */
							__( 'Available placeholders: %s.', 'nm-gift-registry-crowdfunding' ), '{site_title}, {wishlist_title}, {wishlist_type_title}' ),
						'placeholder' => __( 'Refunded free contribution: {wishlist_title}', 'nm-gift-registry-crowdfunding' ),
						'default' => '',
					),
					array(
						'id' => 'email_customer_refunded_free_contribution_email_type',
						'label' => __( 'Email type', 'nm-gift-registry-crowdfunding' ),
						'type' => 'select',
						'desc_tip' => __( 'Choose which format of email to send.', 'nm-gift-registry-crowdfunding' ),
						'class' => 'email_type wc-enhanced-select',
						'options' => nmgr()->settings()->get_email_type_options(),
						'default' => __( 'html', 'nm-gift-registry-crowdfunding' ),
					),
				)
			);
		}
		return $sections;
	}

}
