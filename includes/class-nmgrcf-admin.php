<?php
/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

/**
 * WordPress Admin actions related to plugin
 */
class NMGRCF_Admin {

	public static function run() {
		add_action( 'admin_footer', array( __CLASS__, 'make_add_items_dialog_width_large' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 30 );
		add_action( 'nmgr_post_submitbox_actions', array( __CLASS__, 'show_enable_free_contributions_field' ), 20 );
		add_action( 'nmgr_admin_before_save_post', array( __CLASS__, 'save_enable_free_contributions_field' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'show_close_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'remove_close_notice' ) );
	}

	public static function show_close_notice() {
		if ( get_option( 'nmgrcf_show_close_notice' ) ) {
			$notice = sprintf(
				/* translators: %s: pro version link */
				__( 'This free plugin is no longer being maintained and has been removed from the WordPress repository. Please <a href="%s" target="_blank">buy the pro version</a> to continue receiving updates and support.', 'nm-gift-registry-crowdfunding-lite' ),
				nmgrcf()->pro_version_link
			);
			?>
			<div class="notice-info notice is-dismissible">
				<p><strong><?php echo esc_html( nmgrcf()->name ); ?></strong></p>
				<p><?php echo wp_kses_post( $notice ); ?></p>
				<p>
					<a style="margin-right:15px;" class="button button-primary" target="_blank"
						 href="<?php echo esc_url( nmgrcf()->pro_version_link ); ?>">
						<strong><?php echo esc_html( 'Buy PRO', 'nm-gift-registry-crowdfunding-lite' ); ?></strong>
					</a>
					<a class="button" href="<?php echo esc_url( add_query_arg( 'nmgrcf_remove_notice', 1 ) ); ?>">
						&times; <?php echo esc_html( 'Don\'t show again', 'nm-gift-registry-crowdfunding-lite' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	public static function remove_close_notice() {
		if ( !empty( $_GET[ 'nmgrcf_remove_notice' ] ) ) {
			delete_option( 'nmgrcf_show_close_notice' );
		}
	}

	// Since we are adding the crowdfund column to the add items dialog, we make the width large to accommodate it
	public static function make_add_items_dialog_width_large() {
		if ( !is_nmgr_admin() ) {
			return;
		}

		if ( !is_nmgrcf_crowdfunding_enabled() ) {
			return;
		}
		?>
		<script>
			function nmgrcf_make_add_items_dialog_width_large() {
				var aibtn = document.querySelector('button.nmgr-add-items-action');
				if (aibtn) {
					aibtn.dataset.dialogWidth = '';
				}
			}
			nmgrcf_make_add_items_dialog_width_large();
			jQuery(document.body).on('nmgr_items_reloaded', function () {
				nmgrcf_make_add_items_dialog_width_large();
			});
		</script>
		<?php
	}

	public static function admin_css() {
		if ( !is_nmgr_admin() ) {
			return;
		}
		?>
		<style>
			#nm_gift_registry-crowdfunds.postbox .inside,
			#nm_gift_registry-free-contributions.postbox .inside {
				margin:0;
				padding:0;
			}
		</style>
		<?php
	}

	public static function add_meta_boxes() {
		global $post;

		if ( nmgrcf()->is_pro && is_nmgrcf_crowdfunding_enabled() ) {
			add_meta_box( 'nm_gift_registry-crowdfunds',
				nmgrcf()->is_pro ?
					__( 'Crowdfunds', 'nm-gift-registry-crowdfunding' ) :
					__( 'Crowdfunds', 'nm-gift-registry-crowdfunding-lite' ),
				array( 'NMGRCF_Admin', 'crowdfunds_metabox' ), 'nm_gift_registry', 'normal' );
		}

		if ( isset( $post->ID ) && nmgrcf_get_wishlist( $post->ID ) &&
			nmgrcf_get_wishlist( $post->ID )->is_free_contributions_enabled() ) {
			add_meta_box( 'nm_gift_registry-free-contributions',
				nmgrcf()->is_pro ?
					__( 'Free Contributions', 'nm-gift-registry-crowdfunding' ) :
					__( 'Free Contributions', 'nm-gift-registry-crowdfunding-lite' ),
				array( 'NMGRCF_Admin', 'free_contributions_metabox' ), 'nm_gift_registry', 'normal' );
		}

		if ( is_nmgrcf_wallet_enabled() ) {
			add_meta_box( 'nm_gift_registry-wallet',
				nmgrcf()->is_pro ?
					__( 'Wallet', 'nm-gift-registry-crowdfunding' ) :
					__( 'Wallet', 'nm-gift-registry-crowdfunding-lite' ),
				array( 'NMGRCF_Admin', 'wallet_metabox' ), 'nm_gift_registry', 'side' );
		}
	}

	public static function crowdfunds_metabox( $post ) {
		nmgrcf_get_crowdfunds_template( $post->ID, true );
	}

	public static function free_contributions_metabox( $post ) {
		nmgrcf_get_free_contributions_template( $post->ID, true );
	}

	public static function wallet_metabox( $post ) {
		$wishlist = nmgrcf_get_wishlist( $post );
		if ( $wishlist->is_wallet_transfer_enabled() ) {
			nmgrcf_get_wallet_template( $post->ID, true );
		} else {
			echo '<p class="nmgr-text-center nmgr-grey">' . sprintf(
				/* translators: %s: wishlist type title */
				__( 'Wallet transfer is not enabled for this %s.', 'nm-gift-registry-crowdfunding' ),
				nmgr_get_type_title()
			) . '</p>';
		}
	}

	public static function show_enable_free_contributions_field( $wishlist ) {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		$settings = nmgrcf_get_wishlist( $wishlist )->get_free_contributions_settings();

		$info_icon = nmgr_get_svg( array(
			'icon' => 'info',
			'class' => 'align-with-text nmgr-tip',
			'style' => 'margin-left:3px;',
			'title' => nmgrcf()->is_pro ?
			__( 'Allow contributors to send money to your wallet directly without attaching it to a product.', 'nm-gift-registry-crowdfunding' ) :
			__( 'Allow contributors to send money to your wallet directly without attaching it to a product.', 'nm-gift-registry-crowdfunding-lite' ),
			'fill' => 'gray',
			) );

		$checkbox_args = array(
			'input_id' => 'nmgrcf_enable_free_contributions',
			'input_name' => 'nmgrcf_enable_free_contributions',
			'label_text' => (nmgrcf()->is_pro ?
			__( 'Enable free contributions', 'nm-gift-registry-crowdfunding' ) :
			__( 'Enable free contributions', 'nm-gift-registry-crowdfunding-lite' )) . $info_icon,
			'checked' => filter_var( $settings[ 'enabled' ], FILTER_VALIDATE_BOOLEAN ),
		);

		echo '<div class="misc-pub-section">' . nmgr_get_checkbox_switch( $checkbox_args ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function save_enable_free_contributions_field( $posted_data, $post_id ) {
		if ( !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		$settings = nmgrcf_get_wishlist( $post_id )->get_free_contributions_settings();
		$settings[ 'enabled' ] = filter_input( INPUT_POST, 'nmgrcf_enable_free_contributions' ) ? 1 : 0;
		update_post_meta( $post_id, 'free_contributions_settings', $settings );
	}

}
