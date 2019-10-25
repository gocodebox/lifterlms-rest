<?php
/**
 * REST Memberships Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since   [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Memberships_Controller.
 *
 * @since [version]
 */
class LLMS_REST_Memberships_Controller extends LLMS_REST_Posts_Controller {
	/**
	 * Enrollments controller.
	 *
	 * @var LLMS_REST_Enrollments_Controller
	 */
	protected $enrollments_controller;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_membership';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'memberships';

	/**
	 * Constructor.
	 *
	 * @since [version]
	 */
	public function __construct() {
		$this->enrollments_controller = new LLMS_REST_Enrollments_Controller();
		$this->enrollments_controller->set_collection_params( $this->get_enrollments_collection_params() );
	}

	/**
	 * Get an LLMS_Membership.
	 *
	 * @since [version]
	 *
	 * @param array $object_args Object args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	protected function create_llms_post( $object_args ) {
		$object = new LLMS_Membership( 'new', $object_args );

		return $object && is_a( $object, 'LLMS_Membership' ) ? $object : llms_rest_not_found_error();
	}

	/**
	 * Retrieves the query params for the enrollments objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_enrollments_collection_params() {
		$query_params = $this->enrollments_controller->get_collection_params();

		unset( $query_params['post'] );

		$query_params['student'] = array(
			'description'       => __( 'Limit results to a specific student or a list of students. Accepts a single ' .
			                           'student id or a comma separated list of student ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Get action/filters to be removed before preparing the item for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Membership $membership Membership object.
	 * @return array Array of action/filters to be removed for response.
	 */
	protected function get_filters_to_be_removed_for_response( $membership ) {
		if ( ! llms_blocks_is_post_migrated( $membership->get( 'id' ) ) ) {
			return array();
		}

		return array(
			// hook => [callback, priority].
			'lifterlms_single_membership_after_summary' => array(
				// Membership Information.
				array(
					'callback' => 'lifterlms_template_pricing_table',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Get the Membership's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['auto_enroll']         = array(
			'description' => __(
				'List of courses to automatically enroll students into when they\'re enrolled into the membership.',
				'lifterlms' ),
			'type'        => 'array',
			'default'     => [],
			'items'       => array(
				'type' => 'integer',
			),
		);
		$schema['properties']['catalog_visibility']  = array(
			'description' => __( 'Visibility of the membership in catalogs and search results.', 'lifterlms' ),
			'type'        => 'string',
			'enum'        => array_keys( llms_get_product_visibility_options() ),
			'default'     => 'catalog_search',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['categories']          = array(
			'description' => __( 'List of membership categories.', 'lifterlms' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['instructors']         = array(
			'description' => __(
				'List of post instructors. Defaults to current user when creating a new post.',
				'lifterlms' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['restriction_action']  = array(
			'description' => __(
				'Determines the action to take when content restricted by the membership is accessed by a non-member. ' .
				'- `none`: Remain on page and display the message `restriction_message`. ' .
				'- `membership`: Redirect to the membership\'s permalink. ' .
				'- `page`: Redirect to the permalink of the page identified by `restriction_page_id`. ' .
				'- `custom`: Redirect to the URL identified by `restriction_url`.',
				'lifterlms' ),
			'type'        => 'string',
			'default'     => 'none',
			'enum'        => array( 'none', 'membership', 'page', 'custom' ),
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['restriction_message'] = array(
			'description' => __(
				'Message to display to non-members after a `restriction_action` redirect. ' .
				'When `restriction_action` is `none` replaces the page content with this message.',
				'lifterlms' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
				'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
			),
			'properties'  => array(
				'raw'      => array(
					'description' => __( 'Raw message content.', 'lifterlms' ),
					'default'     => __(
						'You must belong to the [lifterlms_membership_link id="1234"] membership to access this content.',
						'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'rendered' => array(
					'description' => __( 'Rendered message content.', 'lifterlms' ),
					'default'     => __(
						'You must belong to the <a href="https://example.com/membership/gold">Gold</a> membership ' .
						'to access this content.',
						'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
		$schema['properties']['restriction_page_id'] = array(
			'description' => __(
				'WordPress page ID used for redirecting non-members when `restriction_action` is `page`.',
				'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'absint',
			),
		);
		$schema['properties']['restriction_url']     = array(
			'description' => __(
				'URL used for redirecting non-members when `restriction_action` is `custom`.',
				'lifterlms' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'format'      => 'uri',
			'arg_options' => array(
				'sanitize_callback' => 'esc_url_raw',
			),
		);
		$schema['properties']['sales_page_page_id']  = array(
			'description' => __(
				'The WordPress page ID of the sales page. Required when `sales_page_type` equals `page`. ' .
				'Only returned when the `sales_page_type` equals `page`.',
				'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'absint',
			),
		);
		$schema['properties']['sales_page_type']     = array(
			'description' => __(
				'Defines alternate content displayed to visitors and non-enrolled students when accessing the post. ' .
				'- `none` displays the post content. ' .
				'- `content` displays alternate content from the `excerpt` property.' .
				'- `page` redirects to the WordPress page defined in `content_page_id`.' .
				'- `url` redirects to the URL defined in `content_page_url`.',
				'lifterlms' ),
			'type'        => 'string',
			'default'     => 'none',
			'enum'        => array_keys( llms_get_sales_page_types() ),
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['sales_page_url']      = array(
			'description' => __(
				'The URL of the sales page content. Required when `sales_page_type` equals `url`. ' .
				'Only returned when the `sales_page_type` equals `url`.',
				'lifterlms' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'format'      => 'uri',
			'arg_options' => array(
				'sanitize_callback' => 'esc_url_raw',
			),
		);
		$schema['properties']['tags']                = array(
			'description' => __( 'List of membership tags.', 'lifterlms' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Membership|WP_Error
	 */
	protected function get_object( $id ) {
		/** @var LLMS_Membership $membership */
		$membership = llms_get_post( $id );

		return is_a( $membership, 'LLMS_Membership' ) ? $membership : llms_rest_not_found_error();
	}

	/**
	 * Retrieve an ID from the object.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Membership $object LLMS_Membership object.
	 * @return int
	 */
	protected function get_object_id( $object ) {
		return $object->get( 'id' );
	}

	/**
	 * Maps a taxonomy name to the relative rest base.
	 *
	 * @since [version]
	 *
	 * @param object $taxonomy The taxonomy object.
	 * @return string The taxonomy rest base.
	 */
	protected function get_taxonomy_rest_base( $taxonomy ) {
		$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

		$taxonomy_base_map = array(
			'membership_cat' => 'categories',
			'membership_tag' => 'tags',
		);

		return isset( $taxonomy_base_map[ $base ] ) ? $taxonomy_base_map[ $base ] : $base;
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Array of llms post args or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_item = parent::prepare_item_for_database( $request );
		$schema        = $this->get_item_schema();

		// Restriction action.
		if ( ! empty( $schema['properties']['restriction_action'] ) && isset( $request['restriction_action'] ) ) {
			$prepared_item['restriction_redirect_type'] = $request['restriction_action'];
		}

		// Restriction message.
		if ( ! empty( $schema['properties']['restriction_message'] ) && isset( $request['restriction_message'] ) ) {
			if ( is_string( $request['restriction_message'] ) ) {
				$prepared_item['restriction_notice'] = $request['restriction_message'];
			} elseif ( isset( $request['restriction_message']['raw'] ) ) {
				$prepared_item['restriction_notice'] = $request['restriction_message']['raw'];
			}
		}

		// Restriction page id.
		if ( ! empty( $schema['properties']['restriction_page_id'] ) && isset( $request['restriction_page_id'] ) ) {
			$page = get_post( $request['restriction_page_id'] );
			if ( $page && is_a( $page, 'WP_Post' ) ) {
				$prepared_item['redirect_page_id'] = $request['restriction_page_id'];
			} else {
				$prepared_item['redirect_page_id'] = 0;
			}
		}

		// Restriction URL.
		if ( ! empty( $schema['properties']['restriction_url'] ) && isset( $request['restriction_url'] ) ) {
			$prepared_item['redirect_custom_url'] = $request['restriction_url'];
		}

		// Sales page id.
		if ( ! empty( $schema['properties']['sales_page_page_id'] ) && isset( $request['sales_page_page_id'] ) ) {
			$page = get_post( $request['sales_page_page_id'] );
			if ( $page && is_a( $page, 'WP_Post' ) ) {
				$prepared_item['sales_page_content_page_id'] = $request['sales_page_page_id'];
			} else {
				$prepared_item['sales_page_content_page_id'] = 0;
			}
		}

		// Sales page type.
		if ( ! empty( $schema['properties']['sales_page_type'] ) && isset( $request['sales_page_type'] ) ) {
			$prepared_item['sales_page_content_type'] = $request['sales_page_type'];
		}

		// Sales page URL.
		if ( ! empty( $schema['properties']['sales_page_url'] ) && isset( $request['sales_page_url'] ) ) {
			$prepared_item['sales_page_content_url'] = $request['sales_page_url'];
		}

		return $prepared_item;

	}

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Membership $membership LLMS Membership.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $membership ) {
		$parent_links = parent::prepare_links( $membership );
		$id           = $membership->get( 'id' );

		$links = array();

		// Enrollments.
		$links['enrollments'] = array(
			'href' => rest_url( sprintf( '/%s/%s/%d/%s', $this->namespace, $this->rest_base, $id, 'enrollments' ) ),
		);

		// Instructors.
		$links['instructors'] = array(
			'href' => add_query_arg(
				'post',
				$id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'instructors' ) )
			),
		);

		// Students.
		$links['students'] = array(
			'href' => add_query_arg(
				'enrolled_in',
				$id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'students' ) )
			),
		);

		return array_merge( $parent_links, $links );
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Membership $membership Membership object.
	 * @param WP_REST_Request $request    Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $membership, $request ) {
		$data    = parent::prepare_object_for_response( $membership, $request );
		$context = $request->get_param( 'context' );

		// Auto enroll.
		$data['auto_enroll'] = $membership->get_auto_enroll_courses();

		// Catalog visibility.
		$data['catalog_visibility'] = $membership->get_product()->get_catalog_visibility();

		// Categories.
		$data['categories'] = $membership->get_categories(
			array(
				'fields' => 'ids',
			)
		);

		// Instructors.
		$instructors         = $membership->get_instructors();
		$instructors         = empty( $instructors ) ? array() : wp_list_pluck( $instructors, 'id' );
		$data['instructors'] = $instructors;

		// Restriction action.
		$data['restriction_action'] = $membership->get( 'restriction_redirect_type' );
		$data['restriction_action'] = $data['restriction_action'] ? $data['restriction_action'] : 'none';

		// Restriction message.
		$data['restriction_message'] = array(
			'raw'      => $membership->get( 'restriction_notice', $raw = true ),
			'rendered' => do_shortcode( $membership->get( 'restriction_notice' ) ),
		);
		if ( empty( $data['restriction_message']['raw'] ) ) {
			$data['restriction_message']['raw']      = 'You must belong to the [lifterlms_membership_link id="' .
			                                           $membership->get( 'id' ) .
			                                           '"] membership to access this content.';
			$data['restriction_message']['rendered'] = do_shortcode( $data['restriction_message']['raw'] );
		}

		// Restriction page id.
		if ( 'page' === $data['restriction_action'] || 'edit' === $context ) {
			$data['restriction_page_id'] = $membership->get( 'redirect_page_id' );
		}

		// Restriction URL.
		if ( 'custom' === $data['restriction_action'] || 'edit' === $context ) {
			$data['restriction_url'] = $membership->get( 'redirect_custom_url' );
		}

		// Tags.
		$data['tags'] = $membership->get_tags(
			array(
				'fields' => 'ids',
			)
		);

		// Sales page type.
		$data['sales_page_type'] = $membership->get( 'sales_page_content_type' );
		$data['sales_page_type'] = $data['sales_page_type'] ? $data['sales_page_type'] : 'none';

		// Sales page id.
		if ( 'page' === $data['sales_page_type'] || 'edit' === $context ) {
			$data['sales_page_page_id'] = $membership->get( 'sales_page_content_page_id' );
		}

		// Sales page url.
		if ( 'custom' === $data['sales_page_type'] || 'edit' === $context ) {
			$data['sales_page_url'] = $membership->get( 'sales_page_content_url' );
		}

		return $data;
	}

	/**
	 * Register routes.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/enrollments',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique Membership Identifier. The WordPress Post ID', 'lifterlms' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this->enrollments_controller, 'get_items' ),
					'permission_callback' => array( $this->enrollments_controller, 'get_items_permissions_check' ),
					'args'                => $this->enrollments_controller->get_collection_params(),
				),
				'schema' => array( $this->enrollments_controller, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Updates a single llms membership.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Membership $membership    LLMS_Membership instance.
	 * @param WP_REST_Request $request       Full details about the request.
	 * @param array           $schema        The item schema.
	 * @param array           $prepared_item Array.
	 * @param bool            $creating      Optional. Whether we're in creation or update phase. Default true (create).
	 * @return bool|WP_Error True on success or false if nothing to update, WP_Error object if something went wrong during the update.
	 */
	protected function update_additional_object_fields( $membership, $request, $schema, $prepared_item, $creating = true ) {
		$error = new WP_Error();

		// Auto enroll.
		if ( ! empty( $schema['properties']['auto_enroll'] ) && isset( $request['auto_enroll'] ) ) {
			$membership->add_auto_enroll_courses( $request['auto_enroll'], true );
		}

		// Catalog visibility.
		if ( ! empty( $schema['properties']['catalog_visibility'] ) && isset( $request['catalog_visibility'] ) ) {
			$membership->get_product()->set_catalog_visibility( $request['catalog_visibility'] );
		}

		// Instructors.
		if ( ! empty( $schema['properties']['instructors'] ) && isset( $request['instructors'] ) ) {

			$instructors = array();

			foreach ( $request['instructors'] as $instructor_id ) {
				$user_data = get_userdata( $instructor_id );
				if ( ! empty( $user_data ) ) {
					$instructors[] = array(
						'id'   => $instructor_id,
						'name' => $user_data->display_name,
					);
				}
			}

			$membership->set_instructors( $instructors );
		}

		$to_set = array();

		// Set bulk.
		if ( ! empty( $to_set ) ) {
			$update = $membership->set_bulk( $to_set, true );
			if ( is_wp_error( $update ) ) {
				$error = $update;
			}
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		return ! empty( $to_set );
	}
}
