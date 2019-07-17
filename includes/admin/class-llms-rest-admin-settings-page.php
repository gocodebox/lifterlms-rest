<?php
/**
 * Admin Settings Page: REST API
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Page: REST API
 *
 * @since [version]
 */
class LLMS_Rest_Admin_Settings_Page extends LLMS_Settings_Page {

	/**
	 * Constructor
	 *
	 * @since [version]
	 */
	public function __construct() {

		$this->id    = 'rest-api';
		$this->label = __( 'REST API', 'lifterlms' );

		add_filter( 'lifterlms_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'lifterlms_sections_' . $this->id, array( $this, 'output_sections_nav' ) );
		add_action( 'lifterlms_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'lifterlms_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Get the page sections
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_sections() {

		return apply_filters( 'llms_rest_api_settings_sections', array(
			'keys' => __( 'API Keys', 'lifterlms' ),
			'webhooks' => __( 'Webhooks', 'lifterlms' ),
		) );

	}

	/**
	 * Get settings array
	 *
	 * @since [version]
	 *
	 * @return   array
	 */
	public function get_settings() {

		$curr_section = $this->get_current_section();

		$settings = array();
		if ( 'keys' === $curr_section ) {
			$settings = $this->get_settings_keys();
		}

		return apply_filters( 'llms_rest_api_settings_' . $curr_section, $settings );

	}

	public function get_settings_keys() {

		return array();

	}

}

return new LLMS_Rest_Admin_Settings_Page();
