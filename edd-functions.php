<?php

/**
 * Functions used by plugins
 */

if ( ! class_exists( 'Easy_Digital_Downloads_Dependencies' ) ) require_once 'class-edd-dependencies.php';

/**
 * Jigoshop detection
 *
 * @param string  $required_version Optionally force a Jigoshop version to be installed.
 * @return boolean                   Will deactivate the plugin or show an admin notice
 */

if ( ! function_exists( 'is_edd_active' ) ) {

	function is_edd_active( $required_version = '' ) {

		$Easy_Digital_Downloads_Dependencies = new Easy_Digital_Downloads_Dependencies;
		return $Easy_Digital_Downloads_Dependencies->edd_active_check( $required_version );

	}

}
