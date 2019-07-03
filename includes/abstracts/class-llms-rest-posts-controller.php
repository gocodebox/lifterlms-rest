<?php
/**
 * REST LLMS Posts Controller Class
 *
 * @package LLMS_REST
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 *  TODO:
 * - how to handle password required posts
 * - do we really want to reply with "Bad request" for llms_rest_bad_request ?
 * - Implement everything :D
 */

/**
 * LLMS_REST_Posts_Controller
 *
 * @since [version]
 */
abstract class LLMS_REST_Posts_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'llms/v1';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;


	/**
	 * Register routes.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), // see class-wp-rest-controller.php.
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => __( 'Post password. Required if the post is password protected.', 'lifterlms' ),
				'type'        => 'string',
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'lifterlms' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ), // see class-wp-rest-controller.php.
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						// added even if not in the specs.
						'force' => array(
							'description' => __( 'Bypass the trash and force course deletion.', 'lifterlms' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}


	/**
	 * Check if a given request has access to read items.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		// Everybody can list llms posts (in read mode).
		if ( 'edit' === $request['context'] && ! $this->check_post_permissions( 'edit' ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Get a collection of LLMS posts.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args, $request );

		$page        = (int) $query_args['paged'];
		$max_pages   = $query_results['pages'];
		$total_posts = $query_results['total'];
		$objects     = $query_results['objects'];

		if ( $page > $max_pages && $total_posts > 0 ) {
			return llms_rest_bad_request_error();
		}

		$response = rest_ensure_response( $objects );

		$response->header( 'X-WP-Total', $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$base           = add_query_arg( urlencode_deep( $request_params ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}


	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return llms_rest_bad_request_error();
		}

		if ( ! $this->check_post_permissions( 'create' ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		if ( ! $this->check_post_permissions( 'read', $object->get( 'id' ) ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$response = $this->prepare_item_for_response( $object, $request );

		return $response;

	}

	/**
	 * Prepare objects query.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = array();

		/*
		* This array defines mappings between public API query parameters whose
		* values are accepted as-passed, and their internal WP_Query parameter
		* name equivalents (some are the same). Only values which are also
		* present in $registered will be set.
		*/
		$parameter_mappings = array(
			'order'   => 'order',
			'orderby' => 'orderby',
			'page'    => 'paged',
			'exclude' => 'post__not_in',
			'include' => 'post__in',
		);

		/*
		* For each known parameter which is both registered and present in the request,
		* set the parameter's value on the query $args.
		*/
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		$query_args = $this->prepare_items_query( $args, $request );

		return $query_args;

	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		if ( ! $this->check_post_permissions( 'edit', $object->get( 'id' ) ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;
	}

	/**
	 * Updates a single post.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$prepared_object = $this->prepare_object_for_database( $request );

		if ( is_wp_error( $prepared_object ) ) {
			return $prepared_object;
		}

		// convert the post object to an array, otherwise wp_update_post will expect non-escaped input.
		$object_id = wp_update_post( wp_slash( (array) $prepared_object ), true );

		if ( is_wp_error( $object_id ) ) {
			if ( 'db_update_error' === $object_id->get_error_code() ) {
				$object_id->add_data( array( 'status' => 500 ) );
			} else {
				$object_id->add_data( array( 'status' => 400 ) );
			}
			return $object_id;
		}

		$object = $this->get_object( $object_id );

		$fields_update = $this->update_additional_fields_for_object( $object, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		return $this->prepare_item_for_response( $object, $request );

	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @since [version]
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		if ( ! $this->check_post_permissions( 'delete', $object->get( 'id' ) ) ) {
			return llms_rest_authorization_required_error();
		}

		return true;

	}

	/**
	 * Deletes a single llms post.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$id    = $object->get( 'id' );
		$force = (bool) $request['force'];

		$supports_trash = ( EMPTY_TRASH_DAYS > 0 );

		$request->set_param( 'context', 'edit' );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$previous = $this->prepare_item_for_response( $object, $request );
			$result   = wp_delete_post( $id, true );
			$response = new WP_REST_Response();
			$response->set_data(
				array(
					'deleted'  => true,
					'previous' => $previous->get_data(),
				)
			);
		} else {
			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				/* translators: %s: force=true */
				return new WP_Error( 'llms_rest_trash_not_supported', sprintf( __( "The post does not support trashing. Set '%s' to delete.", 'lifterlms' ), 'force=true' ), array( 'status' => 501 ) );
			}

			// Otherwise, only trash if we haven't already.
			if ( 'trash' === $object->get( 'status' ) ) {
				return new WP_Error( 'llms_rest_already_trashed', __( 'The post has already been deleted.', 'lifterlms' ), array( 'status' => 410 ) );
			}

			// (Note that internally this falls through to `wp_delete_post` if
			// the trash is disabled.)
			$result = wp_trash_post( $id );
			$object = $this->get_object( $id );

			$response = $this->prepare_item_for_response( $object, $request );
		}

		if ( ! $result ) {
			return llms_rest_bad_request_error();
		}

		return $response;

	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Post_Model|WP_Error
	 */
	abstract protected function get_object( $id );

	/**
	 * Get objects.
	 *
	 * @since [version]
	 *
	 * @param array           $query_args Query args.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function get_objects( $query_args, $request ) {

		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$objects = array();

		// Allow access to all password protected posts if the context is edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', '__return_false' );
		}

		foreach ( $result as $post ) {

			if ( ! $this->check_post_permissions( 'read', $post->post_id ) ) {
				continue;
			}

			$data      = $this->prepare_item_for_response( $this->get_object( $post ), $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		// Allow access to all password protected posts if the context is edit.
		if ( 'edit' === $request['context'] ) {
			remove_filter( 'post_password_required', '__return_false' );
		}

		$page        = (int) $query_args['paged'];
		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return array(
			'objects' => $objects,
			'total'   => (int) $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		);

	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Post_Model $object  object object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_object_for_response( $object, $request ) {

		$data = array(
			'id'               => $object->get( 'id' ),
			'date_created'     => $object->get_date( 'date', 'Y-m-d H:i:s' ),
			'date_created_gmt' => $object->get_date( 'date_gmt', 'Y-m-d H:i:s' ),
			'date_updated'     => $object->get_date( 'modified', 'Y-m-d H:i:s' ),
			'date_updated_gmt' => $object->get_date( 'modified_gmt', 'Y-m-d H:i:s' ),
			'menu_order'       => $object->get( 'menu_order' ),
			'title'            => array(
				'raw'      => $object->get( 'title', 'raw' ),
				'rendered' => $object->get( 'title' ),
			),
			'password'         => $object->get( 'password' ),
			'slug'             => $object->get( 'name' ),
			'post_type'        => $this->post_type,
			'permalink'        => get_permalink( $object->get( 'id' ) ),
			'status'           => $object->get( 'status' ),
			'featured_media'   => (int) get_post_thumbnail_id( $object->get( 'id' ) ),
			'comment_status'   => $object->get( 'comment_status' ),
			'ping_status'      => $object->get( 'ping_status' ),
			'content'          => array(
				'raw'      => $object->get( 'content', true ),
				'rendered' => post_password_required( $object->get( 'id' ) ) ? '' : apply_filters( 'the_content', $object->get( 'content', true ) ),
			),
			'excerpt'          => array(
				'raw'      => $object->get( 'excerpt', true ),
				'rendered' => post_password_required( $object->get( 'id' ) ) ? '' : apply_filters( 'the_excerpt', $object->get( 'excerpt' ) ),
			),
		);

		return $data;

	}

	/**
	 * Prepare a single item for the REST response
	 *
	 * @since [version]
	 *
	 * @param LLMS_Post_Model $object  LLMS post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $object, $request ) {

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Need to set the global $post because of references to the global $post when e.g. filtering the content, or processing blocks/shortcodes.
		global $post;
		$temp = $post;
		$post = $object->get( 'post' ); // phpcs:ignore
		setup_postdata( $post );
		$data = $this->prepare_object_for_response( $object, $request );
		$post = $temp; // phpcs:ignore
		wp_reset_postdata();

		// Filter data including only schema props.
		$data = array_intersect_key( $data, array_flip( $this->get_fields_for_response( $request ) ) );
		// Filter data by context. E.g. in "view" mode the password property won't be allowed.
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $object );
		$response->add_links( $links );

		return $response;
	}

	/**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since [version]
	 *
	 * @param array           $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
	 * @param WP_REST_Request $request       Optional. Full details about the request.
	 * @return array Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {

		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			$query_args[ $key ] = $value;
		}

		// Map to proper WP_Query orderby param.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'           => 'ID',
				'title'        => 'post_name',
				'data_created' => 'post_date',
				'date_updated' => 'post_modified',
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		// Turn exclude and include params into proper arrays.
		foreach ( array( 'post__in', 'post__not_in' ) as $arg ) {
			if ( isset( $query_args[ $arg ] ) && ! is_array( $query_args[ $arg ] ) ) {
				$query_args[ $arg ] = array_map( 'absint', explode( ',', $query_args[ $arg ] ) );
			}
		}

		return $query_args;

	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return stdClass|WP_Error Post object or WP_Error.
	 */
	protected function prepare_object_for_database( $request ) {

		$prepared_object = new stdClass();

		// LLMS Post ID.
		if ( isset( $request['id'] ) ) {
			$existing_object = $this->get_object( absint( $request['id'] ) );
			if ( is_wp_error( $existing_object ) ) {
				return $existing_object;
			}

			$prepared_object->ID = absint( $request['id'] );
		}

		$schema = $this->get_item_schema();

		// LLMS Post title.
		if ( ! empty( $schema['properties']['title'] ) && isset( $request['title'] ) ) {
			if ( is_string( $request['title'] ) ) {
				$prepared_object->post_title = $request['title'];
			} elseif ( ! empty( $request['title']['raw'] ) ) {
				$prepared_object->post_title = $request['title']['raw'];
			}
		}

		// LLMS Post content.
		if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_object->post_content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_object->post_content = $request['content']['raw'];
			}
		}

		// LLMS Post excerpt.
		if ( ! empty( $schema['properties']['excerpt'] ) && isset( $request['excerpt'] ) ) {
			if ( is_string( $request['excerpt'] ) ) {
				$prepared_object->post_excerpt = $request['excerpt'];
			} elseif ( isset( $request['excerpt']['raw'] ) ) {
				$prepared_object->post_excerpt = $request['excerpt']['raw'];
			}
		}

		// LLMS Post status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
			$prepared_object->post_status = $request['status'];
		}

		// LLMS Post date.
		if ( ! empty( $schema['properties']['date_created'] ) && ! empty( $request['date_created'] ) ) {
			$date_data = rest_get_date_with_gmt( $request['date_created'] );

			if ( ! empty( $date_data ) ) {
				list( $prepared_object->post_date, $prepared_object->post_date_gmt ) = $date_data;
			}
		}

		return $prepared_object;

	}

	/**
	 * Get the LLMS Posts's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique Course Identifier. The WordPress Post ID.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'     => array(
					'description' => __( 'Creation date. Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt' => array(
					'description' => __( 'Creation date (in GMT). Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_updated'     => array(
					'description' => __( 'Date last modified. Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_updated_gmt' => array(
					'description' => __( 'Date last modified (in GMT). Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'menu_order'       => array(
					'description' => __( 'Creation date (in GMT). Format: Y-m-d H:i:s', 'lifterlms' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'absint',
					),
				),
				'title'            => array(
					'description' => __( 'Post title.', 'lifterlms' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
						'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
					),
					'required'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Raw title. Useful when displaying title in the WP Block Editor. Only returned in edit context.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'Rendered title.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'content'          => array(
					'type'        => 'object',
					'description' => __( 'The HTML content of the post.', 'lifterlms' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
						'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
					),
					'required'    => true,
					'properties'  => array(
						'rendered' => array(
							'description' => __( 'Rendered HTML content.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'raw'      => array(
							'description' => __( 'Raw HTML content. Useful when displaying title in the WP Block Editor. Only returned in edit context.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
					),
				),
				'excerpt'          => array(
					'type'        => 'object',
					'description' => __( 'The HTML excerpt of the post.', 'lifterlms' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_object_for_database().
						'validate_callback' => null, // Note: validation implemented in self::prepare_object_for_database().
					),
					'properties'  => array(
						'rendered' => array(
							'description' => __( 'Rendered HTML excerpt.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'raw'      => array(
							'description' => __( 'Raw HTML excerpt. Useful when displaying title in the WP Block Editor. Only returned in edit context.', 'lifterlms' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
					),
				),
				'permalink'        => array(
					'description' => __( 'Post URL.', 'lifterlms' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'             => array(
					'description' => __( 'Post URL slug.', 'lifterlms' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'post_type'        => array(
					'description' => __( 'LifterLMS custom post type', 'lifterlms' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'status'           => array(
					'description' => __( 'The publication status of the post.', 'lifterlms' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future', 'trash', 'auto-draft' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'password'         => array(
					'description' => __( 'Password used to protect access to the content.', 'lifterlms' ),
					'type'        => 'string',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future', 'trash', 'auto-draft' ) ),
					'context'     => array( 'edit' ),
				),
				'featured_media'   => array(
					'description' => __( 'Featured image ID.', 'lifterlms' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'comment_status'   => array(
					'description' => __( 'Post comment status. Default comment status dependent upon general WordPress post discussion settings.', 'lifterlms' ),
					'type'        => 'string',
					'default'     => 'open',
					'enum'        => array( 'open', 'closed' ),
					'context'     => array( 'view', 'edit' ),
				),
				'ping_status'      => array(
					'description' => __( 'Post ping status. Default ping status dependent upon general WordPress post discussion settings.', 'lifterlms' ),
					'type'        => 'string',
					'default'     => 'open',
					'enum'        => array( 'open', 'closed' ),
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		// unset search for the moment.
		unset( $query_params['search'] );

		// page and per_page params are already specified in WP_Rest_Controller->get_collection_params().
		$query_params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.', 'lifterlms' ),
			'type'        => 'string',
			'default'     => 'asc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'lifterlms' ),
			'type'        => 'string',
			'default'     => 'id',
			'enum'        => array(
				'id',
				'title',
				'date_created',
				'date_updated',
				'menu_order',
			),
		);

		$query_params['include'] = array(
			'description' => __( 'Limit results to a list of ids. Accepts a single id or a comma separated list of ids.', 'lifterlms' ),
			'type'        => 'string',
		);

		$query_params['exclude'] = array(
			'description' => __( 'Exclude a list of ids from results. Accepts a single id or a comma separated list of ids.', 'lifterlms' ),
			'type'        => 'string',
		);

		return $query_params;
	}

	/**
	 * Creates a single LLMS post.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		$prepared_object = $this->prepare_object_for_database( $request );

		if ( is_wp_error( $prepared_object ) ) {
			return $prepared_object;
		}

		$object = $this->create_llms_post( $prepared_object );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$fields_update = $this->update_additional_fields_for_object( $object, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( $object, $request );

		$response->set_status( 201 );

		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $object->get( 'id' ) ) ) );

		return $response;
	}

	/**
	 * Get an LLMS_Course
	 *
	 * @since [version]
	 *
	 * @param array $object_args Object args.
	 * @return LLMS_Post_Model|WP_Error
	 */
	abstract protected function create_llms_post( $object_args );

	/**
	 * Prepare links for the request.
	 *
	 * @param LLMS_Post_Model $object  Object data.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $object ) {

		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get( 'id' ) ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		// If we have a featured media, add that.
		$featured_media = get_post_thumbnail_id( $object->get( 'id' ) );
		if ( $featured_media ) {
			$image_url = rest_url( 'wp/v2/media/' . $featured_media );

			$links['https://api.w.org/featuredmedia'] = array(
				'href'       => $image_url,
				'embeddable' => true,
			);
		}

		$taxonomies = get_object_taxonomies( $this->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = add_query_arg(
					'post',
					$object->get( 'id' ),
					rest_url( 'wp/v2/' . $tax_base )
				);

				$links['https://api.w.org/term'][] = array(
					'href'     => $terms_url,
					'taxonomy' => $tax,
				);
			}
		}

		return $links;

	}

	/**
	 * Return an LLMS Post Model raw meta field
	 *
	 * @since [version]
	 *
	 * @param LLMS_Post_Model $object Object.
	 * @param string          $field  Meta key.
	 * @return mixed
	 */
	protected function get_object_raw_meta( $object, $field ) {

		if ( metadata_exists( 'post', $object->get( 'id' ), $object->get( 'meta_prefix' ) . $field ) ) {
			$raw_value = get_post_meta( $object->get( 'id' ), $object->get( 'meta_prefix' ) . $field, true );
		} else {
			$raw_value = $object->get_default_value( $field );
		}
		return $raw_value;

	}

	/**
	 * Check permissions of posts on REST API.
	 *
	 * @since [version]
	 *
	 * @param string $context   Optional. Request context. Default 'read'.
	 * @param int    $object_id Optional. Post ID. Default 0.
	 * @return bool
	 */
	public function check_post_permissions( $context = 'read', $object_id = 0 ) {

		$post_type = $this->post_type;

		$contexts = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
		);

		if ( 'revision' === $post_type ) {
			$permission = false;
		} else {
			$cap              = $contexts[ $context ];
			$post_type_object = get_post_type_object( $post_type );
			$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
		}

		return apply_filters( 'check_post_permissions', $permission, $context, $object_id, $post_type );

	}

}
