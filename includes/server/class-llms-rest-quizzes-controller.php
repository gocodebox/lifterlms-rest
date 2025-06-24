<?php
defined( 'ABSPATH' ) || exit;

class LLMS_REST_Quizzes_Controller extends LLMS_REST_Posts_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'quizzes';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'llms_quiz';

	public function get_item_schema_base() {
		$schema                                     = parent::get_item_schema_base();
		$schema['properties']['id']['context'][]    = 'embed';
		$schema['properties']['title']['context'][] = 'embed';
		// Add more fields as needed
		return $schema;
	}

	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_private_quizzes' ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to view this quiz.', 'lifterlms' ) );
		}
		return true;
	}

	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_private_quizzes' ) ) {
			return llms_rest_authorization_required_error( __( 'You are not allowed to list quizzes.', 'lifterlms' ) );
		}
		return true;
	}

	/**
	 * Whether the trash is supported.
	 *
	 * @since [version]
	 *
	 * @return bool True if the trash is supported, false otherwise.
	 */
	protected function is_trash_supported() {
		return false;
	}
}
