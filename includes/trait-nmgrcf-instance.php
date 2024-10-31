<?php

/**
 * @sync
 */
defined( 'ABSPATH' ) || exit;

trait NMGRCF_Instance {

	private static $instance;

	public static function get_instance( $filepath = null ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $filepath );
		}
		return self::$instance;
	}

}
