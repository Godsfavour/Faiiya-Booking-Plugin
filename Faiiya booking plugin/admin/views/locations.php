<?php
/**
 * Admin view: Coverage Locations & Travel Surcharges.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id   = absint( $_GET['edit'] ?? 0 );
$editing   = $edit_id > 0 ? YAB_Location::get( $edit_id ) : null;
$locations = YAB_Location::get_all( array( 'active_only' => false ) );
$currency  = YAB_Settings::get( 'general', 'currency_symbol', '₦' );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Home Service Coverage Areas & Travel Fees', 'yasmine-artistry-booking' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Location saved.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php elseif ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Location deleted.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php endif; ?>

	<div class="yab-grid-2col">

		<div class="yab-form-col">
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php echo $editing ? esc_html__( 'Edit Coverage Area', 'yasmine-artistry-booking' ) : esc_html__( 'Add Coverage Area', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<form method="post">
						<?php wp_nonce_field( 'yab_save_location', 'yab_loc_nonce' ); ?>
						<input type="hidden" name="yab_loc_action" value="save">
						<input type="hidden" name="location_id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>">

						<p>
							<label for="loc_name"><strong><?php esc_html_e( 'Location / Zone Name', 'yasmine-artistry-booking' ); ?> *</strong></label>
							<input type="text" name="name" id="loc_name" class="widefat" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required placeholder="e.g. Lagos Island / Victoria Island / Lekki Phase 1">
						</p>

						<div class="yab-form-row">
							<p>
								<label for="fee_type"><strong><?php esc_html_e( 'Fee Calculation Type', 'yasmine-artistry-booking' ); ?></strong></label>
								<select name="fee_type" id="fee_type" class="widefat">
									<option value="fixed" <?php selected( $editing ? $editing->fee_type : 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount (+Travel Fee)', 'yasmine-artistry-booking' ); ?></option>
									<option value="percentage" <?php selected( $editing ? $editing->fee_type : 'fixed', 'percentage' ); ?>><?php esc_html_e( 'Percentage of Service Total', 'yasmine-artistry-booking' ); ?></option>
								</select>
							</p>
							<p>
								<label for="fee_amount"><strong><?php esc_html_e( 'Fee Amount', 'yasmine-artistry-booking' ); ?></strong></label>
								<input type="number" step="0.01" name="fee_amount" id="fee_amount" class="widefat" value="<?php echo $editing ? esc_attr( $editing->fee_amount ) : '0.00'; ?>" required>
								<span class="description"><?php esc_html_e( 'Set to 0.00 for free coverage zones.', 'yasmine-artistry-booking' ); ?></span>
							</p>
						</div>

						<p>
							<label for="loc_desc"><strong><?php esc_html_e( 'Area Details / Boundaries', 'yasmine-artistry-booking' ); ?></strong></label>
							<textarea name="description" id="loc_desc" class="widefat" rows="2" placeholder="e.g. Covers Ikoyi, Victoria Island, Oniru, and Lekki Toll Gate"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
						</p>

						<p>
							<label>
								<input type="checkbox" name="is_active" value="1" <?php checked( $editing ? $editing->is_active : 1, 1 ); ?>>
								<strong><?php esc_html_e( 'Active for customer selection', 'yasmine-artistry-booking' ); ?></strong>
							</label>
						</p>

						<p>
							<button type="submit" class="button button-primary">
								<?php echo $editing ? esc_html__( 'Update Location', 'yasmine-artistry-booking' ) : esc_html__( 'Create Location', 'yasmine-artistry-booking' ); ?>
							</button>
							<?php if ( $editing ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-locations' ) ); ?>" class="button">
									<?php esc_html_e( 'Cancel', 'yasmine-artistry-booking' ); ?>
								</a>
							<?php endif; ?>
						</p>
					</form>
				</div>
			</div>
		</div>

		<div class="yab-list-col">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Area Name', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Travel Fee / Surcharge', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Details', 'yasmine-artistry-booking' ); ?></th>
						<th style="width: 90px;"><?php esc_html_e( 'Status', 'yasmine-artistry-booking' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Actions', 'yasmine-artistry-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $locations ) ) : ?>
						<?php foreach ( $locations as $loc ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $loc->name ); ?></strong></td>
								<td>
									<?php
									if ( 'percentage' === $loc->fee_type ) {
										printf( '+%0.1f%%', $loc->fee_amount );
									} else {
										echo $loc->fee_amount > 0 ? esc_html( '+' . YAB_Pricing::format_amount( $loc->fee_amount, $currency . ' ' ) ) : '<span style="color:#276749;">' . esc_html__( 'Free', 'yasmine-artistry-booking' ) . '</span>';
									}
									?>
								</td>
								<td><?php echo esc_html( $loc->description ? $loc->description : '—' ); ?></td>
								<td>
									<span class="yab-status-badge <?php echo $loc->is_active ? 'yab-status-confirmed' : 'yab-status-cancelled'; ?>">
										<?php echo $loc->is_active ? esc_html__( 'Active', 'yasmine-artistry-booking' ) : esc_html__( 'Inactive', 'yasmine-artistry-booking' ); ?>
									</span>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-locations&edit=' . $loc->id ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'yasmine-artistry-booking' ); ?>
									</a>
									<form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this location?');">
										<?php wp_nonce_field( 'yab_save_location', 'yab_loc_nonce' ); ?>
										<input type="hidden" name="yab_loc_action" value="delete">
										<input type="hidden" name="location_id" value="<?php echo esc_attr( $loc->id ); ?>">
										<button type="submit" class="button button-small button-link-delete" style="color: #a00;">
											<?php esc_html_e( 'Delete', 'yasmine-artistry-booking' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" style="text-align: center; padding: 24px; color: #718096;">
								<?php esc_html_e( 'No coverage areas configured yet.', 'yasmine-artistry-booking' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>
