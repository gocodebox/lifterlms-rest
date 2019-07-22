<?php
/**
 * API Key Model.
 *
 * @package  LifterLMS_REST/Models
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_API_Key class..
 *
 * @since [version]
 */
class LLMS_REST_API_Key extends LLMS_Abstract_Database_Store {

	/**
	 * Date Created Field not implemented.
	 *
	 * @var null
	 */
	protected $date_created = null;

	/**
	 * Date Updated Field not implemented.
	 *
	 * @var null
	 */
	protected $date_updated = null;

	/**
	 * Array of table column name => format
	 *
	 * @var  array
	 */
	protected $columns = array(
		'user_id'         => '%d',
		'description'     => '%s',
		'consumer_key'    => '%s',
		'consumer_secret' => '%s',
		'truncated_key'   => '%s',
		'last_access'     => '%s',
	);

	/**
	 * Database Table Name
	 *
	 * @var  string
	 */
	protected $table = 'api_keys';

	/**
	 * The record type
	 *
	 * Used for filters/actions.
	 *
	 * @var  string
	 */
	protected $type = 'rest_api_key';

	/**
	 * Constructor
	 *
	 * @since [version]
	 *
	 * @param int  $id API Key ID.
	 * @param bool $hydrate If true, hydrates the object on instantiation if an ID is supplied.
	 */
	public function __construct( $id = null, $hydrate = true ) {

		$this->id = $id;
		if ( $this->id && $hydrate ) {
			$this->hydrate();
		}

	}

	/**
	 * Retrieve an admin nonce url for deleting an API key.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_delete_link() {

		return add_query_arg(
			array(
				'revoke-key'       => $this->get( 'id' ),
				'key-revoke-nonce' => wp_create_nonce( 'revoke' ),
			),
			LLMS_REST_API()->keys()->get_admin_url()
		);

	}


	/**
	 * Retrieve the admin URL where the api key is managed.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_edit_link() {
		return add_query_arg(
			array(
				'edit-key' => $this->get( 'id' ),
			),
			LLMS_REST_API()->keys()->get_admin_url()
		);
	}

	/**
	 * Retrieve a human-readable date/time string for the date the key was last used.
	 *
	 * Uses WP Core date & time formatting settings.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_last_access_date() {

		$date = __( 'None', 'lifterlms' );
		if ( ! empty( $this->get( 'last_access' ) ) ) {
			$time = strtotime( $this->get( 'last_access' ) );
			// Translators: %1$s: Last access date; %2$s: Last access time.
			$date = sprintf( __( '%1$s at %2$s', 'lifterlms' ), date_i18n( get_option( 'date_format' ), $time ), date_i18n( get_option( 'time_format' ), $time ) );
		}

		return apply_filters( 'llms_rest_api_key_get_last_access_date', $date, $this );

	}




}
