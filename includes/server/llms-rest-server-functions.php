<?php
/**
 * REST Server functions
 *
 * @package LifterLMS_REST/Functions
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.12
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return a WP_Error with proper code, message and status for unauthorized requests.
 *
 * @since 1.0.0-beta.1
 * @since 1.0.0-beta.12 Added a second paramater to avoid checking if the user is logged in.
 *
 * @param string  $message             Optional. The custom error message. Default empty string.
 *                                     When no custom message is provided a predefined message will be used.
 * @param boolean $check_authenticated Optional. Whether or not checking if the current user is logged in. Default `true`.
 * @return WP_Error
 */
function llms_rest_authorization_required_error( $message = '', $check_authenticated = true ) {
	if ( $check_authenticated && is_user_logged_in() ) {
		// 403.
		$error_code = 'llms_rest_forbidden_request';
		$_message   = __( 'You are not authorized to perform this request.', 'lifterlms' );
		$status     = '403';
	} else {
		// 401.
		$error_code = 'llms_rest_unauthorized_request';
		$_message   = __( 'The API credentials were invalid.', 'lifterlms' );
		$status     = '401';
	}

	$message = ! $message ? $_message : $message;
	return new WP_Error( $error_code, $message, array( 'status' => $status ) );
}

/**
 * Return a WP_Error with proper code, message and status for invalid or malformed request syntax.
 *
 * @since 1.0.0-beta.1
 *
 * @param string $message Optional. The custom error message. Default empty string.
 *                        When no custom message is provided a predefined message will be used.
 * @return WP_Error
 */
function llms_rest_bad_request_error( $message = '' ) {
	$message = ! $message ? __( 'Invalid or malformed request syntax.', 'lifterlms' ) : $message;
	return new WP_Error( 'llms_rest_bad_request', $message, array( 'status' => 400 ) );
}

/**
 * Return a WP_Error with proper code, message and status for not found resources.
 *
 * @since 1.0.0-beta.1
 *
 * @param string $message Optional. The custom error message. Default empty string.
 *                        When no custom message is provided a predefined message will be used.
 * @return WP_Error
 */
function llms_rest_not_found_error( $message = '' ) {
	$message = ! $message ? __( 'The requested resource could not be found.', 'lifterlms' ) : $message;
	return new WP_Error( 'llms_rest_not_found', $message, array( 'status' => 404 ) );
}

/**
 * Return a WP_Error for a 500 Internal Server Error.
 *
 * @since 1.0.0-beta.1
 *
 * @param string $message Optional. Custom error message. When none provided a predefined message is used.
 * @return WP_Error
 */
function llms_rest_server_error( $message = '' ) {
	$message = ! $message ? __( 'Internal Server Error.', 'lifterlms' ) : $message;
	return new WP_Error( 'llms_rest_server_error', $message, array( 'status' => 500 ) );
}

/**
 * Validate submitted array of integers is an array of real user ids
 *
 * @since 1.0.0-beta.9
 *
 * @param array $instructors Array of instructors id.
 * @return boolean
 */
function llms_validate_instructors( $instructors ) {
	return ! empty( $instructors ) ? count( array_filter( array_map( 'get_userdata', $instructors ) ) ) === count( $instructors ) : false;
}

/**
 * Validate strict positive integer number
 *
 * @since [version]
 *
 * @param integer $number Integer number to validate.
 * @return boolean
 */
function llms_rest_validate_strictly_positive_int( $number ) {
	return llms_rest_validate_positive_int( $number, false );
}

/**
 * Validate positive integer number including zero
 *
 * @since [version]
 *
 * @param integer $number Integer number to validate.
 * @return boolean
 */
function llms_rest_validate_positive_int_w_zero( $number ) {
	return llms_rest_validate_positive_int( $number );
}


/**
 * Validate positive integer number
 *
 * @since [version]
 *
 * @param integer $number Integer number to validate.
 * @param boolean $include_zero Optional. Whether or not 0 is included. Default is `true`.
 * @return boolean
 */
function llms_rest_validate_positive_int( $number, $include_zero = true ) {
	return false !== filter_var(
		$number,
		FILTER_VALIDATE_INT,
		array(
			'options' => array(
				'min_range' => $include_zero ? 0 : 1,
			),
		)
	);
}

/**
 * Validate strict positive float number
 *
 * @since [version]
 *
 * @param integer $number Float number to validate.
 * @return boolean
 */
function llms_rest_validate_strictly_positive_float( $number ) {
	return llms_rest_validate_positive_float( $number, false );
}

/**
 * Validate strict positive float number including zero
 *
 * @since [version]
 *
 * @param integer $number Float number to validate.
 * @return boolean
 */
function llms_rest_validate_positive_float_w_zero( $number ) {
	return llms_rest_validate_positive_float( $number );
}

/**
 * Validate strict positive float number
 *
 * @since [versoin]
 *
 * @param integer $number Float number to validate.
 * @param boolean $include_zero Optional. Whether or not 0 is included. Default is `true`.
 * @return boolean
 */
function llms_rest_validate_positive_float( $number, $include_zero = true ) {
	// min_range and max_range options for FILTER_VALIDATE_FLOAT are only available since PHP 7.4.
	$is_float = false !== filter_var( (float) $number, FILTER_VALIDATE_FLOAT );
	return $is_float && ( $include_zero ? $number >= 0 : $number > 0 );
}


/**
 * Validate submitted array of integers is an array of real memberships id, or empty.
 *
 * @since [version]
 *
 * @param array $memberships Array of instructors id.
 * @return boolean
 */
function llms_rest_validate_memberships( $memberships ) {

	$valid = true;

	if ( ! empty( $memberships ) ) {
		$real_memberships = array_map(
			function( $membership ) {
				return is_a( llms_get_post( $membership ), 'LLMS_Membership' );
			},
			$memberships
		);

		$valid = count( array_filter( $real_memberships ) ) === count( $memberships );
	}

	return $valid;
}
