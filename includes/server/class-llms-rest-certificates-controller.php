<?php
/**
 * REST Certificates Controller
 *
 * @package LifterLMS/Classes/REST
 *
 * @since [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Certificates_Controller class.
 *
 * @since [version]
 */
class LLMS_REST_Certificates_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'certificates';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_certificate';


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
		$schema['properties']['title']['description'] = __( 'Certificate Template Title', 'lifterlms-assignments' );

		// Update defaults.
		$schema['properties']['content']['required'] = false;

		// Remove unnecessary props.
		$remove = array(
			'status',
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

	/**
	 * Prepare a single object output for response.
	 *
	 * @param  $certificate  Certificate object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 * @since [version]
	 */
	protected function prepare_object_for_response( $certificate, $request ) {

		$data = parent::prepare_object_for_response( $certificate, $request );

		/**
		 * Filters the assignment data for a response.
		 *
		 * @param array             $data    Array of assignment properties prepared for response.
		 * @param LLMS_Assignment   $certificate   Assignment object.
		 * @param WP_REST_Request   $request Full details about the request.
		 *
		 *@since [version]
		 */
		return apply_filters( 'llms_rest_prepare_certificate_object_response', $data, $certificate, $request );
	}

	protected function get_object( $id ) {
		return get_post( $id, OBJECT_K );
	}

	protected function check_read_permission( $object ) {
		if ( current_user_can( 'edit_post', $object->ID ) ) {
			return true;
		}

		return false;
	}

	protected function prepare_object_data_for_response( $object, $request ) {

		return array(
			'id'    => $object->ID,
			'title' => $object->post_title,
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since [version]
	 *
	 * @param LLMS_Assignment $assignment Assignment oblect.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $certificate, $request ) {

		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $certificate->ID ) ),
			),
		);

		return $links;
	}
}
