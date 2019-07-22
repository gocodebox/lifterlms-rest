<?php
/**
 * Handle admin form submissions.
 *
 * @package  LifterLMS_REST/Admin/Classes
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Admin_Form_Controller class..
 *
 * @since [version]
 */
class LLMS_REST_Admin_Form_Controller {

	/**
	 * Constructor.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'handle_events' ) );

	}

	/**
	 * Handles submission of admin forms & nonce links.
	 *
	 * @since [version]
	 *
	 * @return false|void
	 */
	public function handle_events() {

		if ( llms_verify_nonce( 'key-revoke-nonce', 'revoke', 'GET' ) ) {
			$delete = LLMS_REST_API()->keys()->delete( llms_filter_input( INPUT_GET, 'revoke-key', FILTER_VALIDATE_INT ) );
			if ( $delete ) {
				LLMS_Admin_Notices::flash_notice( esc_html__( 'The API Key has been successfully deleted.', 'lifterlms' ), 'success' );
				return llms_redirect_and_exit( admin_url( 'admin.php?page=llms-settings&tab=rest-api&section=keys' ) );
			}
		}

		return false;

	}

}

return new LLMS_REST_Admin_Form_Controller();
