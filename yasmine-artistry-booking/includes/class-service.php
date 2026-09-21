<?php
/**
 * Service data model and CRUD handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Service {

	/**
	 * Create a new service offering.
	 *
	 * @param array $data Service attributes.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new WP_Error( 'invalid_name', __( 'Service name is required.', 'yasmine-artistry-booking' ) );
		}

		$category_id = absint( $data['category_id'] ?? 0 );
		if ( ! $category_id || ! YAB_Category::get( $category_id ) ) {
			return new WP_Error( 'invalid_category', __( 'A valid category must be selected for this service.', 'yasmine-artistry-booking' ) );
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$slug = self::generate_unique_slug( $slug );

		$base_price       = max( 0.00, floatval( $data['base_price'] ?? 0 ) );
		$duration_minutes = max( 15, absint( $data['duration_minutes'] ?? 60 ) );
		$buffer_minutes   = max( 0, absint( $data['buffer_minutes'] ?? 15 ) );
		$description      = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$image_url        = isset( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '';
		$sort_order       = isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0;
		$is_active        = isset( $data['is_active'] ) ? ( $data['is_active'] ? 1 : 0 ) : 1;
		$now              = current_time( 'mysql' );

		$table = YAB_Database::table( 'services' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'category_id'      => $category_id,
				'name'             => $name,
				'slug'             => $slug,
				'description'      => $description,
				'image_url'        => $image_url,
				'base_price'       => $base_price,
				'duration_minutes' => $duration_minutes,
				'buffer_minutes'   => $buffer_minutes,
				'sort_order'       => $sort_order,
				'is_active'        => $is_active,
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to create service.', 'yasmine-artistry-booking' ) );
		}

		$id = $wpdb->insert_id;
		YAB_Logger::log( 'service_created', sprintf( 'Service "%s" created (ID: %d, Category: %d, Price: %0.2f)', $name, $id, $category_id, $base_price ) );

		return $id;
	}

	/**
	 * Update an existing service.
	 *
	 * @param int $id Service ID.
	 * @param array $data Update values.
	 * @return bool|WP_Error
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'not_found', __( 'Service not found.', 'yasmine-artistry-booking' ) );
		}

		$update_fields = array(
			'updated_at' => current_time( 'mysql' ),
		);
		$formats       = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new WP_Error( 'invalid_name', __( 'Service name cannot be empty.', 'yasmine-artistry-booking' ) );
			}
			$update_fields['name'] = $name;
			$formats[]             = '%s';
		}

		if ( isset( $data['category_id'] ) ) {
			$category_id = absint( $data['category_id'] );
			if ( ! YAB_Category::get( $category_id ) ) {
				return new WP_Error( 'invalid_category', __( 'Selected category does not exist.', 'yasmine-artistry-booking' ) );
			}
			$update_fields['category_id'] = $category_id;
			$formats[]                    = '%d';
		}

		if ( isset( $data['slug'] ) ) {
			$slug = sanitize_title( $data['slug'] );
			if ( $slug !== $existing->slug ) {
				$update_fields['slug'] = self::generate_unique_slug( $slug, $id );
				$formats[]             = '%s';
			}
		}

		if ( isset( $data['description'] ) ) {
			$update_fields['description'] = sanitize_textarea_field( $data['description'] );
			$formats[]                    = '%s';
		}

		if ( isset( $data['image_url'] ) ) {
			$update_fields['image_url'] = esc_url_raw( $data['image_url'] );
			$formats[]                  = '%s';
		}

		if ( isset( $data['base_price'] ) ) {
			$update_fields['base_price'] = max( 0.00, floatval( $data['base_price'] ) );
			$formats[]                   = '%f';
		}

		if ( isset( $data['duration_minutes'] ) ) {
			$update_fields['duration_minutes'] = max( 15, absint( $data['duration_minutes'] ) );
			$formats[]                         = '%d';
		}

		if ( isset( $data['buffer_minutes'] ) ) {
			$update_fields['buffer_minutes'] = max( 0, absint( $data['buffer_minutes'] ) );
			$formats[]                       = '%d';
		}

		if ( isset( $data['sort_order'] ) ) {
			$update_fields['sort_order'] = intval( $data['sort_order'] );
			$formats[]                   = '%d';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_fields['is_active'] = $data['is_active'] ? 1 : 0;
			$formats[]                  = '%d';
		}

		$table = YAB_Database::table( 'services' );
		$res   = $wpdb->update( $table, $update_fields, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( false === $res ) {
			return new WP_Error( 'db_error', __( 'Failed to update service.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log( 'service_updated', sprintf( 'Service ID %d updated', $id ) );
		return true;
	}

	/**
	 * Delete a service.
	 *
	 * @param int $id Service ID.
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id = absint( $id );

		// Check if any active bookings reference this service
		$bookings_table = YAB_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bookings_table} WHERE service_id = %d AND booking_status IN ('confirmed', 'pending_payment')",
				$id
			)
		);

		if ( intval( $active_bookings ) > 0 ) {
			return new WP_Error(
				'has_bookings',
				sprintf(
					/* translators: %d: active bookings count */
					__( 'Cannot delete service: %d scheduled or pending booking(s) reference it. Deactivate the service instead.', 'yasmine-artistry-booking' ),
					intval( $active_bookings )
				)
			);
		}

		$table   = YAB_Database::table( 'services' );
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $deleted ) {
			YAB_Logger::log( 'service_deleted', sprintf( 'Service ID %d deleted', $id ) );
			return true;
		}

		return false;
	}

	/**
	 * Retrieve a single service by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = YAB_Database::table( 'services' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) ) );
	}

	/**
	 * Retrieve all services with optional category and status filtering.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$services_table   = YAB_Database::table( 'services' );
		$categories_table = YAB_Database::table( 'categories' );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['category_id'] ) ) {
			$where[]  = 's.category_id = %d';
			$values[] = absint( $args['category_id'] );
		}

		if ( isset( $args['active_only'] ) && $args['active_only'] ) {
			$where[] = 's.is_active = 1';
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT s.*, c.name AS category_name, c.slug AS category_slug 
				  FROM {$services_table} s 
				  LEFT JOIN {$categories_table} c ON s.category_id = c.id 
				  WHERE {$where_clause} 
				  ORDER BY s.sort_order ASC, s.name ASC";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $query );
	}

	/**
	 * Generate a unique slug ensuring no collision.
	 *
	 * @param string $slug
	 * @param int $exclude_id
	 * @return string
	 */
	private static function generate_unique_slug( $slug, $exclude_id = 0 ) {
		global $wpdb;
		$table = YAB_Database::table( 'services' );

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
