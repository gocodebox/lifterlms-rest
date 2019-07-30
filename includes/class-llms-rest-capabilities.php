<?php
/**
 * Manage custom user capabilities.
 *
 * @package  LifterLMS_REST/Classes
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Capabilities class.
 *
 * @since [version]
 */
class LLMS_REST_Capabilities {

	/**
	 * Static Constructor.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public static function init() {

		add_filter( 'llms_get_administrator_core_caps', array( __CLASS__, 'add' ) );
		add_filter( 'llms_get_lms_manager_core_caps', array( __CLASS__, 'add' ) );

	}

	/**
	 * Add REST-specific capabilities to LifterLMS core cap lists.
	 *
	 * @since [version]
	 *
	 * @see LLMS_Roles::get_core_caps()
	 *
	 * @param array $caps Assoc. array of existing caps, array key is the capability and the value is a bool (true = has cap).
	 * @return array
	 */
	public static function add( $caps ) {
		$caps['manage_lifterlms_api_keys'] = true;
		return $caps;
	}

}

return LLMS_REST_Capabilities::init();
