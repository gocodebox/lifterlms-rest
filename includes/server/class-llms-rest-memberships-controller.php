<?php
/**
 * REST Memberships Controller Class
 *
 * @package LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Memberships_Controller
 *
 * @since [version]
 */
class LLMS_REST_Memberships_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'memberships';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_membership';

	/**
	 * Enrollments controller
	 *
	 * @var LLMS_REST_Enrollments_Controller
	 */
	protected $enrollments_controller;

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
	 * Get an LLMS_Membership
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Membership|WP_Error
	 */
	protected function get_object( $id ) {
		$membership = llms_get_post( $id );
		return $membership && is_a( $membership, 'LLMS_Membership' ) ? $membership : llms_rest_not_found_error();
	}

	/**
	 * Create an LLMS_Membership
	 *
	 * @since [version]
	 *
	 * @param array $object_args Object args.
	 * @return LLMS_Membership|WP_Error
	 */
	protected function create_llms_post( $object_args ) {
		$object = new LLMS_Membership( 'new', $object_args );
		return $object && is_a( $object, 'LLMS_Membership' ) ? $object : llms_rest_not_found_error();
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

		$membership_properties = array(
			'auto_enroll'         => array(
				'description' => __( 'List of courses to automatically enroll students into when they\'re enrolled into the membership.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'catalog_visibility'  => array(
				'description' => __( 'Visibility of the membership in catalogs and search results.', 'lifterlms' ),
				'type'        => 'string',
				'enum'        => array_keys( llms_get_product_visibility_options() ),
				'default'     => 'catalog_search',
				'context'     => array( 'view', 'edit' ),
			),
			// consider to move tags and cats in the posts controller abstract.
			'categories'          => array(
				'description' => __( 'List of membership categories.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'tags'                => array(
				'description' => __( 'List of membership tags.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'instructors'         => array(
				'description' => __( 'List of membership instructors. Defaults to current user when creating a new post.', 'lifterlms' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'arg_options' => array(
					'validate_callback' => 'llms_validate_instructors',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'restriction_action'  => array(
				'description' => __(
					'Determines the action to take when content restricted by the membership is accessed by a non-member.<br> - <code>none</code> Remain on page and display the message <code>restriction_message</code>.<br> - <code>membership</code> Redirect to the membership\'s permalink.<br> - <code>page</code> Redirect to the permalink of the page identified by <code>restriction_page_id</code>.<br> - <code>custom</code> Redirct to the URL identified by <code>restriction_url</code>',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'none',
				'enum'        => array(
					'none',
					'membership',
					'page',
					'custom',
				),
				'context'     => array( 'view', 'edit' ),
			),
			'restriction_message' => array(
				'description' => __( 'Message to display to non-members after a restriction_action redirct. When restriction_action is none replaces the page content with this message.', 'lifterlms' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
					'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
				),
				'properties'  => array(
					'raw'      => array(
						'description' => __( 'Raw message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'edit' ),
					),
					'rendered' => array(
						'description' => __( 'Rendered message content.', 'lifterlms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
				'default'     => __( 'You must belong to the [lifterlms_membership_link id="{{membership_id}}" membership to access this content.', 'lifterlms' ),
			),
			'restriction_page_id' => array(
				'description' => __(
					'WordPress page ID used for redirecting non-members when <code>restriction_action</code> is <code>page</code>',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'restriction_url'     => array(
				'description' => __(
					'URL used for redirecting non-members when <code>restriction_action</code> is <code>custom</code>.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'format'      => 'uri',
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
			'sales_page_page_id'  => array(
				'description' => __(
					'The WordPress page ID of the sales page. Required when sales_page_type equals page. Only returned when the sales_page_type equals page.',
					'lifterlms'
				),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'sales_page_type'     => array(
				'description' => __(
					'Defines alternate content displayed to visitors and non-enrolled students when accessing the post.<br> - <code>none</code> displays the post content.<br> - <code>content</code> displays alternate content from the <code>excerpt</code> property.<br> - <code>page</code> redirects to the WordPress page defined in <code>content_page_id</code>.<br> - <code>url</code> redirects to the URL defined in <code>content_page_url</code>',
					'lifterlms'
				),
				'type'        => 'string',
				'default'     => 'none',
				'enum'        => array_keys( llms_get_sales_page_types() ),
				'context'     => array( 'view', 'edit' ),
			),
			'sales_page_url'      => array(
				'description' => __(
					'The URL of the sales page content. Required when <code>content_type</code> equals <code>url</code>. Only returned when the <code>content_type</code> equals <code>url</code>.',
					'lifterlms'
				),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'format'      => 'uri',
				'arg_options' => array(
					'sanitize_callback' => 'esc_url_raw',
				),
			),
		);

		$schema['properties'] = array_merge( (array) $schema['properties'], $membership_properties );

		/**
		 * Filter item schema for the membership controller.
		 *
		 * @since [version]
		 *
		 * @param array $schema Item schema data.
		 */
		return apply_filters( 'llms_rest_membership_item_schema', $schema );

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

		$data = parent::prepare_object_for_response( $membership, $request );

		// Autoenroll.
		$data['auto_enroll'] = $membership->get_auto_enroll_courses();

		// Catalog visibility.
		$data['catalog_visibility'] = $membership->get_product()->get_catalog_visibility();

		// Categories.
		$data['categories'] = wp_get_post_terms(
			$membership->get( 'id' ),
			'membership_cat',
			array(
				'fields' => 'ids',
			)
		);

		// Tags.
		$data['tags'] = wp_get_post_terms(
			$membership->get( 'id' ),
			'membership_tag',
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

		// Restriction page id.
		$data['restriction_page_id'] = $membership->get( 'redirect_page_id' );

		// Restriction url.
		$data['restriction_url'] = $membership->get( 'redirect_custom_url' );

		// Sales page page type.
		$data['sales_page_type'] = $membership->get( 'sales_page_content_type' );
		$data['sales_page_type'] = $data['sales_page_type'] ? $data['sales_page_type'] : 'none';

		// Sales page id/url.
		if ( 'page' === $data['sales_page_type'] ) {
			$data['sales_page_page_id'] = $membership->get( 'sales_page_content_page_id' );
		} elseif ( 'url' === $data['sales_page_type'] ) {
			$data['sales_page_url'] = $membership->get( 'sales_page_content_url' );
		}

		/**
		 * Filters the membership data for a response.
		 *
		 * @since [version]
		 *
		 * @param array           $data       Array of lesson properties prepared for response.
		 * @param LLMS_Membership $membership Membership object.
		 * @param WP_REST_Request $request    Full details about the request.
		 */
		return apply_filters( 'llms_rest_prepare_membership_object_response', $data, $membership, $request );

	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Array of membership args or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared_item = parent::prepare_item_for_database( $request );
		$schema        = $this->get_item_schema();

		// Restrictions.
		if ( ! empty( $schema['properties']['restriction_action'] ) && isset( $request['restriction_action'] ) ) {
			$prepared_item['redirect_page_type'] = $request['restriction_action'];
		}

		if ( ! empty( $schema['properties']['restriction_page_id'] ) && isset( $request['restriction_page_id'] ) ) {
			$sales_page = get_post( $request['restriction_page_id'] );
			if ( $sales_page && is_a( $sales_page, 'WP_Post' ) ) {
				$prepared_item['redirect_page_id'] = $request['restriction_page_id']; // maybe allow only published pages?
			} else {
				$prepared_item['redirect_page_id'] = 0;
			}
		}

		if ( ! empty( $schema['properties']['restriction_url'] ) && isset( $request['restriction_url'] ) ) {
			$prepared_item['redirect_custom_url'] = $request['restriction_url'];
		}

		// Sales page.
		if ( ! empty( $schema['properties']['sales_page_type'] ) && isset( $request['sales_page_type'] ) ) {
			$prepared_item['sales_page_content_type'] = $request['sales_page_type'];
		}

		if ( ! empty( $schema['properties']['sales_page_page_id'] ) && isset( $request['sales_page_page_id'] ) ) {
			$sales_page = get_post( $request['sales_page_page_id'] );
			if ( $sales_page && is_a( $sales_page, 'WP_Post' ) ) {
				$prepared_item['sales_page_content_page_id'] = $request['sales_page_page_id']; // maybe allow only published pages?
			} else {
				$prepared_item['sales_page_content_page_id'] = 0;
			}
		}

		if ( ! empty( $schema['properties']['sales_page_url'] ) && isset( $request['sales_page_url'] ) ) {
			$prepared_item['sales_page_content_url'] = $request['sales_page_url'];
		}

		/**
		 * Filters the membership data for a response.
		 *
		 * @since [version]
		 *
		 * @param array           $prepared_item Array of membership item properties prepared for database.
		 * @param WP_REST_Request $request       Full details about the request.
		 * @param array           $schema        The item schema.
		 */
		return apply_filters( 'llms_rest_pre_insert_membership', $prepared_item, $request, $schema );

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

		// Auto enroll courses.
		if ( ! empty( $schema['properties']['auto_enroll'] ) && isset( $request['auto_enroll'] ) ) {
			$membership->add_auto_enroll_courses( $request['auto_enroll'], true );
		}

		// Membership catalog visibility.
		if ( ! empty( $schema['properties']['catalog_visibility'] ) && isset( $request['catalog_visibility'] ) ) {
			$membership->get_product()->set_catalog_visibility( $request['catalog_visibility'] );
		}
		// Instructors.
		if ( ! empty( $schema['properties']['instructors'] ) ) {

			$instructors = $request['instructors'];

			// When creating, if the instructor is not set, set it with the post author id.

			if ( $creating && ! isset( $instructors ) ) {
				$instructors = array_filter( array( $membership->get( 'author' ) ) );
			}

			if ( ! empty( $instructors ) ) {

				foreach ( $instructors as $instructor_id ) {
					$user_data = get_userdata( $instructor_id );
					if ( ! empty( $user_data ) ) {
						$instructors[] = array(
							'id'   => $instructor_id,
							'name' => $user_data->display_name,
						);
					}
				}
			}

			if ( ! empty( $instructors ) ) {
				$membership->set_instructors( $instructors );
			}
		}

		$to_set = array();

		/**
		 * The following properties have a default value that contains a placeholder ({{membership_id}}) that can be "expanded" only
		 * after the membership has been created.
		 */
		// Restriction message.
		if ( ! empty( $schema['properties']['restriction_message'] ) && isset( $request['restriction_message'] ) ) {
			if ( is_string( $request['restriction_message'] ) ) {
				$to_set['restriction_notice'] = $request['restriction_message'];
			} elseif ( isset( $request['restriction_message']['raw'] ) ) {
				$to_set['restriction_notice'] = $request['restriction_message']['raw'];
			}
		}

		// Needed until the following will be implemented: https://github.com/gocodebox/lifterlms/issues/908.
		$to_set['restriction_add_notice'] = empty( $to_set['restriction_notice'] ) ? 'no' : 'yes';

		// Are we creating a membership?
		// If so, replace the placeholder with the actual membership id.
		if ( $creating ) {

			$_to_expand_props = array(
				'restriction_notice',
			);

			$membership_id = $membership->get( 'id' );

			foreach ( $_to_expand_props as $prop ) {
				if ( ! empty( $to_set[ $prop ] ) ) {
					$to_set[ $prop ] = str_replace( '{{membership_id}}', $membership_id, $to_set[ $prop ] );
				}
			}
		} else { // Needed until the following will be implemented: https://github.com/gocodebox/lifterlms/issues/908.
			$_props = array(
				'restriction_add_notice',
			);
			foreach ( $_props as $_prop ) {
				if ( isset( $to_set[ $_prop ] ) && $to_set[ $_prop ] === $membership->get( $_prop ) ) {
					unset( $to_set[ $_prop ] );
				}
			}
		}

		// Set bulk.
		if ( ! empty( $to_set ) ) {
			$update = $course->set_bulk( $to_set, true );
			if ( is_wp_error( $update ) ) {
				$error = $update;
			}
		}

		if ( $error->errors ) {
			return $error;
		}

		return ! empty( $to_set );

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
				// Pricing Table.
				array(
					'callback' => 'lifterlms_template_pricing_table',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Membership $membership LLMS Membership.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $membership ) {
		$links         = parent::prepare_links( $membership );
		$membership_id = $membership->get( 'id' );

		unset( $links['content'] );

		$membership_links = array();

		// Access plans.
		$membership_links['access_plans'] = array(
			'href' => add_query_arg(
				'post',
				$membership_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'access-plans' ) )
			),
		);

		// Auto enrollent courses.
		$auto_enroll_courses = implode( ',', $membership->get_auto_enroll_courses() );
		if ( $auto_enroll_courses ) {
			$membership_links['auto_enrollment_courses'] = array(
				'href' => add_query_arg(
					'post',
					$auto_enroll_courses,
					rest_url( sprintf( '%s/%s', 'llms/v1', 'courses' ) )
				),
			);
		}

		// Enrollments.
		$membership_links['enrollments'] = array(
			'href' => rest_url( sprintf( '/%s/%s/%d/%s', $this->namespace, $this->rest_base, $membership_id, 'enrollments' ) ),
		);

		// Insturctors.
		$membership_links['instructors'] = array(
			'href' => add_query_arg(
				'post',
				$membership_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'instructors' ) )
			),
		);

		// Students.
		$membership_links['students'] = array(
			'href' => add_query_arg(
				'enrolled_in',
				$membership_id,
				rest_url( sprintf( '%s/%s', 'llms/v1', 'students' ) )
			),
		);

		$links = array_merge( $links, $membership_links );

		/**
		 * Filters the membership's links.
		 *
		 * @since [version]
		 *
		 * @param array           links       Links for the given membership.
		 * @param LLMS_Membership $membership LLMS Membership object.
		 */
		return apply_filters( 'llms_rest_membership_links', $links, $membership );

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

		unset( $query_params['post'], $query_params['status'] );

		$query_params['student'] = array(
			'description'       => __( 'Limit results to a specific student or a list of students. Accepts a single student id or a comma separated list of student ids.', 'lifterlms' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

}
