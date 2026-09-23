<?php
/**
 * Admin Settings management controller.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Admin_Settings {

	public static function render() {
		YAB_Security::check_permissions();

		self::handle_save();

		$current_tab = sanitize_key( $_GET['tab'] ?? 'general' );
		include YAB_PLUGIN_DIR . 'admin/views/settings.php';
	}

	private static function handle_save() {
		if ( empty( $_POST['yab_settings_action'] ) ) {
			return;
		}

		check_admin_referer( 'yab_save_settings_nonce', 'yab_settings_nonce' );

		$tab = sanitize_key( $_POST['current_tab'] ?? 'general' );

		if ( 'general' === $tab ) {
			$data = array(
				'business_name'   => sanitize_text_field( $_POST['business_name'] ?? 'Yasmine Artistry' ),
				'admin_email'     => sanitize_email( $_POST['admin_email'] ?? get_option( 'admin_email' ) ),
				'currency'        => sanitize_text_field( $_POST['currency'] ?? 'NGN' ),
				'currency_symbol' => sanitize_text_field( $_POST['currency_symbol'] ?? '₦' ),
				'timezone'        => sanitize_text_field( $_POST['timezone'] ?? 'Africa/Lagos' ),
			);
			YAB_Settings::update_group( 'general', $data );
		} elseif ( 'rules' === $tab ) {
			$data = array(
				'slot_interval_minutes'   => absint( $_POST['slot_interval_minutes'] ?? 30 ),
				'default_buffer_minutes'  => absint( $_POST['default_buffer_minutes'] ?? 30 ),
				'min_advance_hours'       => absint( $_POST['min_advance_hours'] ?? 4 ),
				'max_future_days'         => absint( $_POST['max_future_days'] ?? 60 ),
				'max_reschedules'         => absint( $_POST['max_reschedules'] ?? 2 ),
				'reschedule_cutoff_hours' => absint( $_POST['reschedule_cutoff_hours'] ?? 6 ),
				'cancellation_allowed'    => isset( $_POST['cancellation_allowed'] ) ? 1 : 0,
			);
			YAB_Settings::update_group( 'rules', $data );
		} elseif ( 'pricing' === $tab ) {
			$data = array(
				'deposit_type'       => sanitize_key( $_POST['deposit_type'] ?? 'percentage' ),
				'deposit_value'      => floatval( $_POST['deposit_value'] ?? 50 ),
				'deposit_percentage' => floatval( $_POST['deposit_value'] ?? 50 ),
				'deposit_fixed'      => floatval( $_POST['deposit_value'] ?? 5000 ),
				'require_deposit'    => isset( $_POST['require_deposit'] ) ? 1 : 0,
				'extra_look_rate'    => floatval( $_POST['extra_look_rate'] ?? 50000 ),
			);
			YAB_Settings::update_group( 'pricing', $data );
		} elseif ( 'paystack' === $tab ) {
			$data = array(
				'mode'            => ! empty( $_POST['test_mode'] ) ? 'test' : 'live',
				'test_mode'       => isset( $_POST['test_mode'] ) ? 1 : 0,
				'test_public_key' => sanitize_text_field( $_POST['test_public_key'] ?? '' ),
				'test_secret_key' => sanitize_text_field( $_POST['test_secret_key'] ?? '' ),
				'live_public_key' => sanitize_text_field( $_POST['live_public_key'] ?? '' ),
				'live_secret_key' => sanitize_text_field( $_POST['live_secret_key'] ?? '' ),
			);
			YAB_Settings::update_group( 'paystack', $data );
		} elseif ( 'emails' === $tab || 'email' === $tab ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			$allowed_html['style'] = array();

			$data = array(
				'sender_name'               => sanitize_text_field( $_POST['sender_name'] ?? 'Yasmine Artistry' ),
				'sender_email'              => sanitize_email( $_POST['sender_email'] ?? get_option( 'admin_email' ) ),
				'admin_notify_email'        => sanitize_email( $_POST['admin_notify_email'] ?? get_option( 'admin_email' ) ),
				'customer_conf_subject'     => sanitize_text_field( $_POST['customer_conf_subject'] ?? 'Appointment Confirmed - {service_name}' ),
				'customer_subj_conf'        => sanitize_text_field( $_POST['customer_conf_subject'] ?? '' ),
				'customer_resched_subject'  => sanitize_text_field( $_POST['customer_resched_subject'] ?? 'Appointment Rescheduled - {service_name}' ),
				'customer_subj_resch'       => sanitize_text_field( $_POST['customer_resched_subject'] ?? '' ),
				'customer_cancel_subject'   => sanitize_text_field( $_POST['customer_cancel_subject'] ?? 'Appointment Cancelled - {service_name}' ),
				'customer_subj_canc'        => sanitize_text_field( $_POST['customer_cancel_subject'] ?? '' ),
				'admin_subj_new'            => sanitize_text_field( $_POST['admin_subj_new'] ?? 'New Confirmed Booking: {booking_reference} - {customer_name}' ),
				// Editable email contents for all email types
				'customer_body_conf'        => isset( $_POST['customer_body_conf'] ) ? wp_kses( wp_unslash( $_POST['customer_body_conf'] ), $allowed_html ) : '',
				'admin_body_new'            => isset( $_POST['admin_body_new'] ) ? wp_kses( wp_unslash( $_POST['admin_body_new'] ), $allowed_html ) : '',
				'customer_body_resch'       => isset( $_POST['customer_body_resch'] ) ? wp_kses( wp_unslash( $_POST['customer_body_resch'] ), $allowed_html ) : '',
				'customer_body_canc'        => isset( $_POST['customer_body_canc'] ) ? wp_kses( wp_unslash( $_POST['customer_body_canc'] ), $allowed_html ) : '',
			);
			YAB_Settings::update_group( 'emails', $data );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'yab-settings', 'tab' => $tab, 'saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
