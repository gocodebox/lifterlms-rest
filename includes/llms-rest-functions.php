<?php
/**
 * REST functions
 *
 * @package LLMS_REST
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

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
