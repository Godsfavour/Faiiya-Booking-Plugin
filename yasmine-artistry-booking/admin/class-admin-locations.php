<?php
/**
 * Admin Locations & Travel Surcharges management controller.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin_Locations {

	public static function render() {
		YAB_Security::check_permissions();

		self::handle_form_submission();

		include YAB_PLUGIN_DIR . 'admin/views/locations.php';
	}

	private static function handle_form_submission() {
		if ( empty( $_POST['yab_loc_action'] ) ) {
			return;
		}

		check_admin_referer( 'yab_save_location', 'yab_loc_nonce' );

		$action = sanitize_key( $_POST['yab_loc_action'] );

		if ( 'delete' === $action ) {
			$id = absint( $_POST['location_id'] ?? 0 );
			YAB_Location::delete( $id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-locations', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'save' === $action ) {
			$id   = absint( $_POST['location_id'] ?? 0 );
			$data = array(
				'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
				'fee_type'    => sanitize_key( $_POST['fee_type'] ?? 'fixed' ),
				'fee_amount'  => floatval( $_POST['fee_amount'] ?? 0 ),
				'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
			);

			if ( empty( $data['name'] ) ) {
				return;
			}

			if ( $id > 0 ) {
				YAB_Location::update( $id, $data );
			} else {
				YAB_Location::create( $data );
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-locations', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
}
