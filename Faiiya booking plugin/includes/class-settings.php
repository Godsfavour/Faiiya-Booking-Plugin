<?php
/**
 * Settings registry, defaults, and sanitization handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Settings {

	/**
	 * Option keys.
	 */
	const OPTION_GENERAL  = 'yab_general_settings';
	const OPTION_RULES    = 'yab_booking_rules';
	const OPTION_DEPOSIT  = 'yab_deposit_settings';
	const OPTION_PAYSTACK = 'yab_paystack_settings';
	const OPTION_EMAIL    = 'yab_email_settings';

	/**
	 * Retrieve a setting value with fallback to default.
	 *
	 * @param string $group Option group ('general', 'rules', 'deposit', 'paystack', 'email').
	 * @param string $key Setting key.
	 * @param mixed  $default Optional custom fallback.
	 * @return mixed
	 */
	public static function get( $group, $key, $default = null ) {
		$defaults = self::get_defaults( $group );
		$options  = get_option( self::get_option_name( $group ), array() );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
	}

	/**
	 * Update a single setting or entire group.
	 *
	 * @param string $group Option group.
	 * @param string|array $key Setting key or associative array of settings.
	 * @param mixed $value Value if key is string.
	 * @return bool
	 */
	public static function set( $group, $key, $value = null ) {
		$option_name = self::get_option_name( $group );
		$options     = get_option( $option_name, self::get_defaults( $group ) );

		if ( is_array( $key ) ) {
			$options = array_merge( $options, $key );
		} else {
			$options[ $key ] = $value;
		}

		return update_option( $option_name, self::sanitize_group( $group, $options ) );
	}

	/**
	 * Alias method to update a whole group of settings at once.
	 *
	 * @param string $group
	 * @param array $data
	 * @return bool
	 */
	public static function update_group( $group, $data ) {
		return self::set( $group, $data );
	}

	/**
	 * Get option name mapped to group.
	 *
	 * @param string $group
	 * @return string
	 */
	public static function get_option_name( $group ) {
		switch ( $group ) {
			case 'rules':
				return self::OPTION_RULES;
			case 'deposit':
			case 'pricing':
				return self::OPTION_DEPOSIT;
			case 'paystack':
				return self::OPTION_PAYSTACK;
			case 'email':
			case 'emails':
				return self::OPTION_EMAIL;
			case 'general':
			default:
				return self::OPTION_GENERAL;
		}
	}

	/**
	 * Default settings for all sections.
	 *
	 * @param string $group
	 * @return array
	 */
	public static function get_defaults( $group ) {
		switch ( $group ) {
			case 'general':
				return array(
					'business_name'   => 'Yasmine Artistry',
					'currency'        => 'NGN',
					'currency_symbol' => '₦',
					'timezone'        => wp_timezone_string(),
					'booking_page_id' => 0,
					'date_format'     => 'F j, Y',
					'time_format'     => 'g:i A',
				);

			case 'rules':
				return array(
					'min_advance_hours'     => 12,    // Must book at least 12 hours ahead
					'max_future_days'       => 60,    // Cannot book further than 60 days
					'default_buffer_mins'   => 30,    // Default travel/setup buffer between home visits
					'rescheduling_allowed'  => 1,     // 1 = allowed, 0 = disabled
					'reschedule_deadline_h' => 24,    // Must reschedule at least 24 hours prior
					'max_reschedules'       => 2,     // Maximum number of reschedules per booking
					'cancellation_allowed'  => 1,     // 1 = allowed, 0 = disabled
					'cancel_deadline_h'     => 48,    // 48 hours notice
				);

			case 'deposit':
				return array(
					'deposit_type'       => 'percentage', // 'percentage' or 'fixed'
					'deposit_percentage' => 30,           // 30% deposit
					'deposit_fixed'      => 5000.00,      // ₦5,000 fixed deposit
					'require_deposit'    => 1,            // 1 = required, 0 = optional
				);

			case 'paystack':
				return array(
					'mode'            => 'test', // 'test' or 'live'
					'test_public_key' => '',
					'test_secret_key' => '',
					'live_public_key' => '',
					'live_secret_key' => '',
				);

			case 'email':
			case 'emails':
				$admin_email = get_option( 'admin_email' );
				return array(
					'sender_name'         => 'Yasmine Artistry',
					'sender_email'        => $admin_email,
					'admin_notify_email'  => $admin_email,
					'customer_subj_conf'  => 'Booking Confirmed - {booking_reference} | Yasmine Artistry',
					'admin_subj_new'      => 'New Confirmed Booking: {booking_reference} - {customer_name}',
					'customer_subj_resch' => 'Booking Rescheduled - {booking_reference} | Yasmine Artistry',
					'customer_subj_canc'  => 'Booking Cancelled - {booking_reference} | Yasmine Artistry',
					'customer_body_conf'  => '',
					'admin_body_new'      => '',
					'customer_body_resch' => '',
					'customer_body_canc'  => '',
				);

			default:
				return array();
		}
	}

	/**
	 * Sanitize configuration values by group.
	 *
	 * @param string $group
	 * @param array $input
	 * @return array
	 */
	public static function sanitize_group( $group, $input ) {
		$sanitized = array();
		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		switch ( $group ) {
			case 'general':
				$sanitized['business_name']   = sanitize_text_field( $input['business_name'] ?? 'Yasmine Artistry' );
				$sanitized['admin_email']     = sanitize_email( $input['admin_email'] ?? get_option( 'admin_email' ) );
				$sanitized['currency']        = sanitize_text_field( $input['currency'] ?? 'NGN' );
				$sanitized['currency_symbol'] = sanitize_text_field( $input['currency_symbol'] ?? '₦' );
				$sanitized['timezone']        = sanitize_text_field( $input['timezone'] ?? wp_timezone_string() );
				$sanitized['booking_page_id'] = absint( $input['booking_page_id'] ?? 0 );
				$sanitized['date_format']     = sanitize_text_field( $input['date_format'] ?? 'F j, Y' );
				$sanitized['time_format']     = sanitize_text_field( $input['time_format'] ?? 'g:i A' );
				break;

			case 'rules':
				$sanitized['slot_interval_minutes']   = absint( $input['slot_interval_minutes'] ?? $input['slot_interval_mins'] ?? 30 );
				$sanitized['min_advance_hours']       = absint( $input['min_advance_hours'] ?? 12 );
				$sanitized['max_future_days']         = absint( $input['max_future_days'] ?? 60 );
				$sanitized['default_buffer_minutes']  = absint( $input['default_buffer_minutes'] ?? $input['default_buffer_mins'] ?? 30 );
				$sanitized['default_buffer_mins']     = $sanitized['default_buffer_minutes'];
				$sanitized['rescheduling_allowed']    = ! empty( $input['rescheduling_allowed'] ) ? 1 : 0;
				$sanitized['reschedule_cutoff_hours'] = absint( $input['reschedule_cutoff_hours'] ?? $input['reschedule_deadline_h'] ?? 24 );
				$sanitized['reschedule_deadline_h']   = $sanitized['reschedule_cutoff_hours'];
				$sanitized['max_reschedules']         = absint( $input['max_reschedules'] ?? 2 );
				$sanitized['cancellation_allowed']    = ! empty( $input['cancellation_allowed'] ) ? 1 : 0;
				$sanitized['cancel_deadline_h']       = absint( $input['cancel_deadline_h'] ?? 48 );
				break;

			case 'deposit':
			case 'pricing':
				$type = sanitize_key( $input['deposit_type'] ?? 'percentage' );
				$sanitized['deposit_type']       = in_array( $type, array( 'percentage', 'fixed', 'full' ), true ) ? $type : 'percentage';
				$sanitized['deposit_percentage'] = floatval( $input['deposit_percentage'] ?? $input['deposit_value'] ?? 30 );
				$sanitized['deposit_fixed']      = floatval( $input['deposit_fixed'] ?? $input['deposit_value'] ?? 5000.00 );
				$sanitized['deposit_value']      = floatval( $input['deposit_value'] ?? $sanitized['deposit_percentage'] );
				$sanitized['require_deposit']    = ! empty( $input['require_deposit'] ) ? 1 : 0;
				break;

			case 'paystack':
				$mode = sanitize_key( $input['mode'] ?? ( ! empty( $input['test_mode'] ) ? 'test' : 'live' ) );
				$sanitized['mode']            = in_array( $mode, array( 'test', 'live' ), true ) ? $mode : 'test';
				$sanitized['test_mode']       = ( 'test' === $sanitized['mode'] || ! empty( $input['test_mode'] ) ) ? 1 : 0;
				$sanitized['test_public_key'] = sanitize_text_field( trim( $input['test_public_key'] ?? '' ) );
				$sanitized['test_secret_key'] = sanitize_text_field( trim( $input['test_secret_key'] ?? '' ) );
				$sanitized['live_public_key'] = sanitize_text_field( trim( $input['live_public_key'] ?? '' ) );
				$sanitized['live_secret_key'] = sanitize_text_field( trim( $input['live_secret_key'] ?? '' ) );
				break;

			case 'email':
			case 'emails':
				$sanitized['sender_name']         = sanitize_text_field( $input['sender_name'] ?? 'Yasmine Artistry' );
				$sanitized['sender_email']        = sanitize_email( $input['sender_email'] ?? get_option( 'admin_email' ) );
				$sanitized['admin_notify_email']  = sanitize_email( $input['admin_notify_email'] ?? $input['admin_email'] ?? get_option( 'admin_email' ) );
				$sanitized['customer_subj_conf']  = sanitize_text_field( $input['customer_subj_conf'] ?? $input['customer_conf_subject'] ?? '' );
				$sanitized['admin_subj_new']      = sanitize_text_field( $input['admin_subj_new'] ?? $input['admin_new_subject'] ?? '' );
				$sanitized['customer_subj_resch'] = sanitize_text_field( $input['customer_subj_resch'] ?? $input['customer_resched_subject'] ?? '' );
				$sanitized['customer_subj_canc']  = sanitize_text_field( $input['customer_subj_canc'] ?? $input['customer_cancel_subject'] ?? '' );
				// Store customizable HTML content templates allowing safe email HTML markup
				$allowed_html = wp_kses_allowed_html( 'post' );
				$allowed_html['style'] = array();
				$sanitized['customer_body_conf']  = isset( $input['customer_body_conf'] ) ? wp_kses( wp_unslash( $input['customer_body_conf'] ), $allowed_html ) : '';
				$sanitized['admin_body_new']      = isset( $input['admin_body_new'] ) ? wp_kses( wp_unslash( $input['admin_body_new'] ), $allowed_html ) : '';
				$sanitized['customer_body_resch'] = isset( $input['customer_body_resch'] ) ? wp_kses( wp_unslash( $input['customer_body_resch'] ), $allowed_html ) : '';
				$sanitized['customer_body_canc']  = isset( $input['customer_body_canc'] ) ? wp_kses( wp_unslash( $input['customer_body_canc'] ), $allowed_html ) : '';
				break;
		}

		return $sanitized;
	}
}
