<?php
/**
 * Redirect module options panel.
 *
 * Table-based UI with read-only rows, inline editing, bulk actions,
 * search, server-side pagination, and import/export support.
 *
 * @package HDAddons\Redirect
 */

use HDAddons\Redirect\Redirect;

\defined( 'ABSPATH' ) || exit;

// Determine current page from query string (persisted across settings saves).
$current_page = max( 1, (int) ( $_GET['redirect_page'] ?? 1 ) );
$paginated    = Redirect::getPaginated( $current_page );
$rules        = $paginated['rules'];
$total        = $paginated['total'];
$total_pages  = $paginated['total_pages'];
$page         = $paginated['page'];
$offset       = $paginated['offset'];

// Build base URL for pagination links.
$base_url = remove_query_arg( 'redirect_page' );

?>
<div class="container">
	<input type="hidden" name="redirect-hidden" value="1">
	<input type="hidden" name="redirect_page" value="<?php echo esc_attr( $page ); ?>">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Redirect Rules', HDA_TEXTDOMAIN ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-randomize"></span>
				<?php esc_html_e( 'Source path must be relative (starting with /). Destination must be a full URL. Matching is case-insensitive; trailing slashes are ignored.', HDA_TEXTDOMAIN ); ?>
			</p>
			<p>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Delete operations take effect immediately without needing to click "Save Changes".', HDA_TEXTDOMAIN ); ?>
			</p>
		</div>

		<!-- ── Toolbar: Add / Search / Import / Export ──────── -->
		<div class="hda-redirect-toolbar">
			<div class="hda-redirect-toolbar__left">
				<button type="button" id="hda-redirect-add" class="button">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add', HDA_TEXTDOMAIN ); ?>
				</button>
				<button type="button" id="hda-redirect-delete-selected" class="button" style="display:none;">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Delete Selected', HDA_TEXTDOMAIN ); ?>
				</button>
				<button type="button" id="hda-redirect-delete-all" class="button" <?php if ( $total === 0 ) echo 'style="display:none;"'; ?>>
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Delete All', HDA_TEXTDOMAIN ); ?>
				</button>
			</div>
			<div class="hda-redirect-toolbar__right">
				<!-- Search -->
				<div class="hda-redirect-search-wrap" <?php if ( $total === 0 ) echo 'style="display:none;"'; ?>>
					<input type="search" id="hda-redirect-search" class="hda-redirect-search" placeholder="<?php esc_attr_e( 'Search rules...', HDA_TEXTDOMAIN ); ?>">
				</div>

				<!-- Import -->
				<span class="hda-redirect-import-wrap">
					<input type="file" id="hda-redirect-import-file" accept=".csv,.xlsx" style="display:none;">
					<button type="button" id="hda-redirect-import-btn" class="button" title="<?php esc_attr_e( 'Import CSV or XLSX', HDA_TEXTDOMAIN ); ?>">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Import', HDA_TEXTDOMAIN ); ?>
					</button>
					<select id="hda-redirect-import-mode" class="hda-redirect-import-mode">
						<option value="append"><?php esc_html_e( 'Append', HDA_TEXTDOMAIN ); ?></option>
						<option value="replace"><?php esc_html_e( 'Replace All', HDA_TEXTDOMAIN ); ?></option>
					</select>
				</span>

				<!-- Export -->
				<?php if ( $total > 0 ) : ?>
					<span class="hda-redirect-export-wrap">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_redirect_export&format=csv' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button" title="<?php esc_attr_e( 'Export CSV', HDA_TEXTDOMAIN ); ?>">
							<span class="dashicons dashicons-download"></span>
							CSV
						</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_redirect_export&format=xlsx' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button" title="<?php esc_attr_e( 'Export XLSX', HDA_TEXTDOMAIN ); ?>">
							<span class="dashicons dashicons-download"></span>
							XLSX
						</a>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<!-- ── Import Status ──────────────────────────── -->
		<div id="hda-redirect-import-status" class="hda-redirect-import-status" style="display:none;"></div>

		<!-- ── Table ──────────────────────────────────── -->
		<div class="hda-table-responsive" id="hda-redirect-table-wrap" <?php if ( $total === 0 ) echo 'style="display:none;"'; ?>>
			<table class="widefat striped hda-redirect-table" id="hda-redirect-table">
				<thead>
					<tr>
						<th class="hda-redirect-table__cb"><input type="checkbox" id="hda-redirect-select-all" title="<?php esc_attr_e( 'Select All', HDA_TEXTDOMAIN ); ?>"></th>
						<th class="hda-redirect-table__num">#</th>
						<th class="hda-redirect-table__from"><?php esc_html_e( 'From (path)', HDA_TEXTDOMAIN ); ?></th>
						<th class="hda-redirect-table__to"><?php esc_html_e( 'To (URL)', HDA_TEXTDOMAIN ); ?></th>
						<th class="hda-redirect-table__type"><?php esc_html_e( 'Type', HDA_TEXTDOMAIN ); ?></th>
						<th class="hda-redirect-table__actions"><?php esc_html_e( 'Actions', HDA_TEXTDOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody id="hda-redirect-rules">
					<?php foreach ( $rules as $i => $rule ) :
						$type_label = ( (int) ( $rule['type'] ?? 301 ) === 302 ) ? '302' : '301';
					?>
						<tr class="hda-redirect-row" data-index="<?php echo esc_attr( $offset + $i ); ?>">
							<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-redirect-cb"></td>
							<td class="hda-redirect-table__num"><?php echo esc_html( $offset + $i + 1 ); ?></td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $rule['from'] ); ?></span>
								<input type="text" class="input hda-redirect-input" name="redirect_from[]" value="<?php echo esc_attr( $rule['from'] ); ?>" placeholder="/old-page" readonly>
							</td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $rule['to'] ); ?></span>
								<input type="url" class="input hda-redirect-input" name="redirect_to[]" value="<?php echo esc_url( $rule['to'] ); ?>" placeholder="https://example.com/new-page" readonly>
							</td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $type_label ); ?></span>
								<input type="hidden" name="redirect_type[]" value="<?php echo esc_attr( $rule['type'] ?? 301 ); ?>" class="hda-redirect-type-hidden">
								<select class="select hda-redirect-select" data-name="redirect_type" disabled>
									<option value="301" <?php selected( $rule['type'] ?? 301, 301 ); ?>>301</option>
									<option value="302" <?php selected( $rule['type'] ?? 301, 302 ); ?>>302</option>
								</select>
							</td>
							<td class="hda-redirect-table__actions-cell">
								<button type="button" class="button button-small hda-redirect-edit" title="<?php esc_attr_e( 'Edit', HDA_TEXTDOMAIN ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="button button-small hda-redirect-remove" title="<?php esc_attr_e( 'Delete', HDA_TEXTDOMAIN ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total === 0 ) : ?>
			<p class="hda-redirect-empty" id="hda-redirect-empty">
				<?php esc_html_e( 'No redirect rules configured. Click "Add Redirect" or import from a file to get started.', HDA_TEXTDOMAIN ); ?>
			</p>
		<?php endif; ?>

		<!-- ── Pagination ─────────────────────────── -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="hda-redirect-pagination">
				<span class="hda-redirect-pagination__info">
					<?php
					printf(
						/* translators: %1$d–%2$d of %3$d */
						esc_html__( 'Showing %1$d–%2$d of %3$d rules', HDA_TEXTDOMAIN ),
						$offset + 1,
						min( $offset + Redirect::PER_PAGE, $total ),
						$total
					);
					?>
				</span>
				<span class="hda-redirect-pagination__links">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $page - 1, $base_url ) ); ?>" class="button button-small">&laquo; <?php esc_html_e( 'Prev', HDA_TEXTDOMAIN ); ?></a>
					<?php endif; ?>

					<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
						<?php if ( $p === $page ) : ?>
							<span class="button button-small button-primary hda-redirect-pagination__current"><?php echo esc_html( $p ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $p, $base_url ) ); ?>" class="button button-small"><?php echo esc_html( $p ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $page + 1, $base_url ) ); ?>" class="button button-small"><?php esc_html_e( 'Next', HDA_TEXTDOMAIN ); ?> &raquo;</a>
					<?php endif; ?>
				</span>
			</div>
		<?php endif; ?>
	</fieldset>
</div>
