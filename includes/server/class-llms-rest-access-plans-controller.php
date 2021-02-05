<?php
/**
 * REST Access Plans Controller
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Access_Plans_Controller class
 *
 * @since [version]
 */
class LLMS_REST_Access_Plans_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = 'llms_access_plan';

	/**
	 * Route base
	 *
	 * @var string
	 */
	protected $rest_base = 'access-plans';

	/**
	 * Get the Access Plan's schema, conforming to JSON Schema
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// Post properties to unset.
		$properties_to_unset = array(
			'comment_status',
			'excerpt',
			'featured_media',
			'password',
			'permalink',
			'ping_status',
			'post_type',
			'slug',
			'status',
		);

		foreach ( $properties_to_unset as $to_unset ) {
			unset( $schema['properties'][ $to_unset ] );
		}

		// The content is not required.
		unset( $schema['properties']['content']['required'] );

		$access_plan_properties = array(
			'price'                     => array(
				'description' => __(
					'Access plan price.',
					'lifterlms'
				),
				'type'        => 'number',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_positive_float_w_zero',
				),
			),
			'access_expiration'         => array(
				'description' => __(
					'Access expiration type.
					`lifetime` provides access until cancelled or until a recurring payment fails.
					`limited-period` provides access for a limited period as specified by `access_length`
					and `access_period` `limited-date` provides access until the date specified by access_expires_date`.',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'lifetime',
				'enum'        => array(
					'lifetime',
					'limited-period',
					'limited-date',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'access_expires'            => array(
				'description' => __(
					'Date when access expires.
					Only applicable when `access_expiration` is `limited-date`. `Format: Y-m-d H:i:s`.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'access_length'             => array(
				'description' => __(
					'Determine the length of access from time of purchase.
					Only applicable when `access_expiration` is `limited-period`.',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'default'     => 1,
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_strictly_positive_int',
					'sanitize_callback' => 'absint',
				),
			),
			'access_period'             => array(
				'description' => __(
					'Determine the length of access from time of purchase.
					Only applicable when `access_expiration` is `limited-period`',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'year',
				'enum'        => array_keys( llms_get_access_plan_period_options() ),
				'context'     => array( 'view', 'edit' ),
			),
			'availability_restrictions' => array(
				'description' => __(
					'Restrict usage of this access plan to students enrolled in at least one of the specified memberships.',
					'lifterlms'
				),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_memberships',
				),

			),
			'enroll_text'               => array(
				'description' => __(
					'Text of the "Purchase" button',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => __( 'Buy Now', 'lifterlms' ),
				'context'     => array( 'view', 'edit' ),
			),
			'frequency'                 => array(
				'description' => __(
					'Billing frequency [0-6].
					`0` denotes a one-time payment.
					`>= 1` denotes a recurring plan.',
					'lifterlms'
				),
				'type'        => 'integer',
				'default'     => 0,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => static function ( $val ) {
						return in_array( $val, range( 0, 6 ), true ) ? true : new WP_Error(
							'rest_invalid_param',
							__( 'Must be an integer in the range 0-6', 'lifterlms' )
						);
					},
					'sanitize_callback' => 'absint',
				),
			),
			'length'                    => array(
				'description' => __(
					'For recurring plans only.
					Determines the number of intervals a plan should run for.
					`0` denotes the plan should run until cancelled.',
					'lifterlms'
				),
				'type'        => 'integer',
				'default'     => 0,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'period'                    => array(
				'description' => __(
					'For recurring plans only.
					Determines the interval of recurring payments.',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'year',
				'enum'        => array_keys( llms_get_access_plan_period_options() ),
				'context'     => array( 'view', 'edit' ),
			),
			'post_id'                   => array(
				'description' => __(
					'Determines the course or membership which can be accessed through the plan.',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_strictly_positive_int',
					'sanitize_callback' => 'absint',
				),
			),
			'redirect_forced'           => array(
				'description' => __(
					"Use this plans's redirect settings when purchasing a Membership this plan is restricted to.
					Applicable only when `availability_restrictions` exist for the plan",
					'lifterlms'
				),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'redirect_page'             => array(
				'description' => __(
					'WordPress page ID to use for checkout success redirection.
					Applicable only when `redirect_type` is page.',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_strictly_positive_int',
					'sanitize_callback' => 'absint',
				),
			),
			'redirect_type'             => array(
				'description' => __(
					"Determines the redirection behavior of the user's browser upon successful checkout or registration through the plan.
					`self`: Redirect to the permalink of the specified `post_id`.
					`page`: Redirect to the permalink of the WordPress page specified by `redirect_page_id`.
					`url`: Redirect to the URL specified by `redirect_url`.",
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'self',
				'enum'        => array(
					'self',
					'page',
					'url',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'redirect_url'              => array(
				'description' => __(
					'URL to use for checkout success redirection.
					Applicable only when `redirect_type` is `url`.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'format'      => 'uri',
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'sale_date_end'             => array(
				'description' => __(
					'Used to automatically end a scheduled sale. If empty, the plan remains on sale indefinitely.
					Only applies when `sale_enabled` is `true`.
					Format: `Y-m-d H:i:s`.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'sale_date_start'           => array(
				'description' => __(
					'Used to automatically start a scheduled sale. If empty, the plan is on sale immediately.
					Only applies when `sale_enabled` is `true`.
					Format: `Y-m-d H:i:s`.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'sale_enabled'              => array(
				'description' => __(
					'Mark the plan as "On Sale" allowing for temporary price adjustments.',
					'lifterlms'
				),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'sale_price'                => array(
				'description' => __(
					'Sale price.
					Only applies when `sale_enabled` is `true`.',
					'lifterlms'
				),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_positive_float_w_zero',
				),
			),
			'sku'                       => array(
				'description' => __(
					'External identifier',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'trial_enabled'             => array(
				'description' => __(
					'Enable a trial period for a recurring access plan.',
					'lifterlms'
				),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'view', 'edit' ),
			),
			'trial_length'              => array(
				'description' => __(
					'Determines the length of trial access.
					Only applies when `trial_enabled` is `true`.',
					'lifterlms'
				),
				'type'        => 'integer',
				'default'     => 1,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_strictly_positive_int',
					'sanitize_callback' => 'absint',
				),
			),
			'trial_period'              => array(
				'description' => __(
					'Determines the length of trial access.
					Only applies when `trial_enabled` is `true`.',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'week',
				'enum'        => array(
					'year',
					'month',
					'week',
					'day',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'trial_price'               => array(
				'description' => __(
					'Determines the price of the trial period.
					Only applies when `trial_enabled` is `true`.',
					'lifterlms'
				),
				'type'        => 'number',
				'default'     => 0,
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'llms_rest_validate_positive_float_w_zero',
				),
			),
			'visibility'                => array(
				'description' => __(
					'Access plan visibility.',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'visible',
				'enum'        => array_keys( llms_get_access_plan_visibility_options() ),
				'context'     => array( 'view', 'edit' ),
			),
		);

		$schema['properties'] = array_merge(
			(array) $schema['properties'],
			$access_plan_properties
		);

		/**
		 * Filter item schema for the access-plan controller
		 *
		 * @since [version]
		 *
		 * @param array $schema Item schema data.
		 */
		$schema = apply_filters( 'llms_rest_access_plan_item_schema', $schema );

		return $schema;
	}

	/**
	 * Retrieves the query params for the objects collection
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['post_id'] = array(
			'description'       => __( 'Retrieve access plans for a specific list of one or more posts. Accepts a course/membership id or comma separated list of course/membership ids.', 'lifterlms' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Retrieves an array of arguments for the delete endpoint
	 *
	 * @since [version]
	 *
	 * @return array Delete endpoint arguments.
	 */
	public function get_delete_item_args() {
		return array();
	}

	/**
	 * Whether the delete should be forced
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the delete should be forced, false otherwise.
	 */
	protected function is_delete_forced( $request ) {
		return true;
	}

	/**
	 * Whether the trash is supported
	 *
	 * @since [version]
	 *
	 * @return bool True if the trash is supported, false otherwise.
	 */
	protected function is_trash_supported() {
		return false;
	}

	/**
	 * Check if a given request has access to create an item
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {

		$can_create = parent::create_item_permissions_check( $request );

		// If current user cannot create the item because of authorization, check if the current user can edit the "parent" course/membership.
		return $this->related_product_permissions_check( $can_create, $request );

	}

	/**
	 * Check if a given request has access to update an item
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		$can_update = parent::update_item_permissions_check( $request );

		// If current user cannot edit the item because of authorization, check if the current user can edit the "parent" course/membership.
		return $this->related_product_permissions_check( $can_update, $request );
	}

	/**
	 * Check if a given request has access to delete an item
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$can_delete = parent::delete_item_permissions_check( $request );

		// If current user cannot delete the item because of authorization, check if the current user can edit the "parent" course/membership.
		return $this->related_product_permissions_check( $can_delete, $request );

	}

	/**
	 * Prepare links for the request
	 *
	 * @since [version]
	 *
	 * @param LLMS_Access_Plan $access_plan LLMS Access Plan instance.
	 * @param WP_REST_Request  $request     Request object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $access_plan, $request ) {

		$links = parent::prepare_links( $access_plan, $request );
		unset( $links['content'] );

		$id         = $access_plan->get( 'id' );
		$product_id = $access_plan->get( 'product_id' );
		$post       = llms_get_post( $product_id );

		$links['post'] = array(
			'href' => rest_url(
				sprintf(
					'%s/%s/%s',
					'llms/v1',
					$access_plan->get_product_type(),
					$product_id
				)
			),
		);

		// Membership restrictions.
		if ( $access_plan->has_availability_restrictions() ) {
			$links['restrictions'] = array(
				'href' => rest_url(
					sprintf(
						'%s/%s?include=%s',
						'llms/v1',
						'memberships',
						implode( ',', $access_plan->get_array( 'availability_restrictions' ) )
					)
				),
			);
		}

		/**
		 * Filters the access plan's links.
		 *
		 * @since [version]
		 *
		 * @param array            $links       Links for the given access plan.
		 * @param LLMS_Access_Plan $access_plan LLMS Access Plan instance.
		 */
		$links = apply_filters( 'llms_rest_access_plan_links', $links, $access_plan );

		return $links;
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Access_Plan $access_plan LLMS Access Plan instance.
	 * @param WP_REST_Request  $request     Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $access_plan, $request ) {
		$data    = parent::prepare_object_for_response( $access_plan, $request );
		$context = $request->get_param( 'context' );

		// Price.
		$data['price'] = $access_plan->is_free() ? 0 : $access_plan->get_price( 'price', array(), 'float' );

		// Access expiration.
		$data['access_expiration'] = $access_plan->get( 'access_expiration' );

		// Access expires date.
		if ( 'limited-date' === $data['access_expiration'] || 'edit' === $context ) {
			$data['access_expires'] = $access_plan->get_date( 'access_expires' );
		}

		// Access length and period.
		if ( 'limited-period' === $data['access_expiration'] || 'edit' === $context ) {
			$data['access_length'] = $access_plan->get( 'access_length' );
			$data['access_period'] = $access_plan->get( 'access_period' );
		}

		// Availability restrictions.
		$data['availability_restrictions'] = $access_plan->has_availability_restrictions() ? array() : $access_plan->get_array( 'availability_restrictions' );

		// Enroll text.
		$data['enroll_text'] = $access_plan->get_enroll_text();

		// Frequency.
		$data['frequency'] = $access_plan->get( 'frequency' );

		// Length and period.
		if ( 0 < $data['frequency'] || 'edit' === $context ) {
			$data['length'] = $access_plan->get( 'length' );
			$data['period'] = $access_plan->get( 'period' );
		}

		// Post ID.
		$data['post_id'] = $access_plan->get( 'product_id' );

		// Redirect forced.
		if ( ! empty( $data['availability_restrictions'] ) || 'edit' === $context ) {
			$data['redirect_forced'] = llms_parse_bool( $access_plan->get( 'checkout_redirect_forced' ) );
		}

		// Redirect type.
		$data['redirect_type'] = $access_plan->get( 'checkout_redirect_type' );

		// Redirect page.
		if ( 'page' === $data['redirect_type'] || 'edit' === $context ) {
			$data['redirect_page'] = $access_plan->get( 'checkout_redirect_page' );
		}

		// Redirect url.
		if ( 'url' === $data['redirect_type'] || 'edit' === $context ) {
			$data['redirect_url'] = $access_plan->get( 'checkout_redirect_url' );
		}

		// Sale enabled.
		$data['sale_enabled'] = llms_parse_bool( $access_plan->get( 'on_sale' ) );

		// Sale start/end and price.
		if ( $data['sale_enabled'] || 'edit' === $context ) {
			$data['sale_start'] = $access_plan->get_date( 'sale_start' );
			$data['sale_end']   = $access_plan->get_date( 'sale_end' );
			$data['sale_price'] = $access_plan->get_price( 'sale_price', array(), 'float' );
		}

		// SKU.
		$data['sku'] = $access_plan->get( 'sku' );

		// Trial.
		$data['trial_enabled'] = $access_plan->has_trial();

		if ( $data['trial_enabled'] || 'edit' === $context ) {
			$data['trial_length'] = $access_plan->get( 'trial_length' );
			$data['trial_period'] = $access_plan->get( 'trial_period' );
			$data['trial_price']  = $access_plan->get_price( 'trial_price', array(), 'float' );
		}

		// Visibility.
		$data['visibility'] = $access_plan->get_visibility();

		/**
		 * Filters the access plan data for a response.
		 *
		 * @since [version]
		 *
		 * @param array            $data        Array of lesson properties prepared for response.
		 * @param LLMS_Access_Plan $access_plan LLMS Access Plan instance.
		 * @param WP_REST_Request  $request     Full details about the request.
		 */
		$data = apply_filters( 'llms_rest_prepare_access_plan_object_response', $data, $access_plan, $request );

		return $data;
	}

	/**
	 * Format query arguments to retrieve a collection of objects
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error
	 */
	protected function prepare_collection_query_args( $request ) {

		$query_args = parent::prepare_collection_query_args( $request );
		if ( is_wp_error( $query_args ) ) {
			return $query_args;
		}

		// Filter by post ID.
		if ( ! empty( $request['post_id'] ) ) {
			$query_args = array_merge(
				$query_args,
				array(
					'meta_query' => array(
						array(
							'key'     => '_llms_product_id',
							'value'   => $request['post_id'],
							'compare' => 'IN',
						),
					),
				)
			);
		}

		return $query_args;
	}

	/**
	 * Prepares a single post for create or update
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Array of llms post args or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared_item = parent::prepare_item_for_database( $request );
		if ( is_wp_error( $prepared_item ) ) {
			return $prepared_item;
		}

		$schema = $this->get_item_schema();

		// Enroll text.
		if ( ! empty( $schema['properties']['enroll_text'] ) && isset( $request['enroll_text'] ) ) {
			$prepared_item['enroll_text'] = $request['enroll_text'];
		}

		// Post id.
		if ( ! empty( $schema['properties']['post_id'] ) && isset( $request['post_id'] ) ) {
			$prepared_item['product_id'] = $request['post_id'];
		}

		// SKU.
		if ( ! empty( $schema['properties']['sku'] ) && isset( $request['sku'] ) ) {
			$prepared_item['sku'] = $request['sku'];
		}

		/**
		 * Filters the access plan data before inserting in the db
		 *
		 * @since [version]
		 *
		 * @param array           $prepared_item Array of access plan item properties prepared for database.
		 * @param WP_REST_Request $request       Full details about the request.
		 * @param array           $schema        The item schema.
		 */
		$prepared_item = apply_filters( 'llms_rest_pre_insert_access_plan', $prepared_item, $request, $schema );

		return $prepared_item;
	}

	/**
	 * Updates an existing single LLMS_Access_Plan in the database
	 *
	 * This method should be used for access plan properties that require the access plan id in order to be saved in the database.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Access_Plan $access_plan   LLMS Access Plan instance.
	 * @param WP_REST_Request  $request       Full details about the request.
	 * @param array            $schema        The item schema.
	 * @param array            $prepared_item Array.
	 * @param bool             $creating      Optional. Whether we're in creation or update phase. Default true (create).
	 * @return bool|WP_Error True on success or false if nothing to update, WP_Error object if something went wrong during the update.
	 */
	protected function update_additional_object_fields( $access_plan, $request, $schema, $prepared_item, $creating = true ) {

		$error = new WP_Error();

		// Will contain the properties to set.
		$to_set = array();

		// Access expiration.
		if ( ! empty( $schema['properties']['access_expiration'] ) && isset( $request['access_expiration'] ) ) {
			$to_set['access_expiration'] = $request['access_expiration'];
		}

		// Access expires.
		if ( ! empty( $schema['properties']['access_expires'] ) && isset( $request['access_expires'] ) ) {
			$access_expires           = rest_parse_date( $request['access_expires'] );
			$to_set['access_expires'] = empty( $access_expires ) ? '' : date_i18n( 'Y-m-d H:i:s', $access_expires );
		}

		// Access length.
		if ( ! empty( $schema['properties']['access_length'] ) && isset( $request['access_length'] ) ) {
			$to_set['access_length'] = $request['access_length'];
		}

		// Access period.
		if ( ! empty( $schema['properties']['access_period'] ) && isset( $request['access_period'] ) ) {
			$to_set['access_period'] = $request['access_period'];
		}

		// Redirect.
		if ( ! empty( $schema['properties']['redirect_type'] ) && isset( $request['redirect_type'] ) ) {
			$to_set['checkout_redirect_type'] = $request['redirect_type'];
		}

		// Redirect page.
		if ( ! empty( $schema['properties']['redirect_page'] ) && isset( $request['redirect_page'] ) ) {
			$redirect_page = get_post( $request['redirect_page'] );
			if ( $redirect_page && is_a( $redirect_page, 'WP_Post' ) ) {
				$to_set['checkout_redirect_page'] = $request['redirect_page']; // maybe allow only published pages?
			}
		}

		// Redirect url.
		if ( ! empty( $schema['properties']['redirect_url'] ) && isset( $request['redirect_url'] ) ) {
			$to_set['checkout_redirect_url'] = $request['redirect_url'];
		}

		// Price.
		if ( ! empty( $schema['properties']['price'] ) && isset( $request['price'] ) ) {
			$to_set['price'] = $request['price'];
		}

		// Sale enabled.
		if ( ! empty( $schema['properties']['sale_enabled'] ) && isset( $request['sale_enabled'] ) ) {
			$to_set['on_sale'] = $request['sale_enabled'] ? 'yes' : 'no';
		}

		// Sale dates.
		if ( ! empty( $schema['properties']['sale_date_start'] ) && isset( $request['sale_date_start'] ) ) {
			$sale_date_start      = rest_parse_date( $request['sale_date_start'] );
			$to_set['sale_start'] = empty( $sale_date_start ) ? '' : date_i18n( 'Y-m-d H:i:s', $sale_date_start );
		}

		if ( ! empty( $schema['properties']['sale_date_end'] ) && isset( $request['sale_date_end'] ) ) {
			$sale_date_end      = rest_parse_date( $request['sale_date_end'] );
			$to_set['sale_end'] = empty( $sale_date_end ) ? '' : date_i18n( 'Y-m-d H:i:s', $sale_date_end );
		}
		// Sale price.
		if ( ! empty( $schema['properties']['sale_price'] ) && isset( $request['sale_price'] ) ) {
			$to_set['sale_price'] = $request['sale_price'];
		}

		// Trial enabled.
		if ( ! empty( $schema['properties']['trial_enabled'] ) && isset( $request['trial_enabled'] ) ) {
			$to_set['trial_offer'] = $request['trial_enabled'] ? 'yes' : 'no';
		}

		// Trial Length.
		if ( ! empty( $schema['properties']['trial_length'] ) && isset( $request['trial_length'] ) ) {
			$to_set['trial_length'] = $request['trial_length'];
		}
		// Trial Period.
		if ( ! empty( $schema['properties']['trial_period'] ) && isset( $request['trial_period'] ) ) {
			$to_set['trial_period'] = $request['trial_period'];
		}
		// Trial price.
		if ( ! empty( $schema['properties']['trial_price'] ) && isset( $request['trial_price'] ) ) {
			$to_set['trial_price'] = $request['trial_price'];
		}

		// Availability restrictions.
		if ( ! empty( $schema['properties']['availability_restrictions'] ) && isset( $request['availability_restrictions'] ) ) {
			$to_set['availability_restrictions'] = $request['availability_restrictions'];
		}

		// Redirect forced.
		if ( ! empty( $schema['properties']['redirect_forced'] ) && isset( $request['redirect_forced'] ) ) {
			$to_set['checkout_redirect_forced'] = $request['redirect_forced'];
		}

		// Frequency.
		if ( ! empty( $schema['properties']['frequency'] ) && isset( $request['frequency'] ) ) {
			$to_set['frequency'] = $request['frequency'];
		}

		// Length.
		if ( ! empty( $schema['properties']['length'] ) && isset( $request['length'] ) ) {
			$to_set['length'] = $request['length'];
		}
		// Period.
		if ( ! empty( $schema['properties']['period'] ) && isset( $request['period'] ) ) {
			$to_set['period'] = $request['period'];
		}

		$this->handle_props_interdependency( $to_set, $access_plan, $creating );

		// Visibility.
		if ( ! empty( $schema['properties']['visibiliy'] ) && isset( $request['visibility'] ) ) {
			$visibility = $access_plan->set_visibility( $request['visibility'] );
			if ( is_wp_error( $visibility ) ) {
				return $visibility;
			}
		}

		// Set bulk.
		if ( ! empty( $to_set ) ) {
			$update = $access_plan->set_bulk( $to_set, true );
			if ( is_wp_error( $update ) ) {
				$error = $update;
			}
		}

		if ( $error->errors ) {
			return $error;
		}

		return ! empty( $to_set ) || ! empty( $visibility );
	}

	/**
	 * Handle properties interdependency
	 *
	 * @since [version]
	 *
	 * @param array            $to_set      Array of properties to be set.
	 * @param LLMS_Access_Plan $access_plan LLMS Access Plan instance.
	 * @param bool             $creating    Whether we're in creation or update phase.
	 * @return void
	 */
	private function handle_props_interdependency( &$to_set, $access_plan, $creating ) {

		// Access Plan properties as saved in the db.
		$saved_props = $access_plan->toArray();

		$this->add_subordinate_props( $to_set, $saved_props, $creating );

		$this->unset_subordinate_props( $to_set, $saved_props );

	}

	/**
	 * Add all the properties which need to be set as consequence of another setting
	 *
	 * These properties must be compared to the saved value before updating, because if equal they will produce an error(see update_post_meta()).
	 *
	 * @since [version]
	 *
	 * @param array $to_set      Array of properties to be set.
	 * @param array $saved_props Array of LLMS_Access_Plan properties as saved in the db.
	 * @param bool  $creating    Whether we're in creation or update phase.
	 * @return void
	 */
	private function add_subordinate_props( &$to_set, $saved_props, $creating ) {

		$subordinate_props = array();

		// Merge new properties to set and saved props.
		$props = wp_parse_args( $to_set, $saved_props );

		// Paid plan.
		if ( $props['price'] > 0 ) {

			$subordinate_props['is_free'] = 'no';

			// One-time (no trial).
			if ( 0 === $props['frequency'] ) {
				$subordinate_props['trial_offer'] = 'no';
			}
		} else {

			$subordinate_props['is_free']     = 'yes';
			$subordinate_props['price']       = 0;
			$subordinate_props['frequency']   = 0;
			$subordinate_props['on_sale']     = 'no';
			$subordinate_props['trial_offer'] = 'no';

		}

		if ( ! $creating ) { // Remove already set properties.

			foreach ( $subordinate_props as $_prop => $value ) {
				if ( isset( $saved_props[ $_prop ] ) && $saved_props[ $_prop ] === $value ) {
					unset( $subordinate_props[ $_prop ] );
				}
			}
		}

		$to_set = array_merge( $to_set, $subordinate_props );

	}

	/**
	 * Remove all the properties that do not need to be set, based on other properties
	 *
	 * @since [version]
	 *
	 * @param array $to_set      Array of properties to be set.
	 * @param array $saved_props Array of LLMS_Access_Plan properties as saved in the db.
	 * @return void
	 */
	private function unset_subordinate_props( &$to_set, $saved_props ) {

		// Merge new properties to set and saved props.
		$props = wp_parse_args( $to_set, $saved_props );

		// No need to create/update recurring props when it's a 1-time payment.
		if ( 0 === $props['frequency'] ) {
			unset( $to_set['length'], $to_set['period'] );
		}

		// No need to create/update trial props when no trial enabled.
		if ( ! llms_parse_bool( $props['trial_offer'] ) ) {
			unset( $to_set['trial_price'], $to_set['trial_length'], $to_set['trial_period'] );
		}

		// No need to create/update sale props when not on sale.
		if ( ! llms_parse_bool( $props['on_sale'] ) ) {
			unset( $to_set['sale_price'], $to_set['sale_end'], $to_set['sale_start'] );
		}

		// Unset redirect props based on redirect settings.
		if ( 'url' === $props['checkout_redirect_type'] ) {
			unset( $to_set['checkout_redirect_page'] );
		} elseif ( 'page' === $props['checkout_redirect_type'] ) {
			unset( $to_set['checkout_redirect_url'] );
		} else {
			unset( $to_set['checkout_redirect_url'], $to_set['checkout_redirect_page'] );
		}

		// Unset expiration props based on expiration settings.
		if ( 'lifetime' === $props['access_expiration'] ) {
			unset( $to_set['access_expires'], $to_set['access_length'], $to_set['access_period'] );
		} elseif ( 'limited-date' === $props['access_expiration'] ) {
			unset( $to_set['access_length'], $to_set['access_period'] );
		} elseif ( 'limited-period' === $props['access_expiration'] ) {
			unset( $to_set['access_expires'] );
		}
	}

	/**
	 * Check if the current user, who has no permissions to manipulate the access plan post, can edit its related product.
	 *
	 * @since [version]
	 *
	 * @param boolean|WP_Error $has_permissions Whether or not the current user has the permission to manipulate the resource.
	 * @param WP_REST_Request  $request         Full details about the request.
	 * @return boolean|WP_Error
	 */
	private function related_product_permissions_check( $has_permissions, $request ) {

		if ( llms_rest_is_authorization_required_error( $has_permissions ) ) {
			$product_id               = isset( $request['id'] ) /* not creation */ ? $this->get_object( (int) $request['id'] )->get( 'product_id' ) : (int) $request['post_id'];
			$product_post_type_object = get_post_type_object( get_post_type( $product_id ) );

			if ( current_user_can( $product_post_type_object->cap->edit_post, $product_id ) ) {
				$has_permissions = true;
			}
		}

		return $has_permissions;
	}

}
