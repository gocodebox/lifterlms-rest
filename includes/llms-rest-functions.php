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
 * Return a WP_Error with proper code, message and status for unauthorized requets.
 *
 * @since [version]
 *
 * @return WP_Error
 */
function llms_rest_authorization_required_error() {
	if ( is_user_logged_in() ) {
		// 403
		$error_code = 'llms_rest_forbidden_request';
		$message    = __( 'You are not authorized to perform this request.', 'lifterlms' );
	} else {
		// 400
		$error_code = 'llms_rest_unauthorized_request';
		$message    = __( 'The API credentials were invalid.', 'lifterlms' );
	}
	return new WP_Error( $error_code, $message, array( 'status' => rest_authorization_required_code() ) );
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

/**
 * Return a WP_Error with proper code, message and status for not found resources.
 *
 * @since [version]
 *
 * @return WP_Error
 */
function llms_rest_not_found_error() {
	// 404
	return new WP_Error( 'llms_rest_not_found', __( 'The requested resource could not be found.', 'lifterlms' ), array( 'status' => 404 ) );
}

/**
 * Return a WP_Error with proper code, message and status for invalid or malformed request syntax.
 *
 * @since [version]
 *
 * @return WP_Error
 */
function llms_rest_bad_request_error() {
	// 400
	return new WP_Error( 'llms_rest_bad_request', __( 'Invalid or malformed request syntax.', 'lifterlms' ), array( 'status' => 400 ) );
}
