<?php
/**
 * Manage admin settings pages.
 *
 * @package  LifterLMS_REST/Admin/Classes
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage admin settings pages.
 *
 * @since [version]
 */
class LLMS_REST_Admin_Settings {

	/**
	 * Constructor.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function __construct() {

		add_filter( 'lifterlms_get_settings_pages', array( $this, 'add_pages' ) );

	}

	public function add_pages( $pages ) {

		$pages[] = include 'class-llms-rest-admin-settings-page.php';

		return $pages;

	}

}

return new LLMS_REST_Admin_Settings();
