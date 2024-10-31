<?php

/**
 * Plugin Name: NM Gift Registry and Wishlist - Crowdfunding
 * Description: Allows items in a wishlist or gift registry to be crowdfunded and funded via free contributions. Adds coupon features to the wishlist. <a href="https://nmerimedia.com/product-category/plugins/" target="_blank">See more plugins&hellip;</a>
 * Author: Nmeri Media
 * Author URI: https://nmerimedia.com
 * License: GPL V3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Version: 3.0.1
 * Domain Path: /languages
 * Requires at least: 4.7
 * Requires PHP: 7.0
 */
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'NMGRCF_LITE' ) ) :

	if ( !class_exists( 'NMGRCF' ) ) {
		include_once 'includes/class-nmgrcf.php';
	}

	if ( !trait_exists( 'NMGRCF_Instance' ) ) {
		include_once 'includes/trait-nmgrcf-instance.php';
	}

	class NMGRCF_LITE extends NMGRCF {

		use NMGRCF_Instance;

		public $requires_nmgr = '3.0.0';
		public $requires_nmgr_pro = '3.0.0';
		public $pro_version_link = 'https://nmerimedia.com/product/crowdfunding-nm-gift-registry-and-wishlist/';

	}

	/**
	 * @return NMGRCF_LITE
	 */
	function nmgrcf_lite() {
		$instance = NMGRCF_LITE::get_instance( __FILE__ );
		return $instance;
	}

	nmgrcf_lite()->init();

	if ( !function_exists( 'nmgrcf' ) ) :

		/**
		 * @return NMGRCF_LITE
		 */
		function nmgrcf() {
			return nmgrcf_lite();
		}

	endif;

endif;