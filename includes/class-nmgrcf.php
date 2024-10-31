<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

class NMGRCF {

	public $name;
	public $version;
	public $url;
	public $path;
	public $is_pro;
	public $requires_nmgr;
	public $requires_nmgr_pro;

	/**
	 * Plugin root filepath e.g /www/html/wordpress/wp-content/plugins/nm-plugin/nm-plugin.php
	 */
	public $file;

	/**
	 * Slug of plugin root file slug e.g nm-plugin
	 */
	public $slug;

	/**
	 * Basename of plugin root file e.g. nm-plugin/nm-plugin.php
	 */
	public $basename;

	/**
	 * Plugin base e.g nm_plugin
	 * Usually taken from plugin root file
	 */
	public $base;

	/**
	 * Notices to be shown
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Plugin root filepath e.g /www/html/wordpress/wp-content/plugins/nm-plugin/nm-plugin.php
	 */
	public function __construct( $filepath = null ) {
		$this->set_plugin_props( $filepath ?? __FILE__  );
	}

	public function set_plugin_props( $filepath ) {
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $filepath );

		$this->name = $plugin_data[ 'Name' ];
		$this->version = $plugin_data[ 'Version' ];
		$this->file = $filepath;
		$this->url = plugin_dir_url( $filepath );
		$this->path = plugin_dir_path( $filepath );
		$this->slug = pathinfo( $filepath, PATHINFO_FILENAME );
		$this->basename = plugin_basename( $filepath );
		$this->base = str_replace( '-', '_', $this->slug );
	}

	public function init() {
		spl_autoload_register( array( $this, 'autoload' ) );

		add_action( 'nmgr_plugin_loaded', array( $this, 'maybe_install_and_run' ) ); // plugins_loaded hook
		add_action( 'nmgrlite_plugin_loaded', array( $this, 'maybe_install_and_run' ) ); // plugins_loaded hook
		add_action( 'init', array( $this, 'show_inactive_notice' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		register_uninstall_hook( $this->file, array( __CLASS__, 'uninstall' ) );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->slug, false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}

	public function autoload( $class ) {
		if ( class_exists( $class ) || false === stripos( $class, 'nmgrcf_' ) ) {
			return;
		}

		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$classes_path = realpath( dirname( $this->file ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
		$filepath = $classes_path . $file;

		if ( file_exists( $filepath ) ) {
			include_once $filepath;
		}
	}

	public function show_inactive_notice() {
		if ( !function_exists( 'nmgr_get_option' ) ) {
			add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'inactive_plugin_action_links' ) );
		}
	}

	public function install_actions() {
		global $wpdb;

		add_option( 'nmgrcf_show_close_notice', 1 );

		/**
		 * Crowdfund settings have been added to NM Gift Registry default settings
		 * so we merge these into the already stored plugin setting in the database
		 */
		$defaults = nmgr()->settings()->get_default_field_values();
		$existing_settings = get_option( 'nmgr_settings' );
		update_option( 'nmgr_settings', array_merge( $defaults, $existing_settings ) );

		/**
		 * Update the placeholder product in versions prior to 2.1.0 to be non-taxable
		 * (Remove this code in version 2.1.0+)
		 */
		if ( version_compare( get_option( 'nmgrcf_version' ), '2.1.0', '<' ) ) {
			$placeholder_product_id = get_option( 'nmgrcf_product_id' );

			if ( $placeholder_product_id ) {
				$product = wc_get_product( $placeholder_product_id );
				if ( $product ) {
					$product->set_tax_status( 'none' );
					$product->save();
				}
			}
		}

		/**
		 * Add new meta keys to replace nmgrcf_credited_to_wallet and nmgrcf_debited_from_wallet
		 * and delete the old ones
		 */
		if ( version_compare( get_option( 'nmgrcf_version' ), '2.2.0', '<' ) ) {
			$table = $wpdb->prefix . 'nmgr_wishlist_itemmeta';

			$results = $wpdb->get_results( "SELECT wishlist_item_id, meta_key, meta_value FROM {$wpdb->prefix}nmgr_wishlist_itemmeta WHERE meta_key IN ('nmgrcf_credited_to_wallet', 'nmgrcf_debited_from_wallet')" );

			foreach ( $results as $res ) {
				if ( 'nmgrcf_credited_to_wallet' == $res->meta_key ) {
					$meta_key = 'nmgrcf_credits_to_wallet';
				} else {
					$meta_key = 'nmgrcf_debits_from_wallet';
				}
				$wpdb->insert(
					$table,
					array(
						'wishlist_item_id' => $res->wishlist_item_id,
						'meta_key' => $meta_key,
						'meta_value' => serialize( array( $res->meta_value ) )
					),
					array(
						'%d',
						'%s',
						'%s'
					)
				);
			}

			$wpdb->delete( $table, array( 'meta_key' => 'nmgrcf_debited_from_wallet' ) );
			$wpdb->delete( $table, array( 'meta_key' => 'nmgrcf_credited_to_wallet' ) );
		}


		update_option( 'nmgrcf_version', $this->version );
		do_action( 'nmgrcf_installed' ); // Occurs on init hook (after activation or during version update)
	}

	public function maybe_install_and_run() {
		if ( $this->maybe_deactivate_plugin() ) {
			$this->do_plugin_deactivation();
			return;
		}

		// Install plugin
		if ( version_compare( get_option( 'nmgrcf_version' ), $this->version, '<' ) ) {
			add_action( 'init', array( $this, 'install_actions' ) );
		}

		// Run plugin
		require_once $this->path . 'includes/functions.php';

		if ( file_exists( $this->path . 'includes/functions-pro.php' ) ) {
			require_once $this->path . 'includes/functions-pro.php';
		}

		/**
		 * These actions have to be present when the plugin is activated even if crowdfunding is disabled as they show
		 * the settings for enabling it, and other features. So we put them before the check to see if crowdfunding is enabled
		 */
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'nmgr_packages', array( $this, 'add_crowdfund_plugin_to_nmgr_packages' ) );

		NMGRCF_Settings::run();

		if ( !is_nmgrcf_crowdfunding_enabled() && !is_nmgrcf_free_contributions_enabled() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'include_sprite_file' ) );
		add_action( 'admin_footer', array( $this, 'include_sprite_file' ) );
		add_filter( 'nmgr_responsive_tables', array( $this, 'make_tables_responsive' ) );
		add_filter( 'nmgr_datatables', array( $this, 'make_tables_datatables' ) );
		add_action( 'wp', array( $this, 'add_shortcodes' ) );
		add_filter( 'nmgr_get_template_args', array( $this, 'get_crowdfund_template_args' ) );
		add_filter( 'nmgr_get_wishlist', array( $this, 'get_wishlist' ), 10, 3 );
		add_filter( 'nmgr_get_wishlist_item', array( $this, 'get_wishlist_item' ), 10, 2 );

		$classes = array(
			NMGRCF_Admin::class,
			NMGRCF_Coupon::class,
			NMGRCF_Add_To_Wishlist::class,
			NMGRCF_Item_Table::class,
			NMGRCF_Wallet::class,
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'run' ) ) {
				$class::run();
			}
		}

		$module_classes = array(
			NMGRCF_Cart::class,
			NMGRCF_Order::class,
			NMGRCF_Templates::class,
		);

		$modules = array( 'Crowdfund', 'Free_Contribution' );

		foreach ( $module_classes as $class ) {
			foreach ( $modules as $module ) {
				$the_class = $class . '_' . $module;
				if ( class_exists( $the_class ) && method_exists( $the_class, 'run' ) ) {
					$the_class::run();
				}
			}
		}

		do_action( 'nmgrcf_plugin_loaded' );
	}

	public function enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? date( 'H:i:s' ) : $this->version;
		$style_deps = is_admin() ? array( 'nmgr-admin' ) : array( 'nmgr-frontend' );
		$script_deps = is_admin() ? array( 'nmgr-admin' ) : array( 'nmgr-frontend' );

		wp_enqueue_style( 'nmgrcf',
			$this->url . 'assets/css/style' . $suffix . '.css',
			$style_deps,
			$version
		);

		wp_enqueue_script( 'nmgrcf',
			$this->url . 'assets/js/script' . $suffix . '.js',
			$script_deps,
			$version,
			true
		);
	}

	public function include_sprite_file() {
		$sprite_file = $this->path . 'assets/svg/sprite.svg';
		if ( file_exists( $sprite_file ) ) {
			include_once $sprite_file;
		}
	}

	public function register_post_status() {
		register_post_status( 'nmgr-crowdfunded', array(
			'label' => nmgrcf()->is_pro ?
				_x( 'NM Gift Registry crowdfunded', 'Product post status', 'nm-gift-registry-crowdfunding' ) :
				_x( 'NM Gift Registry crowdfunded', 'Product post status', 'nm-gift-registry-crowdfunding-lite' ),
			'public' => false,
			'internal' => true,
			/* translators: %s: number of items */
			'label_count' => nmgrcf()->is_pro ? _n_noop( 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'nm-gift-registry-crowdfunding' ) : _n_noop( 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'NM Gift Registry crowdfunded <span class="count">(%s)</span>', 'nm-gift-registry-crowdfunding-lite' ),
		) );
	}

	public static function uninstall() {
		global $wpdb;

		$nmgr_settings = get_option( 'nmgr_settings' );

		if ( !empty( $nmgr_settings ) ) {
			$options = array(
				'enable_crowdfunding'
			);
			foreach ( $options as $option ) {
				if ( isset( $nmgr_settings[ $option ] ) ) {
					unset( $nmgr_settings[ $option ] );
				}
			}
			update_option( 'nmgr_settings', $nmgr_settings );
		}

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'nmgr-crowdfunded';" );
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'nmgrcf\_%';" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'nmgrcf\_%';" );

		/**
		 * Before we delete from nm gift registry tables we have to make sure they exists.
		 * A quick way to do this is to check if the plugin settings exists in the database.
		 * If it does, then the plugin tables also exist so we can delete from them.
		 * This prevents showing an error if the tables don't exist like when the full version
		 * of the plugin is deleted at the same time with the crowdfunding extension.
		 */
		if ( !empty( $nmgr_settings ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}nmgr_wishlist_itemmeta WHERE meta_key LIKE 'nmgrcf\_%';" );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}nmgr_wishlist_itemmeta WHERE meta_key LIKE 'crowdfund\_%';" );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}nmgr_wishlist_itemmeta WHERE meta_key = 'nmgrcf_crowdfunded';" );
		}
	}

	public function inactive_plugin_action_links( $links ) {
		$notice = nmgrcf()->is_pro ?
			__( 'This plugin requires the NM Gift Registry and Wishlist plugin to be activated for it to run.', 'nm-gift-registry-crowdfunding' ) :
			__( 'This plugin requires the NM Gift Registry and Wishlist plugin to be activated for it to run.', 'nm-gift-registry-crowdfunding-lite' );

		return array_merge( $links, array(
			'<span style="color:#ffb900; cursor:pointer;" onclick="alert(\'' . $notice . '\');">' .
			(nmgrcf()->is_pro ?
			__( 'Not active *', 'nm-gift-registry-crowdfunding' ) :
			__( 'Not active *', 'nm-gift-registry-crowdfunding-lite' )
			) .
			'</span>'
			) );
	}

	public function make_tables_responsive( $tables ) {
		$tables[ 'crowdfunds_table' ] = array(
			'selector' => '.nmgrcf-crowdfunds-table',
			'events' => array(
				'nmgrcf_crowdfunds_reloaded'
			),
		);
		$tables[ 'free_contributions_table' ] = array(
			'selector' => '.nmgrcf-free-contributions-table',
			'events' => array(
				'nmgrcf_free_contributions_reloaded'
			),
		);

		$tables[ 'wallet_log_table' ] = array(
			'selector' => '.nmgrcf-wallet-log-table',
			'events' => array(
				array(
					'selector' => '#nmgr-mago',
					'event' => 'shown.bs.modal',
				),
			),
		);

		$tables[ 'coupons_table' ] = array(
			'selector' => '.nmgrcf-coupons-table',
			'events' => array(
				'nmgr_items_reloaded'
			)
		);

		return $tables;
	}

	public function make_tables_datatables( $tables ) {
		$default_options = nmgr_get_default_datatable_options();

		$tables[ 'nmgrcf_crowdfunds_table' ] = array(
			'selector' => '.nmgrcf-crowdfunds-table',
			'events' => array( 'nmgrcf_crowdfunds_reloaded' ),
			'options' => $default_options,
		);
		$tables[ 'nmgrcf_free_contributions_table' ] = array(
			'selector' => '.nmgrcf-free-contributions-table',
			'events' => array( 'nmgrcf_free_contributions_reloaded' ),
			'options' => $default_options,
		);

		$tables[ 'nmgrcf_coupons_table' ] = array(
			'selector' => '.nmgrcf-coupons-table',
			'events' => array( 'nmgr_items_reloaded' ),
			'options' => $default_options,
		);

		$tables[ 'nmgrcf_wallet_log_table' ] = array(
			'selector' => '.nmgrcf-wallet-log-table',
			'events' => array(
				array(
					'selector' => '#nmgr-mago',
					'event' => 'shown.bs.modal',
				),
			),
			'options' => $default_options,
		);

		return $tables;
	}

	public function plugin_action_links( $links ) {
		$url = add_query_arg( array(
			'post_type' => 'nm_gift_registry',
			'page' => 'nmgr-settings',
			'tab' => 'modules',
			'section' => 'crowdfunding',
			), admin_url( 'edit.php' ) );

		return array_merge( $links, array(
			'<a href="' . $url . '">' .
			(nmgrcf()->is_pro ?
			__( 'Settings', 'nm-gift-registry-crowdfunding' ) :
			__( 'Settings', 'nm-gift-registry-crowdfunding-lite' )
			) .
			'</a>'
			) );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( $file == $this->basename ) {
			$links[] = '<a href="https://docs.nmerimedia.com/doc/crowdfunding-nm-gift-registry-and-wishlist/">' .
				(nmgrcf()->is_pro ?
				__( 'Docs', 'nm-gift-registry-crowdfunding' ) :
				__( 'Docs', 'nm-gift-registry-crowdfunding-lite' )
				) . '</a>';

			if ( !$this->is_pro ) {
				$links[] = '<a href="https://nmerimedia.com/product/crowdfunding-nm-gift-registry-and-wishlist" style="color:#b71401;"><strong>' . (nmgrcf()->is_pro ?
					__( 'Get PRO', 'nm-gift-registry-crowdfunding' ) :
					__( 'Get PRO', 'nm-gift-registry-crowdfunding-lite' )
					) . '</strong></a>';
			}
		}
		return $links;
	}

	public function add_crowdfund_plugin_to_nmgr_packages( $packages ) {
		if ( $this->is_pro ) {
			$packages[] = $this->basename;
		}
		return $packages;
	}

	public function add_shortcodes() {
		$shortcodes = array(
			'nmgrcf_get_crowdfunds_template' => 'nmgrcf_crowdfunds',
			'nmgrcf_get_free_contributions_template' => 'nmgrcf_free_contributions',
		);

		foreach ( $shortcodes as $function => $shortcode ) {
			if ( function_exists( $function ) ) {
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}
		}
	}

	public function maybe_deactivate_plugin() {
		$continue = true;

		if ( version_compare( nmgr()->version, '4.0.0', '>=' ) ) {
			$this->notices[] = sprintf(
				/* translators : 1,3 wishlist type title, 2: pro version link */
				__( 'This free version of the plugin only works with %1$s versions <strong>less than 4.0.0</strong>. <a href="%2$s" target="_blank">Please upgrade to the pro version</a> to work with %3$s versions <strong>from 4.0.0</strong>.', 'nm-gift-registry-crowdfunding-lite' ),
				'<strong>' . nmgr()->name . '</strong>',
				nmgrcf()->pro_version_link,
				'<strong>' . nmgr()->name . '</strong>'
			);
			$continue = false;
		}

		if ( $continue && $this->is_pro && !nmgr()->is_pro ) {
			$this->notices[] = sprintf(
				/* translators : %s wishlist type title */
				__( 'This version of the plugin only works with the PRO version of %s', 'nm-gift-registry-crowdfunding' ),
				'<strong>' . nmgr()->name . '</strong>'
			);
			$continue = false;
		}

		if ( $continue && !$this->is_pro && class_exists( 'NMGRCF_PRO' ) ) {
			$this->notices[] = nmgrcf()->is_pro ?
				__( 'The lite version of the plugin has been deactivated as the pro version is active.', 'nm-gift-registry-crowdfunding' ) :
				__( 'The lite version of the plugin has been deactivated as the pro version is active.', 'nm-gift-registry-crowdfunding-lite' );
		}

		$requires_nmgr = nmgr()->is_pro ? $this->requires_nmgr_pro : $this->requires_nmgr;

		if ( $continue && version_compare( nmgr()->version, $requires_nmgr, '<' ) ) {
			$this->notices[] = sprintf(
				/* translators:
				 * 1: plugin name,
				 * 2: NM Gift Registry plugin name,
				 * 3: required NM Gift Registry version,
				 * 4: current NM Gift Registry version
				 */
				nmgrcf()->is_pro ? __( '%1$s needs %2$s %3$s or higher to work. You have version %4$s. Please update it.', 'nm-gift-registry-crowdfunding' ) : __( '%1$s needs %2$s %3$s or higher to work. You have version %4$s. Please update it.', 'nm-gift-registry-crowdfunding-lite' ),
				$this->name,
				'<strong>' . nmgr()->name . '</strong>',
				'<strong>' . $requires_nmgr . '</strong>',
				'<strong>' . nmgr()->version . '</strong>'
			);
		}

		return !empty( $this->notices ) ? true : false;
	}

	public function do_plugin_deactivation() {
		add_action( 'admin_init', array( $this, 'deactivate_plugin' ) );
		add_action( 'admin_notices', array( $this, 'show_deactivation_notice' ) );
	}

	public function deactivate_plugin() {
		deactivate_plugins( $this->basename );
		// phpcs:disable
		if ( isset( $_GET[ 'activate' ] ) ) {
			unset( $_GET[ 'activate' ] );
		}
		// phpcs:enable
	}

	public function show_deactivation_notice() {
		$header = sprintf(
			/* translators: %s: plugin name */
			nmgrcf()->is_pro ? __( '%s deactivated', 'nm-gift-registry-crowdfunding' ) : __( '%s deactivated', 'nm-gift-registry-crowdfunding-lite' ),
			$this->name
		);
		$message = '';

		foreach ( $this->notices as $notice ) {
			$message .= "<p>- $notice</p>";
		}

		printf( '<div class="notice notice-error"><p><strong>%1$s</strong></p>%2$s</div>', esc_html( $header ), wp_kses_post( $message ) );
	}

	/**
	 * Modify the arguments used to get original nm_gift_registry plugin templates
	 */
	public function get_crowdfund_template_args( $args ) {
		if ( isset( $args[ 'args' ][ 'is_crowdfund_template' ] ) ) {
			$args[ 'template_path' ] = 'nm-gift-registry-crowdfunding/';
			$args[ 'default_path' ] = nmgrcf()->path . 'templates/';
		}
		return $args;
	}

	public function get_wishlist( $val, $wishlist_id, $active ) {
		return nmgr_get_option( 'enable_crowdfunding', 1 ) ? nmgrcf_get_wishlist( $wishlist_id, $active ) : $val;
	}

	public function get_wishlist_item( $val, $item_id ) {
		return nmgr_get_option( 'enable_crowdfunding', 1 ) ? nmgrcf_get_item( $item_id ) : $val;
	}

}
