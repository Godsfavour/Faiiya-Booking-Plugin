<?php
/**
 * Admin Services management controller.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin_Services {

	public static function render() {
		YAB_Security::check_permissions();

		self::handle_form_submission();

		include YAB_PLUGIN_DIR . 'admin/views/services.php';
	}

	private static function handle_form_submission() {
		if ( empty( $_POST['yab_service_action'] ) ) {
			return;
		}

		check_admin_referer( 'yab_save_service', 'yab_service_nonce' );

		$action = sanitize_key( $_POST['yab_service_action'] );

		if ( 'delete' === $action ) {
			$id = absint( $_POST['service_id'] ?? 0 );
			YAB_Service::delete( $id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-services', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'save' === $action ) {
			$id   = absint( $_POST['service_id'] ?? 0 );
			$location_ids = isset( $_POST['location_ids'] ) && is_array( $_POST['location_ids'] )
				? array_map( 'absint', $_POST['location_ids'] )
				: array();

			$data = array(
				'category_id'      => absint( $_POST['category_id'] ?? 0 ),
				'name'             => sanitize_text_field( $_POST['name'] ?? '' ),
				'image_url'        => esc_url_raw( $_POST['image_url'] ?? '' ),
				'description'      => sanitize_textarea_field( $_POST['description'] ?? '' ),
				'duration_minutes' => absint( $_POST['duration_minutes'] ?? 60 ),
				'buffer_minutes'   => absint( $_POST['buffer_minutes'] ?? 30 ),
				'base_price'       => floatval( $_POST['base_price'] ?? 0 ),
				'is_active'        => isset( $_POST['is_active'] ) ? 1 : 0,
				'location_ids'     => $location_ids,
			);

			if ( empty( $data['name'] ) ) {
				return;
			}

			if ( $id > 0 ) {
				YAB_Service::update( $id, $data );
			} else {
				YAB_Service::create( $data );
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-services', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
}
