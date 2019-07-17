<?php
/**
 * LifterLMS_REST_API main class.
 *
 * @package  LifterLMS_REST_API/Classes
 *
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS_REST_API class.
 *
 * @since 1.0.0
 */
final class LifterLMS_REST_API {

	/**
	 * Current version of the plugin.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Singleton instance of the class.
	 *
	 * @var     obj
	 */
	private static $_instance = null;

	/**
	 * Singleton Instance of the LifterLMS_REST_API class.
	 *
	 * @since 1.0.0
	 *
	 * @return obj instance of the LifterLMS_REST_API class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __construct() {

		if ( ! defined( 'LLMS_REST_API_VERSION' ) ) {
			define( 'LLMS_REST_API_VERSION', $this->version );
		}

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		// get started.
		add_action( 'plugins_loaded', array( $this, 'init' ), 10 );

	}



	/**
	 * Include files and instantiate classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function includes() {

		// Authentication needs to run early to handle basic auth.
		include_once dirname( __FILE__ ) . '/includes/class-llms-rest-authentication.php';
		include_once dirname( __FILE__ ) . '/includes/class-llms-rest-install.php';

		// Include admin classes.
		if ( is_admin() ) {
			include_once dirname( __FILE__ ) . '/includes/admin/class-llms-rest-admin-settings.php';
		}

		add_action( 'rest_api_init', array( $this, 'rest_api_includes' ), 5 );
		add_action( 'rest_api_init', array( $this, 'rest_api_controllers_init' ), 10 );

	}

	/**
	 * Include REST api specific files.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function rest_api_includes() {

		$includes = array(
			// Functions.
			'llms-rest-functions',

			// Abstracts first.
			'abstracts/class-llms-rest-posts-controller',

			'class-llms-rest-courses-controller',
			'class-llms-rest-sections-controller',
		);

		foreach ( $includes as $include ) {
			include_once LLMS_REST_API_PLUGIN_DIR . 'includes/' . $include . '.php';
		}
	}

	/**
	 * Instantiate REST api Controllers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function rest_api_controllers_init() {

		$controllers = array(
			'LLMS_REST_Courses_Controller',
			'LLMS_REST_Sections_Controller',
		);

		foreach ( $controllers as $controller ) {
			$controller_instance = new $controller();
			$controller_instance->register_routes();
		}

	}

	/**
	 * Include all required files and classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		// only load if we have the minimum LifterLMS version installed & activated.
		if ( function_exists( 'LLMS' ) && version_compare( '3.32.0', LLMS()->version, '<=' ) ) {

			// load includes.
			add_action( 'plugins_loaded', array( $this, 'includes' ), 100 );

		}

	}

	/**
	 * Load l10n files.
	 * The first loaded file takes priority.
	 *
	 * Files can be found in the following order:
	 *      WP_LANG_DIR/lifterlms/lifterlms-LOCALE.mo
	 *      WP_LANG_DIR/plugins/lifterlms-LOCALE.mo
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// load locale.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'lifterlms' );

		// load a lifterlms specific locale file if one exists.
		load_textdomain( 'lifterlms', WP_LANG_DIR . '/lifterlms/lifterlms-' . $locale . '.mo' );

		// load localization files.
		load_plugin_textdomain( 'lifterlms', false, dirname( plugin_basename( __FILE__ ) ) . '//i18n' );

	}

}
