<?php
/**
 * Category data model and CRUD handler.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Category {

	/**
	 * Create a new category.
	 *
	 * @param array $data Input fields.
	 * @return int|WP_Error Category ID or error.
	 */
	public static function create( $data ) {
		global $wpdb;

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new WP_Error( 'invalid_name', __( 'Category name is required.', 'yasmine-artistry-booking' ) );
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$slug = self::generate_unique_slug( $slug );

		$description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$sort_order  = isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0;
		$is_active   = isset( $data['is_active'] ) ? ( $data['is_active'] ? 1 : 0 ) : 1;
		$now         = current_time( 'mysql' );

		$table = YAB_Database::table( 'categories' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'description' => $description,
				'sort_order'  => $sort_order,
				'is_active'   => $is_active,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to create category.', 'yasmine-artistry-booking' ) );
		}

		$id = $wpdb->insert_id;
		YAB_Logger::log( 'category_created', sprintf( 'Category "%s" created (ID: %d)', $name, $id ) );

		return $id;
	}

	/**
	 * Update an existing category.
	 *
	 * @param int $id Category ID.
	 * @param array $data Fields to update.
	 * @return bool|WP_Error
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$id       = absint( $id );
		$existing = self::get( $id );
		if ( ! $existing ) {
			return new WP_Error( 'not_found', __( 'Category not found.', 'yasmine-artistry-booking' ) );
		}

		$update_fields = array(
			'updated_at' => current_time( 'mysql' ),
		);
		$formats       = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new WP_Error( 'invalid_name', __( 'Category name cannot be empty.', 'yasmine-artistry-booking' ) );
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

		$table = YAB_Database::table( 'categories' );
		$res   = $wpdb->update( $table, $update_fields, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( false === $res ) {
			return new WP_Error( 'db_error', __( 'Failed to update category.', 'yasmine-artistry-booking' ) );
		}

		YAB_Logger::log( 'category_updated', sprintf( 'Category ID %d updated', $id ) );
		return true;
	}

	/**
	 * Delete a category.
	 *
	 * @param int $id Category ID.
	 * @return bool|WP_Error
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		// Check if any services belong to this category
		$services_table = YAB_Database::table( 'services' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$service_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$services_table} WHERE category_id = %d", $id ) );

		if ( intval( $service_count ) > 0 ) {
			return new WP_Error(
				'has_services',
				sprintf(
					/* translators: %d: number of services */
					__( 'Cannot delete category: %d active service(s) are still assigned to it.', 'yasmine-artistry-booking' ),
					intval( $service_count )
				)
			);
		}

		$table   = YAB_Database::table( 'categories' );
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $deleted ) {
			YAB_Logger::log( 'category_deleted', sprintf( 'Category ID %d deleted', $id ) );
			return true;
		}

		return false;
	}

	/**
	 * Retrieve a single category by ID.
	 *
	 * @param int $id Category ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = YAB_Database::table( 'categories' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint( $id ) ) );
	}

	/**
	 * Retrieve a single category by slug.
	 *
	 * @param string $slug
	 * @return object|null
	 */
	public static function get_by_slug( $slug ) {
		global $wpdb;
		$table = YAB_Database::table( 'categories' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", sanitize_title( $slug ) ) );
	}

	/**
	 * Retrieve all categories.
	 *
	 * @param array $args Filter options: 'active_only' => bool.
	 * @return array List of category objects.
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = YAB_Database::table( 'categories' );

		$where = '1=1';
		if ( ! empty( $args['active_only'] ) ) {
			$where .= ' AND is_active = 1';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY sort_order ASC, name ASC" );
	}

	/**
	 * Generate a unique slug ensuring no collision.
	 *
	 * @param string $slug Base slug.
	 * @param int $exclude_id Optional ID to exclude from check.
	 * @return string
	 */
	private static function generate_unique_slug( $slug, $exclude_id = 0 ) {
		global $wpdb;
		$table = YAB_Database::table( 'categories' );

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
