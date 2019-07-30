<?php
/**
 * Admin Settings Page: REST API
 *
 * @package LifterLMS_REST/Admin/Classes
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
	 * Holds an LLMS_REST_API_Key instance when a new key is generated.
	 *
	 * Used to show consumer key & secret one time immediately following creation.
	 *
	 * @var null
	 */
	private $generated_key = null;

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

		add_filter( 'llms_settings_rest-api_has_save_button', '__return_false' );
		add_filter( 'llms_table_get_table_classes', array( $this, 'get_table_classes' ), 10, 2 );

		add_action( 'lifterlms_admin_field_title-with-html', array( $this, 'output_title_field' ), 10 );

	}

	/**
	 * Retrieve the id of the current tab/section
	 *
	 * Overrides parent function to set "keys" as the default section instead of the nonexistant "main".
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	protected function get_current_section() {

		$current = parent::get_current_section();
		if ( 'main' === $current ) {
			$all = array_keys( $this->get_sections() );
			$current = $all ? $all[0] : 'main';
		}
		return $current;

	}

	/**
	 * Get the page sections
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = array();

		if ( current_user_can( 'manage_lifterlms_api_keys' ) ) {
			$sections['keys'] = __( 'API Keys', 'lifterlms' );
		}

		$sections['webhooks'] = __( 'Webhooks', 'lifterlms' );

		/**
		 * Modify the available tabs on the REST API settings screen.
		 *
		 * @since [version]
		 *
		 * @param array $sections Array of settings page tabs.
		 */
		return apply_filters( 'llms_rest_api_settings_sections', $sections );

	}

	/**
	 * Get settings array
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_settings() {

		$curr_section = $this->get_current_section();

		$settings = array();
		if ( current_user_can( 'manage_lifterlms_api_keys' ) && 'keys' === $curr_section ) {
			$settings = $this->get_settings_keys();
		}

		return apply_filters( 'llms_rest_api_settings_' . $curr_section, $settings );

	}

	/**
	 * Get settings fields for the Keys tab.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_settings_keys() {

		require_once 'tables/class-llms-rest-table-api-keys.php';

		$add_key = '1' === llms_filter_input( INPUT_GET, 'add-key', FILTER_SANITIZE_NUMBER_INT );
		$key_id  = llms_filter_input( INPUT_GET, 'edit-key', FILTER_SANITIZE_NUMBER_INT );

		$settings = array();

		$settings[] = array(
			'class' => 'top',
			'id'    => 'rest_keys_options_start',
			'type'  => 'sectionstart',
		);

		$settings[] = array(
			'title' => $key_id || $add_key ? __( 'API Key Details', 'lifterlms' ) : __( 'API Keys', 'lifterlms' ),
			'type'  => 'title-with-html',
			'id'    => 'rest_keys_options_title',
			'html'  => $key_id || $add_key ? '' : '<a href="' . esc_url( admin_url( 'admin.php?page=llms-settings&tab=rest-api&section=keys&add-key=1' ) ) . '" class="llms-button-primary small" type="submit" style="top:-2px;">' . __( 'Add API Key', 'lifterlms' ) . '</a>',
		);

		if ( $add_key || $key_id ) {

			$key = $add_key ? false : new LLMS_REST_API_Key( $key_id );
			if ( $this->generated_key ) {
				$key = $this->generated_key;
			}
			if ( $add_key || $key->exists() ) {

				$user_id = $key ? $key->get( 'user_id' ) : get_current_user_id();

				$settings[] = array(
					'title' => __( 'Description', 'lifterlms' ),
					'desc'  => '<br>' . __( 'A friendly, human-readable, name used to identify the key.', 'lifterlms' ),
					'id'    => 'llms_rest_key_description',
					'type'  => 'text',
					'value' => $key ? $key->get( 'description' ) : '',
				);

				$settings[] = array(
					'title'             => __( 'User', 'lifterlms' ),
					'class'             => 'llms-select2-student',
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select a user', 'lifterlms' ),
					),
					'id'                => 'llms_rest_key_user_id',
					'options'           => llms_make_select2_student_array( array( $user_id ) ),
					'type'              => 'select',
				);

				$settings[] = array(
					'title'   => __( 'Permissions', 'lifterlms' ),
					'id'      => 'llms_rest_key_permissions',
					'type'    => 'select',
					'options' => LLMS_REST_API()->keys()->get_permissions(),
					'value'   => $key ? $key->get( 'permissions' ) : '',
				);

				if ( $key && ! $this->generated_key ) {

					$settings[] = array(
						'title'             => __( 'Consumer key ending in', 'lifterlms' ),
						'custom_attributes' => array(
							'readonly' => 'readonly',
						),
						'class'             => 'code',
						'id'                => 'llms_rest_key__read_only_key',
						'type'              => 'text',
						'value'             => '&hellip;' . $key->get( 'truncated_key' ),
					);

					$settings[] = array(
						'title'             => __( 'Last accessed at', 'lifterlms' ),
						'custom_attributes' => array(
							'readonly' => 'readonly',
						),
						'id'                => 'llms_rest_key__read_only_date',
						'type'              => 'text',
						'value'             => $key->get_last_access_date(),
					);

				} elseif ( $this->generated_key ) {

					$settings[] = array(
						'title'             => __( 'Consumer key', 'lifterlms' ),
						'custom_attributes' => array(
							'readonly' => 'readonly',
						),
						'css'               => 'width:400px',
						'class'             => 'code widefat',
						'id'                => 'llms_rest_key__read_only_key',
						'type'              => 'text',
						'value'             => $key->get( 'consumer_key_one_time' ),
					);

					$settings[] = array(
						'title'             => __( 'Consumer secret', 'lifterlms' ),
						'custom_attributes' => array(
							'readonly' => 'readonly',
						),
						'css'               => 'width:400px',
						'class'             => 'code widefat',
						'id'                => 'llms_rest_key__read_only_secret',
						'type'              => 'text',
						'value'             => $key->get( 'consumer_secret' ),
					);

				}

				$buttons = $this->generated_key ? '' : '<br><br><button class="llms-button-primary" type="submit" value="llms-rest-save-key">' . __( 'Save', 'lifterlms' ) . '</button>';
				if ( $key ) {
					$buttons .= $buttons ? '&nbsp;&nbsp;&nbsp;' : '<br><br>';
					$buttons .= '<a class="llms-button-danger" href="' . esc_url( $key->get_delete_link() ) . '">' . __( 'Revoke', 'lifterlms' ) . '</a>';
				}
				$buttons .= wp_nonce_field( 'lifterlms-settings', '_wpnonce', true, false );

				$settings[] = array(
					'type'  => 'custom-html',
					'id'    => 'llms_rest_key_buttons',
					'value' => $buttons,
				);

			} else {

				$settings[] = array(
					'id'    => 'rest_keys_options_invalid_error',
					'type'  => 'custom-html',
					'value' => __( 'Invalid api key.', 'lifterlms' ),
				);

			}
		} else {

			$settings[] = array(
				'id'    => 'llms_api_keys_table',
				'table' => new LLMS_REST_Table_API_Keys(),
				'type'  => 'table',
			);

		}

		$settings[] = array(
			'id'   => 'rest_keys_options_end',
			'type' => 'sectionend',
		);

		return $settings;

	}

	/**
	 * Add CSS classes to the API Keys Table.
	 *
	 * @since [version]
	 *
	 * @param string[] $classes Array of css class names.
	 * @param string   $id Table ID.
	 * @return string[]
	 */
	public function get_table_classes( $classes, $id ) {

		if ( 'rest-api-keys' === $id ) {
			$classes[] = 'text-left';
		}
		return $classes;

	}

	/**
	 * Outputs a custom "title" field with HTML content as the settings section title.
	 *
	 * @since [version]
	 *
	 * @param array $field Settings field arguments.
	 * @return void
	 */
	public function output_title_field( $field ) {

		echo '<p class="llms-label">' . esc_html( $field['title'] ) . ' ' . $field['html'] . '</p>';
		echo '<table class="form-table">';

	}

	/**
	 * Form handler to save Create / Update an API key.
	 *
	 * @since [version]
	 *
	 * @return null|LLMS_REST_API_Key|WP_Error
	 */
	public function save() {

		$ret = null;

		$key_id = llms_filter_input( INPUT_GET, 'edit-key', FILTER_SANITIZE_NUMBER_INT );
		if ( $key_id ) {
			$ret = $this->save_update( $key_id );
		} elseif ( llms_filter_input( INPUT_GET, 'add-key', FILTER_SANITIZE_NUMBER_INT ) ) {
			$ret = $this->save_create();
			if ( ! is_wp_error( $ret ) ) {
				$this->generated_key = $ret;
				LLMS_Admin_Settings::set_message( __( 'API Key generated. Make sure to copy the consumer key and consumer secret. After leaving this page they will not be displayed again.', 'lifterlms' ) );
			}
		}

		if ( is_wp_error( $ret ) ) {
			// Translators: %1$s = Error message; %2$s = Error code.
			LLMS_Admin_Settings::set_error( sprintf( __( 'Error: %1$s [Code: %2$s]', 'lifterlms' ), $ret->get_error_message(), $ret->get_error_code() ) );
		}

		return $ret;

	}

	/**
	 * Form handler to create a new API key.
	 *
	 * @since [version]
	 *
	 * @return LLMS_REST_API_Key|WP_Error
	 */
	protected function save_create() {

		$create = LLMS_REST_API()->keys()->create(
			array(
				'description' => llms_filter_input( INPUT_POST, 'llms_rest_key_description', FILTER_SANITIZE_STRING ),
				'user_id'     => llms_filter_input( INPUT_POST, 'llms_rest_key_user_id', FILTER_SANITIZE_NUMBER_INT ),
				'permissions' => llms_filter_input( INPUT_POST, 'llms_rest_key_permissions', FILTER_SANITIZE_STRING ),
			)
		);

		return $create;

	}

	/**
	 * Form handler to save an API key.
	 *
	 * @since [version]
	 *
	 * @param int $key_id API Key ID.
	 * @return LLMS_REST_API_Key|WP_Error
	 */
	protected function save_update( $key_id ) {

		$key = LLMS_REST_API()->keys()->get( $key_id );
		if ( ! $key ) {
			// Translators: %s = Invalid API Key ID.
			return new WP_Error( 'llms_rest_api_key_not_found', sprintf( __( '"%s" is not a valid API Key.', 'lifterlms' ), $key_id ) );
		}

		$update = LLMS_REST_API()->keys()->update(
			array(
				'id'          => $key_id,
				'description' => llms_filter_input( INPUT_POST, 'llms_rest_key_description', FILTER_SANITIZE_STRING ),
				'user_id'     => llms_filter_input( INPUT_POST, 'llms_rest_key_user_id', FILTER_SANITIZE_NUMBER_INT ),
				'permissions' => llms_filter_input( INPUT_POST, 'llms_rest_key_permissions', FILTER_SANITIZE_STRING ),
			)
		);

		return $update;

	}

}

return new LLMS_Rest_Admin_Settings_Page();
