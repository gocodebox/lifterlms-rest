<?php
/**
 * REST Awarded Certificates Controller
 *
 * @package LifterLMS/Classes/REST
 *
 * @since [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Awarded_Certificates_Controller class.
 *
 * @since [version]
 */
class LLMS_REST_Awarded_Certificates_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'awarded-certificates';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_my_certificate';

	protected $llms_post_class = 'LLMS_User_Certificate';

	/**
	 * Schema properties available for ordering the collection.
	 *
	 * @var string[]
	 */
	protected $orderby_properties = array(
		'id',
		'title',
		'date_created',
		'date_updated',
		'order',
		'relevance',
	);

	/**
	 * Get the Section's schema, conforming to JSON Schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// Update language.
		$schema['properties']['title']['description'] = __( 'Awarded Certificate Title', 'lifterlms' );

		$schema['properties']['certificate_id'] = array(
			'description' => __( 'The ID of the certificate template.', 'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
			'readonly'    => true,
		);

		$schema['properties']['student_id'] = array(
			'description' => __( 'The student ID the certificate was awarded to.', 'lifterlms' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
			'readonly'    => true,
		);

		// Update defaults.
		$schema['properties']['content']['required'] = false;

		// Remove unnecessary props.
		$remove = array(
			'comment_status',
			'password',
			'ping_status',
			'post_type',
			'featured_media',
		);
		foreach ( $remove as $prop ) {
			unset( $schema['properties'][ $prop ] );
		}

		return $schema;
	}

	public function register_routes() {

		// Only registering read-only routes for this controller.
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
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'lifterlms' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_get_item_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}


	protected function get_object( $id_or_object ) {
		return new LLMS_User_Certificate( $id_or_object );
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since [version]
	 *
	 * @param LLMS_User_Certificate $certificate  Certificate object.
	 * @param WP_REST_Request       $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_object_for_response( $certificate, $request ) {

		$data = parent::prepare_object_for_response( $certificate, $request );

		$data['certificate_id'] = $certificate->get( 'parent' );
		$data['student_id']     = $certificate->get( 'author' );

		/**
		 * Filters the assignment data for a response.
		 *
		 * @param array             $data    Array of assignment properties prepared for response.
		 * @param LLMS_Assignment   $certificate   Assignment object.
		 * @param WP_REST_Request   $request Full details about the request.
		 *
		 *@since [version]
		 */
		return apply_filters( 'llms_rest_prepare_assignment_object_response', $data, $certificate, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since [version]
	 *
	 * @param LLMS_User_Certificate $certificate Certificate oblect.
	 * @param WP_REST_Request       $request Request object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $certificate, $request ) {

		$links = parent::prepare_links( $certificate, $request );

		$links['student'] = array(
			'href'       => rest_url(
				sprintf( '/%s/%s/%d', 'llms/v1', 'students', $certificate->get( 'author' ) )
			),
			'embeddable' => true,
		);

		unset( $links['content'] );

		return $links;
	}
}
