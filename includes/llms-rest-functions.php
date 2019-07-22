<?php
/**
 * REST functions
 *
 * @package LifterLMS_REST/Functions
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;


/**
 * Generate a keyed hash value using the HMAC method with the key `llms-rest-api`
 *
 * @since [version]
 *
 * @param string $data Message to be hashed.
 * @return string
 */
function llms_rest_api_hash( $data ) {
	return hash_hmac( 'sha256', $data, 'llms-rest-api' );
}

/**
 * Generate a random hash.
 *
 * @since [version]
 *
 * @return string
 */
function llms_rest_random_hash() {
	if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return sha1( wp_rand() );
	}
	return bin2hex( openssl_random_pseudo_bytes( 20 ) );
}
