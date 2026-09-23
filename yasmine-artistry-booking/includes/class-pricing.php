<?php
/**
 * Server-side pricing calculation and dynamic deposit engine.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Pricing {

	/**
	 * Compute a verifiable price and deposit breakdown for a service and home-service location.
	 *
	 * @param int $service_id
	 * @param int $location_id
	 * @param string $payment_choice 'deposit' or 'full' (customer preference)
	 * @param int $extra_looks Number of extra looks requested (default 0)
	 * @return array|WP_Error
	 */
	public static function calculate_quote( $service_id, $location_id, $payment_choice = 'deposit', $extra_looks = 0 ) {
		$service = YAB_Service::get( $service_id );
		if ( ! $service || ! $service->is_active ) {
			return new WP_Error( 'invalid_service', __( 'The requested service is invalid or currently unavailable.', 'yasmine-artistry-booking' ) );
		}

		$location = YAB_Location::get( $location_id );
		if ( ! $location || ! $location->is_active ) {
			return new WP_Error( 'invalid_location', __( 'The requested service area is invalid or not currently serviced.', 'yasmine-artistry-booking' ) );
		}

		// Verify service offers this specific location
		if ( ! YAB_Service::is_location_available( $service->id, $location->id ) ) {
			return new WP_Error(
				'service_location_unavailable',
				sprintf(
					/* translators: 1: Service name, 2: Location name */
					__( '"%1$s" is not currently offered in %2$s. Please select an eligible location.', 'yasmine-artistry-booking' ),
					$service->name,
					$location->name
				)
			);
		}

		$extra_looks_count = max( 0, min( 10, intval( $extra_looks ) ) );
		$extra_look_rate   = floatval( YAB_Settings::get( 'deposit', 'extra_look_rate', 50000.00 ) );
		$extra_looks_fee   = round( $extra_looks_count * $extra_look_rate, 2 );

		$base_price   = floatval( $service->base_price );
		$location_fee = YAB_Location::calculate_fee( $location->id, $base_price );
		$total_amount = round( $base_price + $location_fee + $extra_looks_fee, 2 );

		// Deposit calculation from policy settings (default 50% for bridal artistry)
		$require_deposit    = (bool) YAB_Settings::get( 'deposit', 'require_deposit', 1 );
		$deposit_type       = YAB_Settings::get( 'deposit', 'deposit_type', 'percentage' );
		$deposit_percentage = floatval( YAB_Settings::get( 'deposit', 'deposit_percentage', 50 ) );
		$deposit_fixed      = floatval( YAB_Settings::get( 'deposit', 'deposit_fixed', 5000.00 ) );

		$standard_deposit = 0.00;
		if ( $require_deposit && $total_amount > 0 ) {
			if ( 'full' === $deposit_type ) {
				$standard_deposit = $total_amount;
			} elseif ( 'percentage' === $deposit_type ) {
				$pct              = max( 1.0, min( 100.0, $deposit_percentage ) );
				$standard_deposit = round( ( $total_amount * ( $pct / 100 ) ), 2 );
			} else {
				// Fixed deposit capped at total amount
				$standard_deposit = min( $total_amount, max( 0.00, $deposit_fixed ) );
			}
		}

		// Check if customer elected to pay the full balance upfront
		if ( 'full' === $payment_choice || 'full' === $deposit_type ) {
			$deposit_required  = $total_amount;
			$balance_remaining = 0.00;
			$payment_mode      = 'full';
		} else {
			$deposit_required  = $standard_deposit;
			$balance_remaining = max( 0.00, round( $total_amount - $deposit_required, 2 ) );
			$payment_mode      = 'deposit';
		}

		$currency        = YAB_Settings::get( 'general', 'currency', 'NGN' );
		$currency_symbol = YAB_Settings::get( 'general', 'currency_symbol', '₦' );

		return array(
			'service_id'           => intval( $service->id ),
			'service_name'         => $service->name,
			'duration_minutes'     => intval( $service->duration_minutes ),
			'buffer_minutes'       => intval( $service->buffer_minutes ),
			'location_id'          => intval( $location->id ),
			'location_name'        => $location->name,
			'base_price'           => $base_price,
			'location_fee'         => $location_fee,
			'extra_looks'          => $extra_looks_count,
			'extra_look_rate'      => $extra_look_rate,
			'extra_looks_fee'      => $extra_looks_fee,
			'total_amount'         => $total_amount,
			'standard_deposit'     => $standard_deposit,
			'deposit_required'     => $deposit_required,
			'balance_remaining'    => $balance_remaining,
			'payment_mode'         => $payment_mode,
			'allow_full_payment'   => true,
			'currency'             => $currency,
			'currency_symbol'      => $currency_symbol,
			'formatted_base'       => self::format_amount( $base_price, $currency_symbol ),
			'formatted_fee'        => self::format_amount( $location_fee, $currency_symbol ),
			'formatted_extra'      => self::format_amount( $extra_looks_fee, $currency_symbol ),
			'formatted_total'      => self::format_amount( $total_amount, $currency_symbol ),
			'formatted_deposit'    => self::format_amount( $deposit_required, $currency_symbol ),
			'formatted_balance'    => self::format_amount( $balance_remaining, $currency_symbol ),
		);
	}

	/**
	 * Format monetary amount with currency symbol and thousands separator.
	 *
	 * @param float $amount
	 * @param string|null $symbol
	 * @return string
	 */
	public static function format_amount( $amount, $symbol = null ) {
		if ( null === $symbol ) {
			$symbol = YAB_Settings::get( 'general', 'currency_symbol', '₦' );
		}
		return $symbol . number_format( floatval( $amount ), 2, '.', ',' );
	}
}
