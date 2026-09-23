<?php
/**
 * Location and dynamic home-service surcharge model.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Location {

	/**
	 * Create a new service location / area.
	 *
	 * @param array $data Location data.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new WP_Error( 'invalid_name', __( 'Location area name is required.', 'yasmine-artistry-booking' ) );
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$slug = self::generate_unique_slug( $slug );

		$fee_type = sanitize_key( $data['fee_type'] ?? 'fixed' );
		if ( ! in_array( $fee_type, array( 'fixed', 'percentage' ), true ) ) {
			$fee_type = 'fixed';
		}

		$fee_amount  = max( 0.00, floatval( $data['fee_amount'] ?? 0.00 ) );
		$description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$sort_order  = isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0;
		$is_active   = isset( $data['is_active'] ) ? ( $data['is_active'] ? 1 : 0 ) : 1;
		$now         = current_time( 'mysql' );

		$table = YAB_Database::table( 'locations' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'description' => $description,
				'fee_type'    => $fee_type,
				'fee_amount'  => $fee_amount,
				'is_active'   => $is_active,
				'sort_order'  => $sort_order,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to create location.', 'yasmine-artistry-booking' ) );
		}

		$id = $wpdb->insert_id;
		YAB_Logger::log( 'location_created', sprintf( 'Location "%s" created (ID: %d, Fee: %s %0.2f)', $name, $id, $fee_type, $fee_amount ) );

		return $id;
	}

	/**
	 * Update an existing location.
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool|WP_Error
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'not_found', __( 'Location not found.', 'yasmine-artistry-booking' ) );
		}

		$update_fields = array(
			'updated_at' => current_time( 'mysql' ),
		);
		$formats       = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new WP_Error( 'invalid_name', __( 'Location area name cannot be empty.', 'yasmine-artistry-booking' ) );
			}
			$update_fields['name'] = $name;
			$formats[]             = '%s';
		}

		if ( isset( $data['slug'] ) ) {
			$slug = sanitize_title( $data['slug'] );
			if ( $slug !== $existing->slug ) {
				$update_fields['slug'] = self::generate_unique_slug( $slug, $id );
				$formats[]             = '%s';
			}
		}

		if ( isset( $data['fee_type'] ) ) {
			$fee_type = sanitize_key( $data['fee_type'] );
			if ( in_array( $fee_type, array( 'fixed', 'percentage' ), true ) ) {
				$update_fields['fee_type'] = $fee_type;
				$formats[]                 = '%s';
			}
		}

		if ( isset( $data['fee_amount'] ) ) {
			$update_fields['fee_amount'] = max( 0.00, floatval( $data['fee_amount'] ) );
			$formats[]                   = '%f';
		}

		if ( isset( $data['description'] ) ) {
			$update_fields['description'] = sanitize_textarea_field( $data['description'] );
			$formats[]                    = '%s';
		}

		if ( isset( $data['sort_order'] ) ) {
			$update_fields['sort_order'] = intval( $data['sort_order'] );
			$formats[]                   = '%d';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_fields['is_active'] = $data['is_active'] ? 1 : 0;
			$formats[]                  = '%d';
		}

		$table = YAB_Database::table( 'locations' );
		$res   = $wpdb->update( $table, $update_fields, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( false === $res ) {
			return new WP_Error( 'db_error', __( 'Failed to update location.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log( 'location_updated', sprintf( 'Location ID %d updated', $id ) );
		return true;
	}

	/**
	 * Delete a location.
	 *
	 * @param int $id
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id = absint( $id );

		// Check if any bookings reference this location
		$bookings_table = YAB_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bookings_table} WHERE location_id = %d AND booking_status IN ('confirmed', 'pending_payment')",
				$id
			)
		);

		if ( intval( $active_bookings ) > 0 ) {
			return new WP_Error(
				'has_bookings',
				sprintf(
					/* translators: %d: active bookings count */
					__( 'Cannot delete location: %d active booking(s) are assigned to it. Deactivate instead.', 'yasmine-artistry-booking' ),
					intval( $active_bookings )
				)
			);
		}

		$table   = YAB_Database::table( 'locations' );
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $deleted ) {
			YAB_Logger::log( 'location_deleted', sprintf( 'Location ID %d deleted', $id ) );
			return true;
		}

		return false;
	}

	/**
	 * Retrieve a single location by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = YAB_Database::table( 'locations' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) ) );
	}

	/**
	 * Retrieve all locations, optionally filtered by service association.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table           = YAB_Database::table( 'locations' );
		$junction_table  = YAB_Database::table( 'service_locations' );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['active_only'] ) ) {
			$where[] = 'l.is_active = 1';
		}

		// If filtered by service_id
		if ( ! empty( $args['service_id'] ) ) {
			$service_id = absint( $args['service_id'] );
			$linked_ids = YAB_Service::get_linked_location_ids( $service_id );
			
			// If this service has explicitly linked locations, restrict to them
			if ( ! empty( $linked_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $linked_ids ), '%d' ) );
				$where[]      = "l.id IN ({$placeholders})";
				foreach ( $linked_ids as $lid ) {
					$values[] = $lid;
				}
			}
			// If empty, all active locations apply to this service
		}

		$where_clause = implode( ' AND ', $where );
		$query        = "SELECT l.* FROM {$table} l WHERE {$where_clause} ORDER BY l.sort_order ASC, l.name ASC";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $query );
	}

	/**
	 * Calculate the dynamic location surcharge on the server.
	 * Never trust a surcharge from the frontend!
	 *
	 * @param int $location_id
	 * @param float $base_price
	 * @return float Surcharge amount rounded to 2 decimal places.
	 */
	public static function calculate_fee( $location_id, $base_price ) {
		$location = self::get( $location_id );
		if ( ! $location || ! $location->is_active ) {
			return 0.00;
		}

		$base_price = max( 0.00, floatval( $base_price ) );
		$fee_amount = floatval( $location->fee_amount );

		if ( 'percentage' === $location->fee_type ) {
			return round( ( $base_price * ( $fee_amount / 100 ) ), 2 );
		}

		// Fixed surcharge
		return round( $fee_amount, 2 );
	}

	/**
	 * Generate a unique slug.
	 *
	 * @param string $slug
	 * @param int $exclude_id
	 * @return string
	 */
	private static function generate_unique_slug( $slug, $exclude_id = 0 ) {
		global $wpdb;
		$table = YAB_Database::table( 'locations' );

		$original_slug = $slug;
		$suffix        = 1;

		while ( true ) {
			if ( $exclude_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND id != %d", $slug, $exclude_id ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug ) );
			}

			if ( ! $exists ) {
				return $slug;
			}

			$suffix++;
			$slug = $original_slug . '-' . $suffix;
		}
	}
}
