<?php
/**
 * Testing Bootstrap
 *
 * @package LifterLMS_REST_API/Tests
 * @since   [version]
 * @version [version]
 */

require_once './vendor/lifterlms/lifterlms-tests/bootstrap.php';

class LLMS_REST_Tests_Bootstrap extends LLMS_Tests_Bootstrap {

	/**
	 * __FILE__ reference, should be defined in the extending class
	 *
	 * @var [type]
	 */
	public $file = __FILE__;

	/**
	 * Name of the testing suite
	 *
	 * @var string
	 */
	public $suite_name = 'LifterLMS REST API';

	/**
	 * Main PHP File for the plugin
	 *
	 * @var string
	 */
	public $plugin_main = 'lifterlms-rest.php';

}

return new LLMS_REST_Tests_Bootstrap();
