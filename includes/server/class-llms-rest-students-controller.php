<?php
/**
 * REST Resource Controller for Students.
 *
 * @package  LifterLMS_REST/Classes/Controllers
 *
 * @since [version]
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_REST_Students_Controller class..
 *
 * @since [version]
 */
class LLMS_REST_Students_Controller extends LLMS_REST_Users_Controller {

	/**
	 * Resource ID or Name.
	 *
	 * @var string
	 */
	protected $resource_name = 'student';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'students';

	/**
	 * Determine if current user has permission to create a user.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! current_user_can( 'create_students' ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to create new users.', 'lifterlms' ) );
		}

		return $this->check_roles_permissions( $request );

	}

	/**
	 * Determine if current user has permission to delete a user.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		if ( ! current_user_can( 'delete_students', $request['id'] ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to delete this student.', 'lifterlms' ) );
		}

		return true;

	}

	/**
	 * Retrieves the query params for the objects collection.
	 *
	 * @since [version]
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['enrolled_in'] = array(
			'description' => __( 'Retrieve only students enrolled in the specified course(s) and/or membership(s). Accepts a single WP Post ID or a comma separated list of IDs.', 'lifterlms' ),
			'type'        => 'string',
		);

		$params['enrolled_not_in'] = array(
			'description' => __( 'Retrieve only students not enrolled in the specified course(s) and/or membership(s). Accepts a single WP Post ID or a comma separated list of IDs.', 'lifterlms' ),
			'type'        => 'string',
		);

		return $params;

	}

	/**
	 * Get the item schema.
	 *
	 * @since [version]
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema                                   = parent::get_item_schema();
		$schema['properties']['roles']['default'] = array( 'student' );

		return $schema;

	}


	/**
	 * Determine if current user has permission to get a user.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {

		$user_id = $request['id'];

		if ( get_current_user_id() === $user_id ) {
			return true;
		}

		if ( ! current_user_can( 'view_students', $user_id ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to view this student.', 'lifterlms' ) );
		}

		return true;

	}

	/**
	 * Determine if current user has permission to list users.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! empty( $request['roles'] ) && ! current_user_can( 'view_others_students' ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to filter students by role.', 'lifterlms' ) );
		}

		if ( ! current_user_can( 'view_students' ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to list students.', 'lifterlms' ) );
		}

		return true;

	}

	/**
	 * Get object.
	 *
	 * @since [version]
	 *
	 * @param int $id Object ID.
	 * @return LLMS_Student|WP_Error
	 */
	protected function get_object( $id ) {

		$student = llms_get_student( $id );
		return $student ? $student : llms_rest_not_found_error();

	}

	/**
	 * Prepare links for the request.
	 *
	 * @since [version]
	 *
	 * @param obj $object Item object.
	 * @return array
	 */
	protected function prepare_links( $object ) {

		$links = parent::prepare_links( $object );

		$links['enrollments'] = array(
			'href' => sprintf( '%s/enrollments', $links['self']['href'] ),
		);
		$links['progress']    = array(
			'href' => sprintf( '%s/progress', $links['self']['href'] ),
		);

		return $links;

	}

	/**
	 * Update item.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function update_item( $request ) {

		$object = $this->get_object( $request['id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Ensure we're not trying to update the email to an email that already exists.
		$owner_id = email_exists( $request['email'] );

		if ( $owner_id && $owner_id !== $request['id'] ) {
			return llms_rest_bad_request_error( __( 'Invalid email address.', 'lifterlms' ) );
		}

		// Cannot change a username.
		if ( ! empty( $request['username'] ) && $request['username'] !== $object->get( 'user_login' ) ) {
			return llms_rest_bad_request_error( __( 'Username is not editable.', 'lifterlms' ) );
		}

		return parent::update_item( $request );

	}


	/**
	 * Determine if current user has permission to update a user.
	 *
	 * @since [version]
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {

		if ( get_current_user_id() === $request['id'] ) {
			return true;
		}

		if ( ! current_user_can( 'edit_students', $request['id'] ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to edit this student.', 'lifterlms' ) );
		}

		return $this->check_roles_permissions( $request );

	}

}
