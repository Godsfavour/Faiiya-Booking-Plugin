<?php
/**
 * Admin view: Services list and management form.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = absint( $_GET['edit'] ?? 0 );
$editing = $edit_id > 0 ? YAB_Service::get( $edit_id ) : null;

$services   = YAB_Service::get_all( array( 'active_only' => false ) );
$categories = YAB_Category::get_all( array( 'active_only' => true ) );
$currency   = YAB_Settings::get( 'general', 'currency_symbol', '₦' );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Service Catalog', 'yasmine-artistry-booking' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Service saved successfully.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php elseif ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Service deleted.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php endif; ?>

	<div class="yab-grid-2col">

		<!-- Left: Form to Add/Edit Service -->
		<div class="yab-form-col">
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php echo $editing ? esc_html__( 'Edit Service', 'yasmine-artistry-booking' ) : esc_html__( 'Add New Service', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<form method="post">
						<?php wp_nonce_field( 'yab_save_service', 'yab_service_nonce' ); ?>
						<input type="hidden" name="yab_service_action" value="save">
						<input type="hidden" name="service_id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>">

						<p>
							<label for="name"><strong><?php esc_html_e( 'Service Name', 'yasmine-artistry-booking' ); ?> *</strong></label>
							<input type="text" name="name" id="name" class="widefat" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required placeholder="e.g. Bridal Glam Makeup (Home Service)">
						</p>

						<p>
							<label for="category_id"><strong><?php esc_html_e( 'Category', 'yasmine-artistry-booking' ); ?></strong></label>
							<select name="category_id" id="category_id" class="widefat">
								<option value="0"><?php esc_html_e( '-- Uncategorized --', 'yasmine-artistry-booking' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $editing ? $editing->category_id : 0, $cat->id ); ?>>
										<?php echo esc_html( $cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>

						<!-- Service Image Picker (Configurable from uploaded images / media library) -->
						<div class="yab-media-field-wrap" style="margin-bottom: 16px;">
							<label><strong><?php esc_html_e( 'Service Image', 'yasmine-artistry-booking' ); ?></strong></label>
							<p class="description" style="margin-top: 2px; margin-bottom: 8px;">
								<?php esc_html_e( 'Select an image from your WordPress Media Library or enter a direct image URL.', 'yasmine-artistry-booking' ); ?>
							</p>
							<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
								<input type="url" name="image_url" id="yab-service-image-url" class="widefat" value="<?php echo $editing && ! empty( $editing->image_url ) ? esc_url( $editing->image_url ) : ''; ?>" placeholder="https://example.com/images/bridal-glam.jpg">
								<button type="button" class="button" id="yab-upload-service-img-btn" style="white-space: nowrap;">
									<?php esc_html_e( 'Choose from Media Library', 'yasmine-artistry-booking' ); ?>
								</button>
							</div>
							<div id="yab-service-img-preview-wrap" style="margin-top: 8px;">
								<img id="yab-service-image-preview" src="<?php echo $editing && ! empty( $editing->image_url ) ? esc_url( $editing->image_url ) : ''; ?>" style="max-width: 140px; max-height: 100px; border-radius: 8px; border: 1px solid #cbd5e1; object-fit: cover; display: <?php echo $editing && ! empty( $editing->image_url ) ? 'block' : 'none'; ?>;" alt="Preview">
								<button type="button" class="button-link-delete" id="yab-remove-service-img-btn" style="margin-top: 4px; color: #dc2626; display: <?php echo $editing && ! empty( $editing->image_url ) ? 'inline-block' : 'none'; ?>;">
									<?php esc_html_e( 'Remove Image', 'yasmine-artistry-booking' ); ?>
								</button>
							</div>
						</div>

						<p>
							<label for="base_price"><strong><?php printf( esc_html__( 'Base Price (%s) *', 'yasmine-artistry-booking' ), esc_html( $currency ) ); ?></strong></label>
							<input type="number" step="0.01" name="base_price" id="base_price" class="widefat" value="<?php echo $editing ? esc_attr( $editing->base_price ) : '0.00'; ?>" required>
						</p>

						<div class="yab-form-row">
							<p>
								<label for="duration_minutes"><strong><?php esc_html_e( 'Duration (Minutes) *', 'yasmine-artistry-booking' ); ?></strong></label>
								<input type="number" step="5" name="duration_minutes" id="duration_minutes" class="widefat" value="<?php echo $editing ? esc_attr( $editing->duration_minutes ) : '60'; ?>" required>
							</p>
							<p>
								<label for="buffer_minutes"><strong><?php esc_html_e( 'Travel Buffer (Minutes) *', 'yasmine-artistry-booking' ); ?></strong></label>
								<input type="number" step="5" name="buffer_minutes" id="buffer_minutes" class="widefat" value="<?php echo $editing ? esc_attr( $editing->buffer_minutes ) : '30'; ?>" required>
								<span class="description"><?php esc_html_e( 'Travel time after appointment before next client.', 'yasmine-artistry-booking' ); ?></span>
							</p>
						</div>

						<p>
							<label for="description"><strong><?php esc_html_e( 'Description / Inclusions', 'yasmine-artistry-booking' ); ?></strong></label>
							<textarea name="description" id="description" class="widefat" rows="3"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
						</p>

						<p>
							<label>
								<input type="checkbox" name="is_active" value="1" <?php checked( $editing ? $editing->is_active : 1, 1 ); ?>>
								<strong><?php esc_html_e( 'Active for online bookings', 'yasmine-artistry-booking' ); ?></strong>
							</label>
						</p>

						<p>
							<button type="submit" class="button button-primary">
								<?php echo $editing ? esc_html__( 'Update Service', 'yasmine-artistry-booking' ) : esc_html__( 'Create Service', 'yasmine-artistry-booking' ); ?>
							</button>
							<?php if ( $editing ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-services' ) ); ?>" class="button">
									<?php esc_html_e( 'Cancel', 'yasmine-artistry-booking' ); ?>
								</a>
							<?php endif; ?>
						</p>
					</form>
				</div>
			</div>
		</div>

		<!-- Right: Services Table -->
		<div class="yab-list-col">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'Image', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Service Name', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Category', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Price', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Duration', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Status', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'yasmine-artistry-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $services ) ) : ?>
						<?php foreach ( $services as $s ) : ?>
							<tr>
								<td>
									<?php if ( ! empty( $s->image_url ) ) : ?>
										<img src="<?php echo esc_url( $s->image_url ); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; display: block;">
									<?php else : ?>
										<div style="width: 40px; height: 40px; border-radius: 6px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 11px;">✦</div>
									<?php endif; ?>
								</td>
								<td><strong><?php echo esc_html( $s->name ); ?></strong></td>
								<td><?php echo esc_html( $s->category_name ? $s->category_name : '—' ); ?></td>
								<td><strong><?php echo esc_html( YAB_Pricing::format_amount( $s->base_price, $currency . ' ' ) ); ?></strong></td>
								<td><?php printf( esc_html__( '%d mins (+%d buffer)', 'yasmine-artistry-booking' ), $s->duration_minutes, $s->buffer_minutes ); ?></td>
								<td>
									<span class="yab-status-badge <?php echo $s->is_active ? 'yab-status-confirmed' : 'yab-status-cancelled'; ?>">
										<?php echo $s->is_active ? esc_html__( 'Active', 'yasmine-artistry-booking' ) : esc_html__( 'Inactive', 'yasmine-artistry-booking' ); ?>
									</span>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-services&edit=' . $s->id ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'yasmine-artistry-booking' ); ?>
									</a>
									<form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this service?');">
										<?php wp_nonce_field( 'yab_save_service', 'yab_service_nonce' ); ?>
										<input type="hidden" name="yab_service_action" value="delete">
										<input type="hidden" name="service_id" value="<?php echo esc_attr( $s->id ); ?>">
										<button type="submit" class="button button-small button-link-delete" style="color: #a00;">
											<?php esc_html_e( 'Delete', 'yasmine-artistry-booking' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6" style="text-align: center; padding: 24px; color: #718096;">
								<?php esc_html_e( 'No services added yet. Create your first service on the left.', 'yasmine-artistry-booking' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>
