<?php
/**
 * Admin view: Service Categories.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id    = absint( $_GET['edit'] ?? 0 );
$editing    = $edit_id > 0 ? YAB_Category::get( $edit_id ) : null;
$categories = YAB_Category::get_all( array( 'active_only' => false ) );
?>

<div class="wrap yab-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Service Categories', 'yasmine-artistry-booking' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Category saved.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php elseif ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Category deleted.', 'yasmine-artistry-booking' ); ?></p></div>
	<?php endif; ?>

	<div class="yab-grid-2col">

		<div class="yab-form-col">
			<div class="postbox">
				<div class="postbox-header">
					<h2><?php echo $editing ? esc_html__( 'Edit Category', 'yasmine-artistry-booking' ) : esc_html__( 'Add Category', 'yasmine-artistry-booking' ); ?></h2>
				</div>
				<div class="inside">
					<form method="post">
						<?php wp_nonce_field( 'yab_save_category', 'yab_cat_nonce' ); ?>
						<input type="hidden" name="yab_cat_action" value="save">
						<input type="hidden" name="category_id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>">

						<p>
							<label for="cat_name"><strong><?php esc_html_e( 'Category Name', 'yasmine-artistry-booking' ); ?> *</strong></label>
							<input type="text" name="name" id="cat_name" class="widefat" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required placeholder="e.g. Bridal Artistry, Studio Glam, Editorial">
						</p>

						<p>
							<label for="cat_desc"><strong><?php esc_html_e( 'Description', 'yasmine-artistry-booking' ); ?></strong></label>
							<textarea name="description" id="cat_desc" class="widefat" rows="3"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
						</p>

						<p>
							<label>
								<input type="checkbox" name="is_active" value="1" <?php checked( $editing ? $editing->is_active : 1, 1 ); ?>>
								<strong><?php esc_html_e( 'Active', 'yasmine-artistry-booking' ); ?></strong>
							</label>
						</p>

						<p>
							<button type="submit" class="button button-primary">
								<?php echo $editing ? esc_html__( 'Update Category', 'yasmine-artistry-booking' ) : esc_html__( 'Create Category', 'yasmine-artistry-booking' ); ?>
							</button>
							<?php if ( $editing ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-categories' ) ); ?>" class="button">
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
						<th><?php esc_html_e( 'Name', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'yasmine-artistry-booking' ); ?></th>
						<th><?php esc_html_e( 'Description', 'yasmine-artistry-booking' ); ?></th>
						<th style="width: 90px;"><?php esc_html_e( 'Status', 'yasmine-artistry-booking' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Actions', 'yasmine-artistry-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $categories ) ) : ?>
						<?php foreach ( $categories as $cat ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
								<td><code><?php echo esc_html( $cat->slug ); ?></code></td>
								<td><?php echo esc_html( $cat->description ? $cat->description : '—' ); ?></td>
								<td>
									<span class="yab-status-badge <?php echo $cat->is_active ? 'yab-status-confirmed' : 'yab-status-cancelled'; ?>">
										<?php echo $cat->is_active ? esc_html__( 'Active', 'yasmine-artistry-booking' ) : esc_html__( 'Inactive', 'yasmine-artistry-booking' ); ?>
									</span>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=yab-categories&edit=' . $cat->id ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'yasmine-artistry-booking' ); ?>
									</a>
									<form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this category?');">
										<?php wp_nonce_field( 'yab_save_category', 'yab_cat_nonce' ); ?>
										<input type="hidden" name="yab_cat_action" value="delete">
										<input type="hidden" name="category_id" value="<?php echo esc_attr( $cat->id ); ?>">
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
								<?php esc_html_e( 'No categories yet.', 'yasmine-artistry-booking' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>
