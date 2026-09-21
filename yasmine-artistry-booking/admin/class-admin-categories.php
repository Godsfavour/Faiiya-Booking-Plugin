<?php
/**
 * Admin Categories management controller.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin_Categories {

	public static function render() {
		YAB_Security::check_permissions();

		self::handle_form_submission();

		include YAB_PLUGIN_DIR . 'admin/views/categories.php';
	}

	private static function handle_form_submission() {
		if ( empty( $_POST['yab_cat_action'] ) ) {
			return;
		}

		check_admin_referer( 'yab_save_category', 'yab_cat_nonce' );

		$action = sanitize_key( $_POST['yab_cat_action'] );

		if ( 'delete' === $action ) {
			$id = absint( $_POST['category_id'] ?? 0 );
			YAB_Category::delete( $id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-categories', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'save' === $action ) {
			$id   = absint( $_POST['category_id'] ?? 0 );
			$data = array(
				'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
				'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
			);

			if ( empty( $data['name'] ) ) {
				return;
			}

			if ( $id > 0 ) {
				YAB_Category::update( $id, $data );
			} else {
				YAB_Category::create( $data );
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'yab-categories', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}
}
